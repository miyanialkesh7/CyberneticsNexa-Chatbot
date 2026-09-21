<?php
/**
 * Runs when the plugin is deleted from the WordPress dashboard.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'cnx_settings' );
