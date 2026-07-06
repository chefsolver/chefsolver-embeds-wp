<?php
/**
 * Minimal plugin class for the ChefSolver Embeds scaffold.
 *
 * @package ChefSolver_Embeds
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton plugin controller (scaffold).
 *
 * Registers the [chefsolver_embed] shortcode with a safe placeholder output.
 * The manifest-driven iframe renderer, Gutenberg block and settings page land
 * in the next iteration (see docs/release-model.md).
 */
class ChefSolver_Embeds_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var ChefSolver_Embeds_Plugin|null
	 */
	protected static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return ChefSolver_Embeds_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire up hooks.
	 */
	protected function __construct() {
		add_shortcode( 'chefsolver_embed', array( $this, 'shortcode_embed' ) );
	}

	/**
	 * [chefsolver_embed] — safe placeholder output for the scaffold.
	 *
	 * @return string
	 */
	public function shortcode_embed() {
		return '<div class="chefsolver-embed chefsolver-embed--placeholder">'
			. esc_html__( 'ChefSolver embed placeholder', 'chefsolver-embeds' )
			. '</div>';
	}
}
