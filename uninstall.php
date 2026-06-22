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

DB::drop_table();
delete_option( 'outpulse_logging_enabled' );
delete_option( 'outpulse_db_version' );
