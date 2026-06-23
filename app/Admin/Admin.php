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
 * @since 1.0.0
 */
class Admin {

	/**
	 * Hook suffixes for OutPulse submenu pages.
	 *
	 * @var string[]
	 */
	private array $hooks = array();

	/**
	 * Wire up admin hooks.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			_x( 'OutPulse', 'page title', 'outpulse' ),
			_x( 'OutPulse', 'menu title', 'outpulse' ),
			'manage_options',
			'outpulse',
			array( LogPage::class, 'render' ),
			'dashicons-shield',
			80
		);

		add_submenu_page( 'outpulse', __( 'Request Log', 'outpulse' ), __( 'Request Log', 'outpulse' ), 'manage_options', 'outpulse', array( LogPage::class, 'render' ) );
		$this->hooks[] = (string) add_submenu_page( 'outpulse', __( 'Dashboard', 'outpulse' ), __( 'Dashboard', 'outpulse' ), 'manage_options', 'outpulse-dashboard', array( Dashboard::class, 'render' ) );
		$this->hooks[] = (string) add_submenu_page( 'outpulse', __( 'Settings', 'outpulse' ), __( 'Settings', 'outpulse' ), 'manage_options', 'outpulse-settings', array( SettingsPage::class, 'render' ) );
	}

	/**
	 * Enqueue CSS and JS only on OutPulse admin pages.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_outpulse' !== $hook && ! in_array( $hook, $this->hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'outpulse-admin',
			OUTPULSE_URL . 'build/main.css',
			array(),
			OUTPULSE_VERSION
		);

		wp_enqueue_script(
			'outpulse-admin',
			OUTPULSE_URL . 'build/main.js',
			array(),
			OUTPULSE_VERSION,
			true
		);

		wp_localize_script(
			'outpulse-admin',
			'outpulseData',
			array(
				'confirmPurge'  => __( 'Delete all logs? This cannot be undone.', 'outpulse' ),
				'confirmDelete' => __( 'Delete selected entries?', 'outpulse' ),
				'chartData7'    => Dashboard::get_chart_data( 7 ),
				'chartData30'   => Dashboard::get_chart_data( 30 ),
				'nonce'         => wp_create_nonce( 'outpulse_admin' ),
			)
		);
	}
}
