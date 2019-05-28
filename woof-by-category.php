<?php
/**
 * Plugin Name: WOOF by Category
 * Plugin URI: https://wordpress.org/plugins/woof-by-category/
 * Description: WooCommerce Product Filter (WOOF) extension to display set of filters depending on current product category page.
 * Author: KAGG Design
 * Version: 2.2.1
 * Author URI: https://kagg.eu/en/
 * Requires at least: 4.4
 * Tested up to: 5.2
 * Requires PHP: 5.2
 * WC requires at least: 3.0
 * WC tested up to: 3.6
 *
 * Text Domain: woof-by-category
 * Domain Path: /languages/
 *
 * @package woof-by-category
 * @author  KAGG Design
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'WOOF_BY_CATEGORY_PATH' ) ) {
	/**
	 * Path to the plugin dir.
	 */
	define( 'WOOF_BY_CATEGORY_PATH', dirname( __FILE__ ) );
}

if ( ! defined( 'WOOF_BY_CATEGORY_URL' ) ) {
	/**
	 * Plugin dir url.
	 */
	define( 'WOOF_BY_CATEGORY_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
}

if ( ! defined( 'WOOF_BY_CATEGORY_FILE' ) ) {
	/**
	 * Main plugin file.
	 */
	define( 'WOOF_BY_CATEGORY_FILE', __FILE__ );
}

if ( ! defined( 'WOOF_BY_CATEGORY_VERSION' ) ) {
	/**
	 * Plugin version.
	 */
	define( 'WOOF_BY_CATEGORY_VERSION', '2.2.1' );
}

/**
 * Init plugin class on plugin load.
 */

static $plugin;

if ( ! isset( $plugin ) ) {
	if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
		require_once WOOF_BY_CATEGORY_PATH . '/vendor/autoload.php';
	} else {
		require_once WOOF_BY_CATEGORY_PATH . '/vendor/autoload_52.php';
	}

	$plugin = new Woof_By_Category();
}
