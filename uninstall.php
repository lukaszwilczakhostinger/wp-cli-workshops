<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Custom tables are left in place so reports and logs survive accidental
 * deactivation. Drop them manually if a full cleanup is required.
 *
 * @package Maintenance_Tools_WCUS26
 * @since 1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
