#!/usr/bin/env bash
# Sync .wordpress-org into an SVN assets/ working copy.
#
# Sourced by deploy-wordpress-org.sh and update-wordpress-org-assets.sh.
# Run this file directly only for --self-test.
#
# README files are never copied. Listing images (everything except README
# and blueprints/) are copied with rsync --delete only when at least one
# such file exists. Until then, screenshots, banners, and icons already in
# SVN are left alone. blueprints/ is synced on its own when that directory
# exists. png, jpg, jpeg, gif, and svg get svn:mime-type so WordPress.org
# displays them.
#
# The assets directory must already be checked out at depth infinity before
# a deleting sync. Files that were never checked out cannot be scheduled
# for svn delete.
#
# SVN_USERNAME and SVN_PASSWORD are read by build_svn_auth_args. They are
# never echoed.

set -euo pipefail

if ! declare -F die >/dev/null 2>&1; then
	die() {
		printf '::error::%s\n' "$1" >&2
		exit 1
	}
fi

if ! declare -F require_cmd >/dev/null 2>&1; then
	require_cmd() {
		if ! command -v "$1" >/dev/null 2>&1; then
			die "Required command not found: $1"
		fi
	}
fi

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

svn_mime_type_for_name() {
	local base lower
	base="$( basename "$1" )"
	lower="$( printf '%s' "${base}" | tr '[:upper:]' '[:lower:]' )"

	# Match the extension only. A locale suffix stays in the stem, so
	# banner-772x250-de.png and screenshot-1-de_DE.png are still image/png.
	case "${lower}" in
		*.png) printf 'image/png\n' ;;
		*.jpg|*.jpeg) printf 'image/jpeg\n' ;;
		*.gif) printf 'image/gif\n' ;;
		*.svg) printf 'image/svg+xml\n' ;;
		*) return 1 ;;
	esac
}

apply_svn_mime_types() {
	local root="$1"
	local skip_blueprints="${2:-}"
	local file mime current

	if [[ ! -d "${root}" ]]; then
		return 0
	fi

	while IFS= read -r file || [[ -n "${file}" ]]; do
		[[ -n "${file}" ]] || continue

		if [[ "${skip_blueprints}" == "skip-blueprints" ]]; then
			case "${file}" in
				"${root}/blueprints"|"${root}/blueprints"/*) continue ;;
			esac
		fi

		if ! mime="$( svn_mime_type_for_name "${file}" )"; then
			continue
		fi

		if ! svn info -- "${file}" >/dev/null 2>&1; then
			continue
		fi

		current="$( svn propget svn:mime-type -- "${file}" 2>/dev/null || true )"
		if [[ "${current}" == "${mime}" ]]; then
			continue
		fi

		svn propset svn:mime-type "${mime}" -- "${file}"
	done < <( find "${root}" -type f -print )
}

prune_unpublishable_files() {
	local root="$1"

	find "${root}" -type f \( \
		-iname 'readme' -o \
		-iname 'readme.md' -o \
		-iname 'readme.txt' -o \
		-iname '.ds_store' -o \
		-iname 'thumbs.db' -o \
		-iname 'desktop.ini' \
	\) -delete
}

wordpress_org_has_publishable_files() {
	local source="$1"
	local path base lower

	[[ -d "${source}" ]] || return 1

	while IFS= read -r path || [[ -n "${path}" ]]; do
		[[ -n "${path}" ]] || continue
		base="$( basename "${path}" )"
		lower="$( printf '%s' "${base}" | tr '[:upper:]' '[:lower:]' )"
		case "${lower}" in
			readme|readme.md|readme.txt|.ds_store|thumbs.db|desktop.ini)
				continue
				;;
		esac
		return 0
	done < <( find "${source}" -type f -print )

	return 1
}

stage_wordpress_org_tree() {
	local source="$1"
	local stage="$2"

	rm -rf "${stage}"
	mkdir -p "${stage}"
	rsync -a --exclude '.svn' "${source}/" "${stage}/"
	prune_unpublishable_files "${stage}"
}

list_root_listing_files() {
	local stage="$1"

	find "${stage}" -mindepth 1 \
		\( -path "${stage}/blueprints" -o -path "${stage}/blueprints/*" \) -prune \
		-o -type f -print
}

# $1 source .wordpress-org directory
# $2 SVN assets directory inside a working copy
sync_wordpress_org_assets() {
	local source="$1"
	local destination="$2"
	local stage

	if [[ ! -d "${source}" ]]; then
		printf 'No .wordpress-org directory. SVN assets/ left unchanged.\n'
		return 0
	fi

	if ! wordpress_org_has_publishable_files "${source}"; then
		printf '.wordpress-org has no listing files or blueprints to publish. README is not copied. SVN assets/ left unchanged.\n'
		return 0
	fi

	require_cmd rsync
	require_cmd svn

	stage="$( mktemp -d "${TMPDIR:-/tmp}/wporg-assets.XXXXXX" )"
	stage_wordpress_org_tree "${source}" "${stage}"

	if [[ -n "$( list_root_listing_files "${stage}" )" ]]; then
		printf 'Syncing .wordpress-org listing files into SVN assets/.\n'
		mkdir -p "${destination}"
		rsync -rc --delete \
			--exclude '.svn' \
			--exclude '/blueprints' \
			--exclude 'README' \
			--exclude 'README.md' \
			--exclude 'README.txt' \
			--exclude 'readme' \
			--exclude 'readme.md' \
			--exclude 'readme.txt' \
			"${stage}/" "${destination}/"
		sync_svn_status "${destination}"
		apply_svn_mime_types "${destination}" "skip-blueprints"
	else
		printf 'No listing images in .wordpress-org. Existing SVN screenshots, banners, and icons left unchanged.\n'
	fi

	# An empty or missing blueprints directory does not remove assets/blueprints.
	# Files inside blueprints/ are the source of truth only when at least one remains.
	if [[ -d "${stage}/blueprints" ]] && [[ -n "$( find "${stage}/blueprints" -type f -print -quit )" ]]; then
		printf 'Syncing .wordpress-org/blueprints into SVN assets/blueprints/.\n'
		mkdir -p "${destination}/blueprints"
		rsync -rc --delete \
			--exclude '.svn' \
			--exclude 'README' \
			--exclude 'README.md' \
			--exclude 'README.txt' \
			--exclude 'readme' \
			--exclude 'readme.md' \
			--exclude 'readme.txt' \
			"${stage}/blueprints/" "${destination}/blueprints/"
		sync_svn_status "${destination}/blueprints"
		apply_svn_mime_types "${destination}/blueprints"
	fi

	rm -rf "${stage}"
}

if [[ "${BASH_SOURCE[0]}" != "$0" ]]; then
	return 0
fi

self_test() {
	local repo url wc source svn_status images_only

	require_cmd svn
	require_cmd svnadmin
	require_cmd rsync

	svn_mime_type_for_name "screenshot-1.png" | grep -qx 'image/png' || die "png mime type"
	svn_mime_type_for_name "screenshot-9.png" | grep -qx 'image/png' || die "screenshot-9 mime type"
	svn_mime_type_for_name "banner-772x250-de.png" | grep -qx 'image/png' || die "short-locale banner mime type"
	svn_mime_type_for_name "banner-1544x500-pt.png" | grep -qx 'image/png' || die "short-locale retina banner mime type"
	svn_mime_type_for_name "screenshot-1-de_DE.png" | grep -qx 'image/png' || die "full-locale screenshot mime type"
	svn_mime_type_for_name "Banner.PNG" | grep -qx 'image/png' || die "PNG mime type"
	svn_mime_type_for_name "banner-772x250.jpg" | grep -qx 'image/jpeg' || die "jpg mime type"
	svn_mime_type_for_name "banner-1544x500.jpeg" | grep -qx 'image/jpeg' || die "jpeg mime type"
	svn_mime_type_for_name "icon.gif" | grep -qx 'image/gif' || die "gif mime type"
	svn_mime_type_for_name "icon.svg" | grep -qx 'image/svg+xml' || die "svg mime type"
	if svn_mime_type_for_name "readme.txt" >/dev/null 2>&1; then
		die "readme.txt was given an image mime type."
	fi

	repo="$( mktemp -d )"
	svnadmin create "${repo}/repo"
	url="file://${repo}/repo"
	mkdir -p "${repo}/seed/trunk" "${repo}/seed/assets" "${repo}/seed/tags"
	printf 'icon\n' > "${repo}/seed/assets/icon.png"
	printf 'old-shot\n' > "${repo}/seed/assets/screenshot-1.jpg"
	printf 'keep\n' > "${repo}/seed/trunk/keep.txt"
	svn import "${repo}/seed" "${url}" -m "seed" --non-interactive >/dev/null

	wc="${repo}/wc"
	svn checkout "${url}" "${wc}" --non-interactive >/dev/null

	source="${repo}/readme-only"
	mkdir -p "${source}"
	printf 'developer notes\n' > "${source}/README.md"
	if wordpress_org_has_publishable_files "${source}"; then
		die "README.md was treated as a publishable asset."
	fi

	(
		cd "${wc}"
		SVN_URL="${url}"
		SVN_AUTH_ARGS=( --non-interactive --no-auth-cache )
		sync_wordpress_org_assets "${source}" assets
	)

	svn_status="$( svn status --ignore-externals "${wc}" )"
	if [[ -n "${svn_status}" ]]; then
		printf '%s\n' "${svn_status}" >&2
		die "README-only .wordpress-org changed SVN assets."
	fi
	[[ -f "${wc}/assets/icon.png" ]] || die "README-only sync removed an existing icon."
	if [[ -e "${wc}/assets/README.md" ]]; then
		die "README.md was copied into SVN assets."
	fi

	mkdir -p "${source}/blueprints"
	printf '{}\n' > "${source}/blueprints/blueprint.json"
	printf 'not for svn\n' > "${source}/blueprints/README.md"
	printf 'not for svn\n' > "${source}/ReadMe.md"

	(
		cd "${wc}"
		SVN_URL="${url}"
		SVN_AUTH_ARGS=( --non-interactive --no-auth-cache )
		sync_wordpress_org_assets "${source}" assets
	)

	[[ -f "${wc}/assets/icon.png" ]] || die "Blueprint sync removed an existing icon."
	[[ -f "${wc}/assets/blueprints/blueprint.json" ]] || die "blueprint.json was not copied."
	if [[ -e "${wc}/assets/README.md" || -e "${wc}/assets/ReadMe.md" || -e "${wc}/assets/blueprints/README.md" ]]; then
		die "A README file was copied into SVN assets."
	fi
	svn_status="$( svn status --ignore-externals "${wc}" )"
	printf '%s\n' "${svn_status}" | grep -q 'assets/blueprints/blueprint.json' || die "Blueprint was not scheduled."
	if printf '%s\n' "${svn_status}" | grep -q 'icon.png'; then
		die "Blueprint sync scheduled a change to an existing icon."
	fi

	printf 'banner\n' > "${source}/screenshot-1.png"
	printf 'de-banner\n' > "${source}/banner-772x250-de.png"
	printf 'fr-retina\n' > "${source}/banner-1544x500-fr.png"
	printf 'wide\n' > "${source}/banner-772x250.jpg"
	printf 'retina\n' > "${source}/banner-1544x500.jpeg"
	printf 'gif\n' > "${source}/icon.gif"
	printf '<svg xmlns="http://www.w3.org/2000/svg"/>\n' > "${source}/icon.svg"
	printf 'junk\n' > "${source}/.DS_Store"

	(
		cd "${wc}"
		SVN_URL="${url}"
		SVN_AUTH_ARGS=( --non-interactive --no-auth-cache )
		sync_wordpress_org_assets "${source}" assets
	)

	svn_status="$( svn status --ignore-externals "${wc}" )"
	printf '%s\n' "${svn_status}" | grep -q 'assets/icon.png' || die "Removed listing file was not scheduled."
	printf '%s\n' "${svn_status}" | grep -q 'assets/screenshot-1.jpg' || die "Old screenshot jpg was not scheduled for removal."
	printf '%s\n' "${svn_status}" | grep -q 'assets/screenshot-1.png' || die "New screenshot was not scheduled."
	printf '%s\n' "${svn_status}" | grep -q 'assets/banner-772x250-de.png' || die "Localized banner was not scheduled."
	printf '%s\n' "${svn_status}" | grep -q 'assets/banner-1544x500-fr.png' || die "Localized retina banner was not scheduled."
	if printf '%s\n' "${svn_status}" | grep -q 'DS_Store'; then
		die ".DS_Store was scheduled for SVN."
	fi
	if [[ -e "${wc}/assets/.DS_Store" || -e "${wc}/assets/README.md" ]]; then
		die "A non-listing file was copied into SVN assets."
	fi
	[[ -d "${wc}/.svn" ]] || die "Sync damaged the working copy .svn directory."

	svn propget svn:mime-type -- "${wc}/assets/screenshot-1.png" | grep -qx 'image/png' || die "png svn:mime-type was not set."
	svn propget svn:mime-type -- "${wc}/assets/banner-772x250-de.png" | grep -qx 'image/png' || die "localized banner svn:mime-type was not set."
	svn propget svn:mime-type -- "${wc}/assets/banner-1544x500-fr.png" | grep -qx 'image/png' || die "localized retina banner svn:mime-type was not set."
	svn propget svn:mime-type -- "${wc}/assets/banner-772x250.jpg" | grep -qx 'image/jpeg' || die "jpg svn:mime-type was not set."
	svn propget svn:mime-type -- "${wc}/assets/banner-1544x500.jpeg" | grep -qx 'image/jpeg' || die "jpeg svn:mime-type was not set."
	svn propget svn:mime-type -- "${wc}/assets/icon.gif" | grep -qx 'image/gif' || die "gif svn:mime-type was not set."
	svn propget svn:mime-type -- "${wc}/assets/icon.svg" | grep -qx 'image/svg+xml' || die "svg svn:mime-type was not set."

	# Listing sync must not delete blueprints when the source has no blueprints directory.
	images_only="${repo}/images-only"
	mkdir -p "${images_only}"
	printf 'banner\n' > "${images_only}/screenshot-1.png"
	(
		cd "${wc}"
		SVN_URL="${url}"
		SVN_AUTH_ARGS=( --non-interactive --no-auth-cache )
		sync_wordpress_org_assets "${images_only}" assets
	)
	[[ -f "${wc}/assets/blueprints/blueprint.json" ]] || die "Listing sync removed assets/blueprints."
	[[ -f "${wc}/assets/screenshot-1.png" ]] || die "Listing file was not kept."
	if [[ -f "${wc}/assets/banner-772x250.jpg" ]]; then
		die "A listing file removed from .wordpress-org was left in the working copy."
	fi

	# Setting the same mime type again must not schedule a property edit.
	(
		cd "${wc}"
		apply_svn_mime_types assets "skip-blueprints"
	)
	if svn status --ignore-externals "${wc}/assets/screenshot-1.png" | grep -q '^.M'; then
		die "Repeating svn:mime-type marked the screenshot modified."
	fi

	rm -rf "${repo}"
	printf 'sync-wordpress-org-assets self-test passed\n'
}

case "${1:-}" in
	--self-test)
		self_test
		;;
	*)
		die "sync-wordpress-org-assets.sh is sourced by the WordPress.org workflows. Run it with --self-test to check the sync locally."
		;;
esac
