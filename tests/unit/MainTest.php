<?php
/**
 * MainTest class file.
 *
 * @package woof-by-category
 */

namespace KAGG\WoofByCategory\Tests\Unit;

use KAGG\WoofByCategory\Main;
use Mockery;
use ReflectionClass;
use ReflectionException;
use tad\FunctionMocker\FunctionMocker;
use WP_Mock;

// phpcs:disable WordPress.WP.AlternativeFunctions.json_encode_json_encode
// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found
// phpcs:disable Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed

/**
 * Class MainTest
 */
class MainTest extends WoofByCategoryTestCase {

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		unset( $GLOBALS['wp_query'], $GLOBALS['sitepress'], $_POST, $_GET, $_REQUEST );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		parent::tearDown();
	}

	/**
	 * Test constructor.
	 *
	 * @throws ReflectionException ReflectionException.
	 */
	public function test_constructor() {
		$classname = Main::class;
		$mock      = Mockery::mock( $classname )->makePartial()->shouldAllowMockingProtectedMethods();

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
		self::assertSame( $required_plugins, $this->get_protected_property( $mock, 'required_plugins' ) );
	}

	/**
	 * Test init().
	 */
	public function test_init() {
		$classname = Main::class;

		/**
		 * Mock.
		 *
		 * @var Mockery\Mock|Main $mock
		 */
		$mock = Mockery::mock( $classname )->makePartial()->shouldAllowMockingProtectedMethods();
		$mock->shouldReceive( 'init_hooks' )->once();

		WP_Mock::userFunction(
			'wp_cache_add_non_persistent_groups',
			[ 'times' => 1 ]
		);

		$mock->init();
	}

	/**
	 * Test init_hooks() when no required plugins activated.
	 *
	 * @throws ReflectionException ReflectionException.
	 */
	public function test_init_hooks_when_no_required_plugins_activated() {
		$basename = 'woof-by-category/woof-by-category.php';
		WP_Mock::userFunction( 'plugin_basename', [ $basename ] );

		$subject = new Main();

		WP_Mock::expectActionAdded( 'admin_init', [ $subject, 'check_requirements' ] );

		WP_Mock::expectFilterNotAdded(
			'pre_option_' . $subject::OPTION_NAME,
			[ $subject, 'wbc_pre_option_woof_by_category_settings' ]
		);
		WP_Mock::expectFilterNotAdded(
			'pre_update_option_' . $subject::OPTION_NAME,
			[ $subject, 'wbc_pre_update_option_woof_by_category_settings' ]
		);

		WP_Mock::expectFilterNotAdded( 'option_woof_settings', [ $subject, 'wbc_option_woof_settings' ] );

		WP_Mock::expectActionNotAdded( 'admin_menu', [ $subject, 'add_settings_page' ] );

		$woof_by_category_file = PLUGIN_PATH . '/woof-by-category.php';

		FunctionMocker::replace(
			'constant',
			static function ( $name ) use ( $woof_by_category_file ) {
				if ( 'WOOF_BY_CATEGORY_FILE' === $name ) {
					return $woof_by_category_file;
				}

				return null;
			}
		);

		WP_Mock::expectFilterNotAdded(
			'plugin_action_links_' . plugin_basename( $woof_by_category_file ),
			[ $subject, 'add_settings_link' ]
		);
		WP_Mock::expectActionNotAdded( 'current_screen', [ $subject, 'setup_fields' ] );
		WP_Mock::expectActionNotAdded( 'plugins_loaded', [ $subject, 'wbc_load_textdomain' ] );
		WP_Mock::expectActionNotAdded( 'admin_enqueue_scripts', [ $subject, 'admin_enqueue_scripts' ] );
		WP_Mock::expectFilterNotAdded(
			'woof_print_content_before_search_form',
			[ $subject, 'woof_print_content_before_search_form_filter' ]
		);

		$method = $this->set_method_accessibility( $subject, 'init_hooks' );
		$method->invoke( $subject );
	}

	/**
	 * Test init_hooks() when required plugins are activated.
	 *
	 * @throws ReflectionException ReflectionException.
	 */
	public function test_init_hooks_when_required_plugins_are_activated() {
		$basename = 'woof-by-category/woof-by-category.php';
		WP_Mock::userFunction( 'plugin_basename', [ $basename ] );

		Mockery::mock( 'WooCommerce' );
		Mockery::mock( 'WOOF' );

		$subject = new Main();

		WP_Mock::expectActionAdded( 'admin_init', [ $subject, 'check_requirements' ] );

		WP_Mock::expectFilterNotAdded(
			'pre_option_' . $subject::OPTION_NAME,
			[ $subject, 'wbc_pre_option_woof_by_category_settings' ]
		);
		WP_Mock::expectFilterNotAdded(
			'pre_update_option_' . $subject::OPTION_NAME,
			[ $subject, 'wbc_pre_update_option_woof_by_category_settings' ]
		);

		WP_Mock::expectFilterAdded( 'option_woof_settings', [ $subject, 'wbc_option_woof_settings' ] );

		WP_Mock::expectActionAdded( 'admin_menu', [ $subject, 'add_settings_page' ] );

		$woof_by_category_file = PLUGIN_PATH . '/woof-by-category.php';

		FunctionMocker::replace(
			'constant',
			static function ( $name ) use ( $woof_by_category_file ) {
				if ( 'WOOF_BY_CATEGORY_FILE' === $name ) {
					return $woof_by_category_file;
				}

				return null;
			}
		);

		WP_Mock::expectFilterAdded(
			'plugin_action_links_' . plugin_basename( $woof_by_category_file ),
			[ $subject, 'add_settings_link' ]
		);
		WP_Mock::expectActionAdded( 'current_screen', [ $subject, 'setup_fields' ] );
		WP_Mock::expectActionAdded( 'plugins_loaded', [ $subject, 'wbc_load_textdomain' ] );
		WP_Mock::expectActionAdded( 'admin_enqueue_scripts', [ $subject, 'admin_enqueue_scripts' ] );
		WP_Mock::expectFilterAdded(
			'woof_print_content_before_search_form',
			[ $subject, 'woof_print_content_before_search_form_filter' ],
			- PHP_INT_MAX
		);

		$method = $this->set_method_accessibility( $subject, 'init_hooks' );
		$method->invoke( $subject );
	}

	/**
	 * Test init_hooks() when required plugins and multilingual plugin are activated.
	 *
	 * @param string $multilingual_plugin Class name of multilingual plugin.
	 *
	 * @dataProvider dp_test_init_hooks_when_required_plugins_and_multilingual_plugin_are_activated
	 * @throws ReflectionException ReflectionException.
	 */
	public function test_init_hooks_when_required_plugins_and_multilingual_plugin_are_activated( $multilingual_plugin ) {
		$basename = 'woof-by-category/woof-by-category.php';
		WP_Mock::userFunction( 'plugin_basename', [ $basename ] );

		Mockery::mock( 'WooCommerce' );
		Mockery::mock( 'WOOF' );
		Mockery::mock( $multilingual_plugin );

		$subject = new Main();

		WP_Mock::expectActionAdded( 'admin_init', [ $subject, 'check_requirements' ] );

		WP_Mock::expectFilterAdded(
			'pre_option_' . $subject::OPTION_NAME,
			[ $subject, 'wbc_pre_option_woof_by_category_settings' ]
		);
		WP_Mock::expectFilterAdded(
			'pre_update_option_' . $subject::OPTION_NAME,
			[ $subject, 'wbc_pre_update_option_woof_by_category_settings' ],
			10,
			2
		);

		WP_Mock::expectFilterAdded( 'option_woof_settings', [ $subject, 'wbc_option_woof_settings' ] );

		WP_Mock::expectActionAdded( 'admin_menu', [ $subject, 'add_settings_page' ] );

		$woof_by_category_file = PLUGIN_PATH . '/woof-by-category.php';

		FunctionMocker::replace(
			'constant',
			static function ( $name ) use ( $woof_by_category_file ) {
				if ( 'WOOF_BY_CATEGORY_FILE' === $name ) {
					return $woof_by_category_file;
				}

				return null;
			}
		);

		WP_Mock::expectFilterAdded(
			'plugin_action_links_' . plugin_basename( $woof_by_category_file ),
			[ $subject, 'add_settings_link' ]
		);
		WP_Mock::expectActionAdded( 'current_screen', [ $subject, 'setup_fields' ] );
		WP_Mock::expectActionAdded( 'plugins_loaded', [ $subject, 'wbc_load_textdomain' ] );
		WP_Mock::expectActionAdded( 'admin_enqueue_scripts', [ $subject, 'admin_enqueue_scripts' ] );
		WP_Mock::expectFilterAdded(
			'woof_print_content_before_search_form',
			[ $subject, 'woof_print_content_before_search_form_filter' ],
			- PHP_INT_MAX
		);

		$method = $this->set_method_accessibility( $subject, 'init_hooks' );
		$method->invoke( $subject );
	}

	/**
	 * Data provider for dp_test_init_hooks_when_required_plugins_and_multilingual_plugin_are_activated
	 *
	 * @return array
	 */
	public function dp_test_init_hooks_when_required_plugins_and_multilingual_plugin_are_activated(): array {
		return [
			'Sitepress' => [ 'Sitepress' ],
			'Polylang'  => [ 'Polylang' ],
		];
	}

	/**
	 * Test get_category_filters() from cache.
	 *
	 * @throws ReflectionException ReflectionException.
	 */
	public function test_get_category_filters_from_cache() {
		$subject = new Main();

		$category_filters = [ 'some_array' ];

		WP_Mock::userFunction(
			'wp_cache_get',
			[
				'args'   => [ Main::class . '::get_category_filters', $subject::CACHE_GROUP ],
				'return' => $category_filters,
				'times'  => 1,
			]
		);

		$method = $this->set_method_accessibility( $subject, 'get_category_filters' );
		self::assertSame( $category_filters, $method->invoke( $subject ) );
	}

	/**
	 * Test get_category_filters().
	 *
	 * @param array|mixed $options  Options.
	 * @param array       $expected Expected.
	 *
	 * @dataProvider dp_test_get_category_filters
	 *
	 * @throws ReflectionException ReflectionException.
	 */
	public function test_get_category_filters( $options, $expected ) {
		global $test_expected;
		$test_expected = $expected;

		$subject = new Main();

		WP_Mock::userFunction(
			'wp_cache_get',
			[
				'args'   => [ Main::class . '::get_category_filters', $subject::CACHE_GROUP ],
				'return' => false,
				'times'  => 1,
			]
		);

		WP_Mock::userFunction(
			'get_option',
			[
				'args'   => [ $subject::OPTION_NAME ],
				'return' => $options,
				'times'  => 1,
			]
		);

		WP_Mock::userFunction(
			'wp_cache_set',
			[
				'args'  => [ Main::class . '::get_category_filters', $expected, $subject::CACHE_GROUP ],
				'times' => 1,
			]
		);

		$method = $this->set_method_accessibility( $subject, 'get_category_filters' );

		self::assertSame( $expected, $method->invoke( $subject ) );
	}

	/**
	 * Data provider for test_get_category_filters
	 *
	 * @return array
	 */
	public function dp_test_get_category_filters(): array {
		$options  = $this->get_test_options();
		$expected = [
			'*'                 =>
				[
					0 => 'pa_weight',
				],
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

	/**
	 * Test wbc_load_textdomain().
	 */
	public function test_wbc_load_textdomain() {
		$subject = new Main();

		WP_Mock::passthruFunction( 'plugin_basename' );

		$woof_by_category_file = PLUGIN_PATH . '/woof-by-category.php';

		FunctionMocker::replace(
			'constant',
			static function ( $name ) use ( $woof_by_category_file ) {
				if ( 'WOOF_BY_CATEGORY_FILE' === $name ) {
					return $woof_by_category_file;
				}

				return null;
			}
		);

		WP_Mock::userFunction( 'load_plugin_textdomain' )->with(
			'woof-by-category',
			false,
			dirname( $woof_by_category_file ) . '/languages/'
		);

		$subject->wbc_load_textdomain();
	}

	/**
	 * Test add_settings_page().
	 */
	public function test_add_settings_page() {
		$subject = new Main();

		WP_Mock::passthruFunction( '__' );
		WP_Mock::userFunction( 'add_submenu_page' )->with(
			'options-general.php',
			'WOOF by Category',
			'WOOF by Category',
			'manage_options',
			'woof-by-category',
			[ $subject, 'woof_by_category_settings_page' ]
		);

		$subject->add_settings_page();

	}

	/**
	 * Test wbc_option_woof_settings().
	 *
	 * @param array $value           Value.
	 * @param array $allowed_filters Allowed filters.
	 * @param array $expected        Expected.
	 *
	 * @dataProvider dp_test_wbc_option_woof_settings
	 */
	public function test_wbc_option_woof_settings( $value, $allowed_filters, $expected ) {
		/**
		 * Mock.
		 *
		 * @var Mockery\Mock|Main $mock
		 */
		$mock = Mockery::mock( Main::class )->shouldAllowMockingProtectedMethods()->makePartial();

		$mock->shouldReceive( 'get_allowed_filters' )->andReturn( $allowed_filters );

		self::assertSame( $expected, $mock->wbc_option_woof_settings( $value ) );
	}

	/**
	 * Data provider for test_wbc_option_woof_settings
	 *
	 * @return array
	 */
	public function dp_test_wbc_option_woof_settings(): array {
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
					'pa_any_text'        => 'radio',
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
					'pa_any_text'        => '',
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
					'pa_any_text'        => '0',
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
					'pa_any_text'        => '0',
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
					'pa_any_text'        => '0',
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
					'pa_any_text'        => '',
					'pa_material'        => '',
					'pa_size'            => '',
					'pa_style'           => '',
					'pa_test-attribute'  => '',
					'pa_weight'          => '',
				],
			'display_in_row'              =>
				[
					'product_visibility' => '0',
					'product_cat'        => '0',
					'product_tag'        => '0',
					'pa_color'           => '1',
					'pa_any_text'        => '0',
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
					'pa_any_text'        => '-1',
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
					'pa_any_text'        => 'ASC',
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
					'pa_any_text'        => 'OR',
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
					'pa_any_text'        => '',
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
					'pa_any_text'        => '',
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
			'plain_overlay_color'         => '',
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
		unset(
			$allowed_value['tax']['pa_material'],
			$allowed_value['tax']['pa_size'],
			$allowed_value['tax']['pa_style'],
			$allowed_value['tax']['pa_weight']
		);

		return [
			'no tax'                  => [ $no_tax_value, [ 'some filters' ], $no_tax_value ],
			'allowed filters is null' => [ $value, null, $value ],
			'allowed filters'         => [ $value, $allowed_filters, $allowed_value ],
		];
	}

	/**
	 * Test wbc_pre_option_woof_by_category_settings().
	 *
	 * @param string|null $lang       Language.
	 * @param bool|array  $lang_value Option for this language.
	 * @param null|array  $value      Option.
	 * @param bool|array  $expected   Expected.
	 *
	 * @dataProvider dp_test_wbc_pre_option_woof_by_category_settings
	 */
	public function test_wbc_pre_option_woof_by_category_settings( $lang, $lang_value, $value, $expected ) {
		/**
		 * Mock.
		 *
		 * @var Mockery\Mock|Main $mock
		 */
		$mock = Mockery::mock( Main::class )->shouldAllowMockingProtectedMethods()->makePartial();

		$mock->shouldReceive( 'get_current_language' )->andReturn( $lang );

		WP_Mock::userFunction( 'get_option' )->with( Main::OPTION_NAME . '_' . $lang )
			->andReturn( $lang_value );
		WP_Mock::userFunction( 'get_option' )->with( Main::OPTION_NAME )->andReturn( $value );

		if ( ! $lang_value ) {
			WP_Mock::userFunction( 'remove_filter' )
				->with(
					'pre_option_' . Main::OPTION_NAME,
					[ $mock, 'wbc_pre_option_woof_by_category_settings' ]
				)
				->once();

			WP_Mock::expectFilterAdded(
				'pre_option_' . Main::OPTION_NAME,
				[ $mock, 'wbc_pre_option_woof_by_category_settings' ]
			);

			WP_Mock::userFunction( 'get_term_by' )->andReturnUsing(
				function ( $field, $value ) {
					return (object) [ 'slug' => $value ];
				}
			);

			if ( is_array( $value ) ) {
				array_pop( $value ); // Remove last element, which is empty.
			}

			WP_Mock::userFunction( 'update_option' )
				->with( Main::OPTION_NAME . '_' . $lang, $value );
		}

		self::assertSame( $expected, $mock->wbc_pre_option_woof_by_category_settings() );
	}

	/**
	 * Data provider for test_wbc_pre_option_woof_by_category_settings
	 *
	 * @return array
	 */
	public function dp_test_wbc_pre_option_woof_by_category_settings(): array {
		$options        = $this->get_test_options();
		$popped_options = $options;
		array_pop( $popped_options );

		$options_ru = $this->get_test_options_ru();

		return [
			'no language, no option'                  => [ null, false, false, false ],
			'no language, option exists'              => [ null, false, $options, $popped_options ],
			'no language, option with no lang exists' => [ null, $options, null, $options ],
			'ru, option_ru exists'                    => [ 'ru', $options_ru, null, $options_ru ],
			'ru, option_ru does not exist'            => [ 'ru', false, $options, $popped_options ],
		];
	}

	/**
	 * Test wbc_pre_update_option_woof_by_category_settings().
	 *
	 * @param string|null $lang         Language.
	 * @param string|null $default_lang Default language.
	 * @param null|array  $value        Option.
	 * @param null|array  $old_value    Old option.
	 * @param bool|array  $expected     Expected.
	 *
	 * @dataProvider dp_wbc_pre_update_option_woof_by_category_settings
	 */
	public function test_wbc_pre_update_option_woof_by_category_settings( $lang, $default_lang, $value, $old_value, $expected ) {
		/**
		 * Mock.
		 *
		 * @var Mockery\Mock|Main $mock
		 */
		$mock = Mockery::mock( Main::class )->shouldAllowMockingProtectedMethods()->makePartial();

		$mock->shouldReceive( 'get_current_language' )->once()->andReturn( $lang );
		$mock->shouldReceive( 'get_default_language' )->once()->andReturn( $default_lang );

		WP_Mock::userFunction( 'update_option' )->with( Main::OPTION_NAME . '_' . $lang, $value );
		WP_Mock::userFunction( 'update_option' )->with( Main::OPTION_NAME, $value );

		if ( $lang === $default_lang ) {
			WP_Mock::userFunction( 'remove_filter' )
				->with(
					'pre_option_' . Main::OPTION_NAME,
					[ $mock, 'wbc_pre_option_woof_by_category_settings' ]
				)
				->once();

			WP_Mock::userFunction( 'remove_filter' )
				->with(
					'pre_update_option_' . Main::OPTION_NAME,
					[ $mock, 'wbc_pre_update_option_woof_by_category_settings' ]
				)
				->once();

			WP_Mock::expectFilterAdded(
				'pre_option_' . Main::OPTION_NAME,
				[ $mock, 'wbc_pre_option_woof_by_category_settings' ]
			);

			WP_Mock::expectFilterAdded(
				'pre_update_option_' . Main::OPTION_NAME,
				[ $mock, 'wbc_pre_update_option_woof_by_category_settings' ],
				10,
				2
			);

			WP_Mock::userFunction( 'update_option' )
				->with( Main::OPTION_NAME, $value );

		}

		self::assertSame( $expected, $mock->wbc_pre_update_option_woof_by_category_settings( $value, $old_value ) );
	}

	/**
	 * Data provider for test_wbc_pre_update_option_woof_by_category_settings
	 *
	 * @return array
	 */
	public function dp_wbc_pre_update_option_woof_by_category_settings(): array {
		$options        = $this->get_test_options();
		$popped_options = $options;
		array_pop( $popped_options );

		$options_ru = $this->get_test_options_ru();
		$options_en = $this->get_test_options_en();

		return [
			'no language, no option'             => [ null, null, false, false, false ],
			'no language, option, no old option' => [ null, null, $options, false, false ],
			'no language, option, old option'    => [ null, null, $options, [], [] ],
			'en, option_en'                      => [ 'en', 'en', $options_ru, [], [] ],
			'ru, option_ru'                      => [ 'ru', 'en', $options_en, [], [] ],
		];
	}

	/**
	 * Test get_default_language().
	 *
	 * @throws ReflectionException ReflectionException.
	 */
	public function test_get_default_language() {
		$sitepress_exists = false;
		$polylang_exists  = false;

		$wpml_default_language = 'en';
		$pll_default_language  = 'ru';

		FunctionMocker::replace(
			'class_exists',
			static function ( $class_name ) use ( &$sitepress_exists, &$polylang_exists ) {
				if ( 'SitePress' === $class_name ) {
					return $sitepress_exists;
				}
				if ( 'Polylang' === $class_name ) {
					return $polylang_exists;
				}

				return null;
			}
		);

		$sitepress = Mockery::mock( 'SitePress' );
		$sitepress->shouldReceive( 'get_default_language' )->andReturn( $wpml_default_language );

		$GLOBALS['sitepress'] = $sitepress;

		WP_Mock::userFunction( 'pll_default_language' )->with()->andReturn( $pll_default_language );

		$subject = new Main();

		$method = $this->set_method_accessibility( $subject, 'get_default_language' );

		self::assertNull( $method->invoke( $subject ) );

		$sitepress_exists = true;
		self::assertSame( $wpml_default_language, $method->invoke( $subject ) );

		$sitepress_exists = false;
		$polylang_exists  = true;
		self::assertSame( 'pll_' . $pll_default_language, $method->invoke( $subject ) );
	}

	/**
	 * Test get_current_language().
	 *
	 * @throws ReflectionException ReflectionException.
	 */
	public function test_get_current_language() {
		$sitepress_exists = false;
		$polylang_exists  = false;

		$wpml_current_language = 'en';
		$pll_current_language  = 'ru';

		FunctionMocker::replace(
			'class_exists',
			static function ( $class_name ) use ( &$sitepress_exists, &$polylang_exists ) {
				if ( 'SitePress' === $class_name ) {
					return $sitepress_exists;
				}
				if ( 'Polylang' === $class_name ) {
					return $polylang_exists;
				}

				return null;
			}
		);

		$sitepress = Mockery::mock( 'SitePress' );
		$sitepress->shouldReceive( 'get_current_language' )->andReturn( $wpml_current_language );

		$GLOBALS['sitepress'] = $sitepress;

		WP_Mock::userFunction( 'pll_current_language' )->with()->andReturn( $pll_current_language );

		$subject = new Main();

		$method = $this->set_method_accessibility( $subject, 'get_current_language' );

		self::assertNull( $method->invoke( $subject ) );

		$sitepress_exists = true;
		self::assertSame( $wpml_current_language, $method->invoke( $subject ) );

		$sitepress_exists = false;
		$polylang_exists  = true;
		self::assertSame( 'pll_' . $pll_current_language, $method->invoke( $subject ) );
	}

	/**
	 * Test get_allowed_filters_from_cache().
	 */
	public function test_get_allowed_filters_from_cache() {
		$mock = Mockery::mock( Main::class )->makePartial();
		$mock->shouldAllowMockingProtectedMethods();

		$product_cat = 'some product cat';
		$mock->shouldReceive( 'get_product_cat' )->andReturn( $product_cat );

		$cats = [ $product_cat ];

		$category_filters = [ 'some category_filters' ];
		$mock->shouldReceive( 'get_category_filters' )->andReturn( $category_filters );

		$expected = [ 'some allowed filters' ];

		WP_Mock::userFunction(
			'wp_json_encode',
			[
				'args'   => [ [ $category_filters, $cats ] ],
				'return' => json_encode( [ $category_filters, $cats ] ),
			]
		);

		$key = md5( json_encode( [ $category_filters, $cats ] ) );

		WP_Mock::userFunction(
			'wp_cache_get',
			[
				'args'   => [ $key, $mock::CACHE_GROUP ],
				'return' => $expected,
			]
		);

		self::assertSame( $expected, $mock->get_allowed_filters() );
	}

	/**
	 * Test get_allowed_filters().
	 *
	 * @param string $product_cat      Product category.
	 * @param array  $category_filters Category filters.
	 * @param array  $allowed_filters  Allowed filters.
	 * @param array  $expected         Expected.
	 *
	 * @dataProvider dp_test_get_allowed_filters
	 */
	public function test_get_allowed_filters( $product_cat, $category_filters, $allowed_filters, $expected ) {
		$mock = Mockery::mock( Main::class )->makePartial();
		$mock->shouldAllowMockingProtectedMethods();

		$mock->shouldReceive( 'get_product_cat' )->andReturn( $product_cat );
		$cats = explode( ',', $product_cat );

		$mock->shouldReceive( 'get_category_filters' )->andReturn( $category_filters );

		WP_Mock::userFunction(
			'wp_json_encode',
			[
				'args'   => [ [ $category_filters, $cats ] ],
				'return' => json_encode( [ $category_filters, $cats ] ),
			]
		);

		$key = md5( json_encode( [ $category_filters, $cats ] ) );

		WP_Mock::userFunction(
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

		if ( null === $expected ) {
			self::assertNull( $mock->get_allowed_filters() );
		} else {
			$filtered = $expected;

			WP_Mock::onFilter( 'wbc_allowed_filters' )->with( $expected, $cats )->reply( $filtered );

			$mock->shouldReceive( 'get_default_filters' )->with()->andReturn( $category_filters['*'] );

			WP_Mock::userFunction(
				'wp_cache_set',
				[
					'args' => [ $key, $filtered, $mock::CACHE_GROUP ],
				]
			);
			self::assertSame( $filtered, $mock->get_allowed_filters() );
		}
	}

	/**
	 * Data provider for test_get_allowed_filters
	 *
	 * @return array
	 */
	public function dp_test_get_allowed_filters(): array {
		$category_filters = [
			'*'                 =>
				[
					0 => 'pa_weight',
				],
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
			'simple-products'   => [],
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
			'default filters'    => [
				'simple-products',
				$category_filters,
				[
					'simple-products' => [],
				],
				[ 'pa_weight' ],
			],
		];
	}

	/**
	 * Tests get_allowed_filters() for single category.
	 *
	 * @param array  $category_filters Category filters.
	 * @param string $current_cat      Current category.
	 * @param array  $distances        Distances.
	 * @param array  $expected         Expected.
	 *
	 * @dataProvider dp_test_get_allowed_filters_for_single_category
	 */
	public function test_get_allowed_filters_for_single_category( $category_filters, $current_cat, $distances, $expected ) {
		$mock = Mockery::mock( Main::class )->makePartial();
		$mock->shouldAllowMockingProtectedMethods();

		$i = 0;
		foreach ( $category_filters as $filter_cat => $filters ) {
			$mock->shouldReceive( 'has_parent' )->with( $filter_cat, $current_cat )->andReturn( $distances[ $i ] );
			$i ++;
		}

		self::assertSame( $expected, $mock->get_allowed_filters_for_single_category( $category_filters, $current_cat ) );
	}

	/**
	 * Data provider for test_get_allowed_filters_for_single_category
	 *
	 * @return array
	 */
	public function dp_test_get_allowed_filters_for_single_category(): array {
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
	 * Tests get_default_filters().
	 *
	 * @param array $options  Options.
	 * @param array $expected Expected.
	 *
	 * @dataProvider dp_test_get_default_filters
	 */
	public function test_get_default_filters( $options, $expected ) {
		$mock = Mockery::mock( Main::class )->makePartial();
		$mock->shouldAllowMockingProtectedMethods();

		WP_Mock::userFunction( 'get_option' )->with( $mock::OPTION_NAME )->andReturn( $options );

		self::assertSame( $expected, $mock->get_default_filters() );
	}

	/**
	 * Data provider for test_get_default_filters().
	 *
	 * @return array
	 */
	public function dp_test_get_default_filters(): array {
		$options = $this->get_test_options();

		return [
			'no option'   => [
				[],
				[],
			],
			'no default'  => [
				[
					0 => [
						'category' => 'some-category',
						'filters'  => [ 'some filter' ],
					],
				],
				[],
			],
			'has default' => [
				$options,
				$options[0]['filters'],
			],
		];
	}

	/**
	 * Test get_product_cat().
	 *
	 * @param string $category_from_woof Category from WOOF.
	 * @param array  $query_vars         Query vars.
	 * @param bool   $is_tax             Is tax.
	 * @param array  $object_types       Object types.
	 * @param bool   $is_shop            Is shop.
	 * @param bool   $is_product         Is product.
	 * @param string $expected           Expected.
	 *
	 * @dataProvider dp_test_get_product_cat
	 */
	public function test_get_product_cat( $category_from_woof, $query_vars, $is_tax, $object_types, $is_shop, $is_product, $expected ) {
		$mock = Mockery::mock( Main::class )->makePartial();
		$mock->shouldAllowMockingProtectedMethods();

		$mock->shouldReceive( 'get_category_from_woof' )->andReturn( $category_from_woof );

		$wp_query = (object) [ 'query_vars' => $query_vars ];

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wp_query'] = $wp_query;

		WP_Mock::userFunction( 'is_tax' )->with()->andReturn( $is_tax );

		if ( $is_tax ) {
			$queried_object = (object) [
				'term_id'  => 1044,
				'slug'     => $expected,
				'taxonomy' => 'pwb-brand',
			];

			$current_taxonomy = (object) [
				'name'        => 'pwb-brand',
				'object_type' => $object_types,
			];

			WP_Mock::userFunction( 'get_queried_object' )->with()->andReturn( $queried_object );
			WP_Mock::userFunction( 'get_taxonomy' )->with( $queried_object->taxonomy )->andReturn( $current_taxonomy );
		}

		WP_Mock::userFunction( 'is_shop' )->with()->andReturn( $is_shop );
		WP_Mock::userFunction( 'is_product' )->with()->andReturn( $is_product );

		self::assertSame( $expected, $mock->get_product_cat() );
	}

	/**
	 * Data provider for dp_test_get_product_cat
	 *
	 * @return array
	 */
	public function dp_test_get_product_cat(): array {
		return [
			'product_cat'             => [
				'assumenda,quisquam',
				[ 'some_query_vars' ],
				null,
				null,
				null,
				null,
				'assumenda,quisquam',
			],
			'query_vars'              => [
				false,
				[ 'product_cat' => 'assumenda' ],
				null,
				null,
				null,
				null,
				'assumenda',
			],
			'is_tax=true, product'    => [ false, [ 'some_query_vars' ], true, [ 'product' ], false, null, 'la-mer' ],
			'is_tax=true, other type' => [ false, [ 'some_query_vars' ], true, [ 'other' ], false, null, null ],
			'is_tax=true, no type'    => [ false, [ 'some_query_vars' ], true, [], false, null, null ],
			'is_shop=false'           => [ false, [ 'some_query_vars' ], null, null, false, null, null ],
			'is_shop=true'            => [ false, [ 'some_query_vars' ], null, null, true, null, '/' ],
			'is_product=false'        => [ false, [ 'some_query_vars' ], null, null, null, false, null ],
			'is_product=true'         => [ false, [ 'some_query_vars' ], null, null, null, true, '/' ],
		];
	}

	/**
	 * Tests get_category_from_woof().
	 *
	 * @param array  $post               POST.
	 * @param array  $get                GET.
	 * @param bool   $is_wp_error        Is WP_Error.
	 * @param string $woof_shortcode_txt WOOF shortcode text.
	 * @param string $additional_taxes   Additional taxes.
	 * @param string $expected           Expected.
	 *
	 * @dataProvider dp_test_get_category_from_woof
	 * @noinspection HttpUrlsUsage
	 */
	public function test_get_category_from_woof(
		$post, $get, $is_wp_error, $woof_shortcode_txt, $additional_taxes, $expected
	) {
		$mock = Mockery::mock( Main::class )->makePartial();
		$mock->shouldAllowMockingProtectedMethods();
		$mock->shouldReceive( 'expand_additional_taxes' )->andReturn( $additional_taxes );

		$_POST                          = $post;
		$_GET                           = $get;
		$_REQUEST['woof_shortcode_txt'] = $woof_shortcode_txt;
		$_REQUEST['additional_taxes']   = $additional_taxes;

		WP_Mock::passthruFunction( 'wp_unslash' );
		WP_Mock::passthruFunction( 'sanitize_text_field' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$link = $_POST['link'] ?? '';
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$parse_url = parse_url( $link, PHP_URL_QUERY );

		WP_Mock::userFunction(
			'wp_parse_url',
			[
				'args'   => [ $link, PHP_URL_QUERY ],
				'return' => $parse_url,
			]
		);

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$really_curr_tax = $_GET['really_curr_tax'] ?? '';
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$really_curr_tax = explode( '-', $really_curr_tax, 2 );
		if ( count( $really_curr_tax ) < 2 ) {
			$really_curr_tax = [ '', '' ];
		}

		$term = (object) [
			'slug' => $really_curr_tax[1],
		];

		WP_Mock::userFunction(
			'get_term',
			[
				'args'   => [ $really_curr_tax[0], $really_curr_tax[1] ],
				'return' => $term,
			]
		);

		WP_Mock::userFunction(
			'is_wp_error',
			[
				'args'   => [ $term ],
				'return' => $is_wp_error,
			]
		);

		WP_Mock::userFunction( 'wc_get_page_permalink' )->with( 'shop' )->andReturn( 'http://test.test/shop/' );

		WP_Mock::userFunction( 'wp_parse_url' )->andReturnUsing(
			function ( $url, $component ) {
				return parse_url( $url, $component );
			}
		);

		self::assertSame( $expected, $mock->get_category_from_woof() );
	}

	/**
	 * Data provider for test_get_category_from_woof.
	 *
	 * @return array
	 * @noinspection HttpUrlsUsage
	 */
	public function dp_test_get_category_from_woof(): array {
		return [
			'no post, no get'                                              => [
				null,
				null,
				null,
				"woof sid='widget'",
				null,
				false,
			],
			'post, no link'                                                => [
				[
					'action' => 'woof_draw_products',
				],
				null,
				null,
				"woof sid='widget'",
				null,
				false,
			],
			'post, link w/o product_cat'                                   => [
				[
					'action' => 'woof_draw_products',
					'link'   => 'http://test.test/shop/?swoof=1&paged=1',
				],
				null,
				null,
				"woof sid='widget'",
				null,
				'/',
			],
			'post, link with product_cat'                                  => [
				[
					'action' => 'woof_draw_products',
					'link'   => 'http://test.test/shop/?swoof=1&product_cat=assumenda&paged=1',
				],
				null,
				null,
				"woof sid='widget'",
				null,
				'assumenda',
			],
			'swoof, no product_cat'                                        => [
				null,
				[
					'swoof' => '1',
				],
				null,
				"woof sid='widget'",
				null,
				false,
			],
			'swoof, product_cat'                                           => [
				null,
				[
					'swoof'       => '1',
					'product_cat' => 'assumenda,quisquam',
				],
				null,
				"woof sid='widget'",
				null,
				'assumenda,quisquam',
			],
			'really_curr_tax, wrong'                                       => [
				null,
				[
					'really_curr_tax' => 'wrong',
				],
				null,
				"woof sid='widget'",
				null,
				false,
			],
			'really_curr_tax, term ok'                                     => [
				null,
				[
					'really_curr_tax' => '27-product_cat',
				],
				false,
				"woof sid='widget'",
				null,
				'product_cat',
			],
			'really_curr_tax, bad term'                                    => [
				null,
				[
					'really_curr_tax' => '31-bad',
				],
				true,
				"woof sid='widget'",
				null,
				false,
			],
			'really_curr_tax, hyphen term ok'                              => [
				null,
				[
					'really_curr_tax' => '1044-pwb-brand',
				],
				false,
				"woof sid='widget'",
				null,
				'pwb-brand',
			],
			'woof_shortcode_txt from widget'                               => [
				null,
				null,
				null,
				"woof sid='widget' autosubmit='-1' start_filtering_btn='0' price_filter='0' redirect='' ajax_redraw='0' btn_position='b' dynamic_recount='-1'",
				null,
				false,
			],
			'woof_shortcode_txt from auto_shortcode'                       => [
				null,
				null,
				null,
				"woof sid='auto_shortcode' autohide='price_filter=0'",
				null,
				false,
			],
			'woof_shortcode_txt from page shortcode'                       => [
				null,
				null,
				null,
				"woof is_ajax='1' per_page='15' columns='3' sid='flat_grey woof_auto_1_columns'",
				null,
				false,
			],
			'woof_shortcode_txt from page shortcode with additional taxes' => [
				null,
				null,
				false,
				"woof is_ajax='1' per_page='15' taxonomies='product_cat:27' columns='3'",
				'product_cat:27,45+locations:30,31',
				'product_cat:27,45+locations:30,31',
			],
		];
	}

	/**
	 * Test expand_additional_taxes().
	 *
	 * @param string      $additional_taxes Additional taxes.
	 * @param array       $terms            Terms.
	 * @param array|false $expected         Expected.
	 *
	 * @dataProvider dp_test_expand_additional_taxes
	 */
	public function test_expand_additional_taxes( $additional_taxes, $terms, $expected ) {
		$mock = Mockery::mock( Main::class )->makePartial();
		$mock->shouldAllowMockingProtectedMethods();

		WP_Mock::userFunction( 'get_term' )->andReturnUsing(
			function ( $term_id, $tax_slug ) use ( $terms ) {
				if ( ! is_int( $term_id ) || ! $tax_slug ) {
					return false;
				}

				if ( ! isset( $terms[ $tax_slug ][ $term_id ] ) ) {
					return false;
				}

				return (object) [
					'slug' => $terms[ $tax_slug ][ $term_id ],
				];
			}
		);
		WP_Mock::userFunction( 'is_wp_error' )->andReturnUsing(
			function ( $term ) {
				return false === $term;
			}
		);

		self::assertSame( $expected, $mock->expand_additional_taxes( $additional_taxes ) );
	}

	/**
	 * Data provider for test_expand_additional_taxes().
	 *
	 * @return array
	 */
	public function dp_test_expand_additional_taxes(): array {
		$terms = [
			'product_cat' => [
				27 => 'Assumenda',
				28 => 'Quisquam',
			],
			'locations'   => [
				30 => 'Riga',
				31 => 'Tallinn',
			],
		];

		return [
			'empty'                            => [ '', $terms, false ],
			'no term specified'                => [ 'product_cat', $terms, false ],
			'cat terms specified'              => [ 'product_cat:27,28', $terms, 'Assumenda' ],
			'cat and location terms specified' => [ 'product_cat:28,27+locations:30,31', $terms, 'Quisquam' ],
			'wrong cat term specified'         => [ 'product_cat:28,99', $terms, 'Quisquam' ],
		];
	}

	/**
	 * Test woof_sort_terms_before_out_filter().
	 */
	public function test_woof_sort_terms_before_out_filter() {
		$allowed_filters = [
			0 => 'product_cat',
			1 => 'pa_color',
		];

		$terms = [
			52 =>
				[
					'term_id'  => 52,
					'slug'     => 'uncategorized',
					'taxonomy' => 'product_cat',
					'name'     => 'Uncategorized',
					'count'    => 0,
					'parent'   => 0,
					'children' => [],
				],
			75 =>
				[
					'term_id'  => 75,
					'slug'     => 'some-taxonomy',
					'taxonomy' => 'some_taxonomy',
					'name'     => 'Some taxonomy',
					'count'    => 0,
					'parent'   => 0,
					'children' => [],
				],
		];

		$expected = $terms;
		unset( $expected [75] );

		/**
		 * Mock.
		 *
		 * @var Mockery\Mock|Main $mock
		 */
		$mock = Mockery::mock( Main::class )->shouldAllowMockingProtectedMethods()->makePartial();
		$mock->shouldReceive( 'get_allowed_filters' )->with()->andReturn( $allowed_filters );
		self::assertSame( $expected, $mock->woof_sort_terms_before_out_filter( $terms ) );
	}

	/**
	 * Test has_parent().
	 *
	 * @param string $filter_cat  Filter category.
	 * @param string $current_cat Current category.
	 * @param int    $expected    Expected.
	 *
	 * @dataProvider dp_test_has_parent
	 * @noinspection PhpUnusedLocalVariableInspection
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

		WP_Mock::userFunction(
			'get_term_by',
			[
				'args'   => [ 'slug', $current_cat, 'product_cat' ],
				'return' => $cat_objects[ $current_cat ] ?? false,
			]
		);

		foreach ( $cat_objects as $slug => $cat_object ) {
			WP_Mock::userFunction(
				'get_term',
				[
					'args'   => [ $cat_object->id ],
					'return' => $cat_object,
				]
			);
		}

		WP_Mock::userFunction(
			'get_term',
			[
				'args'   => [ $bad->parent ],
				'return' => false,
			]
		);

		$mock = Mockery::mock( Main::class )->makePartial();
		$mock->shouldAllowMockingProtectedMethods();
		self::assertSame( $expected, $mock->has_parent( $filter_cat, $current_cat ) );
	}

	/**
	 * Data provider for test_has_parent
	 *
	 * @return array
	 */
	public function dp_test_has_parent(): array {
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

	/**
	 * Test add_settings_link().
	 *
	 * @noinspection HtmlUnknownTarget
	 */
	public function test_add_settings_link() {
		$links        = [ 'some_link' => '<a href="#">Some link</a>' ];
		$action_links = [
			'settings' =>
				'<a href="admin.php?page=woof-by-category" aria-label="' .
				'View WOOF by Category settings">' .
				'Settings</a>',
		];
		$expected     = array_merge( $action_links, $links );

		WP_Mock::passthruFunction( 'admin_url' );
		WP_Mock::passthruFunction( 'esc_attr__' );
		WP_Mock::passthruFunction( 'esc_html__' );

		$subject = new Main();
		self::assertSame( $expected, $subject->add_settings_link( $links ) );
	}

	/**
	 * Test admin_enqueue_scripts().
	 *
	 * @noinspection HttpUrlsUsage
	 */
	public function test_admin_enqueue_scripts() {
		$woof_by_category_url     = 'http://site.org/wp-content/plugins/woof-by-category';
		$woof_by_category_version = 'test-version';
		FunctionMocker::replace(
			'constant',
			static function ( $name ) use ( $woof_by_category_url, $woof_by_category_version ) {
				if ( 'WOOF_BY_CATEGORY_URL' === $name ) {
					return $woof_by_category_url;
				}

				if ( 'WOOF_BY_CATEGORY_VERSION' === $name ) {
					return $woof_by_category_version;
				}

				return null;
			}
		);

		WP_Mock::userFunction(
			'wp_enqueue_style',
			[
				'args'  => [
					'woof-by-category-admin',
					$woof_by_category_url . '/assets/css/woof-by-category-admin.css',
					[],
					$woof_by_category_version,
				],
				'times' => 1,
			]
		);

		$subject = new Main();
		$subject->admin_enqueue_scripts();
	}

	/**
	 * Get test options.
	 *
	 * @return array
	 */
	private function get_test_options(): array {
		return [
			0 =>
				[
					'category' => '*',
					'filters'  =>
						[
							0 => 'pa_weight',
						],
				],
			1 =>
				[
					'category' => '/',
					'filters'  =>
						[
							0 => 'product_cat',
							1 => 'pa_color',
						],
				],
			2 =>
				[
					'category' => 'assumenda',
					'filters'  =>
						[
							0 => 'product_cat',
							1 => 'pa_color',
							2 => 'pa_material',
						],
				],
			3 =>
				[
					'category' => 'sub-assumenda',
					'filters'  =>
						[
							0 => 'product_cat',
							1 => 'pa_color',
						],
				],
			4 =>
				[
					'category' => 'sub-sub-assumenda',
					'filters'  =>
						[
							0 => 'product_cat',
							1 => 'pa_color',
						],
				],
			5 =>
				[
					'category' => 'quisquam',
					'filters'  =>
						[
							0 => 'product_cat',
							1 => 'pa_size',
							2 => 'pa_weight',
						],
				],
			6 =>
				[
					'category' => '',
					'filters'  => [],
				],
		];
	}

	/**
	 * Get test options ru.
	 *
	 * @return array
	 */
	private function get_test_options_ru(): array {
		return [
			0 =>
				[
					'category' => '/',
				],
			1 =>
				[
					'category' => 'assumenda',
					'filters'  =>
						[
							0 => 'pa_color',
							1 => 'pa_material',
						],
				],
			2 =>
				[
					'category' => 'sab-assumenda',
					'filters'  => [ 0 => 'pa_material' ],
				],
			3 =>
				[
					'category' => '',
					'filters'  => [],
				],
		];
	}

	/**
	 * Get test options en.
	 *
	 * @return array
	 */
	private function get_test_options_en(): array {
		return [
			0 =>
				[
					'category' => '/',
					'filters'  => [ 0 => '' ],
				],
			1 =>
				[
					'category' => 'assumenda',
					'filters'  =>
						[
							0 => 'pa_color',
							1 => 'pa_material',
						],
				],
			2 =>
				[
					'category' => 'sub-assumenda',
					'filters'  => [ 0 => 'pa_color' ],
				],
			3 =>
				[
					'category' => 'sub-sub-assumenda',
					'filters'  => [ 0 => 'pa_color' ],
				],
			4 =>
				[
					'category' => 'quisquam',
					'filters'  =>
						[
							0 => 'pa_size',
							1 => 'pa_weight',
						],
				],
			5 =>
				[
					'category' => '',
					'filters'  => [],
				],
		];
	}
}
