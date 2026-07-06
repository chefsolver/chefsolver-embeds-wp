<?php
/**
 * Plugin Name:       ChefSolver Embeds
 * Plugin URI:        https://chefsolver.com/en/wordpress-plugin/
 * Description:       Embed ChefSolver converter widgets via shortcode or Gutenberg block. Scaffold release — manifest-driven embed rendering lands in the next iteration.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Tested up to:      6.7
 * Requires PHP:      7.2
 * Author:            ChefSolver
 * Author URI:        https://chefsolver.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       chefsolver-embeds
 *
 * @package ChefSolver_Embeds
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CHEFSOLVER_EMBEDS_VERSION', '0.1.0' );
define( 'CHEFSOLVER_EMBEDS_DIR', plugin_dir_path( __FILE__ ) );
define( 'CHEFSOLVER_EMBEDS_URL', plugin_dir_url( __FILE__ ) );

require_once CHEFSOLVER_EMBEDS_DIR . 'includes/class-chefsolver-embeds-plugin.php';

/**
 * Boot the plugin on plugins_loaded.
 *
 * @return ChefSolver_Embeds_Plugin
 */
function chefsolver_embeds_boot() {
	return ChefSolver_Embeds_Plugin::instance();
}
add_action( 'plugins_loaded', 'chefsolver_embeds_boot' );
