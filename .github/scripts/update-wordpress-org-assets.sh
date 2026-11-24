#!/usr/bin/env bash
# Update WordPress.org SVN assets and readme.txt without publishing plugin code.
#
# Copies readme.txt into trunk and into tags/<Stable tag>. Syncs .wordpress-org
# through sync-wordpress-org-assets.sh. No other path is written into trunk or tags.
#
# Aborts when HEAD differs from the stable git tag (vX.Y.Z) in any path other
# than readme.txt and .wordpress-org/. Aborts again before commit if svn status
# shows anything else, including a README file under assets/.
#
# dry_run prints the SVN status and diff and does not commit. SVN_USERNAME and
# SVN_PASSWORD come from the environment. They are never echoed.

set -euo pipefail

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# shellcheck source=sync-wordpress-org-assets.sh
source "${SCRIPT_DIR}/sync-wordpress-org-assets.sh"

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

assert_publish_names() {
	if [[ ! "${PLUGIN_SLUG}" =~ ^[a-z0-9-]+$ ]]; then
		die "PLUGIN_SLUG contains unsupported characters."
	fi

	if [[ ! "${GIT_TAG_PREFIX}" =~ ^[A-Za-z0-9._-]*$ ]]; then
		die "GIT_TAG_PREFIX contains unsupported characters."
	fi
}

assert_only_listing_files_differ() {
	local repo="$1"
	local git_tag="$2"
	local path shown=0
	local -a blocked=()

	while IFS= read -r path || [[ -n "${path}" ]]; do
		[[ -n "${path}" ]] || continue
		case "${path}" in
			readme.txt|.wordpress-org|.wordpress-org/*) continue ;;
		esac
		blocked+=( "${path}" )
	done < <( git -C "${repo}" diff --name-only --no-renames "${git_tag}" HEAD )

	if [[ "${#blocked[@]}" -eq 0 ]]; then
		return 0
	fi

	printf 'Files that differ from %s:\n' "${git_tag}" >&2
	for path in "${blocked[@]}"; do
		printf '  %s\n' "${path}" >&2
		shown=$(( shown + 1 ))
		if [[ "${shown}" -ge 50 ]]; then
			printf '  ... %s paths in total\n' "${#blocked[@]}" >&2
			break
		fi
	done

	die "Refusing to update WordPress.org. Compared with ${git_tag}, this tree changes files other than readme.txt and .wordpress-org/. This workflow only publishes the readme and listing assets."
}

fetch_stable_git_tag() {
	local repo="$1"
	local git_tag="$2"

	if ! GIT_TERMINAL_PROMPT=0 git -C "${repo}" fetch --depth=1 --quiet origin "refs/tags/${git_tag}:refs/tags/${git_tag}"; then
		die "Git tag ${git_tag} was not found. readme.txt Stable tag has to match an existing release tag before this workflow can update WordPress.org."
	fi
}

copy_readme_only() {
	local readme="$1"
	local dest_dir="$2"
	local base

	base="$( basename "${readme}" )"
	if [[ "${base}" != "readme.txt" ]]; then
		die "Refusing to copy ${base} into SVN. Only readme.txt may be copied into trunk or a tag."
	fi

	if [[ ! -f "${readme}" ]]; then
		die "readme.txt was not found at ${readme}."
	fi

	mkdir -p "${dest_dir}"
	cp -- "${readme}" "${dest_dir}/readme.txt"
}

ensure_readme_scheduled() {
	local path="$1"
	local line

	if [[ ! -f "${path}" ]]; then
		die "Expected ${path} to exist after copying readme.txt."
	fi

	line="$( svn status --ignore-externals -- "${path}" || true )"
	if [[ "${line}" == '?'* ]]; then
		svn add --parents --force --non-interactive -- "${path}"
	fi
}

assert_svn_listing_changes_only() {
	local stable="$1"
	local line status path base lower

	while IFS= read -r line || [[ -n "${line}" ]]; do
		[[ -n "${line}" ]] || continue
		[[ "${line}" == ---* ]] && continue
		[[ "${#line}" -lt 9 ]] && continue

		status="${line:0:1}"
		path="${line:8}"
		[[ -n "${path}" ]] || continue

		# Missing and unversioned paths have to be scheduled before this check.
		# svn commit would ignore "?" and would not record "!".
		case "${status}" in
			'?'|!)
				die "Refusing to commit unscheduled SVN path ${path}."
				;;
			' '|A|M|D|R) ;;
			*) die "Refusing to commit unrecognized SVN status '${line}'." ;;
		esac

		case "${path}" in
			trunk|trunk/readme.txt) ;;
			tags/"${stable}"|tags/"${stable}"/readme.txt) ;;
			assets|assets/*)
				base="$( basename "${path}" )"
				lower="$( printf '%s' "${base}" | tr '[:upper:]' '[:lower:]' )"
				case "${lower}" in
					readme|readme.md|readme.txt)
						die "Refusing to commit ${path}. README files are not copied to SVN."
						;;
				esac
				;;
			*)
				die "Refusing to commit ${path}. Only readme.txt and assets/ may change."
				;;
		esac
	done < <( svn status --ignore-externals )
}

print_svn_summary() {
	printf '\nSVN status:\n'
	svn status
	printf '\nSVN diff summary:\n'
	svn diff --summarize
}

commit_listing_svn() {
	if [[ "${DRY_RUN}" == "true" ]]; then
		die "Refusing to commit because dry_run is true."
	fi

	if [[ -z "$( svn status --ignore-externals )" ]]; then
		die "SVN working copy has no changes."
	fi

	svn commit "${SVN_AUTH_ARGS[@]}" -m "Update readme and assets"
}

# Caller is inside the SVN working copy.
# $3 is the stable tag directory name, already checked to be X.Y.Z.
stage_listing_update() {
	local readme="$1"
	local assets_source="$2"
	local stable="$3"

	copy_readme_only "${readme}" trunk
	copy_readme_only "${readme}" "tags/${stable}"
	ensure_readme_scheduled "trunk/readme.txt"
	ensure_readme_scheduled "tags/${stable}/readme.txt"

	if wordpress_org_has_publishable_files "${assets_source}"; then
		prepare_assets_directory "${assets_source}"
		sync_wordpress_org_assets "${assets_source}" assets
	else
		printf '.wordpress-org has no listing files or blueprints to publish. SVN assets/ left unchanged.\n'
		prepare_assets_directory ""
	fi

	assert_svn_listing_changes_only "${stable}"
}

update_readme_and_assets() {
	local repo_root readme stable git_tag tag_state

	assert_publish_names

	case "${DRY_RUN}" in
		true|false) ;;
		*) die "dry_run must be true or false (got '${DRY_RUN}')." ;;
	esac

	if [[ -n "${GITHUB_WORKSPACE:-}" ]]; then
		repo_root="${GITHUB_WORKSPACE}"
	else
		repo_root="$( git rev-parse --show-toplevel )"
	fi

	readme="${repo_root}/readme.txt"
	if [[ ! -f "${readme}" ]]; then
		die "readme.txt was not found in ${repo_root}."
	fi

	stable="$( read_stable_tag "${readme}" )"
	if ! is_official_stable_version "${stable}"; then
		die "readme.txt Stable tag is '${stable}'. Only an existing X.Y.Z release can be updated."
	fi

	# svn_tag_state reads VERSION. This workflow never creates a tag.
	VERSION="${stable}"
	git_tag="${GIT_TAG_PREFIX}${stable}"

	printf 'Stable tag %s (git tag %s). dry_run=%s\n' "${stable}" "${git_tag}" "${DRY_RUN}"

	require_cmd git
	require_cmd svn
	require_cmd rsync

	fetch_stable_git_tag "${repo_root}" "${git_tag}"
	assert_only_listing_files_differ "${repo_root}" "${git_tag}"

	SVN_URL="https://plugins.svn.wordpress.org/${PLUGIN_SLUG}"
	build_svn_auth_args

	tag_state="$( svn_tag_state )"
	case "${tag_state}" in
		yes)
			printf 'SVN tag tags/%s exists.\n' "${VERSION}"
			;;
		no)
			die "SVN tag tags/${VERSION} does not exist. This workflow updates the published readme and assets. It does not create a release."
			;;
		*)
			die "Could not check whether tags/${VERSION} exists in SVN."
			;;
	esac

	WORK_DIR="$( mktemp -d )"
	svn checkout --depth immediates "${SVN_AUTH_ARGS[@]}" "${SVN_URL}" "${WORK_DIR}/svn"
	cd "${WORK_DIR}/svn"
	svn update --set-depth infinity "${SVN_AUTH_ARGS[@]}" trunk
	svn update --set-depth empty "${SVN_AUTH_ARGS[@]}" tags
	svn update --set-depth infinity "${SVN_AUTH_ARGS[@]}" "tags/${VERSION}"

	stage_listing_update "${readme}" "${repo_root}/.wordpress-org" "${VERSION}"

	if [[ "${DRY_RUN}" == "true" ]]; then
		print_svn_summary
		printf '\nDry run only. Nothing was committed.\n'
		exit 0
	fi

	if [[ -z "$( svn status --ignore-externals )" ]]; then
		printf 'SVN already matches this readme and .wordpress-org directory. Nothing to commit.\n'
		exit 0
	fi

	commit_listing_svn
	printf 'Updated WordPress.org readme and assets for %s.\n' "${VERSION}"
}

self_test() {
	local repo readme org url wc before after err_file

	is_official_stable_version "1.2.3" || die "1.2.3 should be a stable version."
	if is_official_stable_version "1.2.3-beta"; then
		die "1.2.3-beta should be refused."
	fi

	repo="$( mktemp -d )"
	git -C "${repo}" init -q -b main
	git -C "${repo}" config user.email "test@example.com"
	git -C "${repo}" config user.name "Test"
	printf 'code\n' > "${repo}/plugin.php"
	printf '=== Plugin ===\nStable tag: 1.2.3\n\n== Description ==\n\nold\n' > "${repo}/readme.txt"
	git -C "${repo}" add plugin.php readme.txt
	git -C "${repo}" commit -q -m "release"
	git -C "${repo}" tag v1.2.3
	printf '=== Plugin ===\nStable tag: 1.2.3\n\n== Description ==\n\nnew\n' > "${repo}/readme.txt"
	mkdir -p "${repo}/.wordpress-org"
	printf 'notes\n' > "${repo}/.wordpress-org/README.md"
	git -C "${repo}" add readme.txt .wordpress-org/README.md
	git -C "${repo}" commit -q -m "readme and assets"
	assert_only_listing_files_differ "${repo}" "v1.2.3"

	printf 'changed\n' > "${repo}/plugin.php"
	git -C "${repo}" add plugin.php
	git -C "${repo}" commit -q -m "code change"
	err_file="$( mktemp )"
	if ( assert_only_listing_files_differ "${repo}" "v1.2.3" >"${err_file}" 2>&1 ); then
		die "Stable-tag diff guard did not abort."
	fi
	grep -q 'plugin.php' "${err_file}" || die "Stable-tag diff guard did not report plugin.php."
	grep -q 'Refusing to update WordPress.org' "${err_file}" || die "Stable-tag diff guard did not abort."
	rm -f "${err_file}"

	mkdir -p "${repo}/readme-src"
	readme="${repo}/readme-src/readme.txt"
	printf '=== Plugin ===\nStable tag: 1.2.3\n\nnew-readme\n' > "${readme}"
	if ( copy_readme_only "${repo}/plugin.php" "${repo}/dest" ); then
		die "copy_readme_only accepted a php file."
	fi
	copy_readme_only "${readme}" "${repo}/dest"
	[[ -f "${repo}/dest/readme.txt" ]] || die "copy_readme_only did not write readme.txt."
	if [[ -e "${repo}/dest/plugin.php" ]]; then
		die "copy_readme_only wrote a php file."
	fi
	grep -qx 'new-readme' "${repo}/dest/readme.txt" || die "Copied readme has the wrong contents."

	require_cmd svn
	require_cmd svnadmin
	require_cmd rsync

	svnadmin create "${repo}/repo"
	url="file://${repo}/repo"
	mkdir -p "${repo}/seed/trunk" "${repo}/seed/tags/1.2.3" "${repo}/seed/assets"
	printf 'code\n' > "${repo}/seed/trunk/fluid-checkout.php"
	printf 'code\n' > "${repo}/seed/tags/1.2.3/fluid-checkout.php"
	printf 'old\n' > "${repo}/seed/trunk/readme.txt"
	printf 'old\n' > "${repo}/seed/tags/1.2.3/readme.txt"
	printf 'icon\n' > "${repo}/seed/assets/icon.png"
	svn import "${repo}/seed" "${url}" -m "seed" --non-interactive >/dev/null

	org="${repo}/listing"
	mkdir -p "${org}/blueprints"
	printf 'do not publish\n' > "${org}/README.md"
	printf '{"blueprint":true}\n' > "${org}/blueprints/blueprint.json"

	wc="${repo}/wc"
	svn checkout "${url}" "${wc}" --non-interactive >/dev/null
	SVN_URL="${url}"
	SVN_AUTH_ARGS=( --non-interactive --no-auth-cache )
	VERSION="1.2.3"
	(
		cd "${wc}"
		stage_listing_update "${readme}" "${org}" "1.2.3"
	)

	printf 'evil\n' > "${wc}/trunk/evil.php"
	if ( cd "${wc}" && assert_svn_listing_changes_only "1.2.3" ); then
		die "SVN status guard accepted trunk/evil.php."
	fi
	rm -f "${wc}/trunk/evil.php"

	grep -qx 'code' "${wc}/trunk/fluid-checkout.php" || die "Trunk code changed while staging the readme."
	grep -qx 'code' "${wc}/tags/1.2.3/fluid-checkout.php" || die "Tag code changed while staging the readme."
	grep -qx 'new-readme' "${wc}/trunk/readme.txt" || die "Trunk readme was not updated."
	grep -qx 'new-readme' "${wc}/tags/1.2.3/readme.txt" || die "Stable tag readme was not updated."
	[[ -f "${wc}/assets/icon.png" ]] || die "Asset update removed an existing icon."
	[[ -f "${wc}/assets/blueprints/blueprint.json" ]] || die "Asset update did not copy the blueprint."
	if [[ -e "${wc}/assets/README.md" ]]; then
		die "Asset update copied README.md into SVN."
	fi

	before="$( svnlook youngest "${repo}/repo" )"
	DRY_RUN="true"
	if ( cd "${wc}" && DRY_RUN="true" && commit_listing_svn ); then
		die "Dry run committed to SVN."
	fi
	after="$( svnlook youngest "${repo}/repo" )"
	[[ "${before}" == "${after}" ]] || die "Dry run changed the SVN revision."

	(
		cd "${wc}"
		DRY_RUN="false"
		SVN_AUTH_ARGS=( --non-interactive --no-auth-cache )
		commit_listing_svn
	)

	svn cat "${url}/trunk/fluid-checkout.php" | grep -qx 'code' || die "Committed trunk code changed."
	svn cat "${url}/tags/1.2.3/fluid-checkout.php" | grep -qx 'code' || die "Committed tag code changed."
	svn cat "${url}/trunk/readme.txt" | grep -qx 'new-readme' || die "Committed trunk readme is stale."
	svn cat "${url}/tags/1.2.3/readme.txt" | grep -qx 'new-readme' || die "Committed tag readme is stale."
	svn cat "${url}/assets/icon.png" | grep -qx 'icon' || die "Committed assets lost the existing icon."
	svn cat "${url}/assets/blueprints/blueprint.json" | grep -q 'blueprint' || die "Committed assets are missing the blueprint."
	if svn cat "${url}/assets/README.md" >/dev/null 2>&1; then
		die "README.md was committed to SVN assets."
	fi

	rm -rf "${repo}"
	printf 'update-wordpress-org-assets self-test passed\n'
}

if [[ "${1:-}" == "--self-test" ]]; then
	self_test
	exit 0
fi

update_readme_and_assets
