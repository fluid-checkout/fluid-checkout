# Importing Fluid Checkout Lite Sass

Other Fluid Checkout plugins (PRO and add-ons) compile against Lite's Sass from a sibling directory. Those entry points are unchanged by the Dart Sass upgrade.

## Supported import

From a stylesheet in a sibling plugin, for example `wp-content/plugins/fluid-checkout-pro/sass/`:

```scss
@import "../../fluid-checkout/sass/abstract/all-early";
@import "../../fluid-checkout/sass/abstract/all";
```

`all-early` loads `strip-unit()` and `remsize()`. `all` loads variables, the remaining functions, mixins, and shared placeholder styles. Load `all-early` first. Members are global, which is what `@import` provides.

The leading underscore and the `.scss` extension are optional. This existing form still works:

```scss
@import "../../fluid-checkout/sass/abstract/_all-early";
@import "../../fluid-checkout/sass/abstract/_all";
```

### RTL

Import the RTL overrides **after** `all-early` and **before** `all`. Variables in `all` are `!default`, so the earlier RTL values win.

```scss
@import "../../fluid-checkout/sass/abstract/all-early";
@import "../../fluid-checkout/sass/abstract/variables-rtl";
@import "../../fluid-checkout/sass/abstract/all";
```

### Tom Select

`variables-tom-select` reads checkout field variables from `all`, so it has to come after `all`.

```scss
@import "../../fluid-checkout/sass/abstract/all-early";
@import "../../fluid-checkout/sass/abstract/all";
@import "../../fluid-checkout/sass/abstract/variables-tom-select";
```

### Admin

```scss
@import "../../fluid-checkout/sass/abstract/all-early";
@import "../../fluid-checkout/sass/abstract/all";
@import "../../fluid-checkout/sass/abstract/variables-admin";
```

## Do not `@use` these files

```scss
// This is an error. These files still contain @import.
@use "../../fluid-checkout/sass/abstract/all";
```

Sass refuses to load a stylesheet that contains `@import` with `@use` or `@forward`. One file also cannot serve both systems: members brought in with `@use` / `@forward` are not re-exported through `@import`, so converting the entry points would make the imports above stop defining variables, functions, and mixins.

## Why Lite still uses `@import`

A full move to the module system would change compiled CSS and break dependent plugins.

- RTL and theme stylesheets set `!default` variables by import order. `@use` configuration (`with (...)`) does not preserve that order.
- Partials read globals defined by the file that imports them, such as `$_body-theme-selector`. `@use` does not pass members into imported files.
- Lite `@import`s third-party Sass (`flyout-block`, `sticky-states`) that still uses `@import`. `@use` cannot load those files.
- PRO and add-ons `@import` `abstract/_all-early` and `abstract/_all` and expect a global namespace.

`@import` and the global built-in functions remain deprecated in Dart Sass and are scheduled to be removed in Dart Sass 3, not Dart Sass 2. The Lite gulp build passes `silenceDeprecations: ['import']` so those warnings are not emitted. It also skips deprecation warnings whose source file is under `node_modules` (flyout-block's `transparentize()` is imported by a relative path, so Sass `quietDeps` does not apply). Warnings from Lite's own stylesheets are still printed. Dependent plugins that compile these files with Dart Sass 1.80 or newer can pass `silenceDeprecations: ['import']` until a coordinated module migration.

## What did change

- `sass` is Dart Sass 1.105.1 (previously 1.77.8), loaded through `gulp-sass` 6, which uses the modern `compileString` API. The legacy `render` API is not used.
- Functions in `sass/abstract/functions/` call `sass:math`, `sass:meta`, `sass:map`, and `sass:string` instead of global built-ins. Call sites are unchanged (`strip-unit()`, `remsize()`, `z()`, `icon-glyph()`, `str-replace()`).
