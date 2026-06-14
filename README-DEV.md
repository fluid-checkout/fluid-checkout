# Development setup


## Setup coding standards and Cursor rules

Shared coding conventions, Cursor rules, and Agent skills live in the **[fluid-checkout-standards](https://github.com/fluid-checkout/fluid-checkout-standards)** repository. This repository uses a **local symlink** directing to the canonical standards so that changes to the original standards are picked up immediately by Cursor and AI tooling.

Once configured, Cursor will use the Fluid Checkout Standards rules when:

- Creating new code in Agent mode.
- Creating new code using inline prompts with `Ctrl-K`.

These rules are not used by Cursor when:

- Using code autocomplete suggestions, however, that already uses the context of the file being edited which is usually sufficient to produce code in the project's standards.


### Prerequisites

Clone the standards repo as a sibling of this repository under `wp-content/plugins/`:

```
wp-content/plugins/
├── this-repo/                   ← this repo
├── fluid-checkout-standards/    ← clone here
└── …
```

From inside this repository on the terminal, run:

```bash
git clone git@github.com:fluid-checkout/fluid-checkout-standards.git ../fluid-checkout-standards
```



### Link standards into this repository

From the root directory this repository, run:

```bash
ln -sfn ../fluid-checkout-standards fluid-checkout-standards
```

Then verify with:

```bash
test -f fluid-checkout-standards/.cursor/rules/fc-core.mdc && echo OK
```

The path `fluid-checkout-standards/` should be listed in `.gitignore` for each project repository and **must not** be committed.
The thin Cursor rule `.cursor/rules/fluid-checkout-standards.mdc` in each project points at the original standards repo `fluid-checkout-standards/.cursor/rules/`.



## Third-party file replacements manifest

[`replacements.json`](replacements.json) tracks files in this plugin that replace or fork third-party code (WooCommerce scripts, plugin compat JS, and template overrides).

Entries are grouped by `category`:

| Category | Description |
|---|---|
| `plugin-compat-js` | Compat JS forks for third-party plugins |
| `theme-compat-js` | Compat JS forks for themes |
| `plugin-compat-template` | Template overrides for third-party plugins |
| `theme-compat-template` | Template overrides for themes |
| `woocommerce-template` | WooCommerce template overrides |

Each entry maps our fork to the original source:

| Field | Description |
|---|---|
| `our_path` | Path to our fork in this plugin |
| `their_path` | Upstream source: wp-content-relative path (e.g. `plugins/woocommerce/templates/checkout/payment-method.php`) or public URL to the exact file |

### When to update

After syncing a forked file from upstream, update `@fc-version` / `@version` template headers when applicable.

When adding a new compat fork, regenerate the manifest and review the diff:

```bash
node fluid-checkout-standards/scripts/generate-replacements-json.js
```

Configuration lives in [`replacements.config.json`](replacements.config.json) (scan dirs, exclusions, and manual `their_path` / script handle overrides). See the [replacements skill](../fluid-checkout-standards/.cursor/skills/fluid-checkout-replacements/SKILL.md) for the full workflow.

### Not tracked

- Files directly in `js-src/` (WooCommerce core JS forks are listed via `woocommerce_js_entries` in `replacements.config.json`)
- Built npm libraries under `js/lib/` (collapsible-block, flyout-block, sticky-states, etc.)
- PHP files directly in `inc/` (e.g. copied snippets in `checkout-fields.php`)
- PHP compat files under `inc/compat/plugins/` (copied snippets in compat classes)
- WooCommerce template overrides with `@package fluid-checkout` (Fluid Checkout originals, not third-party forks)
- FC-original compat JS with no upstream file in the source plugin or theme
- WooCommerce Checkout block replacement (`blocks/woocommerce/checkout/`)
- SCSS style forks under `sass/compat/plugins/`
