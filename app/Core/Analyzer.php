<?php
/**
 * Analyzer.
 *
 * @package Nilambar\Outwatch
 */

namespace Nilambar\Outwatch\Core;

/**
 * Scans POST request bodies for risk indicators.
 *
 * @since 1.1.0
 */
class Analyzer {

	private const WP_SENSITIVE_KEYWORDS = array(
		'wp_options',
		'user_email',
		'user_pass',
		'auth_key',
		'secure_auth_key',
		'secret_key',
		'admin_email',
		'blogname',
		'siteurl',
		'db_password',
	);

	// Minimum base64 groups to distinguish a real blob from coincidental matches (~75 decoded bytes).
	private const BASE64_MIN_GROUPS = 25;

	/**
	 * Return risk flag names detected in the request body.
	 *
	 * @since 1.1.0
	 *
	 * @param string $method HTTP method.
	 * @param string $body   Raw request body string.
	 * @return list<string>
	 */
	public static function analyze( string $method, string $body ): array {
		if ( strtoupper( $method ) !== 'POST' || '' === $body ) {
			return array();
		}

		$flags = array();

		if ( preg_match( '/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $body ) ) {
			$flags[] = 'email_address';
		}

		foreach ( self::WP_SENSITIVE_KEYWORDS as $keyword ) {
			if ( false !== stripos( $body, $keyword ) ) {
				$flags[] = 'wp_sensitive_data';
				break;
			}
		}

		$groups = self::BASE64_MIN_GROUPS;
		if ( preg_match( '/(?:[A-Za-z0-9+\/]{4}){' . $groups . ',}(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=|[A-Za-z0-9+\/]{4})/', $body ) ) {
			$flags[] = 'base64_blob';
		}

		return $flags;
	}
}
