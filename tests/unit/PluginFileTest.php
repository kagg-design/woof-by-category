<?php
/**
 * PluginFileTest class file
 *
 * @package woof-by-category
 */

namespace KAGG\WoofByCategory\Tests\Unit;

use KAGG\WoofByCategory\Main;
use Mockery;
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
	 * Test loading of a main plugin file.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_plugin_file_at_first_time(): void {
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
	 * Test that readme.txt contains a proper stable tag.
	 */
	public function test_readme_txt(): void {
		$expected    = [
			'stable_tag' => WOOF_BY_CATEGORY_TEST_VERSION,
			'requires_wp' => '6.0',
			'requires_php' => '7.4',
		];
		$readme_file = PLUGIN_PATH . '/readme.txt';

		$readme_headers = $this->get_file_data(
			$readme_file,
			[
				'stable_tag'  => 'Stable tag',
				'requires_wp' => 'Requires at least',
				'requires_php' => 'Requires PHP',
			],
			'plugin'
		);

		self::assertSame( $expected, $readme_headers );

		$plugin_headers = $this->get_file_data(
			PLUGIN_MAIN_FILE,
			[
				'requires_wp'  => 'Requires at least',
				'requires_php' => 'Requires PHP',
			]
		);

		self::assertSame( '6.0', $plugin_headers['requires_wp'] );
		self::assertSame( '7.4', $plugin_headers['requires_php'] );
		self::assertStringContainsString( "= " . WOOF_BY_CATEGORY_TEST_VERSION . " =", file_get_contents( $readme_file ) );
	}
}
