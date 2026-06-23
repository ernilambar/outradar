<?php
/**
 * SettingsPage.
 *
 * @package Nilambar\Outpulse
 */

namespace Nilambar\Outpulse\Admin;

use Nilambar\Outpulse\Core\DB;

/**
 * Renders and handles the OutPulse Settings admin page.
 *
 * @since 1.0.0
 */
class SettingsPage {

	/**
	 * Render the settings page, handling saves and purge before output.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'outpulse' ) );
		}

		$saved     = false;
		$purged    = false;
		$mu_copied = false;
		$mu_error  = false;

		if ( ! empty( $_POST['outpulse_settings_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['outpulse_settings_nonce'] ), 'outpulse_settings' ) ) {
			if ( isset( $_POST['save_settings'] ) ) {
				self::save();
				$saved = true;
			} elseif ( isset( $_POST['purge_all'] ) ) {
				DB::purge_all_logs();
				$purged = true;
			} elseif ( isset( $_POST['install_mu_loader'] ) ) {
				if ( self::copy_mu_loader() ) {
					$mu_copied = true;
				} else {
					$mu_error = true;
				}
			}
		}

		$logging_enabled  = get_option( 'outpulse_logging_enabled', '1' );
		$retention_days   = (int) get_option( 'outpulse_retention_days', 30 );
		$excluded_plugins = (string) get_option( 'outpulse_excluded_plugins', '' );
		$mu_active        = file_exists( trailingslashit( WPMU_PLUGIN_DIR ) . 'outpulse-loader.php' );
		?>
		<div class="wrap outpulse-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( $saved ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'outpulse' ); ?></p></div>
			<?php endif; ?>

			<?php if ( $purged ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'All logs deleted.', 'outpulse' ); ?></p></div>
			<?php endif; ?>

			<?php if ( $mu_copied ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'MU loader installed.', 'outpulse' ); ?></p></div>
			<?php endif; ?>

			<?php if ( $mu_error ) : ?>
			<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'MU loader install failed. Check file permissions.', 'outpulse' ); ?></p></div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( 'outpulse_settings', 'outpulse_settings_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Logging', 'outpulse' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="outpulse_logging_enabled" value="1" <?php checked( '1', $logging_enabled ); ?> />
								<?php esc_html_e( 'Log outbound HTTP requests.', 'outpulse' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Log Retention', 'outpulse' ); ?></th>
						<td>
							<select name="outpulse_retention_days">
								<?php
								$options = array(
									7  => __( '7 days', 'outpulse' ),
									30 => __( '30 days', 'outpulse' ),
									90 => __( '90 days', 'outpulse' ),
									0  => __( 'Forever', 'outpulse' ),
								);
								foreach ( $options as $value => $label ) :
									?>
								<option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( $retention_days, $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Older logs are auto-deleted daily.', 'outpulse' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Exclude Sources', 'outpulse' ); ?></th>
						<td>
							<textarea name="outpulse_excluded_plugins" rows="6" class="large-text code"><?php echo esc_textarea( $excluded_plugins ); ?></textarea>
							<p class="description"><?php esc_html_e( 'One per line. Matching requests will not be logged.', 'outpulse' ); ?></p>
						</td>
					</tr>

			</table>

				<p class="submit">
					<button type="submit" name="save_settings" class="button button-primary"><?php esc_html_e( 'Save Settings', 'outpulse' ); ?></button>
				</p>
			</form>

			<hr />

			<h2><?php esc_html_e( 'MU Mode', 'outpulse' ); ?></h2>
			<?php if ( $mu_active ) : ?>
			<p><?php esc_html_e( 'MU loader is active. Requests from all plugins are captured.', 'outpulse' ); ?></p>
			<?php else : ?>
			<form method="post">
				<?php wp_nonce_field( 'outpulse_settings', 'outpulse_settings_nonce' ); ?>
				<p><?php esc_html_e( 'Install a loader in mu-plugins to capture requests from all plugins.', 'outpulse' ); ?></p>
				<p>
					<button type="submit" name="install_mu_loader" class="button button-secondary">
						<?php esc_html_e( 'Install MU Loader', 'outpulse' ); ?>
					</button>
				</p>
			</form>
			<?php endif; ?>

			<hr />

			<h2><?php esc_html_e( 'Danger Zone', 'outpulse' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'outpulse_settings', 'outpulse_settings_nonce' ); ?>
				<p><?php esc_html_e( 'Permanently delete all logs. This cannot be undone.', 'outpulse' ); ?></p>
				<p>
					<button type="submit" name="purge_all" class="button button-secondary outpulse-purge-btn" id="outpulse-purge-btn">
						<?php esc_html_e( 'Purge All Logs', 'outpulse' ); ?>
					</button>
				</p>
			</form>
		</div><!-- .outpulse-wrap -->
		<?php
	}

	/**
	 * Copy the bundled MU loader file into WPMU_PLUGIN_DIR.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True on success, false on failure.
	 */
	private static function copy_mu_loader(): bool {
		$source = OUTPULSE_DIR . 'loader/outpulse-loader.php';

		if ( ! file_exists( $source ) ) {
			return false;
		}

		if ( ! file_exists( WPMU_PLUGIN_DIR ) ) {
			wp_mkdir_p( WPMU_PLUGIN_DIR );
		}

		$dest = trailingslashit( WPMU_PLUGIN_DIR ) . 'outpulse-loader.php';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
		return copy( $source, $dest );
	}

	/**
	 * Persist settings from the POST data.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function save(): void {
		update_option( 'outpulse_logging_enabled', ! empty( $_POST['outpulse_logging_enabled'] ) ? '1' : '0' );
		update_option( 'outpulse_retention_days', absint( $_POST['outpulse_retention_days'] ?? 30 ) );
		update_option( 'outpulse_excluded_plugins', sanitize_textarea_field( wp_unslash( (string) ( $_POST['outpulse_excluded_plugins'] ?? '' ) ) ) );
	}
}
