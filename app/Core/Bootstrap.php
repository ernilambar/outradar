<?php
/**
 * Bootstrap.
 *
 * @package Nilambar\OutRadar
 */

namespace Nilambar\OutRadar\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Nilambar\OutRadar\Admin\Admin;
use Nilambar\OutRadar\HTTP\Interceptor;
use Nilambar\OutRadar\Services\DB;

/**
 * Plugin bootstrap class.
 *
 * @since 1.0.0
 */
class Bootstrap {

	/**
	 * Wire up plugin hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'plugins_loaded', array( $this, 'boot' ) );
		add_action( 'outradar_daily_purge', array( $this, 'run_purge' ) );
	}

	/**
	 * Start the plugin once all plugins are loaded.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( '1' === get_option( 'outradar_logging_enabled', '1' ) ) {
			Interceptor::init();
		}

		( new Admin() )->run();
	}

	/**
	 * Runs once on plugin activation: schedule the daily purge cron.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function activate(): void {
		if ( ! wp_next_scheduled( 'outradar_daily_purge' ) ) {
			wp_schedule_event( time(), 'daily', 'outradar_daily_purge' );
		}
	}

	/**
	 * Runs once on plugin deactivation: clear the daily purge cron.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'outradar_daily_purge' );
	}

	/**
	 * Cron handler: purge logs older than the configured retention period.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function run_purge(): void {
		$days = (int) get_option( 'outradar_retention_days', 30 );

		if ( $days > 0 ) {
			DB::purge_old_logs( $days );
		}
	}
}
