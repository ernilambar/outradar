<?php
/**
 * Logger.
 *
 * @package Nilambar\Outwatch
 */

namespace Nilambar\Outwatch\Core;

/**
 * Assembles a log entry and persists it to the database.
 *
 * @since 1.0.0
 */
class Logger {

	/**
	 * Build and write a log row from raw request/response data.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $request Captured request and response data.
	 * @return void
	 */
	public static function write( array $request ): void {
		$source = Tracer::get_source();

		// Skip if this plugin is excluded from logging.
		$excluded_raw = (string) get_option( 'outwatch_excluded_plugins', '' );
		if ( '' !== $excluded_raw && '' !== $source['source_plugin'] ) {
			$excluded = array_filter( array_map( 'trim', explode( "\n", $excluded_raw ) ) );
			if ( in_array( $source['source_plugin'], $excluded, true ) ) {
				return;
			}
		}

		$url    = isset( $request['url'] ) ? (string) $request['url'] : '';
		$domain = self::extract_domain( $url );
		$method = isset( $request['method'] ) ? (string) $request['method'] : 'GET';

		$request_body = $request['request_body'] ?? '';
		if ( ! is_string( $request_body ) ) {
			$encoded      = wp_json_encode( $request_body );
			$request_body = is_string( $encoded ) ? $encoded : '';
		}

		$body_hash = md5( $url . strtoupper( $method ) . $request_body );

		$risk_flags   = Analyzer::analyze( $method, $request_body );
		$cron_hook    = CronTracker::get_current_hook();
		$recurrence   = DB::check_recurring( $domain, $source['source_plugin'] );
		$duplicate_of = DB::find_duplicate( $body_hash );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$page_url = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) : '';

		DB::insert(
			array(
				'timestamp'        => current_time( 'mysql' ),
				'url'              => $url,
				'domain'           => $domain,
				'method'           => $method,
				'request_headers'  => wp_json_encode( self::redact_headers( $request['request_headers'] ?? array() ) ),
				'request_body'     => $request_body,
				'response_code'    => $request['response_code'] ?? null,
				'response_size'    => $request['response_size'] ?? null,
				'source_plugin'    => $source['source_plugin'],
				'source_file'      => $source['source_file'],
				'source_line'      => $source['source_line'],
				'page_url'         => $page_url,
				'context'          => self::detect_context(),
				'risk_flags'       => ! empty( $risk_flags ) ? wp_json_encode( $risk_flags ) : null,
				'is_recurring'     => $recurrence['is_recurring'],
				'recurrence_count' => $recurrence['count'],
				'cron_hook'        => $cron_hook,
				'duplicate_of'     => $duplicate_of,
				'body_hash'        => $body_hash,
			)
		);

		if ( ! empty( $risk_flags ) ) {
			self::maybe_send_risk_notification( $url, $source['source_plugin'], $risk_flags );
		}
	}

	/**
	 * Send a notification email when risk flags are detected, if an email is configured.
	 *
	 * @since 1.2.0
	 *
	 * @param string               $url           Request URL.
	 * @param string               $source_plugin Attributed plugin.
	 * @param array<string, mixed> $risk_flags    Detected flags.
	 * @return void
	 */
	private static function maybe_send_risk_notification( string $url, string $source_plugin, array $risk_flags ): void {
		$email = (string) get_option( 'outwatch_notification_email', '' );

		if ( '' === $email || ! is_email( $email ) ) {
			return;
		}

		$flags   = implode( ', ', array_keys( $risk_flags ) );
		$subject = sprintf( '[OutWatch] Risk flags detected: %s', $flags );
		$body    = sprintf(
			"OutWatch detected risk flags in an outbound request.\n\nPlugin: %s\nURL: %s\nFlags: %s\n",
			$source_plugin,
			$url,
			$flags
		);

		wp_mail( $email, $subject, $body );
	}

	/**
	 * Replace values of sensitive headers with a redaction marker.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $headers Raw headers (array or string).
	 * @return mixed Redacted copy.
	 */
	private static function redact_headers( mixed $headers ): mixed {
		if ( ! is_array( $headers ) ) {
			return $headers;
		}

		$sensitive = array( 'authorization', 'cookie', 'x-auth-token', 'x-api-key', 'x-wp-nonce' );

		$redacted = array();
		foreach ( $headers as $name => $value ) {
			if ( in_array( strtolower( (string) $name ), $sensitive, true ) ) {
				$redacted[ $name ] = '[redacted]';
			} else {
				$redacted[ $name ] = $value;
			}
		}

		return $redacted;
	}

	/**
	 * Extract the hostname from a URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Full URL.
	 * @return string Lowercase hostname, or empty string on failure.
	 */
	private static function extract_domain( string $url ): string {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		return $host ? strtolower( (string) $host ) : '';
	}

	/**
	 * Detect whether the current request is cli, cron, admin, or frontend.
	 *
	 * @since 1.0.0
	 *
	 * @return string One of: cli, cron, admin, frontend.
	 */
	private static function detect_context(): string {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return 'cli';
		}

		if ( wp_doing_cron() ) {
			return 'cron';
		}

		if ( is_admin() ) {
			return 'admin';
		}

		return 'frontend';
	}
}
