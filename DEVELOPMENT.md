# Development setup

## Coding standards and Cursor rules

Shared conventions, Cursor rules, and Agent skills live in the **[fluid-checkout-standards](https://github.com/fluid-checkout/fluid-checkout-standards)** repository. This plugin does **not** vendor standards via a git submodule. Instead, use a **local symlink** so edits in the canonical standards repo are picked up immediately by Cursor and tooling.

### Prerequisites

Clone the standards repo as a sibling of this plugin under `wp-content/plugins/`:

```
wp-content/plugins/
├── fluid-checkout/              ← this repo
├── fluid-checkout-standards/    ← clone here
└── …
```

```bash
git clone git@github.com:fluid-checkout/fluid-checkout-standards.git \
  ../fluid-checkout-standards
```

### Link standards into this plugin

From the **fluid-checkout** plugin root:

```bash
ln -sfn ../fluid-checkout-standards fluid-checkout-standards
```

Verify:

```bash
test -f fluid-checkout-standards/.cursor/rules/fc-core.mdc && echo OK
```

The path `fluid-checkout-standards/` is listed in `.gitignore` and must **not** be committed. The thin Cursor rule `.cursor/rules/fluid-checkout-standards.mdc` in this repo points Agent at `fluid-checkout-standards/.cursor/rules/`.

### Updating conventions

1. Change rules or skills in **fluid-checkout-standards** and merge to `main`.
2. No submodule bump is required in this repo — the symlink always resolves to your local checkout. Pull the standards repo when you need the latest team conventions:

```bash
cd ../fluid-checkout-standards && git pull
```

### Fresh clone of this plugin

After cloning **fluid-checkout**, create the symlink (and ensure **fluid-checkout-standards** exists) as above. Do not run `git submodule update`.
