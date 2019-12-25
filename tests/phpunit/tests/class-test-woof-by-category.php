<?php

/**
 * Class Test_Woof_By_Category
 */
class Test_Woof_By_Category extends Woof_By_Category_TestCase {
	public function setUp() {
		parent::setUp();

		\WP_Mock::userFunction(
			'wp_cache_add_non_persistent_groups',
			[ 'times' => 1 ]
		);
	}

	/**
	 * @throws ReflectionException
	 */
	public function test_constructor() {
		$classname = 'Woof_By_Category';
		$mock = \Mockery::mock( $classname )->makePartial()->shouldAllowMockingProtectedMethods();

		// Now call the constructor.
		$reflected_class = new ReflectionClass( $classname );
		$constructor     = $reflected_class->getConstructor();
		$constructor->invoke( $mock );

		$required_plugins = [
			[
				'plugin' => 'woocommerce/woocommerce.php',
				'name'   => 'WooCommerce',
				'slug'   => 'woocommerce',
				'class'  => 'WooCommerce',
				'active' => false,
			],
			[
				'plugin' => 'woocommerce-products-filter/index.php',
				'name'   => 'WooCommerce Product Filter',
				'slug'   => 'woocommerce-products-filter',
				'class'  => 'WOOF',
				'active' => false,
			],
		];
		$this->assertSame( $required_plugins, $this->get_protected_property( $mock, 'required_plugins' ) );
	}

	/**
	 * @throws ReflectionException
	 */
	public function test_init_hooks_when_no_required_plugins_activated() {
		$basename = 'woof-by-category/woof-by-category.php';
		\WP_Mock::userFunction( 'plugin_basename', [ $basename ] );

		$subject = new Woof_By_Category();

		\WP_Mock::expectActionAdded( 'admin_init', [ $subject, 'check_requirements' ] );

		\WP_Mock::expectFilterNotAdded( 'pre_option_' . $subject::OPTION_NAME, [
			$subject,
			'wbc_pre_option_woof_by_category_settings',
		] );
		\WP_Mock::expectFilterNotAdded(
			'pre_update_option_' . $subject::OPTION_NAME,
			[ $subject, 'wbc_pre_update_option_woof_by_category_settings' ]
		);

		\WP_Mock::expectFilterNotAdded( 'option_woof_settings', [ $subject, 'wbc_option_woof_settings' ] );

		\WP_Mock::expectActionNotAdded( 'admin_menu', [ $subject, 'add_settings_page' ] );
		\WP_Mock::expectFilterNotAdded(
			'plugin_action_links_' . plugin_basename( WOOF_BY_CATEGORY_FILE ),
			[ $subject, 'add_settings_link' ]
		);
		\WP_Mock::expectActionNotAdded( 'current_screen', [ $subject, 'setup_fields' ] );
		\WP_Mock::expectActionNotAdded( 'plugins_loaded', [ $subject, 'wbc_load_textdomain' ] );
		\WP_Mock::expectActionNotAdded( 'admin_enqueue_scripts', [ $subject, 'admin_enqueue_scripts' ] );
		\WP_Mock::expectFilterNotAdded( 'request', [ $subject, 'wbc_request_filter' ] );
		\WP_Mock::expectFilterNotAdded( 'woof_get_request_data', [ $subject, 'wbc_get_request_data' ] );
		\WP_Mock::expectFilterNotAdded(
			'woof_print_content_before_search_form',
			[ $subject, 'woof_print_content_before_search_form_filter' ]
		);

		$method = $this->set_method_accessibility( $subject, 'init_hooks' );
		$method->invoke( $subject );
	}

	/**
	 * @throws ReflectionException
	 */
	public function test_init_hooks_when_required_plugins_are_activated() {
		$basename = 'woof-by-category/woof-by-category.php';
		\WP_Mock::userFunction( 'plugin_basename', [ $basename ] );

		\Mockery::mock( 'WooCommerce' );
		\Mockery::mock( 'WOOF' );

		$subject = new Woof_By_Category();

		\WP_Mock::expectActionAdded( 'admin_init', [ $subject, 'check_requirements' ] );

		\WP_Mock::expectFilterNotAdded( 'pre_option_' . $subject::OPTION_NAME, [
			$subject,
			'wbc_pre_option_woof_by_category_settings',
		] );
		\WP_Mock::expectFilterNotAdded(
			'pre_update_option_' . $subject::OPTION_NAME,
			[ $subject, 'wbc_pre_update_option_woof_by_category_settings' ]
		);

		\WP_Mock::expectFilterAdded( 'option_woof_settings', [ $subject, 'wbc_option_woof_settings' ] );

		\WP_Mock::expectActionAdded( 'admin_menu', [ $subject, 'add_settings_page' ] );
		\WP_Mock::expectFilterAdded(
			'plugin_action_links_' . plugin_basename( WOOF_BY_CATEGORY_FILE ),
			[ $subject, 'add_settings_link' ]
		);
		\WP_Mock::expectActionAdded( 'current_screen', [ $subject, 'setup_fields' ] );
		\WP_Mock::expectActionAdded( 'plugins_loaded', [ $subject, 'wbc_load_textdomain' ] );
		\WP_Mock::expectActionAdded( 'admin_enqueue_scripts', [ $subject, 'admin_enqueue_scripts' ] );
		\WP_Mock::expectFilterAdded( 'request', [ $subject, 'wbc_request_filter' ] );
		\WP_Mock::expectFilterAdded( 'woof_get_request_data', [ $subject, 'wbc_get_request_data' ] );
		\WP_Mock::expectFilterAdded(
			'woof_print_content_before_search_form',
			[ $subject, 'woof_print_content_before_search_form_filter' ]
		);

		$method = $this->set_method_accessibility( $subject, 'init_hooks' );
		$method->invoke( $subject );
	}

	/**
	 * @throws ReflectionException
	 */
	public function test_init_hooks_when_required_plugins_and_WPML_are_activated() {
		$basename = 'woof-by-category/woof-by-category.php';
		\WP_Mock::userFunction( 'plugin_basename', [ $basename ] );

		\Mockery::mock( 'WooCommerce' );
		\Mockery::mock( 'WOOF' );
		\Mockery::mock( 'Sitepress' );

		$subject = new Woof_By_Category();

		\WP_Mock::expectActionAdded( 'admin_init', [ $subject, 'check_requirements' ] );

		\WP_Mock::expectFilterAdded( 'pre_option_' . $subject::OPTION_NAME, [
			$subject,
			'wbc_pre_option_woof_by_category_settings',
		] );
		\WP_Mock::expectFilterAdded(
			'pre_update_option_' . $subject::OPTION_NAME,
			[ $subject, 'wbc_pre_update_option_woof_by_category_settings' ],
			10,
			2
		);

		\WP_Mock::expectFilterAdded( 'option_woof_settings', [ $subject, 'wbc_option_woof_settings' ] );

		\WP_Mock::expectActionAdded( 'admin_menu', [ $subject, 'add_settings_page' ] );
		\WP_Mock::expectFilterAdded(
			'plugin_action_links_' . plugin_basename( WOOF_BY_CATEGORY_FILE ),
			[ $subject, 'add_settings_link' ]
		);
		\WP_Mock::expectActionAdded( 'current_screen', [ $subject, 'setup_fields' ] );
		\WP_Mock::expectActionAdded( 'plugins_loaded', [ $subject, 'wbc_load_textdomain' ] );
		\WP_Mock::expectActionAdded( 'admin_enqueue_scripts', [ $subject, 'admin_enqueue_scripts' ] );
		\WP_Mock::expectFilterAdded( 'request', [ $subject, 'wbc_request_filter' ] );
		\WP_Mock::expectFilterAdded( 'woof_get_request_data', [ $subject, 'wbc_get_request_data' ] );
		\WP_Mock::expectFilterAdded(
			'woof_print_content_before_search_form',
			[ $subject, 'woof_print_content_before_search_form_filter' ]
		);

		$method = $this->set_method_accessibility( $subject, 'init_hooks' );
		$method->invoke( $subject );
	}
}
