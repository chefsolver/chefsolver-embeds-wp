# ChefSolver Embeds — WordPress plugin (development repo)

WordPress plugin to embed [ChefSolver](https://chefsolver.com/) converter widgets (generic unit converters and ingredient-specific converters) via a shortcode or a Gutenberg block, driven by the public catalog at `https://chefsolver.com/embed/manifest.json`.

> **Status: feature-complete MVP (1.0.0).** Manifest-driven iframe renderer, `[chefsolver_embed]` + `[chefsolver_converter]` shortcodes, server-side-rendered `chefsolver/embed` Gutenberg block with manifest search + manual fallback, settings page (defaults, cache TTL, refresh), strict sanitization, and iframes hard-restricted to `chefsolver.com`. Release flow in [`docs/release-model.md`](docs/release-model.md).

## The three places this project lives — and what each one is

| Where | Role |
|---|---|
| **This GitHub repo** (`chefsolver/chefsolver-embeds-wp`) | The **official public development repository**: source of truth for code, issues, QA and releases. Installable ZIPs are built from here (see `bin/build-zip.sh`). |
| **WordPress.org SVN** | **Not yet configured.** It will be a *release-only channel* if/after the plugin is reviewed and approved for the WordPress.org directory. No development happens there; only approved, tagged releases would be synced. |
| **ChefSolver.com** | The **service being embedded**. It publishes the embed pages and the public manifest the plugin consumes. Its source lives in a separate repository and is not part of this project. |

## Repository layout

| Path | Purpose |
|---|---|
| `chefsolver-embeds.php` | Main plugin file: WordPress header, constants, bootstrap. |
| `includes/` | PHP classes: `ChefSolver_Embeds_Plugin` (shortcodes, block, settings, renderer) and `ChefSolver_Embeds_Manifest` (fetch, validate, cache, lookup). |
| `blocks/` | Gutenberg block (`chefsolver/embed`): `block.json` + buildless editor script (WP core globals only, no remote JS). |
| `assets/` | Plugin assets for WordPress.org (icons, banners, screenshots) and local editor assets. |
| `build/` | Output directory for installable ZIPs (gitignored; a script will populate it). |
| `docs/` | Release model, QA checklists, pre-submission notes. |
| `readme.txt` | WordPress.org-format readme (drives the future directory listing). |
| `uninstall.php` | Uninstall cleanup (options/transients only). |
| `LICENSE` | GPLv2 (the plugin is GPLv2-or-later, matching the header and `readme.txt`). |

## Install (QA)

1. Run `bin/build-zip.sh` to produce `build/chefsolver-embeds.zip` (runtime files only, `chefsolver-embeds/` folder at the ZIP root), or grab a ZIP from a release.
2. wp-admin → Plugins → Add New → Upload Plugin → upload → Activate.
3. Put `[chefsolver_embed type="converter" slug="ml-to-grams" lang="en"]` in a post: the ChefSolver converter iframe renders.

See [`docs/qa.md`](docs/qa.md) for the full manual checklist.

## License

GPLv2-or-later. See [`LICENSE`](LICENSE).
