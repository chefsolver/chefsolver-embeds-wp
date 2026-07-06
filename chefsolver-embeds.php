<?php
/**
 * Plugin Name:       ChefSolver Embeds
 * Plugin URI:        https://chefsolver.com/en/embed/
 * Description:        Embed ChefSolver converter widgets (generic and ingredient-specific) via a shortcode or Gutenberg block. Manifest-driven and type-agnostic — it works with any embed listed in the public ChefSolver manifest.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Tested up to:      6.7
 * Requires PHP:      7.2
 * Author:            ChefSolver
 * Author URI:        https://chefsolver.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       chefsolver-embeds
 * Domain Path:       /languages
 *
 * @package ChefSolver_Embeds
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Prefixed constants ───────────────────────────────────────────────────────
define( 'CHEFSOLVER_EMBEDS_VERSION', '1.0.0' );
define( 'CHEFSOLVER_EMBEDS_FILE', __FILE__ );
define( 'CHEFSOLVER_EMBEDS_DIR', plugin_dir_path( __FILE__ ) );
define( 'CHEFSOLVER_EMBEDS_URL', plugin_dir_url( __FILE__ ) );

/** Public manifest that drives every embed the plugin can render. */
define( 'CHEFSOLVER_EMBEDS_MANIFEST_URL', 'https://chefsolver.com/embed/manifest.json' );

/** Option + transient keys. */
define( 'CHEFSOLVER_EMBEDS_OPTION', 'chefsolver_embeds_options' );
define( 'CHEFSOLVER_EMBEDS_TRANSIENT', 'chefsolver_embeds_manifest' );

/** Defaults. */
define( 'CHEFSOLVER_EMBEDS_DEFAULT_TTL', 12 * HOUR_IN_SECONDS );
define( 'CHEFSOLVER_EMBEDS_DEFAULT_HEIGHT', 360 );
define( 'CHEFSOLVER_EMBEDS_MIN_HEIGHT', 120 );
define( 'CHEFSOLVER_EMBEDS_MAX_HEIGHT', 2000 );
define( 'CHEFSOLVER_EMBEDS_MAX_RADIUS', 40 );

/** Hosts an embed iframe is allowed to point at. */
define( 'CHEFSOLVER_EMBEDS_ALLOWED_HOSTS', array( 'chefsolver.com', 'www.chefsolver.com' ) );

/** Allowlists for strict attribute sanitization. */
define( 'CHEFSOLVER_EMBEDS_LANGS', array( 'en', 'es', 'it', 'fr', 'de' ) );
define( 'CHEFSOLVER_EMBEDS_THEMES', array( 'auto', 'light', 'dark' ) );

/**
 * Per-type URL path templates, used ONLY as a safe fallback when the manifest
 * is unavailable and a manual entry is rendered. Discovery/validation always
 * prefers the manifest. `%lang%` and `%slug%` are substituted; for
 * ingredient-converter the slug is the compound `ingredient/conversion`.
 */
define(
	'CHEFSOLVER_EMBEDS_PATH_TEMPLATES',
	array(
		'converter'            => '/embed/%lang%/converters/%slug%/',
		'ingredient-converter' => '/embed/%lang%/convert/%slug%/',
		'calculator'           => '/embed/%lang%/tools/%slug%/',
	)
);

// ── Bootstrap ──────────────────────────────────────────────────────────────
require_once CHEFSOLVER_EMBEDS_DIR . 'includes/class-chefsolver-embeds-manifest.php';
require_once CHEFSOLVER_EMBEDS_DIR . 'includes/class-chefsolver-embeds-plugin.php';

/**
 * Boot the singleton plugin instance.
 *
 * @return ChefSolver_Embeds_Plugin
 */
function chefsolver_embeds_boot() {
	return ChefSolver_Embeds_Plugin::instance();
}
add_action( 'plugins_loaded', 'chefsolver_embeds_boot' );
