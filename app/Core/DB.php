<?php
/**
 * DB.
 *
 * @package Nilambar\Outwatch
 */

namespace Nilambar\Outwatch\Core;

use stdClass;

/**
 * Database table management and write operations.
 *
 * @since 1.0.0
 */
class DB {

	/**
	 * Create the log table on plugin activation.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function create_table(): void {
		global $wpdb;

		$table           = $wpdb->prefix . OUTWATCH_TABLE;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			timestamp DATETIME NOT NULL,
			url TEXT NOT NULL,
			domain VARCHAR(255) NOT NULL DEFAULT '',
			method VARCHAR(10) NOT NULL DEFAULT 'GET',
			request_headers LONGTEXT,
			request_body LONGTEXT,
			response_code INT,
			response_size INT,
			source_plugin VARCHAR(255),
			source_file TEXT,
			source_line INT,
			page_url TEXT,
			context VARCHAR(50) NOT NULL DEFAULT 'frontend',
			is_recurring TINYINT(1) NOT NULL DEFAULT 0,
			recurrence_count INT NOT NULL DEFAULT 0,
			cron_hook VARCHAR(255),
			duplicate_of BIGINT UNSIGNED,
			body_hash VARCHAR(32),
			reviewed TINYINT(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY domain (domain),
			KEY source_plugin (source_plugin(191)),
			KEY timestamp (timestamp),
			KEY body_hash (body_hash)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop the log table on plugin deletion.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function drop_table(): void {
		global $wpdb;

		$table = $wpdb->prefix . OUTWATCH_TABLE;
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Insert a log row.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Column => value pairs.
	 * @return void
	 */
	public static function insert( array $data ): void {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . OUTWATCH_TABLE, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Count recent rows matching the same domain + source_plugin pair.
	 *
	 * @since 1.1.0
	 *
	 * @param string $domain        Request domain.
	 * @param string $source_plugin Attributed plugin name.
	 * @param int    $window        Look-back window in minutes.
	 * @return array{is_recurring: int, count: int}
	 */
	public static function check_recurring( string $domain, string $source_plugin, int $window = 5 ): array {
		global $wpdb;

		$table = $wpdb->prefix . OUTWATCH_TABLE;

		$count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE domain = %s AND source_plugin = %s AND timestamp >= DATE_SUB(NOW(), INTERVAL %d MINUTE)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$domain,
				$source_plugin,
				$window
			)
		);

		return array(
			'is_recurring' => $count > 0 ? 1 : 0,
			'count'        => $count,
		);
	}

	/**
	 * Return the ID of an existing row with the same body_hash in the look-back window, or null.
	 *
	 * Only considers rows that are not themselves duplicates (duplicate_of IS NULL), so dedup
	 * chains always point to the original.
	 *
	 * @since 1.1.0
	 *
	 * @param string $body_hash MD5 of url + method + body.
	 * @param int    $window    Look-back window in minutes.
	 * @return int|null
	 */
	public static function find_duplicate( string $body_hash, int $window = 60 ): ?int {
		global $wpdb;

		$table = $wpdb->prefix . OUTWATCH_TABLE;

		$id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE body_hash = %s AND duplicate_of IS NULL AND timestamp >= DATE_SUB(NOW(), INTERVAL %d MINUTE) ORDER BY id ASC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$body_hash,
				$window
			)
		);

		return null !== $id ? (int) $id : null;
	}

	// -------------------------------------------------------------------------
	// Admin query methods (Phase 3)
	// -------------------------------------------------------------------------

	/**
	 * Return aggregate summary counts for the dashboard.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, int>
	 */
	public static function get_summary(): array {
		global $wpdb;

		$table = $wpdb->prefix . OUTWATCH_TABLE;

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT
				COUNT(*) AS total,
				SUM(CASE WHEN DATE(timestamp) = CURDATE() THEN 1 ELSE 0 END) AS today,
				SUM(CASE WHEN timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS week,
				COUNT(DISTINCT domain) AS unique_domains,
				SUM(CASE WHEN context = 'frontend' THEN 1 ELSE 0 END) AS ctx_frontend,
				SUM(CASE WHEN context = 'admin' THEN 1 ELSE 0 END) AS ctx_admin,
				SUM(CASE WHEN context = 'cron' THEN 1 ELSE 0 END) AS ctx_cron,
				SUM(CASE WHEN context = 'cli' THEN 1 ELSE 0 END) AS ctx_cli
			FROM {$table}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return array_fill_keys( array( 'total', 'today', 'week', 'unique_domains', 'ctx_frontend', 'ctx_admin', 'ctx_cron', 'ctx_cli' ), 0 );
		}

		return array_map( 'intval', $row );
	}

	/**
	 * Return the top plugins by request count.
	 *
	 * @since 1.0.0
	 *
	 * @param int $limit Max rows to return.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_top_plugins( int $limit = 5 ): array {
		global $wpdb;

		$table = $wpdb->prefix . OUTWATCH_TABLE;

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT source_plugin, COUNT(*) AS total FROM {$table} WHERE source_plugin IS NOT NULL AND source_plugin != '' GROUP BY source_plugin ORDER BY total DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$limit
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Return daily request counts for the last N days (filling gaps with 0).
	 *
	 * @since 1.0.0
	 *
	 * @param int $days Number of days to look back.
	 * @return array<string, int> Associative array keyed by date string (Y-m-d).
	 */
	public static function get_requests_by_day( int $days = 7 ): array {
		global $wpdb;

		$table = $wpdb->prefix . OUTWATCH_TABLE;

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT DATE(timestamp) AS day, COUNT(*) AS total FROM {$table} WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL %d DAY) GROUP BY DATE(timestamp) ORDER BY day ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$days
			),
			ARRAY_A
		);

		$result = array();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$date            = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
			$result[ $date ] = 0;
		}

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$result[ $row['day'] ] = (int) $row['total'];
			}
		}

		return $result;
	}

	/**
	 * Build a safe WHERE fragment from filter params (each clause already prepared).
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $filters Filter key-value pairs.
	 * @return string WHERE fragment (starts with ' AND' when non-empty).
	 */
	private static function build_where( array $filters ): string {
		global $wpdb;

		$where = '';

		if ( ! empty( $filters['domain'] ) ) {
			$where .= $wpdb->prepare( ' AND domain LIKE %s', '%' . $wpdb->esc_like( (string) $filters['domain'] ) . '%' );
		}

		if ( ! empty( $filters['plugin'] ) ) {
			$where .= $wpdb->prepare( ' AND source_plugin = %s', $filters['plugin'] );
		}

		if ( ! empty( $filters['method'] ) ) {
			$where .= $wpdb->prepare( ' AND method = %s', $filters['method'] );
		}

		if ( ! empty( $filters['context'] ) ) {
			$where .= $wpdb->prepare( ' AND context = %s', $filters['context'] );
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$where .= $wpdb->prepare( ' AND DATE(timestamp) >= %s', $filters['date_from'] );
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where .= $wpdb->prepare( ' AND DATE(timestamp) <= %s', $filters['date_to'] );
		}

		return $where;
	}

	/**
	 * Return a paginated set of log rows matching the given filters.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $filters   Filter key-value pairs.
	 * @param int                  $page      1-based page number.
	 * @param int                  $per_page  Rows per page.
	 * @return array<int, object>
	 */
	public static function get_log_rows( array $filters, int $page = 1, int $per_page = 50 ): array {
		global $wpdb;

		$table  = $wpdb->prefix . OUTWATCH_TABLE;
		$where  = self::build_where( $filters );
		$offset = ( max( 1, $page ) - 1 ) * $per_page;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( "SELECT * FROM {$table} WHERE 1=1{$where} ORDER BY timestamp DESC LIMIT {$per_page} OFFSET {$offset}" );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Count total log rows matching the given filters.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $filters Filter key-value pairs.
	 * @return int
	 */
	public static function count_log_rows( array $filters ): int {
		global $wpdb;

		$table = $wpdb->prefix . OUTWATCH_TABLE;
		$where = self::build_where( $filters );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE 1=1{$where}" );

		return (int) $count;
	}

	/**
	 * Return a summary of all domains: first/last seen, total requests, plugins, flagged count.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, object>
	 */
	public static function get_domain_summary(): array {
		global $wpdb;

		$table = $wpdb->prefix . OUTWATCH_TABLE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT
				domain,
				MIN(timestamp) AS first_seen,
				MAX(timestamp) AS last_seen,
				COUNT(*) AS total,
				GROUP_CONCAT(DISTINCT source_plugin ORDER BY source_plugin SEPARATOR ', ') AS plugins
			FROM {$table}
			WHERE domain != ''
			GROUP BY domain
			ORDER BY total DESC"
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Return a per-plugin summary: total requests, unique domains, flagged count.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, object>
	 */
	public static function get_plugin_summary(): array {
		global $wpdb;

		$table = $wpdb->prefix . OUTWATCH_TABLE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT
				source_plugin,
				COUNT(*) AS total,
				COUNT(DISTINCT domain) AS unique_domains
			FROM {$table}
			WHERE source_plugin IS NOT NULL AND source_plugin != ''
			GROUP BY source_plugin
			ORDER BY total DESC"
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Return distinct source_plugin values for the filter dropdown.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	public static function get_all_source_plugins(): array {
		global $wpdb;

		$table = $wpdb->prefix . OUTWATCH_TABLE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_col( "SELECT DISTINCT source_plugin FROM {$table} WHERE source_plugin IS NOT NULL AND source_plugin != '' ORDER BY source_plugin ASC" );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Fetch a single log row by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Row ID.
	 * @return object|null
	 */
	public static function get_row( int $id ): ?object {
		global $wpdb;

		$table = $wpdb->prefix . OUTWATCH_TABLE;

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return $row instanceof stdClass ? $row : null;
	}

	/**
	 * Delete log rows older than the given number of days.
	 *
	 * @since 1.0.0
	 *
	 * @param int $days Age threshold in days.
	 * @return int Number of rows deleted.
	 */
	public static function purge_old_logs( int $days ): int {
		global $wpdb;

		$table  = $wpdb->prefix . OUTWATCH_TABLE;
		$result = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "DELETE FROM {$table} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)", $days ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return is_int( $result ) ? $result : 0;
	}

	/**
	 * Delete all log rows.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of rows deleted.
	 */
	public static function purge_all_logs(): int {
		global $wpdb;

		$table  = $wpdb->prefix . OUTWATCH_TABLE;
		$result = $wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return is_int( $result ) ? $result : 0;
	}

	/**
	 * Delete log rows by a list of IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param int[] $ids Row IDs to delete.
	 * @return int Number of rows deleted.
	 */
	public static function delete_by_ids( array $ids ): int {
		if ( empty( $ids ) ) {
			return 0;
		}

		global $wpdb;

		$table        = $wpdb->prefix . OUTWATCH_TABLE;
		$ids          = array_map( 'intval', $ids );
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		$result = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "DELETE FROM {$table} WHERE id IN ({$placeholders})", ...$ids ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return is_int( $result ) ? $result : 0;
	}

	/**
	 * Mark log rows as reviewed by a list of IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param int[] $ids Row IDs to mark as reviewed.
	 * @return void
	 */
	public static function mark_reviewed_by_ids( array $ids ): void {
		if ( empty( $ids ) ) {
			return;
		}

		global $wpdb;

		$table        = $wpdb->prefix . OUTWATCH_TABLE;
		$ids          = array_map( 'intval', $ids );
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "UPDATE {$table} SET reviewed = 1 WHERE id IN ({$placeholders})", ...$ids ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}
}
