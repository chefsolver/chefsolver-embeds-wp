# ChefSolver Embeds — WordPress plugin (development repo)

WordPress plugin to embed [ChefSolver](https://chefsolver.com/) converter widgets (generic unit converters and ingredient-specific converters) via a shortcode or a Gutenberg block, driven by the public catalog at `https://chefsolver.com/embed/manifest.json`.

> **Status: scaffold.** The current code registers the `[chefsolver_embed]` shortcode with a safe placeholder. The manifest-driven iframe renderer, Gutenberg block, and settings page land in the next iteration — see [`docs/release-model.md`](docs/release-model.md).

## The three places this project lives — and what each one is

| Where | Role |
|---|---|
| **This GitHub repo** (`robertmain53/chefsolver-embeds-wp`) | The **development repository**: source of truth for code, issues, QA and releases. Installable ZIPs are built from here. |
| **WordPress.org SVN** | **Not yet configured.** It will be a *release-only channel* if/after the plugin is reviewed and approved for the WordPress.org directory. No development happens there; only approved, tagged releases would be synced. |
| **ChefSolver.com** | The **service being embedded**. It publishes the embed pages and the public manifest the plugin consumes. Its source lives in a separate repository and is not part of this project. |

## Repository layout

| Path | Purpose |
|---|---|
| `chefsolver-embeds.php` | Main plugin file: WordPress header, constants, bootstrap. |
| `includes/` | PHP classes (`ChefSolver_Embeds_Plugin`, future manifest/renderer classes). |
| `assets/` | Plugin assets for WordPress.org (icons, banners, screenshots) and local editor assets. |
| `build/` | Output directory for installable ZIPs (gitignored; a script will populate it). |
| `docs/` | Release model, QA checklists, pre-submission notes. |
| `readme.txt` | WordPress.org-format readme (drives the future directory listing). |
| `uninstall.php` | Uninstall cleanup (options/transients only). |
| `LICENSE` | GPLv2 (the plugin is GPLv2-or-later, matching the header and `readme.txt`). |

## Install (QA)

1. Zip the repo contents into `chefsolver-embeds.zip` (folder `chefsolver-embeds/` at the ZIP root), or grab a ZIP from a release.
2. wp-admin → Plugins → Add New → Upload Plugin → upload → Activate.
3. Put `[chefsolver_embed]` in a post: you should see the placeholder output.

See [`docs/qa.md`](docs/qa.md) for the full manual checklist.

## License

GPLv2-or-later. See [`LICENSE`](LICENSE).
