<?php
/**
 * Exporter.
 *
 * @package Nilambar\Outpulse
 */

namespace Nilambar\Outpulse\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Nilambar\Outpulse\Core\DB;

/**
 * Handles CSV and JSON export of log data via admin-post.php.
 *
 * @since 1.0.0
 */
class Exporter {

	/**
	 * Handle the outpulse_export admin-post action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'outpulse' ) );
		}

		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'outpulse_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'outpulse' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$filters = array(
			'domain'    => isset( $_REQUEST['domain'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['domain'] ) ) : '',
			'plugin'    => isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '',
			'method'    => isset( $_REQUEST['method'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['method'] ) ) : '',
			'context'   => isset( $_REQUEST['context'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['context'] ) ) : '',
			'date_from' => isset( $_REQUEST['date_from'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['date_from'] ) ) : '',
			'date_to'   => isset( $_REQUEST['date_to'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['date_to'] ) ) : '',
		);
		$format  = isset( $_REQUEST['format'] ) && 'json' === $_REQUEST['format'] ? 'json' : 'csv';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$rows = DB::get_log_rows( $filters, 1, 100000 );

		if ( 'json' === $format ) {
			self::output_json( $rows );
		} else {
			self::output_csv( $rows );
		}
	}

	/**
	 * Stream rows as a CSV file download.
	 *
	 * @since 1.0.0
	 *
	 * @param object[] $rows Log rows.
	 * @return void
	 */
	private static function output_csv( array $rows ): void {
		$filename = 'outpulse-export-' . gmdate( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$columns = array(
			'id',
			'timestamp',
			'url',
			'domain',
			'method',
			'response_code',
			'response_size',
			'source_plugin',
			'source_file',
			'source_line',
			'context',
			'page_url',
			'is_recurring',
			'recurrence_count',
			'cron_hook',
			'duplicate_of',
		);

		$out = fopen( 'php://output', 'w' );

		if ( false === $out ) {
			exit;
		}

		fputcsv( $out, $columns, ',', '"', '\\' );

		foreach ( $rows as $row ) {
			$line = array();
			foreach ( $columns as $col ) {
				$line[] = $row->$col ?? '';
			}
			fputcsv( $out, $line, ',', '"', '\\' );
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Stream rows as a JSON file download.
	 *
	 * @since 1.0.0
	 *
	 * @param object[] $rows Log rows.
	 * @return void
	 */
	private static function output_json( array $rows ): void {
		$filename = 'outpulse-export-' . gmdate( 'Y-m-d' ) . '.json';

		header( 'Content-Type: application/json; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$encoded = wp_json_encode( $rows, JSON_PRETTY_PRINT );
		echo $encoded ? $encoded : '[]'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}
