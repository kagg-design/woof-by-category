<?php
/**
 * Bootstrap file for Woof-By-Category phpunit tests.
 *
 * @package woof-by-category
 */

use tad\FunctionMocker\FunctionMocker;

/**
 * Plugin test dir.
 */
define( 'PLUGIN_TESTS_DIR', __DIR__ );

/**
 * Plugin main file.
 */
define( 'PLUGIN_MAIN_FILE', __DIR__ . '/../../woof-by-category.php' );

/**
 * Path to the plugin dir.
 */
define( 'PLUGIN_PATH', __DIR__ . '/../..' );

require_once PLUGIN_PATH . '/vendor/autoload.php';

/**
 * Main plugin file.
 */
define( 'WOOF_BY_CATEGORY_FILE', PLUGIN_PATH . '/woof-by-category.php' );

/**
 * Plugin dir url.
 */
define( 'WOOF_BY_CATEGORY_URL', 'http://site.org/wp-content/plugins/woof-by-category' );

/**
 * Plugin version.
 */
define( 'WOOF_BY_CATEGORY_VERSION', 'test-version' );

FunctionMocker::init(
	[
		'blacklist'             => [
			realpath( PLUGIN_PATH ),
		],
		'whitelist'             => [
			realpath( PLUGIN_PATH . '/classes' ),
		],
	]
);

\WP_Mock::bootstrap();
