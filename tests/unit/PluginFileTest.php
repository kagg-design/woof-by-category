<?php
/**
 * PluginFileTest class file
 *
 * @package woof-by-category
 */

namespace KAGG\WoofByCategory\Tests\Unit;

use KAGG\WoofByCategory\Main;
use Mockery;
use tad\FunctionMocker\FunctionMocker;
use WP_Mock;

/**
 * Class PluginFileTest
 *
 * @group plugin-file
 */
class PluginFileTest extends WoofByCategoryTestCase {

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		unset( $GLOBALS['woof_by_category_plugin'] );
		parent::tearDown();
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
	 * @noinspection        PhpIncludeInspection
	 */
	public function test_plugin_file_at_first_time() {
		global $woof_by_category_plugin;

		$mock = Mockery::mock( 'overload:' . Main::class );
		$mock->shouldReceive( 'init' )->once();

		WP_Mock::passthruFunction( 'plugin_dir_url' );
		WP_Mock::passthruFunction( 'untrailingslashit' );

		require PLUGIN_MAIN_FILE;

		$expected    = [
			'version' => WOOF_BY_CATEGORY_TEST_VERSION,
		];
		$plugin_file = PLUGIN_MAIN_FILE;

		$plugin_headers = $this->get_file_data(
			$plugin_file,
			[ 'version' => 'Version' ],
			'plugin'
		);

		self::assertSame( $expected, $plugin_headers );

		self::assertSame( WOOF_BY_CATEGORY_TEST_VERSION, constant( 'WOOF_BY_CATEGORY_VERSION' ) );
		self::assertSame( WOOF_BY_CATEGORY_PATH, dirname( PLUGIN_MAIN_FILE ) );
		self::assertSame( WOOF_BY_CATEGORY_FILE, PLUGIN_MAIN_FILE );
		self::assertSame( WOOF_BY_CATEGORY_URL, PLUGIN_MAIN_FILE );

		self::assertInstanceOf( Main::class, $woof_by_category_plugin );
	}


	/**
	 * Test that readme.txt contains proper stable tag.
	 */
	public function test_readme_txt() {
		$expected    = [
			'stable_tag' => WOOF_BY_CATEGORY_TEST_VERSION,
		];
		$readme_file = PLUGIN_PATH . '/readme.txt';

		$readme_headers = $this->get_file_data(
			$readme_file,
			[ 'stable_tag' => 'Stable tag' ],
			'plugin'
		);

		self::assertSame( $expected, $readme_headers );
	}
}
