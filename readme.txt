=== ChefSolver Embeds ===
Contributors: chefsolver
Tags: converter, cooking, embed, iframe, shortcode
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 0.1.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed ChefSolver cooking converter widgets via shortcode or block. Scaffold release.

== Description ==

ChefSolver Embeds will let you place ChefSolver converter widgets (generic unit converters and ingredient-specific converters) on your site via a shortcode or a Gutenberg block, driven by the public catalog at `https://chefsolver.com/embed/manifest.json`.

This is the **scaffold release**: it registers the `[chefsolver_embed]` shortcode with a safe placeholder output. The manifest-driven iframe renderer, Gutenberg block and settings page land in the next iteration.

The plugin will embed content from the third-party service ChefSolver (https://chefsolver.com/) inside an iframe. The plugin itself adds no tracking, no cookies, and loads no remote JavaScript.

== Installation ==

1. In wp-admin go to Plugins → Add New → Upload Plugin and upload the plugin ZIP.
2. Activate the plugin.
3. Add the `[chefsolver_embed]` shortcode to any post or page.

== Frequently Asked Questions ==

= Is this plugin on WordPress.org? =

Not yet. This is a development scaffold distributed as a ZIP. A WordPress.org listing may follow after review and approval.

= What does the shortcode output today? =

A safe placeholder ("ChefSolver embed placeholder"). Real embed rendering arrives in the next iteration.

== Changelog ==

= 0.1.0 =
* Initial scaffold: plugin header, minimal `ChefSolver_Embeds_Plugin` class, `[chefsolver_embed]` placeholder shortcode, uninstall stub, docs and release model.
