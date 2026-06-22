<?php
/**
 * Plugin Name:       OutPulse
 * Description:       Intercept, log, and analyze all outbound HTTP requests made by WordPress core, plugins, and themes.
 * Version: 1.0.0
 * Author:            Nilambar Sharma
 * Author URI:        https://github.com/ernilambar
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       outpulse
 * Domain Path:       /languages
 * Requires PHP:      8.0
 * Requires at least: 7.0
 *
 * @package Nilambar\Outpulse
 */

use Nilambar\Outpulse\Core\Bootstrap;
use Nilambar\Outpulse\Core\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OUTPULSE_VERSION', '1.0.0' );
define( 'OUTPULSE_DIR', plugin_dir_path( __FILE__ ) );
define( 'OUTPULSE_URL', plugin_dir_url( __FILE__ ) );
define( 'OUTPULSE_BASE_FILENAME', plugin_basename( __FILE__ ) );
define( 'OUTPULSE_TABLE', 'outpulse_log' );

require_once OUTPULSE_DIR . 'vendor/autoload.php';

register_activation_hook( __FILE__, array( DB::class, 'create_table' ) );
register_activation_hook( __FILE__, array( Bootstrap::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Bootstrap::class, 'deactivate' ) );

( new Bootstrap() )->run();
