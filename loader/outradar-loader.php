<?php
/**
 * OutRadar MU Loader
 *
 * Loads OutRadar before regular plugins for complete request capture.
 * Drop this file into wp-content/mu-plugins/ to enable MU mode.
 *
 * @package Nilambar\OutRadar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$outradar_main = WP_PLUGIN_DIR . '/outradar/outradar.php';

if ( file_exists( $outradar_main ) ) {
	require_once $outradar_main;
}
