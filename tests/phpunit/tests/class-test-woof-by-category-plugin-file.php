<?php
/**
 * Test_Woof_By_Category_Plugin_File class file
 *
 * @package woof-by-category
 */

/**
 * Class Test_Woof_By_Category_Plugin_File
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @group plugin-file
 */
class Test_Woof_By_Category_Plugin_File  extends Woof_By_Category_TestCase {

	/**
	 * Test loading of main plugin file.
	 */
	public function test_plugin_file_at_first_time() {
		global $woof_by_category_plugin;

		$mock = Mockery::mock( 'overload:' . Woof_By_Category::class );
		$mock->shouldReceive( 'init' );

		require_once PLUGIN_MAIN_FILE;

		$this->assertInstanceOf( Woof_By_Category::class, $woof_by_category_plugin );
	}
}
