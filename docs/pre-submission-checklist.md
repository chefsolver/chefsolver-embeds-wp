# WordPress.org pre-submission checklist — ChefSolver Embeds

Status of the plugin against WordPress.org Plugin Directory guidelines.

## Licensing
- [x] GPLv2-or-later declared in the plugin header (`License`, `License URI`).
- [x] Same license declared in `readme.txt`.
- [x] No bundled code under an incompatible license.

## Naming & structure
- [x] Unique slug `chefsolver-embeds`; main file `chefsolver-embeds.php`.
- [x] Complete plugin header (Name, URI, Description, Version, Requires at least, Tested up to, Requires PHP, Author, Author URI, License, License URI, Text Domain).
- [x] All global constants prefixed `CHEFSOLVER_EMBEDS_`.
- [x] Main class prefixed `ChefSolver_Embeds_` (no unprefixed globals; only two prefixed bootstrap functions).

## Security
- [x] Every PHP file exits when `ABSPATH` is not defined (`uninstall.php` checks `WP_UNINSTALL_PLUGIN`).
- [x] All shortcode/block attributes sanitized with allowlists or strict patterns (`type`, `slug`, `lang`, `theme`, `compact`, `radius`, `accent`, `height`).
- [x] Output escaped (`esc_url`, `esc_attr`, `esc_html`) at the point of output.
- [x] Settings write protected by `manage_options` capability and Settings API nonce.
- [x] "Refresh manifest" action protected by `manage_options` + `check_admin_referer` nonce.
- [x] REST endpoints gated by `edit_posts` capability.
- [x] iframe `src` restricted to allowed ChefSolver hosts (`chefsolver.com`, `www.chefsolver.com`) with an https scheme check.

## Privacy / external services
- [x] External service (ChefSolver iframe + manifest fetch) disclosed in `readme.txt`.
- [x] No visitor tracking, cookies, or analytics added by the plugin.
- [x] No remote JavaScript: editor script/styles are local; only the iframe content is remote.
- [x] No forced credit/backlink emitted outside the iframe — attribution stays inside the ChefSolver iframe.

## Data / cleanliness
- [x] Manifest cached in a transient with a configurable TTL (min 300s); not fetched on every page load.
- [x] `uninstall.php` removes only this plugin's option and transient (multisite-aware).
- [x] No custom database tables, no files written outside the plugin.

## Manifest handling
- [x] Manifest fetched via the WordPress HTTP API (`wp_remote_get`).
- [x] JSON validated before use; each entry validated for `type`, `lang`, `title`, a ChefSolver `embedUrl`, and a derivable `slug`, with safe fallbacks.
- [x] Tool list is not hardcoded — any valid manifest entry (any `type`) can be embedded.

## Quality
- [x] `php -l` passes on every PHP file.
- [x] Install from ZIP and uninstall verified (see `docs/qa.md`).
- [x] `readme.txt` validates against the WordPress.org readme format (headers, Tags ≤ 5, Stable tag, sections).

## Out of scope (intentionally, per project constraints)
- No analytics/tracking, no accounts, no private API, no premium upsell.
- No automated WordPress.org submission or SVN deploy.
