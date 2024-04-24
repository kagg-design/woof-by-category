<?php
/**
 * WOOF by Category
 *
 * @package              woof-by-category
 * @author               KAGG Design
 * @license              GPL-2.0-or-later
 * @wordpress-plugin
 *
 * Plugin Name:          WOOF by Category
 * Plugin URI:           https://wordpress.org/plugins/woof-by-category/
 * Description:          WooCommerce Product Filter (WOOF) extension to display a set of filters depending on the current product category page.
 * Version:              3.1.1
 * Requires at least:    5.0
 * Requires PHP:         7.0
 * Author:               KAGG Design
 * Author URI:           https://kagg.eu/en/
 * License:              GPL v2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          woof-by-category
 * Domain Path:          /languages/
 *
 * WC requires at least: 3.0
 * WC tested up to:      8.8
 */

// phpcs:ignore Generic.Commenting.DocComment.MissingShort
/** @noinspection PhpUnused PhpUnused */

use KAGG\WoofByCategory\Main;

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
const WOOF_BY_CATEGORY_VERSION = '3.1.1';

/**
 * Path to the plugin dir.
 */
const WOOF_BY_CATEGORY_PATH = __DIR__;

/**
 * Plugin dir url.
 */
define( 'WOOF_BY_CATEGORY_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

/**
 * Main plugin file.
 */
const WOOF_BY_CATEGORY_FILE = __FILE__;

/**
 * Init plugin class on the plugin load.
 */
require_once WOOF_BY_CATEGORY_PATH . '/vendor/autoload.php';

/**
 * Get main class instance.
 *
 * @return Main
 */
function woof_by_category(): Main {
	// Global for backwards compatibility.
	global $woof_by_category_plugin;

	if ( ! $woof_by_category_plugin ) {
		$woof_by_category_plugin = new Main();
	}

	return $woof_by_category_plugin;
}

woof_by_category()->init();
