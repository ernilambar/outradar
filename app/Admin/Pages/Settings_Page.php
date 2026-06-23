<?php
/**
 * Settings_Page.
 *
 * @package Nilambar\Outpulse
 */

namespace Nilambar\Outpulse\Admin\Pages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Nilambar\Outpulse\Services\DB;

/**
 * Renders and handles the Settings admin page.
 *
 * @since 1.0.0
 */
class Settings_Page {

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

		if ( ! empty( $_POST['outpulse_settings_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['outpulse_settings_nonce'] ), 'outpulse_save_settings' ) && isset( $_POST['save_settings'] ) ) {
			self::save();
			$saved = true;
		} elseif ( ! empty( $_POST['outpulse_mu_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['outpulse_mu_nonce'] ), 'outpulse_install_mu' ) && isset( $_POST['install_mu_loader'] ) ) {
			if ( self::copy_mu_loader() ) {
				$mu_copied = true;
			} else {
				$mu_error = true;
			}
		} elseif ( ! empty( $_POST['outpulse_purge_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['outpulse_purge_nonce'] ), 'outpulse_purge_logs' ) && isset( $_POST['purge_all'] ) ) {
			DB::purge_all_logs();
			$purged = true;
		}

		$logging_enabled          = get_option( 'outpulse_logging_enabled', '1' );
		$retention_days           = (int) get_option( 'outpulse_retention_days', 30 );
		$excluded_plugins         = (string) get_option( 'outpulse_excluded_plugins', '' );
		$delete_data_on_uninstall = get_option( 'outpulse_delete_data_on_uninstall', '0' );
		$mu_active                = file_exists( trailingslashit( WPMU_PLUGIN_DIR ) . 'outpulse-loader.php' );
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
				<?php wp_nonce_field( 'outpulse_save_settings', 'outpulse_settings_nonce' ); ?>

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
									/* translators: %d: number of days */
									7  => sprintf( _n( '%d day', '%d days', 7, 'outpulse' ), 7 ),
									/* translators: %d: number of days */
									30 => sprintf( _n( '%d day', '%d days', 30, 'outpulse' ), 30 ),
									/* translators: %d: number of days */
									90 => sprintf( _n( '%d day', '%d days', 90, 'outpulse' ), 90 ),
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

					<tr>
						<th scope="row"><?php esc_html_e( 'Delete Data on Uninstall', 'outpulse' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="outpulse_delete_data_on_uninstall" value="1" <?php checked( '1', $delete_data_on_uninstall ); ?> />
								<?php esc_html_e( 'Remove all logs and settings when the plugin is deleted.', 'outpulse' ); ?>
							</label>
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
			<p><?php esc_html_e( 'MU loader is active. Requests from all sources are captured.', 'outpulse' ); ?></p>
			<?php else : ?>
			<form method="post">
				<?php wp_nonce_field( 'outpulse_install_mu', 'outpulse_mu_nonce' ); ?>
				<p><?php esc_html_e( 'Install the MU loader to capture requests from all sources.', 'outpulse' ); ?></p>
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
				<?php wp_nonce_field( 'outpulse_purge_logs', 'outpulse_purge_nonce' ); ?>
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
		update_option( 'outpulse_delete_data_on_uninstall', ! empty( $_POST['outpulse_delete_data_on_uninstall'] ) ? '1' : '0' );
	}
}
