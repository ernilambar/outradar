<?php
/**
 * PluginPage.
 *
 * @package Nilambar\Outwatch
 */

namespace Nilambar\Outwatch\Admin;

use Nilambar\Outwatch\Core\DB;

/**
 * Renders the per-plugin breakdown admin page.
 *
 * @since 1.2.0
 */
class PluginPage {

	/**
	 * Render the plugin breakdown page.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'outwatch' ) );
		}

		$rows = DB::get_plugin_summary();
		?>
		<div class="wrap outwatch-wrap">
			<h1><?php esc_html_e( 'Source Activity', 'outwatch' ); ?></h1>

			<?php if ( empty( $rows ) ) : ?>
				<p><?php esc_html_e( 'No source data yet.', 'outwatch' ); ?></p>
			<?php else : ?>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Source', 'outwatch' ); ?></th>
						<th><?php esc_html_e( 'Total Requests', 'outwatch' ); ?></th>
						<th><?php esc_html_e( 'Unique Domains', 'outwatch' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=outwatch-log&plugin=' . rawurlencode( (string) $row->source_plugin ) ) ); ?>">
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
		</div><!-- .outwatch-wrap -->
		<?php
	}
}
