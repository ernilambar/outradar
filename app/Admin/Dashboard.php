<?php
/**
 * Dashboard.
 *
 * @package Nilambar\Outwatch
 */

namespace Nilambar\Outwatch\Admin;

use Nilambar\Outwatch\Core\DB;

/**
 * Renders the OutWatch Dashboard admin page.
 *
 * @since 1.2.0
 */
class Dashboard {

	/**
	 * Render the dashboard page.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'outwatch' ) );
		}

		$stats       = DB::get_summary();
		$top_plugins = DB::get_top_plugins( 5 );
		?>
		<div class="wrap outwatch-wrap">
			<h1><?php esc_html_e( 'OutWatch', 'outwatch' ); ?></h1>

			<div class="outwatch-widgets">

				<div class="outwatch-widget">
					<h3><?php esc_html_e( 'Total Requests', 'outwatch' ); ?></h3>
					<div class="outwatch-stat-group">
						<div class="outwatch-stat">
							<span class="outwatch-stat-number"><?php echo esc_html( number_format_i18n( $stats['today'] ) ); ?></span>
							<span class="outwatch-stat-label"><?php esc_html_e( 'Today', 'outwatch' ); ?></span>
						</div>
						<div class="outwatch-stat">
							<span class="outwatch-stat-number"><?php echo esc_html( number_format_i18n( $stats['week'] ) ); ?></span>
							<span class="outwatch-stat-label"><?php esc_html_e( '7 Days', 'outwatch' ); ?></span>
						</div>
						<div class="outwatch-stat">
							<span class="outwatch-stat-number"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
							<span class="outwatch-stat-label"><?php esc_html_e( 'All Time', 'outwatch' ); ?></span>
						</div>
					</div>
				</div>

				<div class="outwatch-widget">
					<h3><?php esc_html_e( 'Overview', 'outwatch' ); ?></h3>
					<table class="outwatch-kv-table">
						<tr>
							<td><?php esc_html_e( 'Unique Domains', 'outwatch' ); ?></td>
							<td><strong><?php echo esc_html( number_format_i18n( $stats['unique_domains'] ) ); ?></strong></td>
						</tr>

					</table>
				</div>

				<div class="outwatch-widget">
					<h3><?php esc_html_e( 'By Context', 'outwatch' ); ?></h3>
					<table class="outwatch-kv-table">
						<tr>
							<td><?php esc_html_e( 'Frontend', 'outwatch' ); ?></td>
							<td><strong><?php echo esc_html( number_format_i18n( $stats['ctx_frontend'] ) ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Admin', 'outwatch' ); ?></td>
							<td><strong><?php echo esc_html( number_format_i18n( $stats['ctx_admin'] ) ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Cron', 'outwatch' ); ?></td>
							<td><strong><?php echo esc_html( number_format_i18n( $stats['ctx_cron'] ) ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'CLI', 'outwatch' ); ?></td>
							<td><strong><?php echo esc_html( number_format_i18n( $stats['ctx_cli'] ) ); ?></strong></td>
						</tr>
					</table>
				</div>

				<?php if ( ! empty( $top_plugins ) ) : ?>
				<div class="outwatch-widget">
					<h3><?php esc_html_e( 'Top Plugins', 'outwatch' ); ?></h3>
					<table class="outwatch-kv-table">
						<?php foreach ( $top_plugins as $row ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=outwatch-log&plugin=' . rawurlencode( (string) $row['source_plugin'] ) ) ); ?>">
									<?php echo esc_html( (string) $row['source_plugin'] ); ?>
								</a>
							</td>
							<td><strong><?php echo esc_html( number_format_i18n( (int) $row['total'] ) ); ?></strong></td>
						</tr>
						<?php endforeach; ?>
					</table>
				</div>
				<?php endif; ?>

			</div><!-- .outwatch-widgets -->

			<div class="outwatch-chart-section">
				<h2><?php esc_html_e( 'Requests — Last 7 Days', 'outwatch' ); ?></h2>
				<canvas id="outwatch-chart" width="800" height="220"></canvas>
			</div>

		</div><!-- .outwatch-wrap -->
		<?php
	}

	/**
	 * Return chart data for the last 7 days (passed to JS via wp_localize_script).
	 *
	 * @since 1.2.0
	 *
	 * @return array<string, mixed>
	 */
	public static function get_chart_data(): array {
		$raw    = DB::get_requests_by_day( 7 );
		$labels = array();
		$values = array();

		foreach ( $raw as $date => $count ) {
			$labels[] = $date;
			$values[] = $count;
		}

		return array(
			'labels' => $labels,
			'values' => $values,
		);
	}
}
