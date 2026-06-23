<?php
/**
 * Plugin Name:       OutRadar
 * Plugin URI: https://github.com/ernilambar/outradar
 * Description:       Log outbound HTTP requests.
 * Version: 1.0.0
 * Author:            Nilambar Sharma
 * Author URI:        https://github.com/ernilambar
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       outradar
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires at least: 6.9
 *
 * @package Nilambar\OutRadar
 */

use Nilambar\OutRadar\Core\Bootstrap;
use Nilambar\OutRadar\Services\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'OUTRADAR_LOADED' ) ) {
	return;
}
define( 'OUTRADAR_LOADED', true );

define( 'OUTRADAR_VERSION', '1.0.0' );
define( 'OUTRADAR_DIR', plugin_dir_path( __FILE__ ) );
define( 'OUTRADAR_URL', plugin_dir_url( __FILE__ ) );
define( 'OUTRADAR_BASE_FILENAME', plugin_basename( __FILE__ ) );
define( 'OUTRADAR_TABLE', 'outradar_log' );

require_once OUTRADAR_DIR . 'vendor/autoload.php';

register_activation_hook( __FILE__, array( DB::class, 'create_table' ) );
register_activation_hook( __FILE__, array( Bootstrap::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Bootstrap::class, 'deactivate' ) );

( new Bootstrap() )->run();
