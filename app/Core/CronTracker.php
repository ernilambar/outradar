<?php
/**
 * CronTracker.
 *
 * @package Nilambar\Outpulse
 */

namespace Nilambar\Outpulse\Core;

/**
 * Identifies the scheduled event hook that triggered the current HTTP request.
 *
 * @since 1.1.0
 */
class CronTracker {

	// WordPress lifecycle hooks that run before/around cron event handlers.
	private const WP_LIFECYCLE_HOOKS = array(
		'muplugins_loaded',
		'plugins_loaded',
		'setup_theme',
		'after_setup_theme',
		'init',
		'wp_loaded',
		'parse_request',
		'send_headers',
		'parse_query',
		'pre_get_posts',
		'wp',
		'template_redirect',
		'get_header',
		'wp_head',
		'wp_footer',
		'get_footer',
		'shutdown',
		'pre_http_request',
		'http_request_args',
		'http_api_debug',
		'http_api_curl',
	);

	/**
	 * Return the scheduled event hook currently executing, or null when not in cron.
	 *
	 * Reads $wp_current_filter and returns the first entry that is not a known
	 * WordPress lifecycle hook — in a cron context that is the scheduled event
	 * hook that initiated the outbound request.
	 *
	 * @since 1.1.0
	 *
	 * @return string|null
	 */
	public static function get_current_hook(): ?string {
		if ( ! wp_doing_cron() ) {
			return null;
		}

		global $wp_current_filter;

		foreach ( $wp_current_filter as $hook ) {
			if ( ! in_array( $hook, self::WP_LIFECYCLE_HOOKS, true ) ) {
				return $hook;
			}
		}

		return null;
	}
}
