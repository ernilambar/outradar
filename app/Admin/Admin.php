<?php
/**
 * Admin.
 *
 * @package Nilambar\Outpulse
 */

namespace Nilambar\Outpulse\Admin;

/**
 * Registers admin menus, enqueues assets, and dispatches export requests.
 *
 * @since 1.2.0
 */
class Admin {

	/**
	 * Wire up admin hooks.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_outpulse_export', array( Exporter::class, 'handle' ) );
	}

	/**
	 * Register the top-level menu and sub-pages.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'OutPulse', 'outpulse' ),
			__( 'OutPulse', 'outpulse' ),
			'manage_options',
			'outpulse',
			array( Dashboard::class, 'render' ),
			'dashicons-shield',
			80
		);

		add_submenu_page( 'outpulse', __( 'Dashboard', 'outpulse' ), __( 'Dashboard', 'outpulse' ), 'manage_options', 'outpulse', array( Dashboard::class, 'render' ) );
		add_submenu_page( 'outpulse', __( 'Request Log', 'outpulse' ), __( 'Request Log', 'outpulse' ), 'manage_options', 'outpulse-log', array( LogPage::class, 'render' ) );
		add_submenu_page( 'outpulse', __( 'Domains', 'outpulse' ), __( 'Domains', 'outpulse' ), 'manage_options', 'outpulse-domains', array( DomainPage::class, 'render' ) );
		add_submenu_page( 'outpulse', __( 'Sources', 'outpulse' ), __( 'Sources', 'outpulse' ), 'manage_options', 'outpulse-sources', array( PluginPage::class, 'render' ) );
		add_submenu_page( 'outpulse', __( 'Settings', 'outpulse' ), __( 'Settings', 'outpulse' ), 'manage_options', 'outpulse-settings', array( SettingsPage::class, 'render' ) );
	}

	/**
	 * Enqueue CSS and JS only on OutPulse admin pages.
	 *
	 * @since 1.2.0
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		$outpulse_hooks = array(
			'toplevel_page_outpulse',
			'outpulse_page_outpulse-log',
			'outpulse_page_outpulse-domains',
			'outpulse_page_outpulse-sources',
			'outpulse_page_outpulse-settings',
		);

		if ( ! in_array( $hook, $outpulse_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'outpulse-admin',
			OUTPULSE_URL . 'assets/admin/css/outpulse-admin.css',
			array(),
			OUTPULSE_VERSION
		);

		wp_enqueue_script(
			'outpulse-admin',
			OUTPULSE_URL . 'assets/admin/js/outpulse-admin.js',
			array(),
			OUTPULSE_VERSION,
			true
		);

		wp_localize_script(
			'outpulse-admin',
			'outpulseData',
			array(
				'confirmPurge'  => __( 'Delete all log entries? This cannot be undone.', 'outpulse' ),
				'confirmDelete' => __( 'Delete the selected log entries?', 'outpulse' ),
				'chartData'     => Dashboard::get_chart_data(),
				'nonce'         => wp_create_nonce( 'outpulse_admin' ),
			)
		);
	}
}
