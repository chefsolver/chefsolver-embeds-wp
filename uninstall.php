<?php
/**
 * Uninstall cleanup for ChefSolver Embeds.
 *
 * The scaffold stores no options or transients yet; this file exists so the
 * uninstall lifecycle is wired from day one. Future versions will remove the
 * plugin's own options/transients here — and nothing else.
 *
 * @package ChefSolver_Embeds
 */

// Only run from the WordPress uninstall lifecycle.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Reserved keys for future versions; deleting them is a no-op today.
delete_option( 'chefsolver_embeds_options' );
delete_transient( 'chefsolver_embeds_manifest' );
