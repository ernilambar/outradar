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
	 * Known-safe domains that should not be highlighted.
	 *
	 * @var string[]
	 */
	private static array $default_safe = array(
		'wordpress.org',
		'api.wordpress.org',
		'downloads.wordpress.org',
		'plugins.svn.wordpress.org',
		'themes.svn.wordpress.org',
		'googleapis.com',
		'gravatar.com',
	);

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

		$rows      = DB::get_domain_summary();
		$safe_raw  = (string) get_option( 'outwatch_safe_domains', '' );
		$safe_list = array_filter( array_map( 'trim', explode( "\n", $safe_raw ) ) );
		$safe_list = array_merge( self::$default_safe, $safe_list );
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
						<th><?php esc_html_e( 'Risk Flags', 'outwatch' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<?php
						$domain  = (string) $row->domain;
						$is_safe = false;
						foreach ( $safe_list as $safe ) {
							if ( '' === $safe ) {
								continue;
							}
							if ( $domain === $safe || str_ends_with( $domain, '.' . $safe ) ) {
								$is_safe = true;
								break;
							}
						}
						?>
					<tr class="<?php echo ! $is_safe ? 'outwatch-domain-unknown' : ''; ?>">
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=outwatch-log&domain=' . rawurlencode( $domain ) ) ); ?>">
								<?php echo esc_html( $domain ); ?>
							</a>
							<?php if ( ! $is_safe ) : ?>
								<span class="outwatch-badge outwatch-badge--warn" title="<?php esc_attr_e( 'Not on safe domains list', 'outwatch' ); ?>">?</span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( (string) $row->first_seen ); ?></td>
						<td><?php echo esc_html( (string) $row->last_seen ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $row->total ) ); ?></td>
						<td class="outwatch-truncate" title="<?php echo esc_attr( (string) $row->plugins ); ?>">
							<?php echo esc_html( (string) $row->plugins ); ?>
						</td>
						<td>
							<?php if ( (int) $row->flagged > 0 ) : ?>
								<span class="outwatch-risk"><?php echo esc_html( number_format_i18n( (int) $row->flagged ) ); ?></span>
							<?php else : ?>
								0
							<?php endif; ?>
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
