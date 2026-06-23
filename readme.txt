=== OutRadar ===
Contributors: nilambar
Tags: http, logging, requests, monitoring, debug
Requires at least: 6.9
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Log outbound HTTP requests from WordPress plugins, themes, and core.

== Description ==

OutRadar hooks into WordPress's HTTP layer and records every outbound request — URL, method, status code, response size, originating plugin or theme, and execution context (frontend, admin, cron, CLI).

**Features**

* Logs all outbound HTTP requests via `WP_HTTP`
* Attributes each request to its source plugin or theme
* Filterable, paginated request log in the admin
* Dashboard with request counts, domain breakdown, and context chart
* CSV and JSON export
* Configurable log retention (7 days, 30 days, 90 days, or forever)
* Source exclusion list to suppress noisy plugins

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/outradar`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **OutRadar** in the admin menu to view the request log.
4. Configure retention and exclusions under **OutRadar → Settings**.

== Changelog ==

= 1.0.0 =
* Initial release.
