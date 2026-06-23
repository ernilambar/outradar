<?php
/**
 * LogPage.
 *
 * @package Nilambar\Outpulse
 */

namespace Nilambar\Outpulse\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Nilambar\Outpulse\Core\DB;

/**
 * Renders the paginated, filterable Request Log admin page.
 *
 * @since 1.0.0
 */
class LogPage {

	/**
	 * Render the log page, handling bulk actions before output.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'outpulse' ) );
		}

		self::handle_bulk_action();

		$filters = self::get_filters();
		$page    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$total   = DB::count_log_rows( $filters );
		$rows    = DB::get_log_rows( $filters, $page );
		$plugins = DB::get_all_source_plugins();

		$per_page    = 50;
		$total_pages = (int) ceil( $total / $per_page );
		?>
		<div class="wrap outpulse-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="get" class="outpulse-filter-form">
				<input type="hidden" name="page" value="outpulse" />

				<input
					type="text"
					name="domain"
					placeholder="<?php esc_attr_e( 'Domain', 'outpulse' ); ?>"
					value="<?php echo esc_attr( $filters['domain'] ?? '' ); ?>"
				/>

				<select name="plugin">
					<option value=""><?php esc_html_e( 'All sources', 'outpulse' ); ?></option>
					<?php foreach ( $plugins as $plugin ) : ?>
					<option value="<?php echo esc_attr( $plugin ); ?>" <?php selected( $filters['plugin'] ?? '', $plugin ); ?>>
						<?php echo esc_html( $plugin ); ?>
					</option>
					<?php endforeach; ?>
				</select>

				<select name="method">
					<option value=""><?php esc_html_e( 'All methods', 'outpulse' ); ?></option>
					<?php foreach ( array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD' ) as $method ) : ?>
					<option value="<?php echo esc_attr( $method ); ?>" <?php selected( $filters['method'] ?? '', $method ); ?>>
						<?php echo esc_html( $method ); ?>
					</option>
					<?php endforeach; ?>
				</select>

				<select name="context">
					<option value=""><?php esc_html_e( 'All contexts', 'outpulse' ); ?></option>
					<?php foreach ( array( 'frontend', 'admin', 'cron', 'cli' ) as $ctx ) : ?>
					<option value="<?php echo esc_attr( $ctx ); ?>" <?php selected( $filters['context'] ?? '', $ctx ); ?>>
						<?php echo esc_html( $ctx ); ?>
					</option>
					<?php endforeach; ?>
				</select>

				<input
					type="date"
					name="date_from"
					title="<?php esc_attr_e( 'From', 'outpulse' ); ?>"
					value="<?php echo esc_attr( $filters['date_from'] ?? '' ); ?>"
				/>

				<input
					type="date"
					name="date_to"
					title="<?php esc_attr_e( 'To', 'outpulse' ); ?>"
					value="<?php echo esc_attr( $filters['date_to'] ?? '' ); ?>"
				/>

				<button type="submit" class="button"><?php esc_html_e( 'Filter', 'outpulse' ); ?></button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=outpulse' ) ); ?>" class="button"><?php esc_html_e( 'Reset', 'outpulse' ); ?></a>
			</form>

			<form method="post" id="outpulse-log-form">
				<?php wp_nonce_field( 'outpulse_bulk', 'outpulse_nonce' ); ?>

				<div class="tablenav top">
					<div class="alignleft actions bulkactions">
						<select name="bulk_action">
							<option value=""><?php esc_html_e( 'Bulk actions', 'outpulse' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete', 'outpulse' ); ?></option>
						</select>
						<button type="submit" class="button action" id="outpulse-bulk-submit"><?php esc_html_e( 'Apply', 'outpulse' ); ?></button>
					</div>

					<div class="alignleft actions">
						<a href="<?php echo esc_url( self::export_url( $filters, 'csv' ) ); ?>" class="button"><?php esc_html_e( 'Export CSV', 'outpulse' ); ?></a>
						<a href="<?php echo esc_url( self::export_url( $filters, 'json' ) ); ?>" class="button"><?php esc_html_e( 'Export JSON', 'outpulse' ); ?></a>
					</div>

					<?php self::render_pagination( $page, $total_pages, $total, $filters ); ?>
				</div>

				<table class="wp-list-table widefat fixed striped outpulse-log-table">
					<thead>
						<tr>
							<td class="manage-column column-cb check-column"><input type="checkbox" id="outpulse-select-all" /></td>
							<th><?php esc_html_e( 'Timestamp', 'outpulse' ); ?></th>
							<th><?php esc_html_e( 'Domain', 'outpulse' ); ?></th>
							<th><?php esc_html_e( 'Method', 'outpulse' ); ?></th>
							<th><?php esc_html_e( 'Status', 'outpulse' ); ?></th>
							<th><?php esc_html_e( 'Source', 'outpulse' ); ?></th>
							<th><?php esc_html_e( 'Context', 'outpulse' ); ?></th>
							<th><?php esc_html_e( 'Flags', 'outpulse' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rows ) ) : ?>
						<tr>
							<td colspan="8"><?php esc_html_e( 'No requests found.', 'outpulse' ); ?></td>
						</tr>
						<?php else : ?>
							<?php foreach ( $rows as $row ) : ?>
						<tr class="outpulse-log-row">
							<th scope="row" class="check-column">
								<input type="checkbox" name="row_ids[]" value="<?php echo esc_attr( (string) $row->id ); ?>" class="outpulse-row-check" />
							</th>
							<td>
								<button type="button" class="outpulse-row-toggle button-link" data-id="<?php echo esc_attr( (string) $row->id ); ?>">
									<?php echo esc_html( (string) $row->timestamp ); ?>
								</button>
								<span class="outpulse-time-ago">
								<?php
								/* translators: %s: human-readable time difference, e.g. "2 minutes" */
								echo '(' . esc_html( sprintf( __( '%s ago', 'outpulse' ), human_time_diff( (int) strtotime( (string) $row->timestamp ) ) ) ) . ')';
								?>
								</span>
							</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=outpulse&domain=' . rawurlencode( (string) $row->domain ) ) ); ?>">
									<?php echo esc_html( (string) $row->domain ); ?>
								</a>
							</td>
							<td><code><?php echo esc_html( (string) $row->method ); ?></code></td>
							<td>
								<?php
								$code  = (int) $row->response_code;
								$class = $code >= 400 ? 'outpulse-status-error' : ( $code >= 300 ? 'outpulse-status-redirect' : 'outpulse-status-ok' );
								?>
								<span class="outpulse-status <?php echo esc_attr( $class ); ?>"><?php echo esc_html( (string) $row->response_code ); ?></span>
							</td>
							<td>
								<?php if ( ! empty( $row->source_plugin ) ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=outpulse&plugin=' . rawurlencode( (string) $row->source_plugin ) ) ); ?>">
									<?php echo esc_html( (string) $row->source_plugin ); ?>
								</a>
								<?php else : ?>
								&mdash;
								<?php endif; ?>
							</td>
							<td><span class="outpulse-context outpulse-context--<?php echo esc_attr( (string) $row->context ); ?>"><?php echo esc_html( (string) $row->context ); ?></span></td>
							<td>
								<?php if ( ! empty( $row->is_recurring ) ) : ?>
									<span class="outpulse-flag outpulse-flag--recurring" title="<?php esc_attr_e( 'Recurring request', 'outpulse' ); ?>">&#8635;</span>
								<?php endif; ?>
								<?php if ( ! empty( $row->duplicate_of ) ) : ?>
									<span class="outpulse-flag outpulse-flag--duplicate" title="<?php esc_attr_e( 'Duplicate request', 'outpulse' ); ?>">&#8645;</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr class="outpulse-detail-row" id="outpulse-detail-<?php echo esc_attr( (string) $row->id ); ?>" style="display:none;">
							<td colspan="8">
								<div class="outpulse-detail-inner">
									<p><strong><?php esc_html_e( 'URL', 'outpulse' ); ?>:</strong> <code><?php echo esc_html( (string) $row->url ); ?></code></p>
									<p>
										<strong><?php esc_html_e( 'Source', 'outpulse' ); ?>:</strong>
										<?php echo esc_html( (string) $row->source_file ); ?>
										<?php if ( ! empty( $row->source_line ) ) : ?>
											<?php echo esc_html( sprintf( ':%d', (int) $row->source_line ) ); ?>
										<?php endif; ?>
									</p>
									<?php if ( ! empty( $row->request_headers ) ) : ?>
									<details>
										<summary><?php esc_html_e( 'Request Headers', 'outpulse' ); ?></summary>
										<pre><?php echo esc_html( (string) $row->request_headers ); ?></pre>
									</details>
									<?php endif; ?>
									<?php if ( ! empty( $row->request_body ) ) : ?>
									<details>
										<summary><?php esc_html_e( 'Request Body', 'outpulse' ); ?></summary>
										<pre><?php echo esc_html( (string) $row->request_body ); ?></pre>
									</details>
									<?php endif; ?>
									<?php if ( ! empty( $row->cron_hook ) ) : ?>
									<p><strong><?php esc_html_e( 'Cron Hook', 'outpulse' ); ?>:</strong> <code><?php echo esc_html( (string) $row->cron_hook ); ?></code></p>
									<?php endif; ?>
								</div>
							</td>
						</tr>
						<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>

				<div class="tablenav bottom">
					<?php self::render_pagination( $page, $total_pages, $total, $filters ); ?>
				</div>

			</form>
		</div><!-- .outpulse-wrap -->
		<?php
	}

	/**
	 * Handle bulk delete / mark-reviewed actions before page output.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function handle_bulk_action(): void {
		if ( empty( $_POST['outpulse_nonce'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'outpulse' ) );
		}

		if ( ! wp_verify_nonce( sanitize_key( $_POST['outpulse_nonce'] ), 'outpulse_bulk' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'outpulse' ) );
		}

		$action = isset( $_POST['bulk_action'] ) ? sanitize_key( $_POST['bulk_action'] ) : '';
		$ids    = isset( $_POST['row_ids'] ) && is_array( $_POST['row_ids'] ) ? array_map( 'intval', $_POST['row_ids'] ) : array();

		if ( empty( $action ) || empty( $ids ) ) {
			return;
		}

		if ( 'delete' === $action ) {
			DB::delete_by_ids( $ids );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=outpulse' ) );
		exit;
	}

	/**
	 * Parse and sanitize filter values from GET params.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	private static function get_filters(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		return array(
			'domain'    => isset( $_GET['domain'] ) ? sanitize_text_field( wp_unslash( $_GET['domain'] ) ) : '',
			'plugin'    => isset( $_GET['plugin'] ) ? sanitize_text_field( wp_unslash( $_GET['plugin'] ) ) : '',
			'method'    => isset( $_GET['method'] ) ? sanitize_text_field( wp_unslash( $_GET['method'] ) ) : '',
			'context'   => isset( $_GET['context'] ) ? sanitize_text_field( wp_unslash( $_GET['context'] ) ) : '',
			'date_from' => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
			'date_to'   => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Build the export URL for the current filter set.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $filters Active filters.
	 * @param string               $format  'csv' or 'json'.
	 * @return string
	 */
	public static function export_url( array $filters, string $format ): string {
		$params = array_merge(
			array_filter( $filters, static fn( $v ) => '' !== $v && false !== $v ),
			array(
				'action'   => 'outpulse_export',
				'format'   => $format,
				'_wpnonce' => wp_create_nonce( 'outpulse_export' ),
			)
		);

		return add_query_arg( $params, admin_url( 'admin-post.php' ) );
	}

	/**
	 * Render pagination links.
	 *
	 * @since 1.0.0
	 *
	 * @param int                  $current     Current page number.
	 * @param int                  $total_pages Total page count.
	 * @param int                  $total_rows  Total row count.
	 * @param array<string, mixed> $filters     Active filters.
	 * @return void
	 */
	private static function render_pagination( int $current, int $total_pages, int $total_rows, array $filters ): void {
		if ( $total_pages <= 1 ) {
			return;
		}

		$base_url            = admin_url( 'admin.php' );
		$base_params         = array_filter( $filters, static fn( $v ) => '' !== $v && false !== $v );
		$base_params['page'] = 'outpulse';

		echo '<div class="tablenav-pages">';
		printf(
			'<span class="displaying-num">%s</span>',
			esc_html(
				sprintf(
					/* translators: %d: number of items */
					_n( '%d item', '%d items', $total_rows, 'outpulse' ),
					number_format_i18n( $total_rows )
				)
			)
		);

		echo '<span class="pagination-links">';

		if ( $current > 1 ) {
			printf(
				'<a href="%s" class="prev-page button">&lsaquo;</a>',
				esc_url( add_query_arg( array_merge( $base_params, array( 'paged' => $current - 1 ) ), $base_url ) )
			);
		}

		printf(
			'<span class="paging-input">%d / %d</span>',
			(int) $current,
			(int) $total_pages
		);

		if ( $current < $total_pages ) {
			printf(
				'<a href="%s" class="next-page button">&rsaquo;</a>',
				esc_url( add_query_arg( array_merge( $base_params, array( 'paged' => $current + 1 ) ), $base_url ) )
			);
		}

		echo '</span></div>';
	}
}
