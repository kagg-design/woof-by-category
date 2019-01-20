<?php
/**
 * Plugin Name: WOOF by Category
 * Plugin URI: https://wordpress.org/plugins/woof-by-category/
 * Description: WooCommerce Product Filter (WOOF) extension to display set of filters depending on current product category page.
 * Author: KAGG Design
 * Version: 2.0.1
 * Author URI: https://kagg.eu/en/
 * Requires at least: 4.4
 * Tested up to: 5.0
 * WC requires at least: 3.0
 * WC tested up to: 3.5
 *
 * Text Domain: woof-by-category
 * Domain Path: /languages/
 *
 * @package woof-by-category
 * @author KAGG Design
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'WOOF_BY_CATEGORY_PATH', dirname( __FILE__ ) );
define( 'WOOF_BY_CATEGORY_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'WOOF_BY_CATEGORY_FILE', __FILE__ );
define( 'WOOF_BY_CATEGORY_VERSION', '2.0.1' );

static $plugin;

if ( ! isset( $plugin ) ) {
	$autoloader_dir = WOOF_BY_CATEGORY_PATH . '/vendor';
	if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
		$autoloader = $autoloader_dir . '/autoload.php';
	} else {
		$autoloader = $autoloader_dir . '/autoload_52.php';
	}
	require_once $autoloader;

	$plugin = new Woof_By_Category();
}
