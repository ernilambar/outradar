<?php
/**
 * Admin.
 *
 * @package Nilambar\OutRadar
 */

namespace Nilambar\OutRadar\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Nilambar\OutRadar\Admin\Pages\Dashboard_Page;
use Nilambar\OutRadar\Admin\Pages\Log_Page;
use Nilambar\OutRadar\Admin\Pages\Settings_Page;

/**
 * Registers admin menus, enqueues assets, and dispatches export requests.
 *
 * @since 1.0.0
 */
class Admin {

	/**
	 * Hook suffixes for registered submenu pages.
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
		add_action( 'admin_post_outradar_export', array( Exporter::class, 'handle' ) );
		add_action( 'wp_ajax_outradar_get_row', array( Ajax::class, 'get_row' ) );
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
			_x( 'OutRadar', 'page title', 'outradar' ),
			_x( 'OutRadar', 'menu title', 'outradar' ),
			'manage_options',
			'outradar',
			array( Log_Page::class, 'render' ),
			'dashicons-shield'
		);

		add_submenu_page( 'outradar', __( 'Log', 'outradar' ), __( 'Log', 'outradar' ), 'manage_options', 'outradar', array( Log_Page::class, 'render' ) );
		$this->hooks[] = (string) add_submenu_page( 'outradar', __( 'Dashboard', 'outradar' ), __( 'Dashboard', 'outradar' ), 'manage_options', 'outradar-dashboard', array( Dashboard_Page::class, 'render' ) );
		$this->hooks[] = (string) add_submenu_page( 'outradar', __( 'Settings', 'outradar' ), __( 'Settings', 'outradar' ), 'manage_options', 'outradar-settings', array( Settings_Page::class, 'render' ) );
	}

	/**
	 * Enqueue CSS and JS only on plugin admin pages.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_outradar' !== $hook && ! in_array( $hook, $this->hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'outradar-admin',
			OUTRADAR_URL . 'build/main.css',
			array(),
			OUTRADAR_VERSION
		);

		wp_enqueue_script(
			'outradar-admin',
			OUTRADAR_URL . 'build/main.js',
			array(),
			OUTRADAR_VERSION,
			true
		);

		wp_localize_script(
			'outradar-admin',
			'outradarData',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'outradar_get_row' ),
				'confirmPurge'  => __( 'Permanently delete all logs. This cannot be undone.', 'outradar' ),
				'confirmDelete' => __( 'Delete selected entries?', 'outradar' ),
				'chartData7'    => Dashboard_Page::get_chart_data( 7 ),
				'chartData30'   => Dashboard_Page::get_chart_data( 30 ),
			)
		);
	}
}
