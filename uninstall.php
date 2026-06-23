<?php
/**
 * Runs when the plugin is deleted via the WordPress admin.
 *
 * @package Nilambar\Outpulse
 */

use Nilambar\Outpulse\Core\DB;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! defined( 'OUTPULSE_TABLE' ) ) {
	define( 'OUTPULSE_TABLE', 'outpulse_log' );
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

if ( '1' === get_option( 'outpulse_delete_data_on_uninstall' ) ) {
	DB::drop_table();
	delete_option( 'outpulse_logging_enabled' );
	delete_option( 'outpulse_retention_days' );
	delete_option( 'outpulse_excluded_plugins' );
	delete_option( 'outpulse_delete_data_on_uninstall' );
}
