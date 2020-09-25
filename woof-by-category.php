<?php
/**
 * Plugin Name: WOOF by Category
 * Plugin URI: https://wordpress.org/plugins/woof-by-category/
 * Description: WooCommerce Product Filter (WOOF) extension to display set of filters depending on current product category page.
 * Author: KAGG Design
 * Version: 2.9.2
 * Author URI: https://kagg.eu/en/
 * Requires at least: 4.4
 * Tested up to: 5.5
 * Requires PHP: 5.6
 * WC requires at least: 3.0
 * WC tested up to: 4.5
 *
 * Text Domain: woof-by-category
 * Domain Path: /languages/
 *
 * @package woof-by-category
 * @author  KAGG Design
 */

// @codeCoverageIgnoreStart
if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly.
}
// @codeCoverageIgnoreEnd

if ( defined( 'WOOF_BY_CATEGORY_VERSION' ) ) {
	return;
}

/**
 * Plugin version.
 */
define( 'WOOF_BY_CATEGORY_VERSION', '2.9.2' );

/**
 * Path to the plugin dir.
 */
define( 'WOOF_BY_CATEGORY_PATH', dirname( __FILE__ ) );

/**
 * Plugin dir url.
 */
define( 'WOOF_BY_CATEGORY_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

/**
 * Main plugin file.
 */
define( 'WOOF_BY_CATEGORY_FILE', __FILE__ );

/**
 * Init plugin class on plugin load.
 */
require_once constant( 'WOOF_BY_CATEGORY_PATH' ) . '/vendor/autoload.php';

$woof_by_category_plugin = new Woof_By_Category();
$woof_by_category_plugin->init();
