<?php
/**
 * DomainPage.
 *
 * @package Nilambar\Outpulse
 */

namespace Nilambar\Outpulse\Admin;

use Nilambar\Outpulse\Core\DB;

/**
 * Renders the Domain Analysis admin page.
 *
 * @since 1.2.0
 */
class DomainPage {

	/**
	 * Render the domain analysis page.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'outpulse' ) );
		}

		$rows = DB::get_domain_summary();
		?>
		<div class="wrap outpulse-wrap">
			<h1><?php esc_html_e( 'Domain Analysis', 'outpulse' ); ?></h1>

			<?php if ( empty( $rows ) ) : ?>
				<p><?php esc_html_e( 'No domain data yet.', 'outpulse' ); ?></p>
			<?php else : ?>

			<table class="wp-list-table widefat fixed striped outpulse-domain-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Domain', 'outpulse' ); ?></th>
						<th><?php esc_html_e( 'First Seen', 'outpulse' ); ?></th>
						<th><?php esc_html_e( 'Last Seen', 'outpulse' ); ?></th>
						<th><?php esc_html_e( 'Requests', 'outpulse' ); ?></th>
						<th><?php esc_html_e( 'Sources', 'outpulse' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<?php $domain = (string) $row->domain; ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=outpulse-log&domain=' . rawurlencode( $domain ) ) ); ?>">
								<?php echo esc_html( $domain ); ?>
							</a>
						</td>
						<td><?php echo esc_html( (string) $row->first_seen ); ?></td>
						<td><?php echo esc_html( (string) $row->last_seen ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $row->total ) ); ?></td>
						<td class="outpulse-truncate" title="<?php echo esc_attr( (string) $row->plugins ); ?>">
							<?php echo esc_html( (string) $row->plugins ); ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php endif; ?>
		</div><!-- .outpulse-wrap -->
		<?php
	}
}
