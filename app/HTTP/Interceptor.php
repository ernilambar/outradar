<?php
/**
 * Interceptor.
 *
 * @package Nilambar\OutRadar
 */

namespace Nilambar\OutRadar\HTTP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Nilambar\OutRadar\Services\Logger;
use WP_Error;

/**
 * Hooks into WP_HTTP to capture every outbound request.
 *
 * @since 1.0.0
 */
class Interceptor {

	/**
	 * Request headers, body, and start time stashed before the request fires, keyed by fingerprint.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, array{headers: mixed, body: mixed, start: float}>
	 */
	private static array $pending = array();

	/**
	 * Register WP_HTTP hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'http_request_args', array( self::class, 'capture_args' ), PHP_INT_MAX, 2 );
		add_action( 'http_api_debug', array( self::class, 'on_response' ), 10, 5 );
	}

	/**
	 * Stash headers and body before the request is dispatched.
	 *
	 * Runs via the `http_request_args` filter; method is always set at this point.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Parsed request args.
	 * @param string               $url  Request URL.
	 * @return array<string, mixed> Unchanged args.
	 */
	public static function capture_args( array $args, string $url ): array {
		$key = self::fingerprint( $url, (string) ( $args['method'] ?? 'GET' ) );

		self::$pending[ $key ] = array(
			'headers' => $args['headers'] ?? array(),
			'body'    => $args['body'] ?? '',
			'start'   => microtime( true ),
		);

		return $args;
	}

	/**
	 * Log the completed request.
	 *
	 * Runs via the `http_api_debug` action. Skips redirect intermediates.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>|WP_Error $response    HTTP response or WP_Error.
	 * @param string                        $context     'response' for completed requests, 'redirect' for intermediates.
	 * @param string                        $transport   HTTP transport class name (unused).
	 * @param array<string, mixed>          $parsed_args Full request args.
	 * @param string                        $url         Request URL.
	 * @return void
	 */
	public static function on_response( $response, string $context, string $transport, array $parsed_args, string $url ): void {
		if ( 'response' !== $context ) {
			return;
		}

		$method = strtoupper( (string) ( $parsed_args['method'] ?? 'GET' ) );
		$key    = self::fingerprint( $url, $method );

		$pending = self::$pending[ $key ] ?? array();
		unset( self::$pending[ $key ] );

		$duration      = isset( $pending['start'] ) ? (int) round( ( microtime( true ) - $pending['start'] ) * 1000 ) : null;
		$response_code = null;
		$response_size = null;

		if ( ! is_wp_error( $response ) ) {
			$response_code = (int) wp_remote_retrieve_response_code( $response );
			$response_size = strlen( wp_remote_retrieve_body( $response ) );
		}

		Logger::write(
			array(
				'url'             => $url,
				'method'          => $method,
				'request_headers' => $pending['headers'] ?? array(),
				'request_body'    => $pending['body'] ?? '',
				'response_code'   => $response_code,
				'response_size'   => $response_size,
				'duration'        => $duration,
			)
		);
	}

	/**
	 * Generate a deduplication key for a URL + method pair.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url    Request URL.
	 * @param string $method HTTP method.
	 * @return string MD5 hash.
	 */
	private static function fingerprint( string $url, string $method ): string {
		return md5( $url . $method );
	}
}
