<?php
// This is global bootstrap for autoloading.

/**
 * Path to the plugin dir.
 */
define( 'PLUGIN_PATH', __DIR__ . '/../..' );

/**
 * Main plugin file.
 */
define( 'WOOF_BY_CATEGORY_FILE', PLUGIN_PATH . '/woof-by-category.php' );

$autoloader_dir = PLUGIN_PATH . '/vendor';
$autoloader = $autoloader_dir . '/autoload.php';
require_once $autoloader;
