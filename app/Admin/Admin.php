<?php
/**
 * Admin.
 *
 * @package Nilambar\Outwatch
 */

namespace Nilambar\Outwatch\Admin;

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
		add_action( 'admin_post_outwatch_export', array( Exporter::class, 'handle' ) );
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
			__( 'OutWatch', 'outwatch' ),
			__( 'OutWatch', 'outwatch' ),
			'manage_options',
			'outwatch',
			array( Dashboard::class, 'render' ),
			'dashicons-shield',
			80
		);

		add_submenu_page( 'outwatch', __( 'Dashboard', 'outwatch' ), __( 'Dashboard', 'outwatch' ), 'manage_options', 'outwatch', array( Dashboard::class, 'render' ) );
		add_submenu_page( 'outwatch', __( 'Request Log', 'outwatch' ), __( 'Request Log', 'outwatch' ), 'manage_options', 'outwatch-log', array( LogPage::class, 'render' ) );
		add_submenu_page( 'outwatch', __( 'Domains', 'outwatch' ), __( 'Domains', 'outwatch' ), 'manage_options', 'outwatch-domains', array( DomainPage::class, 'render' ) );
		add_submenu_page( 'outwatch', __( 'Plugins', 'outwatch' ), __( 'Plugins', 'outwatch' ), 'manage_options', 'outwatch-plugins', array( PluginPage::class, 'render' ) );
		add_submenu_page( 'outwatch', __( 'Settings', 'outwatch' ), __( 'Settings', 'outwatch' ), 'manage_options', 'outwatch-settings', array( SettingsPage::class, 'render' ) );
	}

	/**
	 * Enqueue CSS and JS only on OutWatch admin pages.
	 *
	 * @since 1.2.0
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		$outwatch_hooks = array(
			'toplevel_page_outwatch',
			'outwatch_page_outwatch-log',
			'outwatch_page_outwatch-domains',
			'outwatch_page_outwatch-plugins',
			'outwatch_page_outwatch-settings',
		);

		if ( ! in_array( $hook, $outwatch_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'outwatch-admin',
			OUTWATCH_URL . 'assets/admin/css/outwatch-admin.css',
			array(),
			OUTWATCH_VERSION
		);

		wp_enqueue_script(
			'outwatch-admin',
			OUTWATCH_URL . 'assets/admin/js/outwatch-admin.js',
			array(),
			OUTWATCH_VERSION,
			true
		);

		wp_localize_script(
			'outwatch-admin',
			'outwatchData',
			array(
				'confirmPurge'  => __( 'Delete all log entries? This cannot be undone.', 'outwatch' ),
				'confirmDelete' => __( 'Delete the selected log entries?', 'outwatch' ),
				'chartData'     => Dashboard::get_chart_data(),
				'nonce'         => wp_create_nonce( 'outwatch_admin' ),
			)
		);
	}
}
