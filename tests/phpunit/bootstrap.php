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
define( 'PLUGIN_MAIN_FILE', realpath( __DIR__ . '/../../woof-by-category.php' ) );

/**
 * Path to the plugin dir.
 */
define( 'PLUGIN_PATH', realpath( dirname( PLUGIN_MAIN_FILE ) ) );

/**
 * Kilobytes in bytes.
 */
define( 'KB_IN_BYTES', 1024 );

/**
 * Plugin version.
 */
define( 'WOOF_BY_CATEGORY_TEST_VERSION', '2.15' );

require_once PLUGIN_PATH . '/vendor/autoload.php';

FunctionMocker::init(
	[
		'blacklist'             => [
			realpath( PLUGIN_PATH ),
		],
		'whitelist'             => [
			realpath( PLUGIN_PATH . '/woof-by-category.php' ),
			realpath( PLUGIN_PATH . '/classes' ),
		],
		'redefinable-internals' => [
			'define',
			'defined',
			'constant',
			'class_exists',
		],
	]
);

\WP_Mock::bootstrap();
