<?php
/**
 * Uninstall cleanup for ChefSolver Embeds.
 *
 * Removes only this plugin's option and transient. Nothing else is touched.
 *
 * @package ChefSolver_Embeds
 */

// Only run from the WordPress uninstall lifecycle.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Keep in sync with the plugin's constants (not loaded during uninstall).
$chefsolver_embeds_option    = 'chefsolver_embeds_options';
$chefsolver_embeds_transient = 'chefsolver_embeds_manifest';

delete_option( $chefsolver_embeds_option );
delete_transient( $chefsolver_embeds_transient );

// Multisite: clean per-site option/transient as well.
if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( (array) $site_ids as $site_id ) {
		switch_to_blog( (int) $site_id );
		delete_option( $chefsolver_embeds_option );
		delete_transient( $chefsolver_embeds_transient );
		restore_current_blog();
	}
}
