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
