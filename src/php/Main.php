<?php
/**
 * Main class file.
 *
 * @package woof-by-category
 */

namespace KAGG\WoofByCategory;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WP_Term;

/**
 * Main class.
 *
 * @class Main
 */
class Main {
	/**
	 * Plugin base option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'woof_by_category_settings';

	/**
	 * Admin screen id.
	 *
	 * @var string
	 */
	const SCREEN_ID = 'settings_page_woof-by-category';

	/**
	 * Plugin cache group.
	 *
	 * @var string
	 */
	const CACHE_GROUP = __CLASS__;

	/**
	 * Default filters key.
	 *
	 * @var string
	 */
	const DEFAULT_FILTERS_KEY = '*';

	/**
	 * Required plugins.
	 *
	 * @var array
	 */
	protected $required_plugins = [];

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Order of product categories.
	 *
	 * @var array
	 */
	private $product_cat_order;

	/**
	 * Woof_By_Category constructor.
	 */
	public function __construct() {
		$this->required_plugins = [
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
	}

	/**
	 * Init plugin.
	 */
	public function init() {
		wp_cache_add_non_persistent_groups( [ self::CACHE_GROUP ] );

		$this->init_hooks();
	}

	/**
	 * Init hooks.
	 */
	protected function init_hooks() {
		add_action( 'admin_init', [ $this, 'check_requirements' ] );

		foreach ( $this->required_plugins as $required_plugin ) {
			if ( ! class_exists( $required_plugin['class'] ) ) {
				return;
			}
		}

		add_filter( 'option_woof_settings', [ $this, 'wbc_option_woof_settings' ] );

		if ( class_exists( 'SitePress' ) || class_exists( 'Polylang' ) ) {
			$this->add_option_filters();
		}

		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_filter(
			'plugin_action_links_' . plugin_basename( constant( 'WOOF_BY_CATEGORY_FILE' ) ),
			[ $this, 'add_settings_link' ]
		);
		add_action( 'current_screen', [ $this, 'setup_fields' ] );
		add_action( 'plugins_loaded', [ $this, 'wbc_load_textdomain' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_filter(
			'woof_print_content_before_search_form',
			[ $this, 'woof_print_content_before_search_form_filter' ]
		);

		add_filter(
			'woof_sort_terms_before_out',
			[ $this, 'woof_sort_terms_before_out_filter' ],
			- PHP_INT_MAX
		);

		add_action( 'before_woocommerce_init', [ $this, 'declare_wc_compatibility' ] );
		add_filter( 'admin_footer_text', [ $this, 'admin_footer_text' ] );
		add_filter( 'update_footer', [ $this, 'update_footer' ], PHP_INT_MAX );
	}

	/**
	 * Filter get_option( 'woof_settings' ) to unset WOOF filters not related to the current category.
	 *
	 * @param mixed $value 'woof_settings' option value read from a database.
	 *
	 * @return mixed Modified 'woof_settings'.
	 */
	public function wbc_option_woof_settings( $value ) {
		if ( ! isset( $value['tax'] ) ) {
			return $value;
		}

		$allowed_filters = $this->get_allowed_filters();
		if ( null === $allowed_filters ) {
			return $value;
		}

		foreach ( $value['tax'] as $filter => $filter_value ) {
			if ( ! in_array( $filter, $allowed_filters, true ) ) {
				unset( $value['tax'][ $filter ] );
			}
		}

		return $value;
	}

	/**
	 * Add pre_option filter for plugin options.
	 */
	private function add_pre_option_filter() {
		add_filter( 'pre_option_' . self::OPTION_NAME, [ $this, 'wbc_pre_option_woof_by_category_settings' ] );
	}

	/**
	 * Add pre_update_option filter for plugin options.
	 */
	private function add_pre_update_option_filter() {
		add_filter(
			'pre_update_option_' . self::OPTION_NAME,
			[ $this, 'wbc_pre_update_option_woof_by_category_settings' ],
			10,
			2
		);
	}

	/**
	 * Add filters to get and update plugin options.
	 */
	private function add_option_filters() {
		$this->add_pre_option_filter();
		$this->add_pre_update_option_filter();
	}

	/**
	 * Remove pre_option filter for plugin options.
	 */
	private function remove_pre_option_filter() {
		remove_filter( 'pre_option_' . self::OPTION_NAME, [ $this, 'wbc_pre_option_woof_by_category_settings' ] );
	}

	/**
	 * Remove pre_update_option filter for plugin options.
	 */
	private function remove_pre_update_option_filter() {
		remove_filter(
			'pre_update_option_' . self::OPTION_NAME,
			[ $this, 'wbc_pre_update_option_woof_by_category_settings' ]
		);
	}

	/**
	 * Remove filters to get and update plugin options.
	 */
	private function remove_option_filters() {
		$this->remove_pre_option_filter();
		$this->remove_pre_update_option_filter();
	}

	/**
	 * Filter get_option() of plugin settings to get value for the current language.
	 * Pass through if WPML or Polylang is not active.
	 *
	 * @return mixed Settings for the current WPML language.
	 */
	public function wbc_pre_option_woof_by_category_settings() {
		$lang       = $this->get_current_language();
		$lang_value = get_option( self::OPTION_NAME . '_' . $lang );
		if ( ! $lang_value ) {
			$this->remove_pre_option_filter();
			$value = get_option( self::OPTION_NAME );
			$this->add_pre_option_filter();

			$lang_value = $this->translate_options( $value );
			update_option( self::OPTION_NAME . '_' . $lang, $lang_value );
		}

		return $lang_value;
	}

	/**
	 * Filter update_option() of plugin settings to store value for the current language.
	 * Pass through if WPML or Polylang is not active.
	 *
	 * @param mixed $value     The new, unserialized option value.
	 * @param mixed $old_value The old option value.
	 *
	 * @return mixed Settings for the current WPML language.
	 */
	public function wbc_pre_update_option_woof_by_category_settings( $value, $old_value ) {
		$lang = $this->get_current_language();
		update_option( self::OPTION_NAME . '_' . $lang, $value );

		if ( $this->get_default_language() === $lang ) {
			$this->remove_option_filters();
			update_option( self::OPTION_NAME, $value );
			$this->add_option_filters();
		}

		return $old_value;
	}

	/**
	 * Get the default language.
	 *
	 * @return bool|mixed|string|null
	 * @noinspection PhpUndefinedMethodInspection PhpUndefinedMethodInspection.
	 */
	protected function get_default_language() {
		if ( class_exists( 'SitePress' ) ) {
			global $sitepress;

			return $sitepress->get_default_language();
		}

		if ( class_exists( 'Polylang' ) ) {
			return 'pll_' . pll_default_language();
		}

		return null;
	}

	/**
	 * Get the current language.
	 *
	 * @return bool|mixed|string|null
	 * @noinspection PhpUndefinedMethodInspection PhpUndefinedMethodInspection.
	 */
	protected function get_current_language() {
		if ( class_exists( 'SitePress' ) ) {
			global $sitepress;

			return $sitepress->get_current_language();
		}

		if ( class_exists( 'Polylang' ) ) {
			return 'pll_' . pll_current_language();
		}

		return null;
	}

	/**
	 * Translate options for a new language.
	 *
	 * @param array|mixed $options Plugin options.
	 *
	 * @return array|mixed
	 * @noinspection PhpUnusedLocalVariableInspection PhpUnusedLocalVariableInspection.
	 */
	private function translate_options( $options ) {
		if ( ! $options ) {
			return $options;
		}

		$translated_options = [];
		foreach ( $options as $key => $group ) {
			if ( isset( $group['category'] ) && $group['category'] ) {
				if ( '/' === $group['category'] ) {
					$translated_options[] = $group;
				} else {
					$translated_category = $this->get_term_by_slug( $group['category'] );
					if ( $translated_category ) {
						$group['category']    = $translated_category->slug;
						$translated_options[] = $group;
					}
				}
			}
		}

		return $translated_options;
	}

	/**
	 * Get allowed filters for current categories.
	 *
	 * @return array|null null indicates that we should not change WOOF filters.
	 */
	protected function get_allowed_filters() {
		/**
		 * In theory, there could be a number of product_cat arguments.
		 * But request like
		 * http://test.kagg.eu/?post_type=product&product_cat=assumenda&product_cat=quisquam
		 * returns only one product-category: quisquam (the last one).
		 * It redirects to
		 * http://test.kagg.eu/product-category/quisquam/?post_type=product
		 * Conclusion: WooCommerce can show only one product category on the category page.
		 */
		$product_cat = $this->get_product_cat();
		if ( null === $product_cat ) {
			// Return null to indicate that we should not change WOOF filters.
			return null;
		}

		$cats = explode( ',', $product_cat );

		$category_filters = $this->get_category_filters();

		$cache_key       = md5( wp_json_encode( [ $category_filters, $cats ] ) );
		$allowed_filters = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $allowed_filters ) {
			return $allowed_filters;
		}

		$allowed_filters = [];
		foreach ( $cats as $current_cat ) {
			$allowed_filters[] = $this->get_allowed_filters_for_single_category( $category_filters, $current_cat );
		}
		$allowed_filters = array_merge( [], ...$allowed_filters );

		$allowed_filters = array_values( array_unique( $allowed_filters ) );

		/**
		 * Filters the array of allowed filters.
		 *
		 * @param array $allowed_filters The array of allowed filters for the given set of categories.
		 * @param string[] $cats The array of categories.
		 */
		$allowed_filters = apply_filters( 'wbc_allowed_filters', $allowed_filters, $cats );

		if ( empty( $allowed_filters ) ) {
			$allowed_filters = $this->get_default_filters();
		}

		wp_cache_set( $cache_key, $allowed_filters, self::CACHE_GROUP );

		return $allowed_filters;
	}

	/**
	 * Get allowed filters for the current single category.
	 *
	 * @param array  $category_filters Filters.
	 * @param string $current_cat      Current single category.
	 *
	 * @return array|mixed
	 */
	protected function get_allowed_filters_for_single_category( $category_filters, $current_cat ) {
		$allowed_filters        = [];
		$max_distance_to_parent = PHP_INT_MAX;
		foreach ( $category_filters as $filter_cat => $filters ) {
			$distance_to_parent = $this->has_parent( $filter_cat, $current_cat );
			if (
				0 <= $distance_to_parent &&
				$distance_to_parent < $max_distance_to_parent
			) {
				$max_distance_to_parent = $distance_to_parent;
				$allowed_filters        = $filters ?: [];
			}
		}

		return $allowed_filters;
	}

	/**
	 * Get default filters.
	 *
	 * @return array
	 */
	protected function get_default_filters(): array {
		// Get current settings.
		$options = get_option( self::OPTION_NAME );

		if ( ! $options ) {
			return [];
		}

		foreach ( $options as $option ) {
			if (
				isset( $option['category'] ) &&
				self::DEFAULT_FILTERS_KEY === $option['category']
			) {
				return $option['filters'];
			}
		}

		return [];
	}

	/**
	 * Get product category string.
	 *
	 * @return string|null null indicates that we should not change WOOF filters.
	 */
	protected function get_product_cat() {
		global $wp_query;

		$product_cat = $this->get_category_from_woof();
		if ( null === $product_cat || $product_cat ) {
			return $product_cat;
		}

		if ( isset( $wp_query->query_vars['product_cat'] ) ) {
			return $wp_query->query_vars['product_cat'];
		}

		if ( is_tax() ) {
			$slug = $this->get_current_taxonomy_slug();
			if ( $slug ) {
				return $slug;
			}
		}

		if ( is_shop() || is_product() ) {
			return '/';
		}

		return null;
	}

	/**
	 * Get slug of current taxonomy.
	 *
	 * @return string
	 */
	private function get_current_taxonomy_slug(): string {
		$queried_object = get_queried_object();

		if ( null === $queried_object || ! isset( $queried_object->taxonomy ) ) {
			return '';
		}

		$current_taxonomy = get_taxonomy( $queried_object->taxonomy );

		if ( false === $current_taxonomy ) {
			return '';
		}

		$object_types = $current_taxonomy->object_type;

		if ( ! empty( $object_types ) && in_array( 'product', $object_types, true ) ) {
			return $queried_object->slug;
		}

		return '';
	}

	/**
	 * Get product_cat from WOOF POST/GET variables.
	 *
	 * @return string|false|null
	 * False indicates that no category from WOOF was found.
	 * Null indicates that we should not change WOOF filters.
	 */
	protected function get_category_from_woof() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['action'] ) && ( 'woof_draw_products' === $_POST['action'] ) ) {
			$link = isset( $_POST['link'] ) ? sanitize_text_field( wp_unslash( $_POST['link'] ) ) : '';
			parse_str( wp_parse_url( $link, PHP_URL_QUERY ), $query_arr );
			$cat = $query_arr['product_cat'] ?? false;

			if ( $cat ) {
				return $cat;
			}

			$link_path = wp_parse_url( $link, PHP_URL_PATH );
			$shop_path = wp_parse_url( wc_get_page_permalink( 'shop' ), PHP_URL_PATH );

			if ( $link_path === $shop_path ) {
				return '/';
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$swoof = isset( $_GET['swoof'] ) && sanitize_text_field( wp_unslash( $_GET['swoof'] ) );
		if ( $swoof ) {
			$cat = isset( $_GET['product_cat'] ) ? sanitize_text_field( wp_unslash( $_GET['product_cat'] ) ) : false;

			if ( $cat ) {
				return $cat;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$really_curr_tax = isset( $_GET['really_curr_tax'] ) ? sanitize_text_field( wp_unslash( $_GET['really_curr_tax'] ) ) : '';
		if ( $really_curr_tax ) {
			// Works for widget and subcategory.
			$really_curr_tax = explode( '-', $really_curr_tax, 2 );

			if ( count( $really_curr_tax ) < 2 ) {
				return false;
			}

			$term = get_term( $really_curr_tax[0], $really_curr_tax[1] );

			if ( ! is_wp_error( $term ) ) {
				return $term->slug;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_REQUEST['woof_shortcode_txt'] ) ) {
			if ( false !== strpos( $_REQUEST['woof_shortcode_txt'], "sid='widget'" ) ) {
				// Allow working widget as usual.
				return false;
			}

			if ( false !== strpos( $_REQUEST['woof_shortcode_txt'], "sid='auto_shortcode'" ) ) {
				// Allow working auto_shortcode as usual.
				return false;
			}

			if ( isset( $_REQUEST['additional_taxes'] ) ) {
				// Process additional taxes in the shortcode.
				return $this->expand_additional_taxes( $_REQUEST['additional_taxes'] );
			}
		}
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return false;
	}

	/**
	 * Expand additional taxes and return the first tax from it.
	 *
	 * @todo Allow to return several taxes, and merge filters for them.
	 *
	 * @param string $additional_taxes Additional taxonomies.
	 *
	 * @return array|mixed
	 */
	protected function expand_additional_taxes( $additional_taxes ) {
		if ( ! $additional_taxes ) {
			return false;
		}

		$additional_taxes_array = explode( '+', $additional_taxes );

		$slugs = [];

		foreach ( $additional_taxes_array as $taxes ) {
			$taxes = explode( ':', $taxes );
			if ( 2 !== count( $taxes ) ) {
				continue;
			}
			$tax_slug  = $taxes[0];
			$tax_terms = explode( ',', $taxes[1] );
			foreach ( $tax_terms as $term_id ) {
				$term = get_term( (int) $term_id, $tax_slug );
				if ( ! is_wp_error( $term ) ) {
					$slugs[] = $term->slug;
				}
			}
		}

		return empty( $slugs ) ? false : $slugs[0];
	}

	/**
	 * Get category filters.
	 *
	 * @return array
	 * @noinspection PhpUnusedLocalVariableInspection PhpUnusedLocalVariableInspection.
	 */
	protected function get_category_filters(): array {
		$category_filters = wp_cache_get( __METHOD__, self::CACHE_GROUP );

		if ( false !== $category_filters ) {
			return $category_filters;
		}

		$category_filters = [];

		// Get current settings.
		$options = get_option( self::OPTION_NAME );

		if ( $options ) {
			foreach ( $options as $key => $group ) {
				if ( isset( $group['category'] ) && $group['category'] ) { // Ignore empty groups.
					if ( isset( $group['filters'] ) ) {
						$category_filters[ $group['category'] ] = array_values( $group['filters'] );
					} else {
						$category_filters[ $group['category'] ] = null;
					}
				}
			}
		}

		wp_cache_set( __METHOD__, $category_filters, self::CACHE_GROUP );

		return $category_filters;
	}

	/**
	 * Print script before WOOF form filter to change js object 'woof_current_values'.
	 */
	public function woof_print_content_before_search_form_filter() {
		$allowed_filters = $this->get_allowed_filters();
		if ( null === $allowed_filters ) {
			return;
		}

		$keys_to_delete = [];
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		foreach ( $_GET as $key => $value ) {
			$key = filter_var( $key, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if (
				false !== strpos( $key, 'pa_' ) &&
				! in_array( $key, $allowed_filters, true )
			) {
				$keys_to_delete[] = $key;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $keys_to_delete ) {
			return;
		}

		echo '<script>';
		foreach ( $keys_to_delete as $key ) {
			echo 'delete woof_current_values["' . esc_html( $key ) . '"]; ';
		}
		echo 'jQuery(document).ready(function($){ woof_get_submit_link(); })';
		echo '</script>';
	}

	/**
	 * Filter terms before output.
	 *
	 * @param array|mixed $terms Terms.
	 *
	 * @return array|mixed
	 */
	public function woof_sort_terms_before_out_filter( $terms ) {
		$allowed_filters = $this->get_allowed_filters();

		if ( null === $allowed_filters ) {
			return $terms;
		}

		$allowed_filters = $allowed_filters ?: [];
		foreach ( $terms as $id => $term ) {
			if ( ! in_array( $term['taxonomy'], $allowed_filters, true ) ) {
				unset( $terms[ $id ] );
			}
		}

		return $terms;
	}

	/**
	 * Load plugin text domain.
	 */
	public function wbc_load_textdomain() {
		load_plugin_textdomain(
			'woof-by-category',
			false,
			dirname( plugin_basename( constant( 'WOOF_BY_CATEGORY_FILE' ) ) ) . '/languages/'
		);
	}

	/**
	 * Add the settings page to the menu.
	 */
	public function add_settings_page() {
		$parent_slug = 'options-general.php';
		$page_title  = __( 'WOOF by Category', 'woof-by-category' );
		$menu_title  = __( 'WOOF by Category', 'woof-by-category' );
		$capability  = 'manage_options';
		$menu_slug   = 'woof-by-category';
		$callback    = [ $this, 'woof_by_category_settings_page' ];

		add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback );
	}

	/**
	 * Options page.
	 */
	public function woof_by_category_settings_page() {
		?>
		<div class="wrap">
			<h2 id="title">
				<?php
				// Admin panel title.
				echo( esc_html( __( 'WOOF by Category Plugin Options', 'woof-by-category' ) ) );
				?>
			</h2>

			<form id="wbc-options" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="POST">
				<?php
				settings_fields( 'woof_by_category_group' ); // Hidden protection fields.
				do_settings_sections( 'woof-by-category' ); // Sections with options.
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Is current admin screen the plugin options screen.
	 *
	 * @return bool
	 */
	private function is_wbc_options_screen(): bool {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$current_screen = get_current_screen();

		if ( ! $current_screen ) {
			return false;
		}

		$screen_id = self::SCREEN_ID;

		return 'options' === $current_screen->id || $screen_id === $current_screen->id;
	}

	/**
	 * Setup options fields.
	 */
	public function setup_fields() {
		if ( ! $this->is_wbc_options_screen() ) {
			return;
		}

		$product_categories = array_merge(
			[
				''                        => __( '--Select Category--', 'woof-by-category' ),
				self::DEFAULT_FILTERS_KEY => __( '-Default filters-', 'woof-by-category' ),
				'/'                       => __( '-Shop Page-', 'woof-by-category' ),
			],
			$this->get_product_categories()
		);

		$this->product_cat_order = array_keys( $product_categories );

		$woof_filters = array_merge(
			[ '' => __( '--Select Filters--', 'woof-by-category' ) ],
			$this->get_woof_filters()
		);

		// Get current settings.
		$this->options = get_option( self::OPTION_NAME );

		if ( $this->options ) {
			foreach ( $this->options as $key => $group ) {
				if ( ! ( isset( $group['category'] ) && $group['category'] ) ) {
					// Remove a group with empty categories.
					unset( $this->options[ $key ] );
				}
			}
		} else {
			$this->options = [];
		}

		// Sort settings array in the same order as a product_cat_order array,
		// i.e., in hierarchical order.
		uksort( $this->options, [ $this, 'compare_cat' ] );

		// Reindex settings array.
		$this->options = array_values( $this->options );

		// Add an empty group to the end.
		$count                   = count( $this->options );
		$this->options[ $count ] = [
			'category' => '',
			'filters'  => [],
		];
		++$count;

		// Save settings.
		update_option( self::OPTION_NAME, $this->options );

		for ( $i = 0; $i < $count; $i++ ) {
			$fields = [
				[
					'group'   => $i,
					'uid'     => 'category',
					'label'   => __( 'Product Category', 'woof-by-category' ),
					'section' => 'first_section',
					'type'    => 'select',
					'options' => $product_categories,
					'default' => '',
				],
				[
					'group'   => $i,
					'uid'     => 'filters',
					'label'   => __( 'Filters', 'woof-by-category' ),
					'section' => 'first_section',
					'type'    => 'multiple',
					'options' => $woof_filters,
					'default' => '',
				],
			];
			add_settings_section(
				'first_section',
				__( 'Categories and Filters', 'woof-by-category' ),
				'',
				'woof-by-category'
			);
			foreach ( $fields as $field ) {
				register_setting( 'woof_by_category_group', self::OPTION_NAME );
				add_settings_field(
					$field['uid'] . $i,
					$field['label'],
					[ $this, 'field_callback' ],
					'woof-by-category',
					$field['section'],
					$field
				);
			}
		}
	}

	/**
	 * Compare categories to sort.
	 *
	 * @param int $a Index in $this->options array.
	 * @param int $b Index in $this->options array.
	 *
	 * @return int Result of comparison.
	 */
	public function compare_cat( $a, $b ): int {
		$cat_a   = $this->options[ $a ]['category'];
		$index_a = array_search( $cat_a, $this->product_cat_order, true );
		$cat_b   = $this->options[ $b ]['category'];
		$index_b = array_search( $cat_b, $this->product_cat_order, true );

		if ( $index_a < $index_b ) {
			return - 1;
		}
		if ( $index_a > $index_b ) {
			return 1;
		}

		return 0;
	}

	/**
	 * Output field.
	 *
	 * @param array $arguments The list of fields.
	 */
	public function field_callback( $arguments ) {
		$value = get_option( self::OPTION_NAME ); // Get current settings.
		if ( $value ) {
			$value = $value[ $arguments['group'] ] [ $arguments['uid'] ] ?? null;
		} else { // If no value exists.
			$value = $arguments['default']; // Set to our default.
		}

		// Check which type of field we want.
		switch ( $arguments['type'] ) {
			case 'select': // If it is a select dropdown.
				if ( ! empty( $arguments['options'] ) && is_array( $arguments['options'] ) ) {
					$options_markup = '';
					foreach ( $arguments['options'] as $key => $label ) {
						/**
						 * %s is not an attribute
						 *
						 * @noinspection HtmlUnknownAttribute HtmlUnknownAttribute.
						 */
						$options_markup .= sprintf(
							'<option value="%s" %s>%s</option>',
							esc_attr( $key ),
							selected( $value, $key, false ),
							esc_html( $label )
						);
					}
					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
					printf(
						'<select name="woof_by_category_settings[%1$s][%2$s]">%3$s</select>',
						esc_html( $arguments['group'] ),
						esc_html( $arguments['uid'] ),
						$options_markup
					);
					// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				break;
			case 'multiple': // If it is a multiple select dropdown.
				if ( ! empty( $arguments['options'] ) && is_array( $arguments['options'] ) ) {
					$options_markup = '';
					foreach ( $arguments['options'] as $key => $label ) {
						$selected = '';
						if (
							is_array( $value ) &&
							in_array( $key, $value, true )
						) {
							$selected = selected( $key, $key, false );
						}
						/**
						 * %s is not an attribute
						 *
						 * @noinspection HtmlUnknownAttribute HtmlUnknownAttribute.
						 */
						$options_markup .= sprintf(
							'<option value="%s" %s>%s</option>',
							esc_attr( $key ),
							$selected,
							esc_html( $label )
						);
					}
					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
					printf(
						'<select multiple="multiple" name="woof_by_category_settings[%1$s][%2$s][]">%3$s</select>',
						esc_html( $arguments['group'] ),
						esc_html( $arguments['uid'] ),
						$options_markup
					);
					// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				break;
			default:
				break;
		}

		// If there is a help text.
		$helper = $arguments['helper'] ?? '';

		if ( $helper ) {
			printf( '<span class="helper"> %s</span>', esc_html( $helper ) ); // Show it.
		}

		// If there is a supplemental text.
		$supplemental = $arguments['supplemental'] ?? '';

		if ( $supplemental ) {
			printf( '<p class="description">%s</p>', esc_html( $supplemental ) ); // Show it.
		}
	}

	/**
	 * Check plugin requirements. If not met, show message and deactivate the plugin.
	 */
	public function check_requirements() {
		if ( ! $this->requirements_met() ) {
			add_action( 'admin_notices', [ $this, 'show_plugin_not_found_notice' ] );
			if ( is_plugin_active( plugin_basename( constant( 'WOOF_BY_CATEGORY_FILE' ) ) ) ) {
				deactivate_plugins( plugin_basename( constant( 'WOOF_BY_CATEGORY_FILE' ) ) );
				// phpcs:disable WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
				// phpcs:enable WordPress.Security.NonceVerification.Recommended
				add_action( 'admin_notices', [ $this, 'show_deactivate_notice' ] );
			}
		}
	}

	/**
	 * Check if plugin requirements met.
	 *
	 * @return bool Requirements met.
	 * @noinspection PhpIncludeInspection PhpIncludeInspection.
	 */
	private function requirements_met(): bool {
		$all_active = true;

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		foreach ( $this->required_plugins as $key => $required_plugin ) {
			if ( is_plugin_active( $required_plugin['plugin'] ) ) {
				$this->required_plugins[ $key ]['active'] = true;
			} else {
				$all_active = false;
			}
		}

		return $all_active;
	}

	/**
	 * Show required plugins not found a message.
	 *
	 * @noinspection PhpUnusedLocalVariableInspection PhpUnusedLocalVariableInspection.
	 */
	public function show_plugin_not_found_notice() {
		$message = __( 'WOOF by Category plugin requires the following plugins installed and activated: ', 'woof-by-category' );

		$message_parts = [];
		foreach ( $this->required_plugins as $key => $required_plugin ) {
			if ( ! $required_plugin['active'] ) {
				$href = '/wp-admin/plugin-install.php?tab=plugin-information&plugin=';

				$href .= $required_plugin['slug'] . '&TB_iframe=true&width=640&height=500';

				$message_parts[] = '<em><a href="' . $href . '" class="thickbox">' . $required_plugin['name'] . '</a></em>';
			}
		}

		$count = count( $message_parts );
		foreach ( $message_parts as $key => $message_part ) {
			if ( 0 !== $key ) {
				if ( ( ( $count - 1 ) === $key ) ) {
					$message .= ' and ';
				} else {
					$message .= ', ';
				}
			}
			$message .= $message_part;
		}

		$message .= '.';

		$this->admin_notice( $message, 'notice notice-error is-dismissible' );
	}

	/**
	 * Show a notice to inform the user that the plugin has been deactivated.
	 */
	public function show_deactivate_notice() {
		$this->admin_notice( __( 'WOOF by Category plugin has been deactivated.', 'woof-by-category' ), 'notice notice-info is-dismissible' );
	}

	/**
	 * Show admin notice.
	 *
	 * @param string $message    Message to show.
	 * @param string $class_name Message class: notice notice-success notice-error notice-warning notice-info
	 *                           is-dismissible.
	 */
	private function admin_notice( $message, $class_name ) {
		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<p>
				<?php echo wp_kses_post( $message ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Get hierarchy of product categories in an array.
	 *
	 * @param int $cat_id Top product category id.
	 *
	 * @return array
	 * @noinspection PhpUnusedLocalVariableInspection PhpUnusedLocalVariableInspection.
	 */
	private function get_product_categories( $cat_id = 0 ): array {
		$cat_list = [];

		$crumbs = $this->get_product_term_crumbs( $cat_id );
		$level  = count( $crumbs );

		$crumbs_string = '';
		foreach ( $crumbs as $key => $crumb ) {
			$crumbs_string .= ' < ' . $crumb['name'] . ' (' . $crumb['count'] . ')';
		}

		$taxonomies = $this->get_taxonomies();

		$args       = [
			'taxonomy'     => $taxonomies,
			'parent'       => $cat_id,
			'orderby'      => 'name',
			'show_count'   => true,
			'hierarchical' => true,
			'hide_empty'   => false,
		];
		$categories = get_terms( $args );

		if ( is_wp_error( $categories ) ) {
			return $cat_list;
		}

		foreach ( $categories as $cat ) {
			$cat_list[ $cat->slug ] = str_repeat( '&nbsp;', $level * 2 ) . $cat->name . ' (' . $cat->count . ')' . $crumbs_string;
			$child_categories       = $this->get_product_categories( $cat->term_id );
			if ( ! empty( $child_categories ) ) {
				foreach ( $child_categories as $key => $value ) {
					$cat_list[ $key ] = $value;
				}
			}
		}

		return $cat_list;
	}

	/**
	 * Get names and counts of a term and all its parents.
	 *
	 * @param int $term_id Product category id.
	 *
	 * @return array
	 */
	private function get_product_term_crumbs( $term_id ): array {
		$crumbs = [];

		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return $crumbs;
		}

		while ( $term && ! is_wp_error( $term ) && 0 !== $term->term_id ) {
			$crumb['name']  = $term->name;
			$crumb['count'] = $term->count;
			$crumbs[]       = $crumb;
			$term           = get_term( $term->parent );
		}

		return $crumbs;
	}

	/**
	 * Get WOOF filters in an array.
	 */
	private function get_woof_filters(): array {
		$filters = [];

		remove_filter( 'option_woof_settings', [ $this, 'wbc_option_woof_settings' ] );

		$woof_settings = get_option( 'woof_settings' );

		add_filter( 'option_woof_settings', [ $this, 'wbc_option_woof_settings' ] );

		if ( isset( $woof_settings['tax'] ) ) {
			foreach ( $woof_settings['tax'] as $tax => $value ) {
				$label           = get_taxonomy( $tax )->labels->singular_name;
				$filters[ $tax ] = $label;
			}
		}

		return $filters;
	}

	/**
	 * Find if category slug exists in the array of slugs or their parents.
	 *
	 * @param string      $filter_cat  Category slug to find.
	 * @param string|null $current_cat Array of category slugs or null.
	 *
	 * @return int Distance to parent in levels or -1 if parent is not found.
	 */
	protected function has_parent( $filter_cat, $current_cat ): int {
		if ( null === $current_cat ) {
			return - 1;
		}

		if ( ( '/' === $filter_cat ) && ( '/' === $current_cat ) ) {
			return 0;
		}

		$current_cat_object = $this->get_term_by_slug( $current_cat );

		if ( ! $current_cat_object ) {
			return - 1;
		}

		if ( $filter_cat === $current_cat_object->slug ) {
			return 0;
		}

		$parent_id          = $current_cat_object->parent;
		$distance_to_parent = 0;

		while ( 0 !== $parent_id ) {
			++$distance_to_parent;

			$current_cat_object = get_term( $parent_id );

			if ( ! $current_cat_object || is_wp_error( $current_cat_object ) ) {
				return - 1;
			}

			if ( $filter_cat === $current_cat_object->slug ) {
				return $distance_to_parent;
			}

			$parent_id = $current_cat_object->parent;
		}

		return - 1;
	}

	/**
	 * Get term by slug in all taxonomies.
	 *
	 * @param string $slug Term slug.
	 *
	 * @return WP_Term|false
	 */
	private function get_term_by_slug( $slug ) {
		$taxonomies = $this->get_taxonomies();

		foreach ( $taxonomies as $taxonomy ) {
			$current_cat_object = get_term_by( 'slug', $slug, $taxonomy );
			if ( $current_cat_object ) {
				return $current_cat_object;
			}
		}

		return false;
	}

	/**
	 * Get taxonomies to use.
	 *
	 * @return array
	 */
	private function get_taxonomies(): array {
		/**
		 * Filters the product taxonomies to use.
		 *
		 * @param array $categories Product categories.
		 */
		return (array) apply_filters( 'wbc_product_categories', [ 'product_cat' ] );
	}

	/**
	 * Add link to plugin setting page on plugins page.
	 *
	 * @param array|mixed $links Plugin links.
	 *
	 * @return array Plugin links
	 */
	public function add_settings_link( $links ): array {
		$action_links = [
			'settings' =>
				'<a href="' . admin_url( 'admin.php?page=woof-by-category' ) . '" aria-label="' .
				esc_attr__( 'View WOOF by Category settings', 'woof-by-category' ) . '">' .
				esc_html__( 'Settings', 'woof-by-category' ) . '</a>',
		];

		return array_merge( $action_links, (array) $links );
	}

	/**
	 * Enqueue plugin scripts.
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style(
			'woof-by-category-admin',
			constant( 'WOOF_BY_CATEGORY_URL' ) . '/assets/css/woof-by-category-admin.css',
			[],
			constant( 'WOOF_BY_CATEGORY_VERSION' )
		);
	}

	/**
	 * Declare compatibility with custom order tables for WooCommerce.
	 *
	 * @return void
	 */
	public function declare_wc_compatibility() {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				constant( 'WOOF_BY_CATEGORY_FILE' ),
				true
			);
		}
	}

	/**
	 * When a user is on the plugin admin page, display footer text that graciously asks them to rate us.
	 *
	 * @param string|mixed $text Footer text.
	 *
	 * @return string|mixed
	 * @noinspection HtmlUnknownTarget
	 */
	public function admin_footer_text( $text ) {
		if ( ! $this->is_wbc_options_screen() ) {
			return $text;
		}

		$url = 'https://wordpress.org/support/plugin/woof-by-category/reviews/?filter=5#new-post';

		return wp_kses(
			sprintf(
			/* translators: 1: plugin name, 2: wp.org review link with stars, 3: wp.org review link with text. */
				__( 'Please rate %1$s %2$s on %3$s. Thank you!', 'woof-by-category' ),
				'<strong>Woof by Category</strong>',
				sprintf(
					'<a href="%s" target="_blank" rel="noopener noreferrer">★★★★★</a>',
					$url
				),
				sprintf(
					'<a href="%s" target="_blank" rel="noopener noreferrer">WordPress.org</a>',
					$url
				)
			),
			[
				'a' => [
					'href'   => [],
					'target' => [],
					'rel'    => [],
				],
			]
		);
	}

	/**
	 * Show a plugin version in the update footer.
	 *
	 * @param string|mixed $content The content that will be printed.
	 *
	 * @return string|mixed
	 */
	public function update_footer( $content ) {
		if ( ! $this->is_wbc_options_screen() ) {
			return $content;
		}

		return sprintf(
		/* translators: 1: plugin version. */
			__( 'Version %s', 'woof-by-category' ),
			WOOF_BY_CATEGORY_VERSION
		);
	}
}
