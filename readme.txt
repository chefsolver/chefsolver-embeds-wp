=== ChefSolver Embeds ===
Contributors: chefsolver
Tags: converter, cooking, embed, iframe, shortcode
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed ChefSolver cooking converter widgets via a shortcode or a Gutenberg block. Manifest-driven and type-agnostic.

== Description ==

ChefSolver Embeds lets you place ChefSolver converter widgets on your site with a shortcode or a Gutenberg block. Each widget is a small iframe served by ChefSolver.

The plugin is **manifest-driven**: the list of embeddable tools comes from the public catalog at `https://chefsolver.com/embed/manifest.json`. It is **type-agnostic** — it renders generic unit converters (for example mL → grams), ingredient-specific converters (for example honey mL → grams) and standalone calculators (pizza dough calculator, recipe scaler, recipe converter), and will keep working with new embed types added to the manifest in the future, without a plugin update.

= External service disclosure =

This plugin embeds a third-party service, **ChefSolver** (https://chefsolver.com/), inside an `<iframe>`.

* It fetches the public embed catalog from `https://chefsolver.com/embed/manifest.json` (server-side, via the WordPress HTTP API) and caches it in a transient. This is used to validate and resolve which tool to embed.
* On the front end, the only remote content loaded is the ChefSolver embed page itself, inside the iframe (for example `https://chefsolver.com/embed/en/converters/ml-to-grams/`). The iframe target is always restricted to the `chefsolver.com` host.
* Terms and privacy for the embedded service: https://chefsolver.com/en/terms/

= Privacy =

* The plugin itself does **not** track visitors, set cookies, or send any visitor data anywhere.
* The plugin loads no remote JavaScript. All editor scripts and styles are bundled locally; the only remote front-end content is the ChefSolver iframe.
* The embedded ChefSolver page runs inside its own iframe under ChefSolver's own privacy policy. Attribution, data sources and the link back to ChefSolver are shown **inside** that iframe by ChefSolver, not injected by this plugin.

= Features =

* `[chefsolver_embed]` shortcode and a `chefsolver/embed` Gutenberg block.
* `[chefsolver_converter]` alias for the common converter case.
* Server-side rendered block: the block preview and the front end use the same PHP renderer.
* Block editor search/select of any tool present in the manifest, with a manual fallback if the manifest is offline.
* Style options passed through to the embed: theme (light/dark/auto), accent color, compact mode, corner radius, height.
* Settings page for default language, theme, height, and manifest cache TTL, plus a manual "Refresh manifest" button.
* Strict input sanitization and escaped output; iframes are restricted to ChefSolver hosts.

== Installation ==

1. In wp-admin go to Plugins → Add New → Upload Plugin and upload `chefsolver-embeds.zip`.
2. Activate the plugin.
3. (Optional) Visit Settings → ChefSolver Embeds to set default language, theme, height and cache TTL.
4. Add a "ChefSolver Embed" block, or use the `[chefsolver_embed]` shortcode in any post or page.

== Frequently Asked Questions ==

= What does the plugin load from chefsolver.com? =

Server-side, it fetches the public JSON catalog `https://chefsolver.com/embed/manifest.json` and caches it. On the front end it loads one ChefSolver embed page per widget, inside an iframe. Nothing else is requested from chefsolver.com.

= Does it work with the Classic Editor? =

Yes. Use the `[chefsolver_embed]` or `[chefsolver_converter]` shortcode. The block is only needed for the block editor.

= What if the manifest is temporarily unavailable? =

Rendering still works: the plugin builds a validated fallback URL for known types and always restricts the iframe to the chefsolver.com host. In the block editor you can enter the type, slug and language manually.

= How do I embed an ingredient-specific converter? =

Use `type="ingredient-converter"` and a compound slug of `ingredient/conversion`, e.g. `[chefsolver_embed type="ingredient-converter" slug="honey/ml-to-grams" lang="en"]`.

= Does the plugin track visitors? =

No. The plugin adds no analytics, cookies, or tracking. The embedded ChefSolver page operates under its own policy inside the iframe.

== Screenshots ==

1. The ChefSolver Embed block with tool search and style options in the editor.
2. A rendered converter embed on the front end.
3. The Settings → ChefSolver Embeds page.

== Changelog ==

= 1.0.0 =
* Initial release: `[chefsolver_embed]` + `[chefsolver_converter]` shortcodes, server-rendered `chefsolver/embed` block, manifest fetch + transient cache, settings page, strict sanitization, ChefSolver-host-restricted iframes.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
