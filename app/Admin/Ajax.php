<?php
/**
 * Ajax.
 *
 * @package Nilambar\OutRadar
 */

namespace Nilambar\OutRadar\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Nilambar\OutRadar\Services\DB;
use Nilambar\OutRadar\Utils\WP_Utils;

/**
 * Handles admin AJAX requests.
 *
 * @since 1.0.0
 */
class Ajax {

	/**
	 * Return a single log row as JSON for the detail modal.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function get_row(): void {
		check_ajax_referer( 'outradar_get_row', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( null, 400 );
		}

		$row = DB::get_row( $id );

		if ( ! $row ) {
			wp_send_json_error( null, 404 );
		}

		$row->time_ago = WP_Utils::format_time_ago( (int) strtotime( (string) $row->timestamp ) );

		wp_send_json_success( $row );
	}
}
