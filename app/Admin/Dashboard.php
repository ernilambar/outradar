<?php
/**
 * Dashboard.
 *
 * @package Nilambar\Outpulse
 */

namespace Nilambar\Outpulse\Admin;

use Nilambar\Outpulse\Core\DB;

/**
 * Renders the OutPulse Dashboard admin page.
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
			wp_die( esc_html__( 'You do not have permission to access this page.', 'outpulse' ) );
		}

		$stats       = DB::get_summary();
		$top_plugins = DB::get_top_plugins( 5 );
		?>
		<div class="wrap outpulse-wrap">
			<h1><?php esc_html_e( 'OutPulse', 'outpulse' ); ?></h1>

			<div class="outpulse-widgets">

				<div class="outpulse-widget">
					<h3><?php esc_html_e( 'Total Requests', 'outpulse' ); ?></h3>
					<div class="outpulse-stat-group">
						<div class="outpulse-stat">
							<span class="outpulse-stat-number"><?php echo esc_html( number_format_i18n( $stats['today'] ) ); ?></span>
							<span class="outpulse-stat-label"><?php esc_html_e( 'Today', 'outpulse' ); ?></span>
						</div>
						<div class="outpulse-stat">
							<span class="outpulse-stat-number"><?php echo esc_html( number_format_i18n( $stats['week'] ) ); ?></span>
							<span class="outpulse-stat-label"><?php esc_html_e( '7 Days', 'outpulse' ); ?></span>
						</div>
						<div class="outpulse-stat">
							<span class="outpulse-stat-number"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
							<span class="outpulse-stat-label"><?php esc_html_e( 'All Time', 'outpulse' ); ?></span>
						</div>
					</div>
				</div>

				<div class="outpulse-widget">
					<h3><?php esc_html_e( 'Overview', 'outpulse' ); ?></h3>
					<table class="outpulse-kv-table">
						<tr>
							<td><?php esc_html_e( 'Unique Domains', 'outpulse' ); ?></td>
							<td><strong><?php echo esc_html( number_format_i18n( $stats['unique_domains'] ) ); ?></strong></td>
						</tr>

					</table>
				</div>

				<div class="outpulse-widget">
					<h3><?php esc_html_e( 'By Context', 'outpulse' ); ?></h3>
					<table class="outpulse-kv-table">
						<tr>
							<td><?php esc_html_e( 'Frontend', 'outpulse' ); ?></td>
							<td><strong><?php echo esc_html( number_format_i18n( $stats['ctx_frontend'] ) ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Admin', 'outpulse' ); ?></td>
							<td><strong><?php echo esc_html( number_format_i18n( $stats['ctx_admin'] ) ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Cron', 'outpulse' ); ?></td>
							<td><strong><?php echo esc_html( number_format_i18n( $stats['ctx_cron'] ) ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'CLI', 'outpulse' ); ?></td>
							<td><strong><?php echo esc_html( number_format_i18n( $stats['ctx_cli'] ) ); ?></strong></td>
						</tr>
					</table>
				</div>

				<?php if ( ! empty( $top_plugins ) ) : ?>
				<div class="outpulse-widget">
					<h3><?php esc_html_e( 'Top Sources', 'outpulse' ); ?></h3>
					<table class="outpulse-kv-table">
						<?php foreach ( $top_plugins as $row ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=outpulse-log&plugin=' . rawurlencode( (string) $row['source_plugin'] ) ) ); ?>">
									<?php echo esc_html( (string) $row['source_plugin'] ); ?>
								</a>
							</td>
							<td><strong><?php echo esc_html( number_format_i18n( (int) $row['total'] ) ); ?></strong></td>
						</tr>
						<?php endforeach; ?>
					</table>
				</div>
				<?php endif; ?>

			</div><!-- .outpulse-widgets -->

			<div class="outpulse-chart-section">
				<h2><?php esc_html_e( 'Requests — Last 7 Days', 'outpulse' ); ?></h2>
				<canvas id="outpulse-chart" width="800" height="220"></canvas>
			</div>

		</div><!-- .outpulse-wrap -->
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
