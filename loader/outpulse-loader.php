<?php
/**
 * OutPulse MU Loader
 *
 * Loads OutPulse before regular plugins for complete request capture.
 * Drop this file into wp-content/mu-plugins/ to enable MU mode.
 *
 * @package Nilambar\Outpulse
 */

$outpulse_main = WP_PLUGIN_DIR . '/outpulse/outpulse.php';

if ( file_exists( $outpulse_main ) ) {
	require_once $outpulse_main;
}
