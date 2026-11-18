#!/usr/bin/env bash
# Publish the zip already attached to a GitHub release to WordPress.org SVN.
#
# This does not build the plugin. pack-plugin-zip uploads the zip while
# creating the git tag. This script downloads that asset and copies it into
# SVN trunk, tags/X.Y.Z, and assets/ when .wordpress-org/ is on the tag.
#
# Checks stop the script before svn commit. dry_run prints the status and
# diff and commits nothing. A real commit publishes the draft GitHub release
# only after svn commit succeeds.
#
# SVN_USERNAME and SVN_PASSWORD come from the environment. They are never echoed.

set -euo pipefail

PLUGIN_SLUG="${PLUGIN_SLUG:-fluid-checkout}"
GIT_TAG_PREFIX="${GIT_TAG_PREFIX:-v}"
DRY_RUN="${DRY_RUN:-true}"
WORK_DIR=""

cleanup() {
	if [[ -n "${WORK_DIR}" && -d "${WORK_DIR}" ]]; then
		rm -rf "${WORK_DIR}"
	fi
}
trap cleanup EXIT

die() {
	printf '::error::%s\n' "$1" >&2
	exit 1
}

require_cmd() {
	if ! command -v "$1" >/dev/null 2>&1; then
		die "Required command not found: $1"
	fi
}

# Plain X.Y.Z only. Alpha, beta, rc, dev, and any other suffix are refused.
# Same rule as _officialStableVersionPattern in gulpfile.js, including trim.
is_official_stable_version() {
	local candidate="${1:-}"

	candidate="${candidate#"${candidate%%[![:space:]]*}"}"
	candidate="${candidate%"${candidate##*[![:space:]]}"}"
	[[ "${candidate}" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]
}

build_svn_auth_args() {
	SVN_AUTH_ARGS=( --non-interactive --no-auth-cache )

	if [[ -n "${SVN_USERNAME:-}" && -n "${SVN_PASSWORD:-}" ]]; then
		SVN_AUTH_ARGS+=( --username "${SVN_USERNAME}" --password "${SVN_PASSWORD}" )
		return 0
	fi

	if [[ "${DRY_RUN}" != "true" ]]; then
		die "SVN_USERNAME and SVN_PASSWORD must both be set on the wordpress-org environment when dry_run is false."
	fi
}

prepare_assets_directory() {
	local output status

	set +e
	output="$( svn list --non-interactive "${SVN_URL}/assets" 2>&1 )"
	status=$?
	set -e

	if [[ "${status}" -eq 0 ]]; then
		svn update --set-depth infinity "${SVN_AUTH_ARGS[@]}" assets
		return 0
	fi

	if [[ "${output}" != *"W160013"* ]]; then
		printf '%s\n' "${output}" >&2
		die "Could not check whether SVN assets/ exists."
	fi

	if [[ -n "${1:-}" ]]; then
		printf 'SVN has no assets directory. Creating it for .wordpress-org.\n'
		svn mkdir --non-interactive assets
		return 0
	fi

	printf 'SVN has no assets directory. Leaving it absent.\n'
}

svn_tag_state() {
	local output status

	set +e
	output="$( svn list --non-interactive "${SVN_URL}/tags/${VERSION}" 2>&1 )"
	status=$?
	set -e

	if [[ "${status}" -eq 0 ]]; then
		printf 'yes\n'
		return 0
	fi

	if [[ "${output}" == *"W160013"* ]]; then
		printf 'no\n'
		return 0
	fi

	printf '%s\n' "${output}" >&2
	printf 'error\n'
}

read_stable_tag() {
	local file="$1"

	awk '
		/^==[^=]/ { exit }
		{
			sub( /\r/, "", $0 )
			if ( match( $0, /^Stable tag:[[:space:]]*/ ) ) {
				value = substr( $0, RLENGTH + 1 )
				sub( /[[:space:]]*$/, "", value )
				print value
				exit
			}
		}
	' "${file}"
}

read_plugin_version() {
	local file="$1"

	head -c 8192 -- "${file}" | tr -d '\r' | sed -n 's/^[[:space:]*]*Version:[[:space:]]*//p' | head -n 1 | sed 's/[[:space:]]*$//'
}

find_plugin_root() {
	local unpack="$1"
	local name
	local -a entries=()

	while IFS= read -r name; do
		if [[ "${name}" == "__MACOSX" || "${name}" == ".DS_Store" ]]; then
			continue
		fi
		entries+=( "${name}" )
	done < <( find "${unpack}" -mindepth 1 -maxdepth 1 -printf '%f\n' )

	if [[ "${#entries[@]}" -eq 1 && -d "${unpack}/${entries[0]}" ]]; then
		printf '%s\n' "${unpack}/${entries[0]}"
		return 0
	fi

	if [[ -f "${unpack}/readme.txt" ]]; then
		printf '%s\n' "${unpack}"
		return 0
	fi

	die "Could not find the plugin root inside the zip. The top folder name is ignored, but readme.txt has to be at that root."
}

assert_package_tree() {
	local root="$1"
	local expected="$2"
	local main_file stable header forbidden

	if [[ ! -f "${root}/readme.txt" ]]; then
		die "Zip is missing readme.txt."
	fi

	main_file="${root}/${PLUGIN_SLUG}.php"
	if [[ ! -f "${main_file}" ]]; then
		die "Zip is missing ${PLUGIN_SLUG}.php."
	fi

	forbidden="$( find "${root}" \( -name '.github' -o -name '.wordpress-org' \) -print -quit || true )"
	if [[ -n "${forbidden}" ]]; then
		die "Zip contains ${forbidden}. .github and .wordpress-org must stay out of the package."
	fi

	stable="$( read_stable_tag "${root}/readme.txt" )"
	header="$( read_plugin_version "${main_file}" )"

	if [[ "${stable}" != "${expected}" ]]; then
		die "readme.txt Stable tag is '${stable}', expected '${expected}'."
	fi

	if [[ "${header}" != "${expected}" ]]; then
		die "${PLUGIN_SLUG}.php Version is '${header}', expected '${expected}'."
	fi

	printf 'Package header Version and Stable tag are both %s.\n' "${expected}"
}

# Schedule svn add for new files and svn delete for files rsync removed.
# Only the topmost path in each tree is scheduled. svn add/delete cover children.
sync_svn_status() {
	local target="$1"
	local line status path
	local -a missing=()
	local -a unversioned=()

	while IFS= read -r line || [[ -n "${line}" ]]; do
		[[ -z "${line}" ]] && continue
		[[ "${line}" == ---* ]] && continue
		[[ "${#line}" -lt 9 ]] && continue

		status="${line:0:1}"
		path="${line:8}"

		if [[ "${status}" == "!" ]]; then
			missing+=( "${path}" )
		elif [[ "${status}" == "?" ]]; then
			unversioned+=( "${path}" )
		fi
	done < <( svn status --ignore-externals -- "${target}" )

	schedule_topmost "delete" "${missing[@]+"${missing[@]}"}"
	schedule_topmost "add" "${unversioned[@]+"${unversioned[@]}"}"
}

path_has_listed_ancestor() {
	local path="$1"
	shift
	local other

	for other in "$@"; do
		if [[ "${path}" == "${other}/"* ]]; then
			return 0
		fi
	done

	return 1
}

schedule_topmost() {
	local action="$1"
	shift
	local path

	[[ "$#" -gt 0 ]] || return 0

	for path in "$@"; do
		if path_has_listed_ancestor "${path}" "$@"; then
			continue
		fi

		if [[ "${action}" == "delete" ]]; then
			svn delete --force --non-interactive -- "${path}"
		else
			svn add --parents --force --non-interactive -- "${path}"
		fi
	done
}

sync_directory() {
	local source="$1"
	local destination="$2"

	mkdir -p "${destination}"
	rsync -rc --delete --exclude '.svn' "${source}/" "${destination}/"
	sync_svn_status "${destination}"
}

fetch_wordpress_org_assets() {
	local git_tag="$1"
	local dest="$2"

	git fetch --depth=1 origin "refs/tags/${git_tag}:refs/tags/${git_tag}"

	if ! git ls-tree -d --name-only "${git_tag}" -- .wordpress-org | grep -qx '.wordpress-org'; then
		printf 'No .wordpress-org directory on tag %s. SVN assets/ will be left unchanged.\n' "${git_tag}" >&2
		return 0
	fi

	rm -rf "${dest}"
	mkdir -p "${dest}"
	git archive "${git_tag}" .wordpress-org | tar -x -C "${dest}"

	if [[ -z "$( find "${dest}/.wordpress-org" -type f -print -quit )" ]]; then
		printf '.wordpress-org on tag %s has no files. SVN assets/ will be left unchanged.\n' "${git_tag}" >&2
		return 0
	fi

	printf '%s\n' "${dest}/.wordpress-org"
}

download_release_zip() {
	local git_tag="$1"
	local asset_name="$2"
	local dest="$3"
	local release_json asset_count

	release_json="$( gh release view "${git_tag}" --repo "${GITHUB_REPOSITORY}" --json isDraft,tagName,assets,url )"
	asset_count="$( jq -r --arg name "${asset_name}" '[.assets[] | select(.name == $name)] | length' <<<"${release_json}" )"

	if [[ "${asset_count}" != "1" ]]; then
		printf 'Assets on %s:\n' "${git_tag}" >&2
		jq -r '.assets[].name // empty' <<<"${release_json}" >&2 || true
		die "GitHub release ${git_tag} must have exactly one asset named ${asset_name}."
	fi

	printf 'GitHub release %s (draft: %s) %s\n' \
		"$( jq -r '.tagName' <<<"${release_json}" )" \
		"$( jq -r '.isDraft' <<<"${release_json}" )" \
		"$( jq -r '.url' <<<"${release_json}" )" >&2

	mkdir -p "${dest}"
	gh release download "${git_tag}" --repo "${GITHUB_REPOSITORY}" --pattern "${asset_name}" --dir "${dest}" --clobber
	printf '%s\n' "${dest}/${asset_name}"
}

print_svn_summary() {
	printf '\nSVN status:\n'
	svn status
	printf '\nSVN diff summary:\n'
	svn diff --summarize
}

commit_svn() {
	if [[ "${DRY_RUN}" == "true" ]]; then
		die "Refusing to commit because dry_run is true."
	fi

	if [[ -z "$( svn status --ignore-externals )" ]]; then
		die "SVN working copy has no changes. The GitHub release was not published."
	fi

	svn commit "${SVN_AUTH_ARGS[@]}" -m "Release ${VERSION}"
}

publish_github_release() {
	if [[ "${DRY_RUN}" == "true" ]]; then
		die "Refusing to publish the GitHub release because dry_run is true."
	fi

	if ! gh release edit "${GIT_TAG}" --repo "${GITHUB_REPOSITORY}" --draft=false --latest; then
		die "SVN commit succeeded, but publishing GitHub release ${GIT_TAG} failed. SVN tag tags/${VERSION} now exists, so do not rerun this workflow for ${VERSION}. Publish the release with: gh release edit ${GIT_TAG} --draft=false --latest"
	fi

	printf 'Published GitHub release %s.\n' "${GIT_TAG}"
}

deploy() {
	local asset_name git_tag zip_path unpack_dir plugin_root assets_dir assets_source tag_state

	: "${VERSION:?Set VERSION to the stable X.Y.Z release.}"
	: "${GITHUB_REPOSITORY:?GITHUB_REPOSITORY is required, for example fluid-checkout/fluid-checkout.}"

	case "${DRY_RUN}" in
		true|false) ;;
		*) die "dry_run must be true or false (got '${DRY_RUN}')." ;;
	esac

	if [[ ! "${PLUGIN_SLUG}" =~ ^[a-z0-9-]+$ ]]; then
		die "PLUGIN_SLUG contains unsupported characters."
	fi

	if [[ ! "${GIT_TAG_PREFIX}" =~ ^[A-Za-z0-9._-]*$ ]]; then
		die "GIT_TAG_PREFIX contains unsupported characters."
	fi

	if ! is_official_stable_version "${VERSION}"; then
		die "Refusing version '${VERSION}'. Only a stable X.Y.Z version can be published. Pre-release suffixes stay off WordPress.org."
	fi

	require_cmd gh
	require_cmd jq
	require_cmd svn
	require_cmd rsync
	require_cmd unzip
	require_cmd git

	GIT_TAG="${GIT_TAG_PREFIX}${VERSION}"
	asset_name="${PLUGIN_SLUG}-${VERSION}.zip"
	SVN_URL="https://plugins.svn.wordpress.org/${PLUGIN_SLUG}"
	git_tag="${GIT_TAG}"

	WORK_DIR="$( mktemp -d )"
	zip_path="$( download_release_zip "${git_tag}" "${asset_name}" "${WORK_DIR}/download" )"

	unpack_dir="${WORK_DIR}/unpack"
	mkdir -p "${unpack_dir}"
	unzip -q "${zip_path}" -d "${unpack_dir}"
	plugin_root="$( find_plugin_root "${unpack_dir}" )"
	printf 'Plugin root inside the zip: %s\n' "$( basename "${plugin_root}" )"
	assert_package_tree "${plugin_root}" "${VERSION}"

	tag_state="$( svn_tag_state )"
	case "${tag_state}" in
		yes)
			die "SVN tag tags/${VERSION} already exists. Refusing to replace a published WordPress.org version."
			;;
		no)
			printf 'SVN tag tags/%s does not exist yet.\n' "${VERSION}"
			;;
		*)
			die "Could not check whether tags/${VERSION} exists in SVN."
			;;
	esac

	build_svn_auth_args

	assets_dir="${WORK_DIR}/tag-assets"
	assets_source="$( fetch_wordpress_org_assets "${git_tag}" "${assets_dir}" )"

	svn checkout --depth immediates "${SVN_AUTH_ARGS[@]}" "${SVN_URL}" "${WORK_DIR}/svn"
	cd "${WORK_DIR}/svn"
	svn update --set-depth infinity "${SVN_AUTH_ARGS[@]}" trunk
	svn update --set-depth empty "${SVN_AUTH_ARGS[@]}" tags
	prepare_assets_directory "${assets_source}"

	printf 'Syncing the release zip into trunk.\n'
	sync_directory "${plugin_root}" trunk
	svn copy -- "trunk" "tags/${VERSION}"

	if [[ -n "${assets_source}" ]]; then
		printf 'Syncing .wordpress-org into SVN assets/.\n'
		sync_directory "${assets_source}" assets
	fi

	if [[ "${DRY_RUN}" == "true" ]]; then
		print_svn_summary
		printf '\nDry run only. Nothing was committed. The GitHub release was not published.\n'
		exit 0
	fi

	commit_svn
	publish_github_release
}

self_test() {
	local script_dir repo_root repo url wc plugin stable_cases case_version root value expect

	script_dir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
	repo_root="$( cd "${script_dir}/../.." && pwd )"

	if ! grep -F '/^\d+\.\d+\.\d+$/' "${repo_root}/gulpfile.js" >/dev/null; then
		die "gulpfile.js is missing the official stable version pattern."
	fi

	stable_cases=(
		"1.2.3:yes"
		"5.0.0:yes"
		"5.0.1:yes"
		"10.20.30:yes"
		"5.0.1-alpha-6:no"
		"5.0.1-beta-1:no"
		"5.0.1-rc-1:no"
		"5.0.1-rc.1:no"
		"5.0.1-dev:no"
		"5.0.1-preview:no"
		"v5.0.1:no"
		"5.0.1+build:no"
		"5.0:no"
		"5:no"
		":no"
		" 5.0.1:yes"
		"5.0.1 :yes"
	)

	for case_version in "${stable_cases[@]}"; do
		value="${case_version%%:*}"
		expect="${case_version##*:}"
		if is_official_stable_version "${value}"; then
			[[ "${expect}" == "yes" ]] || die "Expected '${value}' to be refused."
		else
			[[ "${expect}" == "no" ]] || die "Expected '${value}' to be stable."
		fi
	done

	plugin="$( mktemp -d )"
	mkdir -p "${plugin}/fluid-checkout"
	cat > "${plugin}/fluid-checkout/fluid-checkout.php" <<'EOF'
<?php
/*
Plugin Name: Fluid Checkout for WooCommerce - Lite
Version: 1.2.3
*/
EOF
	printf '=== Fluid Checkout ===\nStable tag: 1.2.3\n\n== Description ==\n\nStable tag: 9.9.9\n' > "${plugin}/fluid-checkout/readme.txt"
	PLUGIN_SLUG="fluid-checkout"
	assert_package_tree "${plugin}/fluid-checkout" "1.2.3"

	if ( assert_package_tree "${plugin}/fluid-checkout" "9.9.9" ); then
		die "Mismatched Stable tag was accepted."
	fi

	mkdir -p "${plugin}/fluid-checkout/.github"
	if ( assert_package_tree "${plugin}/fluid-checkout" "1.2.3" ); then
		die "A zip containing .github was accepted."
	fi
	rm -rf "${plugin}/fluid-checkout/.github"
	mkdir -p "${plugin}/fluid-checkout/.wordpress-org"
	if ( assert_package_tree "${plugin}/fluid-checkout" "1.2.3" ); then
		die "A zip containing .wordpress-org was accepted."
	fi

	# Loose zip: the top folder name is not the slug.
	root="$( find_plugin_root "${plugin}" )"
	[[ "$( basename "${root}" )" == "fluid-checkout" ]] || die "find_plugin_root ignored a single top folder."

	require_cmd svn
	require_cmd svnadmin
	require_cmd rsync

	repo="$( mktemp -d )"
	svnadmin create "${repo}/repo"
	url="file://${repo}/repo"
	mkdir -p "${repo}/seed/trunk" "${repo}/seed/assets" "${repo}/seed/tags/1.0.0"
	printf 'old\n' > "${repo}/seed/trunk/old-file.txt"
	printf 'icon\n' > "${repo}/seed/assets/icon.txt"
	printf 'keep-me\n' > "${repo}/seed/assets/keep.txt"
	printf 'tagged\n' > "${repo}/seed/tags/1.0.0/old-file.txt"
	svn import "${repo}/seed" "${url}" -m "seed" --non-interactive >/dev/null

	wc="${repo}/wc"
	svn checkout --depth immediates "${url}" "${wc}" --non-interactive >/dev/null
	(
		cd "${wc}"
		SVN_URL="${url}"
		SVN_AUTH_ARGS=( --non-interactive --no-auth-cache )
		svn update --set-depth infinity trunk --non-interactive --quiet
		svn update --set-depth empty tags --non-interactive --quiet
		prepare_assets_directory "present"
	)

	mkdir -p "${repo}/plugin" "${repo}/org-assets"
	printf 'new\n' > "${repo}/plugin/new-file.txt"
	cp "${plugin}/fluid-checkout/fluid-checkout.php" "${repo}/plugin/fluid-checkout.php"
	cp "${plugin}/fluid-checkout/readme.txt" "${repo}/plugin/readme.txt"
	printf 'banner\n' > "${repo}/org-assets/banner.txt"

	(
		cd "${wc}"
		sync_directory "${repo}/plugin" trunk
		svn copy -- "trunk" "tags/1.2.3" >/dev/null
		sync_directory "${repo}/org-assets" assets
	)

	if [[ -e "${wc}/trunk/.svn" && ! -d "${wc}/trunk/.svn" ]]; then
		die "rsync damaged the trunk .svn entry."
	fi

	svn_status="$( svn status --ignore-externals "${wc}" )"
	printf '%s\n' "${svn_status}" | grep -q 'tags/1.2.3' || die "Tag copy was not scheduled."
	printf '%s\n' "${svn_status}" | grep -q 'trunk/new-file.txt' || die "New trunk file was not scheduled."
	printf '%s\n' "${svn_status}" | grep -q 'trunk/old-file.txt' || die "Removed trunk file was not scheduled."
	printf '%s\n' "${svn_status}" | grep -q 'assets/banner.txt' || die "New asset was not scheduled."
	printf '%s\n' "${svn_status}" | grep -q 'assets/icon.txt' || die "Removed asset was not scheduled."

	before="$( svnlook youngest "${repo}/repo" )"
	DRY_RUN="true"
	# commit_svn must refuse a dry run even if someone calls it after staging.
	if ( cd "${wc}" && DRY_RUN="true" && commit_svn ); then
		die "Dry run committed to SVN."
	fi
	after="$( svnlook youngest "${repo}/repo" )"
	[[ "${before}" == "${after}" ]] || die "Dry run changed the SVN revision."

	(
		cd "${wc}"
		DRY_RUN="false"
		SVN_AUTH_ARGS=( --non-interactive --no-auth-cache )
		VERSION="1.2.3"
		commit_svn
	)

	svn cat "${url}/tags/1.2.3/new-file.txt" | grep -qx 'new' || die "Tag copy did not include the new trunk file."
	if svn cat "${url}/tags/1.2.3/old-file.txt" >/dev/null 2>&1; then
		die "Tag copy still contains the removed trunk file."
	fi
	svn cat "${url}/assets/banner.txt" | grep -qx 'banner' || die "Asset sync did not upload the new file."
	if svn cat "${url}/assets/icon.txt" >/dev/null 2>&1; then
		die "Asset sync left a removed file in SVN."
	fi
	if svn cat "${url}/assets/keep.txt" >/dev/null 2>&1; then
		die "Asset sync kept a file that was not in .wordpress-org."
	fi

	SVN_URL="${url}"
	VERSION="1.2.3"
	[[ "$( svn_tag_state )" == "yes" ]] || die "Committed tag was not visible."
	VERSION="9.9.9"
	[[ "$( svn_tag_state )" == "no" ]] || die "Missing tag was reported as present."

	rm -rf "${plugin}" "${repo}"
	printf 'self-test passed\n'
}

if [[ "${1:-}" == "--self-test" ]]; then
	self_test
	exit 0
fi

if [[ "${1:-}" == "--check-version" ]]; then
	if is_official_stable_version "${2:-}"; then
		printf 'stable\n'
		exit 0
	fi
	printf 'not-stable\n'
	exit 1
fi

deploy
