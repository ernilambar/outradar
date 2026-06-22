<?php
/**
 * SettingsPage.
 *
 * @package Nilambar\Outwatch
 */

namespace Nilambar\Outwatch\Admin;

use Nilambar\Outwatch\Core\DB;

/**
 * Renders and handles the OutWatch Settings admin page.
 *
 * @since 1.2.0
 */
class SettingsPage {

	/**
	 * Render the settings page, handling saves and purge before output.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'outwatch' ) );
		}

		$saved  = false;
		$purged = false;

		if ( ! empty( $_POST['outwatch_settings_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['outwatch_settings_nonce'] ), 'outwatch_settings' ) ) {
			if ( isset( $_POST['save_settings'] ) ) {
				self::save();
				$saved = true;
			} elseif ( isset( $_POST['purge_all'] ) ) {
				DB::purge_all_logs();
				$purged = true;
			}
		}

		$logging_enabled    = get_option( 'outwatch_logging_enabled', '1' );
		$retention_days     = (int) get_option( 'outwatch_retention_days', 30 );
		$safe_domains       = (string) get_option( 'outwatch_safe_domains', '' );
		$excluded_plugins   = (string) get_option( 'outwatch_excluded_plugins', '' );
		$notification_email = (string) get_option( 'outwatch_notification_email', '' );
		?>
		<div class="wrap outwatch-wrap">
			<h1><?php esc_html_e( 'OutWatch Settings', 'outwatch' ); ?></h1>

			<?php if ( $saved ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'outwatch' ); ?></p></div>
			<?php endif; ?>

			<?php if ( $purged ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'All logs deleted.', 'outwatch' ); ?></p></div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( 'outwatch_settings', 'outwatch_settings_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Logging', 'outwatch' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="outwatch_logging_enabled" value="1" <?php checked( '1', $logging_enabled ); ?> />
								<?php esc_html_e( 'Log all outbound HTTP requests', 'outwatch' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Log Retention', 'outwatch' ); ?></th>
						<td>
							<select name="outwatch_retention_days">
								<?php
								$options = array(
									7  => __( '7 days', 'outwatch' ),
									30 => __( '30 days', 'outwatch' ),
									90 => __( '90 days', 'outwatch' ),
									0  => __( 'Forever', 'outwatch' ),
								);
								foreach ( $options as $value => $label ) :
									?>
								<option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( $retention_days, $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Logs older than this are deleted automatically each day.', 'outwatch' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Safe Domains', 'outwatch' ); ?></th>
						<td>
							<textarea name="outwatch_safe_domains" rows="6" class="large-text code"><?php echo esc_textarea( $safe_domains ); ?></textarea>
							<p class="description"><?php esc_html_e( 'One domain per line. These are not highlighted on the Domain Analysis page.', 'outwatch' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Exclude Plugins', 'outwatch' ); ?></th>
						<td>
							<textarea name="outwatch_excluded_plugins" rows="6" class="large-text code"><?php echo esc_textarea( $excluded_plugins ); ?></textarea>
							<p class="description"><?php esc_html_e( 'One plugin name per line. Requests attributed to these plugins will not be logged.', 'outwatch' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Notification Email', 'outwatch' ); ?></th>
						<td>
							<input type="email" name="outwatch_notification_email" value="<?php echo esc_attr( $notification_email ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Receive an email when risk flags are detected. Leave blank to disable.', 'outwatch' ); ?></p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" name="save_settings" class="button button-primary"><?php esc_html_e( 'Save Settings', 'outwatch' ); ?></button>
				</p>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Danger Zone', 'outwatch' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'outwatch_settings', 'outwatch_settings_nonce' ); ?>
				<p><?php esc_html_e( 'Permanently delete all log entries. This cannot be undone.', 'outwatch' ); ?></p>
				<p>
					<button type="submit" name="purge_all" class="button button-secondary outwatch-purge-btn" id="outwatch-purge-btn">
						<?php esc_html_e( 'Purge All Logs', 'outwatch' ); ?>
					</button>
				</p>
			</form>
		</div><!-- .outwatch-wrap -->
		<?php
	}

	/**
	 * Persist settings from the POST data.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	private static function save(): void {
		update_option( 'outwatch_logging_enabled', ! empty( $_POST['outwatch_logging_enabled'] ) ? '1' : '0' );
		update_option( 'outwatch_retention_days', absint( $_POST['outwatch_retention_days'] ?? 30 ) );
		update_option( 'outwatch_safe_domains', sanitize_textarea_field( wp_unslash( (string) ( $_POST['outwatch_safe_domains'] ?? '' ) ) ) );
		update_option( 'outwatch_excluded_plugins', sanitize_textarea_field( wp_unslash( (string) ( $_POST['outwatch_excluded_plugins'] ?? '' ) ) ) );

		$email = sanitize_email( wp_unslash( (string) ( $_POST['outwatch_notification_email'] ?? '' ) ) );
		update_option( 'outwatch_notification_email', is_email( $email ) ? $email : '' );
	}
}
