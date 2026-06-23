<?php
/**
 * Dashboard.
 *
 * @package Nilambar\OutRadar
 */

namespace Nilambar\OutRadar\Admin\Pages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Nilambar\OutRadar\Services\DB;

/**
 * Renders the Dashboard admin page.
 *
 * @since 1.0.0
 */
class Dashboard_Page {

	/**
	 * Render the dashboard page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'outradar' ) );
		}

		$stats       = DB::get_summary();
		$top_plugins = DB::get_top_plugins( 5 );
		$domain_rows = DB::get_domain_summary();
		$source_rows = DB::get_plugin_summary();
		?>
		<div class="wrap outradar-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="outradar-widgets">

				<div class="outradar-widget">
					<h3><?php esc_html_e( 'Total Requests', 'outradar' ); ?></h3>
					<div class="outradar-stat-group">
						<div class="outradar-stat">
							<span class="outradar-stat-number"><?php echo esc_html( number_format_i18n( $stats['today'] ) ); ?></span>
							<span class="outradar-stat-label"><?php esc_html_e( 'Today', 'outradar' ); ?></span>
						</div>
						<div class="outradar-stat">
							<span class="outradar-stat-number"><?php echo esc_html( number_format_i18n( $stats['week'] ) ); ?></span>
							<span class="outradar-stat-label">
							<?php
								/* translators: %d: number of days */
								echo esc_html( sprintf( _n( '%d day', '%d days', 7, 'outradar' ), 7 ) );
							?>
								</span>
						</div>
						<div class="outradar-stat">
							<span class="outradar-stat-number"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
							<span class="outradar-stat-label"><?php esc_html_e( 'All Time', 'outradar' ); ?></span>
						</div>
					</div>
				</div>

				<div class="outradar-widget">
					<h3><?php esc_html_e( 'Overview', 'outradar' ); ?></h3>
					<table class="outradar-kv-table">
						<tr>
							<td><?php esc_html_e( 'Unique Domains', 'outradar' ); ?></td>
							<td><strong><?php echo esc_html( number_format_i18n( $stats['unique_domains'] ) ); ?></strong></td>
						</tr>

					</table>
				</div>

				<div class="outradar-widget">
					<h3><?php esc_html_e( 'By Context', 'outradar' ); ?></h3>
					<table class="outradar-kv-table">
						<tr>
							<td><?php esc_html_e( 'Frontend', 'outradar' ); ?></td>
							<td><strong><?php echo esc_html( number_format_i18n( $stats['ctx_frontend'] ) ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Admin', 'outradar' ); ?></td>
							<td><strong><?php echo esc_html( number_format_i18n( $stats['ctx_admin'] ) ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Cron', 'outradar' ); ?></td>
							<td><strong><?php echo esc_html( number_format_i18n( $stats['ctx_cron'] ) ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'CLI', 'outradar' ); ?></td>
							<td><strong><?php echo esc_html( number_format_i18n( $stats['ctx_cli'] ) ); ?></strong></td>
						</tr>
					</table>
				</div>

				<?php if ( ! empty( $top_plugins ) ) : ?>
				<div class="outradar-widget">
					<h3><?php esc_html_e( 'Top Sources', 'outradar' ); ?></h3>
					<table class="outradar-kv-table">
						<?php foreach ( $top_plugins as $row ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=outradar&plugin=' . rawurlencode( (string) $row['source_plugin'] ) ) ); ?>">
									<?php echo esc_html( (string) $row['source_plugin'] ); ?>
								</a>
							</td>
							<td><strong><?php echo esc_html( number_format_i18n( (int) $row['total'] ) ); ?></strong></td>
						</tr>
						<?php endforeach; ?>
					</table>
				</div>
				<?php endif; ?>

			</div><!-- .outradar-widgets -->

			<div class="outradar-chart-section">
				<div class="outradar-chart-header">
					<h2><?php esc_html_e( 'Requests by Context', 'outradar' ); ?></h2>
					<div class="outradar-chart-controls">
						<button class="button outradar-range-btn active" data-range="7">
						<?php
							/* translators: %d: number of days */
							echo esc_html( sprintf( _n( '%d day', '%d days', 7, 'outradar' ), 7 ) );
						?>
							</button>
						<button class="button outradar-range-btn" data-range="30">
						<?php
							/* translators: %d: number of days */
							echo esc_html( sprintf( _n( '%d day', '%d days', 30, 'outradar' ), 30 ) );
						?>
							</button>
					</div>
				</div>
				<div class="outradar-chart-legend">
					<span class="outradar-legend-item" data-ctx="cron"><?php esc_html_e( 'Cron', 'outradar' ); ?></span>
					<span class="outradar-legend-item" data-ctx="frontend"><?php esc_html_e( 'Frontend', 'outradar' ); ?></span>
					<span class="outradar-legend-item" data-ctx="admin"><?php esc_html_e( 'Admin', 'outradar' ); ?></span>
					<span class="outradar-legend-item" data-ctx="cli"><?php esc_html_e( 'CLI', 'outradar' ); ?></span>
				</div>
				<canvas id="outradar-chart" width="800" height="220"></canvas>
			</div>

			<h2><?php esc_html_e( 'Domains', 'outradar' ); ?></h2>

			<?php if ( empty( $domain_rows ) ) : ?>
				<p><?php esc_html_e( 'No data yet.', 'outradar' ); ?></p>
			<?php else : ?>
			<table class="wp-list-table widefat fixed striped outradar-domain-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Domain', 'outradar' ); ?></th>
						<th><?php esc_html_e( 'First Seen', 'outradar' ); ?></th>
						<th><?php esc_html_e( 'Last Seen', 'outradar' ); ?></th>
						<th><?php esc_html_e( 'Requests', 'outradar' ); ?></th>
						<th><?php esc_html_e( 'Sources', 'outradar' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $domain_rows as $row ) : ?>
						<?php $domain = (string) $row->domain; ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=outradar&domain=' . rawurlencode( $domain ) ) ); ?>">
								<?php echo esc_html( $domain ); ?>
							</a>
						</td>
						<td><?php echo esc_html( (string) $row->first_seen ); ?></td>
						<td><?php echo esc_html( (string) $row->last_seen ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $row->total ) ); ?></td>
						<td class="outradar-truncate" title="<?php echo esc_attr( (string) $row->plugins ); ?>">
							<?php echo esc_html( (string) $row->plugins ); ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Sources', 'outradar' ); ?></h2>

			<?php if ( empty( $source_rows ) ) : ?>
				<p><?php esc_html_e( 'No data yet.', 'outradar' ); ?></p>
			<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Source', 'outradar' ); ?></th>
						<th><?php esc_html_e( 'Total Requests', 'outradar' ); ?></th>
						<th><?php esc_html_e( 'Unique Domains', 'outradar' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $source_rows as $row ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=outradar&plugin=' . rawurlencode( (string) $row->source_plugin ) ) ); ?>">
								<?php echo esc_html( (string) $row->source_plugin ); ?>
							</a>
						</td>
						<td><?php echo esc_html( number_format_i18n( (int) $row->total ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $row->unique_domains ) ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>

		</div><!-- .outradar-wrap -->
		<?php
	}

	/**
	 * Return stacked chart data for the last N days (passed to JS via wp_localize_script).
	 *
	 * @since 1.0.0
	 *
	 * @param int $days Number of days to look back.
	 * @return array<string, mixed>
	 */
	public static function get_chart_data( int $days = 7 ): array {
		$raw    = DB::get_requests_by_day_context( $days );
		$labels = array();
		$series = array(
			'cron'     => array(),
			'frontend' => array(),
			'admin'    => array(),
			'cli'      => array(),
		);

		foreach ( $raw as $date => $counts ) {
			$labels[]             = $date;
			$series['cron'][]     = $counts['cron'];
			$series['frontend'][] = $counts['frontend'];
			$series['admin'][]    = $counts['admin'];
			$series['cli'][]      = $counts['cli'];
		}

		return array(
			'labels' => $labels,
			'series' => $series,
		);
	}
}
