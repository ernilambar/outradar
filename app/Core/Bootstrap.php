<?php
/**
 * Bootstrap.
 *
 * @package Nilambar\Outpulse
 */

namespace Nilambar\Outpulse\Core;

use Nilambar\Outpulse\Admin\Admin;

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
		add_action( 'outpulse_daily_purge', array( $this, 'run_purge' ) );
	}

	/**
	 * Start the plugin once all plugins are loaded.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( '1' === get_option( 'outpulse_logging_enabled', '1' ) ) {
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
		if ( ! wp_next_scheduled( 'outpulse_daily_purge' ) ) {
			wp_schedule_event( time(), 'daily', 'outpulse_daily_purge' );
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
		wp_clear_scheduled_hook( 'outpulse_daily_purge' );
	}

	/**
	 * Cron handler: purge logs older than the configured retention period.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function run_purge(): void {
		$days = (int) get_option( 'outpulse_retention_days', 30 );

		if ( $days > 0 ) {
			DB::purge_old_logs( $days );
		}
	}
}
