<?php
/**
 * WP_Utils.
 *
 * @package Nilambar\OutRadar
 */

namespace Nilambar\OutRadar\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress-specific utility helpers.
 *
 * @since 1.0.0
 */
class WP_Utils {

	/**
	 * Return a human-readable "X ago" string for a given Unix timestamp.
	 *
	 * @since 1.0.0
	 *
	 * @param int $time Unix timestamp.
	 * @return string Translated string like "3 hours ago".
	 */
	public static function format_time_ago( int $time ): string {
		return sprintf(
			/* translators: %s: human-readable time difference, e.g. "2 minutes" */
			__( '%s ago', 'outradar' ),
			human_time_diff( $time )
		);
	}

	/**
	 * Format a byte count as a human-readable size string.
	 *
	 * Uses binary (1024-based) units. Decimal places default to 2.
	 *
	 * @since 1.0.0
	 *
	 * @param int $bytes    Number of bytes.
	 * @param int $decimals Decimal places for the formatted value.
	 * @return string Formatted string like "512 B", "1.50 KB", or "2.30 MB".
	 */
	public static function format_file_size( int $bytes, int $decimals = 2 ): string {
		if ( $bytes <= 0 ) {
			return '0 B';
		}

		$sizes    = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );
		$size_key = (int) floor( log( $bytes ) / log( 1024 ) );
		$size_key = min( $size_key, count( $sizes ) - 1 );

		if ( 0 === $size_key ) {
			return $bytes . ' B';
		}

		return number_format( $bytes / pow( 1024, $size_key ), $decimals ) . ' ' . $sizes[ $size_key ];
	}
}
