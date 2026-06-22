<?php
/**
 * DomainPage.
 *
 * @package Nilambar\Outwatch
 */

namespace Nilambar\Outwatch\Admin;

use Nilambar\Outwatch\Core\DB;

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
			wp_die( esc_html__( 'You do not have permission to access this page.', 'outwatch' ) );
		}

		$rows = DB::get_domain_summary();
		?>
		<div class="wrap outwatch-wrap">
			<h1><?php esc_html_e( 'Domain Analysis', 'outwatch' ); ?></h1>

			<?php if ( empty( $rows ) ) : ?>
				<p><?php esc_html_e( 'No domain data yet.', 'outwatch' ); ?></p>
			<?php else : ?>

			<table class="wp-list-table widefat fixed striped outwatch-domain-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Domain', 'outwatch' ); ?></th>
						<th><?php esc_html_e( 'First Seen', 'outwatch' ); ?></th>
						<th><?php esc_html_e( 'Last Seen', 'outwatch' ); ?></th>
						<th><?php esc_html_e( 'Requests', 'outwatch' ); ?></th>
						<th><?php esc_html_e( 'Plugins', 'outwatch' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<?php $domain = (string) $row->domain; ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=outwatch-log&domain=' . rawurlencode( $domain ) ) ); ?>">
								<?php echo esc_html( $domain ); ?>
							</a>
						</td>
						<td><?php echo esc_html( (string) $row->first_seen ); ?></td>
						<td><?php echo esc_html( (string) $row->last_seen ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $row->total ) ); ?></td>
						<td class="outwatch-truncate" title="<?php echo esc_attr( (string) $row->plugins ); ?>">
							<?php echo esc_html( (string) $row->plugins ); ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php endif; ?>
		</div><!-- .outwatch-wrap -->
		<?php
	}
}
