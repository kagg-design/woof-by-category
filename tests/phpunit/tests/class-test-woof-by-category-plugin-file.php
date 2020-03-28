<?php
/**
 * Test_Woof_By_Category_Plugin_File class file
 *
 * @package woof-by-category
 */

use tad\FunctionMocker\FunctionMocker;

/**
 * Class Test_Woof_By_Category_Plugin_File
 *
 * @group plugin-file
 */
class Test_Woof_By_Category_Plugin_File extends Woof_By_Category_TestCase {

	public function tearDown() {
		unset( $GLOBALS[ 'woof_by_category_plugin' ] );
		parent::tearDown();
	}

	public function test_when_woof_by_category_version_defined() {
		FunctionMocker::replace(
			'defined',
			function ( $name ) {
				if ( 'ABSPATH' === $name ) {
					return true;
				}

				if ( 'WOOF_BY_CATEGORY_VERSION' === $name ) {
					return true;
				}

				return null;
			}
		);

		$define = FunctionMocker::replace( 'define', null );

		require PLUGIN_MAIN_FILE;

		$define->wasNotCalled();
	}

	/**
	 * Test loading of main plugin file.
	 *
	 * Does not work with php 5.6 due to the bug in Reflection class prior php 7.0,
	 * and relevant problem in Patchwork.
	 *
	 * @requires            PHP >= 7.0
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_plugin_file_at_first_time() {
		global $woof_by_category_plugin;

		$mock = Mockery::mock( 'overload:' . Woof_By_Category::class );
		$mock->shouldReceive( 'init' );

		$define = FunctionMocker::replace( 'define', null );
		FunctionMocker::replace(
			'constant',
			function ( $name ) {
				if ( $name === 'WOOF_BY_CATEGORY_PATH' ) {
					return PLUGIN_PATH;
				}

				return null;
			}
		);

		\WP_Mock::passthruFunction( 'plugin_dir_url' );
		\WP_Mock::passthruFunction( 'untrailingslashit' );

		require PLUGIN_MAIN_FILE;

		$define->wasCalledWithOnce( [ 'WOOF_BY_CATEGORY_VERSION', '2.6' ] );
		$define->wasCalledWithOnce( [ 'WOOF_BY_CATEGORY_PATH', dirname( PLUGIN_MAIN_FILE ) ] );
		$define->wasCalledWithOnce( [ 'WOOF_BY_CATEGORY_URL', PLUGIN_MAIN_FILE ] );
		$define->wasCalledWithOnce( [ 'WOOF_BY_CATEGORY_FILE', PLUGIN_MAIN_FILE ] );

		$this->assertInstanceOf( Woof_By_Category::class, $woof_by_category_plugin );
	}
}
