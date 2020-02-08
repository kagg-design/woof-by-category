<?php
/**
 * Test_Woof_By_Category class file.
 *
 * @package woof-by-category
 */

use tad\FunctionMocker\FunctionMocker;

/**
 * Class Test_Woof_By_Category
 */
class Test_Woof_By_Category extends Woof_By_Category_TestCase {

	public function tearDown() {
		unset( $GLOBALS['wp_query'], $_POST, $_GET );

		parent::tearDown();
	}

	/**
	 * @throws ReflectionException
	 */
	public function test_constructor() {
		$classname = 'Woof_By_Category';
		$mock      = \Mockery::mock( $classname )->makePartial()->shouldAllowMockingProtectedMethods();

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

	public function test_init() {
		$classname = 'Woof_By_Category';
		$mock      = \Mockery::mock( $classname )->makePartial()->shouldAllowMockingProtectedMethods();
		$mock->shouldReceive( 'init_hooks' )->once();

		\WP_Mock::userFunction(
			'wp_cache_add_non_persistent_groups',
			[ 'times' => 1 ]
		);

		$mock->init();
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
		\WP_Mock::expectFilterAdded(
			'woof_print_content_before_search_form',
			[ $subject, 'woof_print_content_before_search_form_filter' ]
		);

		$method = $this->set_method_accessibility( $subject, 'init_hooks' );
		$method->invoke( $subject );
	}

	/**
	 * @dataProvider dp_test_init_hooks_when_required_plugins_and_multilingual_plugin_are_activated
	 * @throws ReflectionException
	 */
	public function test_init_hooks_when_required_plugins_and_multilingual_plugin_are_activated( $mulitilingual_plugin ) {
		$basename = 'woof-by-category/woof-by-category.php';
		\WP_Mock::userFunction( 'plugin_basename', [ $basename ] );

		\Mockery::mock( 'WooCommerce' );
		\Mockery::mock( 'WOOF' );
		\Mockery::mock( $mulitilingual_plugin );

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
		\WP_Mock::expectFilterAdded(
			'woof_print_content_before_search_form',
			[ $subject, 'woof_print_content_before_search_form_filter' ]
		);

		$method = $this->set_method_accessibility( $subject, 'init_hooks' );
		$method->invoke( $subject );
	}

	/**
	 * Data provider for dp_test_init_hooks_when_required_plugins_and_multilingual_plugin_are_activated
	 *
	 * @return array
	 */
	public function dp_test_init_hooks_when_required_plugins_and_multilingual_plugin_are_activated() {
		return [
			'Sitepress' => [ 'Sitepress' ],
			'Polylang'  => [ 'Polylang' ],
		];
	}

	/**
	 * @throws ReflectionException
	 */
	public function test_get_category_filters_from_cache() {
		$subject = new Woof_By_Category();

		$category_filters = [ 'some_array' ];

		\WP_Mock::userFunction(
			'wp_cache_get',
			[
				'args'   => [ 'Woof_By_Category::get_category_filters', $subject::CACHE_GROUP ],
				'return' => $category_filters,
				'times'  => 1,
			]
		);

		$method = $this->set_method_accessibility( $subject, 'get_category_filters' );
		$this->assertSame( $category_filters, $method->invoke( $subject ) );
	}

	/**
	 * @param array|mixed $options
	 * @param array       $expected
	 *
	 * @dataProvider dp_test_get_category_filters
	 *
	 * @throws ReflectionException
	 */
	public function test_get_category_filters( $options, $expected ) {
		global $test_expected;
		$test_expected = $expected;

		$subject = new Woof_By_Category();

		\WP_Mock::userFunction(
			'wp_cache_get',
			[
				'args'   => [ 'Woof_By_Category::get_category_filters', $subject::CACHE_GROUP ],
				'return' => false,
				'times'  => 1,
			]
		);

		\WP_Mock::userFunction(
			'get_option',
			[
				'args'   => [ $subject::OPTION_NAME ],
				'return' => $options,
				'times'  => 1,
			]
		);

		\WP_Mock::userFunction(
			'wp_cache_set',
			[
				'args'  => [ 'Woof_By_Category::get_category_filters', $expected, $subject::CACHE_GROUP ],
				'times' => 1,
			]
		);

		$method = $this->set_method_accessibility( $subject, 'get_category_filters' );

		$this->assertSame( $expected, $method->invoke( $subject ) );
	}

	/**
	 * Data provider for test_get_category_filters
	 *
	 * @return array
	 */
	public function dp_test_get_category_filters() {
		$options  = [
			0 =>
				[
					'category' => '/',
					'filters'  =>
						[
							0 => 'product_cat',
							1 => 'pa_color',
						],
				],
			1 =>
				[
					'category' => 'assumenda',
					'filters'  =>
						[
							0 => 'product_cat',
							1 => 'pa_color',
							2 => 'pa_material',
						],
				],
			2 =>
				[
					'category' => 'sub-assumenda',
					'filters'  =>
						[
							0 => 'product_cat',
							1 => 'pa_color',
						],
				],
			3 =>
				[
					'category' => 'sub-sub-assumenda',
					'filters'  =>
						[
							0 => 'product_cat',
							1 => 'pa_color',
						],
				],
			4 =>
				[
					'category' => 'quisquam',
					'filters'  =>
						[
							0 => 'product_cat',
							1 => 'pa_size',
							2 => 'pa_weight',
						],
				],
			5 =>
				[
					'category' => '',
					'filters'  =>
						[
						],
				],
		];
		$expected = [
			'/'                 =>
				[
					0 => 'product_cat',
					1 => 'pa_color',
				],
			'assumenda'         =>
				[
					0 => 'product_cat',
					1 => 'pa_color',
					2 => 'pa_material',
				],
			'sub-assumenda'     =>
				[
					0 => 'product_cat',
					1 => 'pa_color',
				],
			'sub-sub-assumenda' =>
				[
					0 => 'product_cat',
					1 => 'pa_color',
				],
			'quisquam'          =>
				[
					0 => 'product_cat',
					1 => 'pa_size',
					2 => 'pa_weight',
				],
		];

		return [
			'no options'              => [ false, [] ],
			'options'                 => [ $options, $expected ],
			'options with no filters' => [
				array_merge( $options, [ [ 'category' => 'no_filters_cat' ] ] ),
				array_merge( $expected, [ 'no_filters_cat' => null ] ),
			],
		];
	}

	public function test_wbc_load_textdomain() {
		$subject = new Woof_By_Category();

		\WP_Mock::passthruFunction( 'plugin_basename' );
		\WP_Mock::userFunction( 'load_plugin_textdomain' )->with(
			'woof-by-category',
			false,
			dirname( WOOF_BY_CATEGORY_FILE ) . '/languages/'
		);

		$subject->wbc_load_textdomain();
	}

	public function test_add_settings_page() {
		$subject = new Woof_By_Category();

		\WP_Mock::passthruFunction( '__' );
		\WP_Mock::userFunction( 'add_menu_page' )->with(
			'WOOF by Category',
			'WOOF by Category',
			'manage_options',
			'woof-by-category',
			[ $subject, 'woof_by_category_settings_page' ],
			'dashicons-filter'
		);

		$subject->add_settings_page();

	}

	/**
	 * @param $value
	 * @param $expected
	 *
	 * @dataProvider dp_test_wbc_option_woof_settings
	 */
	public function test_wbc_option_woof_settings( $value, $allowed_filters, $expected ) {
		$mock = \Mockery::mock( 'Woof_By_Category' )->shouldAllowMockingProtectedMethods()->makePartial();

		$mock->shouldReceive( 'get_allowed_filters' )->andReturn( $allowed_filters );

		$this->assertSame( $expected, $mock->wbc_option_woof_settings( $value ) );
	}

	/**
	 * Data provider for test_wbc_option_woof_settings
	 *
	 * @return array
	 */
	public function dp_test_wbc_option_woof_settings() {
		$value = [
			'items_order'                 => '',
			'by_price'                    =>
				[
					'show'              => '0',
					'tooltip_text'      => '',
					'show_button'       => '0',
					'title_text'        => '',
					'ranges'            => '',
					'first_option_text' => '',
					'ion_slider_step'   => '1',
					'price_tax'         => '0',
				],
			'tax_type'                    =>
				[
					'product_visibility' => 'radio',
					'product_cat'        => 'mselect',
					'product_tag'        => 'radio',
					'pa_color'           => 'checkbox',
					'pa_lyuboj-tekst'    => 'radio',
					'pa_material'        => 'radio',
					'pa_size'            => 'checkbox',
					'pa_style'           => 'radio',
					'pa_test-attribute'  => 'radio',
					'pa_weight'          => 'checkbox',
				],
			'excluded_terms'              =>
				[
					'product_visibility' => '',
					'product_cat'        => '',
					'product_tag'        => '',
					'pa_color'           => '',
					'pa_lyuboj-tekst'    => '',
					'pa_material'        => '',
					'pa_size'            => '',
					'pa_style'           => '',
					'pa_test-attribute'  => '',
					'pa_weight'          => '',
				],
			'tax_block_height'            =>
				[
					'product_visibility' => '0',
					'product_cat'        => '0',
					'product_tag'        => '0',
					'pa_color'           => '0',
					'pa_lyuboj-tekst'    => '0',
					'pa_material'        => '0',
					'pa_size'            => '0',
					'pa_style'           => '0',
					'pa_test-attribute'  => '0',
					'pa_weight'          => '0',
				],
			'show_title_label'            =>
				[
					'product_visibility' => '0',
					'product_cat'        => '1',
					'product_tag'        => '0',
					'pa_color'           => '1',
					'pa_lyuboj-tekst'    => '0',
					'pa_material'        => '1',
					'pa_size'            => '1',
					'pa_style'           => '0',
					'pa_test-attribute'  => '0',
					'pa_weight'          => '1',
				],
			'show_toggle_button'          =>
				[
					'product_visibility' => '0',
					'product_cat'        => '2',
					'product_tag'        => '0',
					'pa_color'           => '2',
					'pa_lyuboj-tekst'    => '0',
					'pa_material'        => '2',
					'pa_size'            => '2',
					'pa_style'           => '0',
					'pa_test-attribute'  => '0',
					'pa_weight'          => '2',
				],
			'tooltip_text'                =>
				[
					'product_visibility' => '',
					'product_cat'        => '',
					'product_tag'        => '',
					'pa_color'           => '',
					'pa_lyuboj-tekst'    => '',
					'pa_material'        => '',
					'pa_size'            => '',
					'pa_style'           => '',
					'pa_test-attribute'  => '',
					'pa_weight'          => '',
				],
			'dispay_in_row'               =>
				[
					'product_visibility' => '0',
					'product_cat'        => '0',
					'product_tag'        => '0',
					'pa_color'           => '1',
					'pa_lyuboj-tekst'    => '0',
					'pa_material'        => '1',
					'pa_size'            => '1',
					'pa_style'           => '0',
					'pa_test-attribute'  => '0',
					'pa_weight'          => '1',
				],
			'orderby'                     =>
				[
					'product_visibility' => '-1',
					'product_cat'        => '-1',
					'product_tag'        => '-1',
					'pa_color'           => '-1',
					'pa_lyuboj-tekst'    => '-1',
					'pa_material'        => '-1',
					'pa_size'            => '-1',
					'pa_style'           => '-1',
					'pa_test-attribute'  => '-1',
					'pa_weight'          => '-1',
				],
			'order'                       =>
				[
					'product_visibility' => 'ASC',
					'product_cat'        => 'ASC',
					'product_tag'        => 'ASC',
					'pa_color'           => 'ASC',
					'pa_lyuboj-tekst'    => 'ASC',
					'pa_material'        => 'ASC',
					'pa_size'            => 'ASC',
					'pa_style'           => 'ASC',
					'pa_test-attribute'  => 'ASC',
					'pa_weight'          => 'ASC',
				],
			'comparison_logic'            =>
				[
					'product_visibility' => 'OR',
					'product_cat'        => 'OR',
					'product_tag'        => 'OR',
					'pa_color'           => 'OR',
					'pa_lyuboj-tekst'    => 'OR',
					'pa_material'        => 'OR',
					'pa_size'            => 'OR',
					'pa_style'           => 'OR',
					'pa_test-attribute'  => 'OR',
					'pa_weight'          => 'OR',
				],
			'custom_tax_label'            =>
				[
					'product_visibility' => '',
					'product_cat'        => '',
					'product_tag'        => '',
					'pa_color'           => '',
					'pa_lyuboj-tekst'    => '',
					'pa_material'        => '',
					'pa_size'            => '',
					'pa_style'           => '',
					'pa_test-attribute'  => '',
					'pa_weight'          => '',
				],
			'not_toggled_terms_count'     =>
				[
					'product_visibility' => '',
					'product_cat'        => '',
					'product_tag'        => '',
					'pa_color'           => '',
					'pa_lyuboj-tekst'    => '',
					'pa_material'        => '',
					'pa_size'            => '',
					'pa_style'           => '',
					'pa_test-attribute'  => '',
					'pa_weight'          => '',
				],
			'tax'                         =>
				[
					'product_cat' => '1',
					'pa_color'    => '1',
					'pa_material' => '1',
					'pa_size'     => '1',
					'pa_style'    => '1',
					'pa_weight'   => '1',
				],
			'icheck_skin'                 => 'square_square',
			'overlay_skin'                => 'default',
			'overlay_skin_bg_img'         => '',
			'plainoverlay_color'          => '',
			'default_overlay_skin_word'   => '',
			'use_chosen'                  => '1',
			'use_beauty_scroll'           => '1',
			'ion_slider_skin'             => 'skinNice',
			'use_tooltip'                 => '1',
			'woof_tooltip_img'            => '',
			'woof_auto_hide_button_img'   => '',
			'woof_auto_hide_button_txt'   => '',
			'woof_auto_subcats_plus_img'  => '',
			'woof_auto_subcats_minus_img' => '',
			'toggle_type'                 => 'text',
			'toggle_opened_text'          => '',
			'toggle_closed_text'          => '',
			'toggle_opened_image'         => '',
			'toggle_closed_image'         => '',
			'custom_front_css'            => '',
			'custom_css_code'             => '',
			'js_after_ajax_done'          => '',
			'init_only_on_reverse'        => '0',
			'init_only_on'                => '',
			'per_page'                    => '-1',
			'optimize_js_files'           => '0',
			'listen_catalog_visibility'   => '0',
			'disable_swoof_influence'     => '0',
			'cache_count_data'            => '0',
			'cache_terms'                 => '0',
			'show_woof_edit_view'         => '1',
			'custom_extensions_path'      => '',
			'activated_extensions'        => '',
		];

		$no_tax_value = $value;
		unset( $no_tax_value['tax'] );

		$allowed_filters = [
			0 => 'product_cat',
			1 => 'pa_color',
		];

		$allowed_value = $value;
		unset( $allowed_value['tax']['pa_material'] );
		unset( $allowed_value['tax']['pa_size'] );
		unset( $allowed_value['tax']['pa_style'] );
		unset( $allowed_value['tax']['pa_weight'] );

		return [
			'no tax'                  => [ $no_tax_value, [ 'some filters' ], $no_tax_value ],
			'allowed filters is null' => [ $value, null, $value ],
			'allowed filters'         => [ $value, $allowed_filters, $allowed_value ],
		];
	}

	public function test_get_allowed_filters_from_cache() {
		$mock = \Mockery::mock( 'Woof_By_Category' )->shouldAllowMockingProtectedMethods()->makePartial();

		$product_cat = 'some product cat';
		$mock->shouldReceive( 'get_product_cat' )->andReturn( $product_cat );

		$cats = [ $product_cat ];

		$category_filters = [ 'some category_filters' ];
		$mock->shouldReceive( 'get_category_filters' )->andReturn( $category_filters );

		$expected = [ 'some allowed filters' ];

		\WP_Mock::userFunction(
			'wp_json_encode',
			[
				'args'   => [ [ $category_filters, $cats ] ],
				'return' => json_encode( [ $category_filters, $cats ] ),
			]
		);

		$key = md5( json_encode( [ $category_filters, $cats ] ) );

		\WP_Mock::userFunction(
			'wp_cache_get',
			[
				'args'   => [ $key, $mock::CACHE_GROUP ],
				'return' => $expected,
			]
		);

		$this->assertSame( $expected, $mock->get_allowed_filters() );
	}

	/**
	 * @param $product_cat
	 * @param $category_filters
	 * @param $allowed_filters
	 * @param $expected
	 *
	 * @dataProvider dp_test_get_allowed_filters
	 */
	public function test_get_allowed_filters( $product_cat, $category_filters, $allowed_filters, $expected ) {
		$mock = \Mockery::mock( 'Woof_By_Category' )->shouldAllowMockingProtectedMethods()->makePartial();

		$mock->shouldReceive( 'get_product_cat' )->andReturn( $product_cat );
		$cats = explode( ',', $product_cat );

		$mock->shouldReceive( 'get_category_filters' )->andReturn( $category_filters );

		\WP_Mock::userFunction(
			'wp_json_encode',
			[
				'args'   => [ [ $category_filters, $cats ] ],
				'return' => json_encode( [ $category_filters, $cats ] ),
			]
		);

		$key = md5( json_encode( [ $category_filters, $cats ] ) );

		\WP_Mock::userFunction(
			'wp_cache_get',
			[
				'args'   => [ $key, $mock::CACHE_GROUP ],
				'return' => false,
			]
		);

		foreach ( $cats as $current_cat ) {
			$mock->shouldReceive( 'get_allowed_filters_for_single_category' )->with( $category_filters, $current_cat )
			     ->andReturn( $allowed_filters[ $current_cat ] );
		}

		\WP_Mock::userFunction(
			'wp_cache_set',
			[
				'args' => [ $key, $expected, $mock::CACHE_GROUP ],
			]
		);

		$this->assertSame( $expected, $mock->get_allowed_filters() );
	}

	/**
	 * Data provider for test_get_allowed_filters
	 *
	 * @return array
	 */
	public function dp_test_get_allowed_filters() {
		$category_filters = [
			'/'                 =>
				[
					0 => 'product_cat',
					1 => 'pa_color',
				],
			'assumenda'         =>
				[
					0 => 'product_cat',
					1 => 'pa_color',
					2 => 'pa_material',
				],
			'sub-assumenda'     =>
				[
					0 => 'product_cat',
					1 => 'pa_color',
				],
			'sub-sub-assumenda' =>
				[
					0 => 'product_cat',
					1 => 'pa_color',
				],
			'quisquam'          =>
				[
					0 => 'product_cat',
					1 => 'pa_size',
					2 => 'pa_weight',
				],
		];

		return [
			'null product cat'   => [
				null,
				[ 'filters' ],
				[ '' => 'allowed' ],
				null,
			],
			'assumenda'          => [
				'assumenda',
				$category_filters,
				[ 'assumenda' => [ 'product_cat', 'pa_color', 'pa_material' ] ],
				[ 'product_cat', 'pa_color', 'pa_material' ],
			],
			'assumenda,quisquam' => [
				'assumenda,quisquam',
				$category_filters,
				[
					'assumenda' => [ 'product_cat', 'pa_color', 'pa_material' ],
					'quisquam'  => [ 'product_cat', 'pa_size', 'pa_weight' ],
				],
				[ 'product_cat', 'pa_color', 'pa_material', 'pa_size', 'pa_weight' ],
			],
		];
	}

	/**
	 * @param $category_filters
	 * @param $current_cat
	 * @param $distances
	 * @param $expected
	 *
	 * @dataProvider dp_test_get_allowed_filters_for_single_category
	 */
	public function test_get_allowed_filters_for_single_category( $category_filters, $current_cat, $distances, $expected ) {
		$mock = \Mockery::mock( 'Woof_By_Category' )->shouldAllowMockingProtectedMethods()->makePartial();

		$i = 0;
		foreach ( $category_filters as $filter_cat => $filters ) {
			$mock->shouldReceive( 'has_parent' )->with( $filter_cat, $current_cat )->andReturn( $distances[ $i ] );
			$i ++;
		}

		$this->assertSame( $expected, $mock->get_allowed_filters_for_single_category( $category_filters, $current_cat ) );
	}

	/**
	 * Data provider for test_get_allowed_filters_for_single_category
	 *
	 * @return array
	 */
	public function dp_test_get_allowed_filters_for_single_category() {
		$category_filters = [
			'/'                 =>
				[
					0 => 'product_cat',
					1 => 'pa_color',
				],
			'assumenda'         =>
				[
					0 => 'product_cat',
					1 => 'pa_color',
					2 => 'pa_material',
				],
			'sub-assumenda'     =>
				[
					0 => 'product_cat',
					1 => 'pa_color',
				],
			'sub-sub-assumenda' =>
				[
					0 => 'product_cat',
					1 => 'pa_color',
				],
			'quisquam'          =>
				[
					0 => 'product_cat',
					1 => 'pa_size',
					2 => 'pa_weight',
				],
		];

		return [
			'no filters'     => [
				[],
				'some_cat',
				[ - 1, - 1, - 1, - 1, - 1 ],
				[],
			],
			'not listed cat' => [
				$category_filters,
				'some_cat',
				[ - 1, - 1, - 1, - 1, - 1 ],
				[],
			],
			'assumenda'      => [
				$category_filters,
				'assumenda',
				[ 1, 0, - 1, - 1, - 1 ],
				[ 'product_cat', 'pa_color', 'pa_material' ],
			],
			'quisquam'       => [
				$category_filters,
				'quisquam',
				[ 1, - 1, - 1, - 1, 0 ],
				[ 'product_cat', 'pa_size', 'pa_weight' ],
			],
			'sub-quisquam'   => [
				$category_filters,
				'quisquam',
				[ 2, - 1, - 1, - 1, 1 ],
				[ 'product_cat', 'pa_size', 'pa_weight' ],
			],
		];
	}

	/**
	 * @param $category_from_woof
	 * @param $query_vars
	 * @param $is_shop
	 * @param $expected
	 *
	 * @dataProvider dp_test_get_product_cat
	 */
	public function test_get_product_cat( $category_from_woof, $query_vars, $is_shop, $expected ) {
		$mock = \Mockery::mock( 'Woof_By_Category' )->shouldAllowMockingProtectedMethods()->makePartial();

		$mock->shouldReceive( 'get_category_from_woof' )->andReturn( $category_from_woof );

		$wp_query = (object) [ 'query_vars' => $query_vars ];

		$GLOBALS['wp_query'] = $wp_query;

		\WP_Mock::userFunction(
			'is_shop',
			[
				'return' => $is_shop,
			]
		);

		$this->assertSame( $expected, $mock->get_product_cat() );
	}

	/**
	 * Data provider for dp_test_get_product_cat
	 *
	 * @return array
	 */
	public function dp_test_get_product_cat() {
		return [
			'product_cat'   => [ 'assumenda,quisquam', [ 'some_query_vars' ], null, 'assumenda,quisquam' ],
			'query_vars'    => [ null, [ 'product_cat' => 'assumenda' ], null, 'assumenda' ],
			'is_shop=false' => [ null, [ 'some_query_vars' ], false, null ],
			'is_shop=true'  => [ null, [ 'some_query_vars' ], true, '/' ],
		];
	}

	/**
	 * @param $post
	 * @param $get
	 * @param $is_wp_error
	 * @param $expected
	 *
	 * @dataProvider dp_test_get_category_from_woof
	 */
	public function test_get_category_from_woof( $post, $get, $is_wp_error, $expected ) {
		$mock = \Mockery::mock( 'Woof_By_Category' )->shouldAllowMockingProtectedMethods()->makePartial();

		$_POST = $post;
		$_GET  = $get;

		\WP_Mock::passthruFunction( 'wp_unslash' );
		\WP_Mock::passthruFunction( 'sanitize_text_field' );

		$link      = isset( $_POST['link'] ) ? $_POST['link'] : '';
		$parse_url = parse_url( $link, PHP_URL_QUERY );

		\WP_Mock::userFunction(
			'wp_parse_url',
			[
				'args'   => [ $link, PHP_URL_QUERY ],
				'return' => $parse_url,
			]
		);

		$really_curr_tax = isset( $_GET['really_curr_tax'] ) ? $_GET['really_curr_tax'] : '';
		$really_curr_tax = explode( '-', $really_curr_tax );
		if ( count( $really_curr_tax ) < 2 ) {
			$really_curr_tax = [ '', '' ];
		}

		$term = (object) [
			'slug' => $really_curr_tax[0],
		];

		\WP_Mock::userFunction(
			'get_term',
			[
				'args'   => [ $really_curr_tax[0], $really_curr_tax[1] ],
				'return' => $term,
			]
		);

		\WP_Mock::userFunction(
			'is_wp_error',
			[
				'args'   => [ $term ],
				'return' => $is_wp_error,
			]
		);

		$this->assertSame( $expected, $mock->get_category_from_woof() );
	}

	/**
	 * Data provider for test_get_category_from_woof.
	 *
	 * @return array
	 */
	public function dp_test_get_category_from_woof() {
		return [
			'no post, no get'             => [
				null,
				null,
				null,
				null,
			],
			'post, no link'               => [
				[
					'action' => 'woof_draw_products',
				],
				null,
				null,
				null,
			],
			'post, link w/o product_cat'  => [
				[
					'action' => 'woof_draw_products',
					'link'   => 'http://test.test/shop/?swoof=1&paged=1',
				],
				null,
				null,
				null,
			],
			'post, link with product_cat' => [
				[
					'action' => 'woof_draw_products',
					'link'   => 'http://test.test/shop/?swoof=1&product_cat=assumenda&paged=1',
				],
				null,
				null,
				'assumenda',
			],
			'swoof, no product_cat'       => [
				null,
				[
					'swoof' => '1',
				],
				null,
				null,
			],
			'swoof, product_cat'          => [
				null,
				[
					'swoof'       => '1',
					'product_cat' => 'assumenda,quisquam',
				],
				null,
				'assumenda,quisquam',
			],
			'really_curr_tax, wrong'      => [
				null,
				[
					'really_curr_tax' => 'wrong',
				],
				null,
				null,
			],
			'really_curr_tax, term ok'    => [
				null,
				[
					'really_curr_tax' => 'assumenda-product',
				],
				false,
				'assumenda',
			],
			'really_curr_tax, bad term'   => [
				null,
				[
					'really_curr_tax' => 'assumenda-product',
				],
				true,
				null,
			],
		];
	}

	/**
	 * @param $filter_cat
	 * @param $current_cat
	 * @param $expected
	 *
	 * @dataProvider dp_test_has_parent
	 */
	public function test_has_parent( $filter_cat, $current_cat, $expected ) {
		$assumenda         = (object) [
			'id'     => 101,
			'parent' => 0,
			'slug'   => 'assumenda',
		];
		$sub_assumenda     = (object) [
			'id'     => 102,
			'parent' => 101,
			'slug'   => 'sub-assumenda',
		];
		$sub_sub_assumenda = (object) [
			'id'     => 103,
			'parent' => 102,
			'slug'   => 'sub-sub-assumenda',
		];
		$bad               = (object) [
			'id'     => 104,
			'parent' => 1111,
			'slug'   => 'bad',
		];
		$cat_objects       = [
			'assumenda'         => $assumenda,
			'sub-assumenda'     => $sub_assumenda,
			'sub-sub-assumenda' => $sub_sub_assumenda,
			'bad'               => $bad,
		];

		\WP_Mock::userFunction(
			'get_term_by',
			[
				'args'   => [ 'slug', $current_cat, 'product_cat' ],
				'return' => isset( $cat_objects[ $current_cat ] ) ? $cat_objects[ $current_cat ] : false,
			]
		);

		foreach ( $cat_objects as $slug => $cat_object ) {
			\WP_Mock::userFunction(
				'get_term_by',
				[
					'args'   => [ 'id', $cat_object->id, 'product_cat' ],
					'return' => $cat_object,
				]
			);
		}

		\WP_Mock::userFunction(
			'get_term_by',
			[
				'args'   => [ 'id', $bad->parent, 'product_cat' ],
				'return' => false,
			]
		);

		$mock = \Mockery::mock( 'Woof_By_Category' )->shouldAllowMockingProtectedMethods()->makePartial();
		$this->assertSame( $expected, $mock->has_parent( $filter_cat, $current_cat ) );
	}

	/**
	 * Data provider for test_has_parent
	 *
	 * @return array
	 */
	public function dp_test_has_parent() {
		return [
			[ 'some_cat', null, - 1 ],
			[ '/', '/', 0 ],
			[ '/', 'wrong_cat', - 1 ],
			[ '/', 'assumenda', - 1 ],
			[ 'assumenda', 'assumenda', 0 ],
			[ 'assumenda', 'sub-assumenda', 1 ],
			[ 'assumenda', 'sub-sub-assumenda', 2 ],
			[ 'assumenda', 'bad', - 1 ],
		];
	}

	public function test_add_settings_link() {
		$links        = [ 'some_link' => '<a href="#">Some link</a>' ];
		$action_links = [
			'settings' =>
				'<a href="' . 'admin.php?page=woof-by-category' . '" aria-label="' .
				'View WOOF by Category settings' . '">' .
				'Settings' . '</a>',
		];
		$expected     = array_merge( $action_links, $links );

		\WP_Mock::passthruFunction( 'admin_url' );
		\WP_Mock::passthruFunction( 'esc_attr__' );
		\WP_Mock::passthruFunction( 'esc_html__' );

		$subject = new Woof_By_Category();
		self::assertSame( $expected, $subject->add_settings_link( $links ) );
	}

	public function test_admin_enqueue_scripts() {
		\WP_Mock::userFunction(
			'wp_enqueue_style',
			[
				'args'  => [
					'woof-by-category-admin',
					WOOF_BY_CATEGORY_URL . '/css/woof-by-category-admin.css',
					[],
					WOOF_BY_CATEGORY_VERSION,
				],
				'times' => 1,
			]
		);

		$subject = new Woof_By_Category();
		$subject->admin_enqueue_scripts();
	}
}
