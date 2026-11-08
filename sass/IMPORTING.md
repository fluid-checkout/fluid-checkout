# Importing Fluid Checkout Lite Sass

Sibling plugins compile against Lite's public module at `sass/abstract/`. Load it with `@use`, not `@import`.

## Supported import

From a stylesheet in a sibling plugin, for example `wp-content/plugins/fluid-checkout-pro/sass/`:

```scss
@use "../../fluid-checkout/sass/abstract" as fc;

.example {
	color: fc.$black;
	margin-top: fc.remsize( 1.6rem );
	z-index: fc.z( modal );
}
```

Use a namespace (`fc`, `fcpro`, etc.). `@use "../../fluid-checkout/sass/abstract" as *` is allowed when you control every member name.

### RTL

Load the RTL module **first** in that compilation (before any other `@use` of `abstract`):

```scss
@use "../../fluid-checkout/sass/abstract/index-rtl";
@use "partials/__imports-checkout-steps";
```

`index-rtl` forwards the public module with alignment and other RTL overrides already applied.

### Tom Select and admin tokens

```scss
@use "../../fluid-checkout/sass/abstract" as fc;
@use "../../fluid-checkout/sass/abstract/variables-admin" as fc-admin;
@use "../../fluid-checkout/sass/abstract/variables-tom-select" as fc-ts;
```

Load `variables-admin` and `variables-tom-select` after the main module. Configure checkout field variables with `@use ".../abstract" as fc with (...)` on the **first** load when needed.

## PRO and add-ons extending Lite

Forward Lite first, then plugin variables and styles:

```scss
// fluid-checkout-pro/sass/abstract/_index.scss
@forward "../../../fluid-checkout/sass/abstract";
@forward "variables";
@forward "styles/all";
```

## Shared UI libs (flyout-block, sticky-states)

Canonical Sass lives in `fluid-checkout-standards/shared/sass/lib/` and syncs into Lite as `sass/lib/`. Load with `@use`, not `@import`:

```scss
@use 'lib/flyout-block'; // theme only
@use 'lib/sticky-states';
@use 'lib/flyout-block/index-flyout-only'; // theme alias
@use 'lib/flyout-block/index-animations'; // every animation
@use 'lib/flyout-block/theme/animations/fade-in-up'; // one animation
@use 'partials/flyout-block-animations'; // Lite-only: FC-used subset
```

Animations live under `lib/flyout-block/theme/animations/` (one file each), plus `_imports-all.scss`. The Fluid Checkout subset is selected in Lite at `partials/_flyout-block-animations.scss` (not in standards).

Edit those libs in standards, then sync (`--file sass-lib-flyout-block` / `sass-lib-sticky-states`) and rebuild CSS.

## Third-party Sass

Tom Select still uses `@import` inside `sass/vendor/` bridge files. Entry stylesheets load them with `@use 'sass:meta'` and `@include meta.load-css('…')`. The gulp build sets `quietDeps: true` and `loadPaths` for `sass/` and `sass/vendor/`.

## Check

After changing Sass sources, from the plugin root:

```bash
npx gulp build-css
```

See also `fc-conversion-kit/sass/README.md` for the same module pattern used across the ecosystem.
