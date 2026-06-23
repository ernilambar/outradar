<?php
/**
 * Tracer.
 *
 * @package Nilambar\Outpulse
 */

namespace Nilambar\Outpulse\Services;

/**
 * Attributes an HTTP request to its source plugin, theme, or WordPress core via stack trace.
 *
 * @since 1.0.0
 */
class Tracer {

	/**
	 * Walk the call stack and return the first non-plugin, non-core frame.
	 *
	 * @since 1.0.0
	 *
	 * @return array{source_plugin: string, source_file: string, source_line: int}
	 */
	public static function get_source(): array {
		$frames = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions

		$plugins_dir    = wp_normalize_path( WP_PLUGIN_DIR );
		$mu_plugins_dir = wp_normalize_path( WPMU_PLUGIN_DIR );
		$themes_dir     = wp_normalize_path( get_theme_root() );
		$wp_includes    = wp_normalize_path( ABSPATH . 'wp-includes/' );
		$outpulse_dir   = wp_normalize_path( OUTPULSE_DIR );

		$first_core_frame = null;

		foreach ( $frames as $frame ) {
			if ( empty( $frame['file'] ) ) {
				continue;
			}

			$file = wp_normalize_path( $frame['file'] );

			if ( str_starts_with( $file, $outpulse_dir ) ) {
				continue;
			}

			$plugin = self::resolve_plugin( $file, $plugins_dir, $mu_plugins_dir );
			if ( '' !== $plugin ) {
				return array(
					'source_plugin' => $plugin,
					'source_file'   => $file,
					'source_line'   => $frame['line'] ?? 0,
				);
			}

			$theme = self::resolve_theme( $file, $themes_dir );
			if ( '' !== $theme ) {
				return array(
					'source_plugin' => '[Theme] ' . $theme,
					'source_file'   => $file,
					'source_line'   => $frame['line'] ?? 0,
				);
			}

			if ( null === $first_core_frame && str_starts_with( $file, $wp_includes ) ) {
				$first_core_frame = $frame;
			}
		}

		if ( null !== $first_core_frame ) {
			return array(
				'source_plugin' => '[WordPress Core]',
				'source_file'   => wp_normalize_path( $first_core_frame['file'] ),
				'source_line'   => $first_core_frame['line'] ?? 0,
			);
		}

		return array(
			'source_plugin' => '[Unknown]',
			'source_file'   => '',
			'source_line'   => 0,
		);
	}

	/**
	 * Resolve a file path to a plugin folder name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file        Normalized absolute file path.
	 * @param string $plugins_dir Normalized plugins directory.
	 * @param string $mu_dir      Normalized mu-plugins directory.
	 * @return string Plugin folder name, or empty string if not a plugin file.
	 */
	private static function resolve_plugin( string $file, string $plugins_dir, string $mu_dir ): string {
		foreach ( array( $plugins_dir, $mu_dir ) as $dir ) {
			if ( str_starts_with( $file, $dir . '/' ) ) {
				$relative = substr( $file, strlen( $dir ) + 1 );
				return explode( '/', $relative )[0];
			}
		}

		return '';
	}

	/**
	 * Resolve a file path to a theme folder name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file       Normalized absolute file path.
	 * @param string $themes_dir Normalized themes directory.
	 * @return string Theme folder name, or empty string if not a theme file.
	 */
	private static function resolve_theme( string $file, string $themes_dir ): string {
		if ( str_starts_with( $file, $themes_dir . '/' ) ) {
			$relative = substr( $file, strlen( $themes_dir ) + 1 );
			return explode( '/', $relative )[0];
		}

		return '';
	}
}
