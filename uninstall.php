<?php
/**
 * Runs when the plugin is deleted via the WordPress admin.
 *
 * @package Nilambar\OutRadar
 */

use Nilambar\OutRadar\Core\DB;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! defined( 'OUTRADAR_TABLE' ) ) {
	define( 'OUTRADAR_TABLE', 'outradar_log' );
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

if ( '1' === get_option( 'outradar_delete_data_on_uninstall' ) ) {
	DB::drop_table();
	delete_option( 'outradar_logging_enabled' );
	delete_option( 'outradar_retention_days' );
	delete_option( 'outradar_excluded_plugins' );
	delete_option( 'outradar_delete_data_on_uninstall' );
}
