# QA checklist — ChefSolver Embeds (scaffold)

Manual checks for the current scaffold release. Grows with each feature iteration.

## Install
- [ ] Build/obtain `chefsolver-embeds.zip` (folder `chefsolver-embeds/` at the ZIP root).
- [ ] wp-admin → Plugins → Add New → Upload Plugin → upload the ZIP → installs without errors.
- [ ] With `WP_DEBUG` enabled: no notices/warnings on install.

## Activation
- [ ] Activate the plugin: no fatal errors, site front end and wp-admin load normally.
- [ ] Plugin row shows name, description, version `0.1.0`, and GPLv2-or-later license link.

## Shortcode placeholder
- [ ] Add `[chefsolver_embed]` to a post (block editor: Shortcode block; Classic Editor: plain text).
- [ ] Front end renders: `ChefSolver embed placeholder` inside a `div.chefsolver-embed--placeholder`.
- [ ] Output is escaped/safe — view source: no unexpected markup or script.
- [ ] Shortcode with stray attributes (e.g. `[chefsolver_embed foo="<script>"]`) still renders only the placeholder.

## Deactivate / uninstall
- [ ] Deactivate: no errors; placeholder shortcode renders as plain text (WordPress default for unregistered shortcodes).
- [ ] Delete the plugin: uninstall runs without errors.
- [ ] Confirm no `chefsolver_embeds_options` option or `chefsolver_embeds_manifest` transient rows remain in `wp_options`.

## Code health
- [ ] `php -l` passes on every `.php` file.
- [ ] Every PHP file blocks direct access (`ABSPATH` / `WP_UNINSTALL_PLUGIN` guard).
