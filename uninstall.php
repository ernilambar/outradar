<?php
/**
 * Runs when the plugin is deleted via the WordPress admin.
 *
 * @package Nilambar\Outwatch
 */

use Nilambar\Outwatch\Core\DB;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! defined( 'OUTWATCH_TABLE' ) ) {
	define( 'OUTWATCH_TABLE', 'outwatch_log' );
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

DB::drop_table();
delete_option( 'outwatch_logging_enabled' );
delete_option( 'outwatch_db_version' );
