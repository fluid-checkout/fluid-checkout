# WordPress.org listing assets

Files in this folder are published to the plugin SVN `assets/` directory. They are not part of the installable zip.

`README.md` is for this repository. It is not copied to SVN.

`blueprints/blueprint.json` is copied to SVN `assets/blueprints/blueprint.json`. That is the file the WordPress.org Live Preview reads. A committer still has to set the preview to public on the plugin's Advanced view before the button is shown to everyone. Docs: https://developer.wordpress.org/plugins/wordpress-org/previews-and-blueprints/

Until this folder contains listing images, sync leaves the screenshots, banners, and icons already on WordPress.org in place. A blueprint-only update does not remove them. After the first listing image is added here, this folder is the source of truth for those root files: new files are added, changed files are replaced, and root files removed from here are removed from SVN. `blueprints/` is synced on its own and is not removed when this folder has no `blueprints/` directory.

## File names

WordPress.org only displays files with these names. Use lowercase. `.png` and `.jpg` are both accepted for screenshots and banners. Icons may be `.png`, `.jpg`, `.gif`, or `.svg`.

- `screenshot-1.png`. One file per line under `== Screenshots ==` in `readme.txt`, in that order. The readme line is the caption.
- `screenshot-1-de_DE.png` for a locale. A language code works too, for example `screenshot-1-de.png`.
- `banner-772x250.png` and `banner-1544x500.png`. The 1544×500 file is only used together with the 772×250 file. Right-to-left: `banner-772x250-rtl.png` and `banner-1544x500-rtl.png`. A locale suffix works the same way, for example `banner-772x250-es_ES.png`.
- `icon-128x128.png` and `icon-256x256.png`. `icon.svg` may be added as well. Keep a PNG icon too. WordPress.org uses it when SVG is not shown.

The sync sets `svn:mime-type` to `image/png`, `image/jpeg`, `image/gif`, or `image/svg+xml` so WordPress.org displays the files instead of downloading them.

Handbook: https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/
