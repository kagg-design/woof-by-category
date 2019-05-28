<?php
/**
 * Woof_By_Category class file.
 *
 * @package woof-by-category
 */

/**
 * Woof_By_Category class.
 *
 * @class Woof_By_Category
 */
class Woof_By_Category {
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
	const SCREEN_ID = 'toplevel_page_woof-by-category';

	/**
	 * Plugin cache group.
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'woof_by_category';

	/**
	 * Required plugins.
	 *
	 * @var array
	 */
	protected $required_plugins = array();

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
		$this->required_plugins = array(
			array(
				'plugin' => 'woocommerce/woocommerce.php',
				'name'   => 'WooCommerce',
				'slug'   => 'woocommerce',
				'class'  => 'WooCommerce',
				'active' => false,
			),
			array(
				'plugin' => 'woocommerce-products-filter/index.php',
				'name'   => 'WooCommerce Product Filter',
				'slug'   => 'woocommerce-products-filter',
				'class'  => 'WOOF',
				'active' => false,
			),
		);

		wp_cache_add_non_persistent_groups( array( self::CACHE_GROUP ) );

		$this->init_hooks();
	}

	/**
	 * Init hooks.
	 */
	public function init_hooks() {
		add_action( 'admin_init', array( $this, 'check_requirements' ) );

		foreach ( $this->required_plugins as $required_plugin ) {
			if ( ! class_exists( $required_plugin['class'] ) ) {
				return;
			}
		}

		add_filter( 'option_woof_settings', array( $this, 'wbc_option_woof_settings' ) );

		if ( class_exists( 'Sitepress' ) ) {
			$this->add_option_filters();
		}

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_filter(
			'plugin_action_links_' . plugin_basename( WOOF_BY_CATEGORY_FILE ),
			array( $this, 'add_settings_link' ),
			10
		);
		add_action( 'current_screen', array( $this, 'setup_fields' ) );
		add_action( 'plugins_loaded', array( $this, 'wbc_load_textdomain' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'request', array( $this, 'wbc_request_filter' ) );
		add_filter( 'woof_get_request_data', array( $this, 'wbc_get_request_data' ), 10, 1 );
		add_filter(
			'woof_print_content_before_search_form',
			array( $this, 'woof_print_content_before_search_form_filter' )
		);
	}

	/**
	 * Filter get_option( 'woof_settings' ) to unset WOOF filters not related to current category.
	 *
	 * @param mixed $value 'woof_settings' option value read from database.
	 *
	 * @return mixed Modified 'woof_settings'.
	 */
	public function wbc_option_woof_settings( $value ) {
		$allowed_filters = $this->get_allowed_filters();

		if ( ! isset( $value['tax'] ) ) {
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
		add_filter(
			'pre_option_' . self::OPTION_NAME,
			array(
				$this,
				'wbc_pre_option_woof_by_category_settings',
			)
		);
	}

	/**
	 * Add pre_update_option filter for plugin options.
	 */
	private function add_pre_update_option_filter() {
		add_filter(
			'pre_update_option_' . self::OPTION_NAME,
			array(
				$this,
				'wbc_pre_update_option_woof_by_category_settings',
			),
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
		remove_filter(
			'pre_option_' . self::OPTION_NAME,
			array(
				$this,
				'wbc_pre_option_woof_by_category_settings',
			)
		);
	}

	/**
	 * Remove pre_update_option filter for plugin options.
	 */
	private function remove_pre_update_option_filter() {
		remove_filter(
			'pre_update_option_' . self::OPTION_NAME,
			array(
				$this,
				'wbc_pre_update_option_woof_by_category_settings',
			)
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
	 * Filter get_option() of plugin settings to get value for current WPML language.
	 * Pass through if WPML is not active.
	 *
	 * @return mixed Settings for current WPML language.
	 */
	public function wbc_pre_option_woof_by_category_settings() {
		global $sitepress;

		$lang       = $sitepress->get_current_language();
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
	 * Filter update_option() of plugin settings to store value for current WPML language.
	 * Pass through if WPML is not active.
	 *
	 * @param mixed $value     The new, unserialized option value.
	 * @param mixed $old_value The old option value.
	 *
	 * @return mixed Settings for current WPML language.
	 */
	public function wbc_pre_update_option_woof_by_category_settings( $value, $old_value ) {
		global $sitepress;

		$lang = $sitepress->get_current_language();
		update_option( self::OPTION_NAME . '_' . $lang, $value );

		if ( $sitepress->get_default_language() === $lang ) {
			$this->remove_option_filters();
			update_option( self::OPTION_NAME, $value );
			$this->add_option_filters();
		}

		return $old_value;
	}

	/**
	 * Translate options for a new language.
	 *
	 * @param array $options Plugin options.
	 *
	 * @return array
	 */
	private function translate_options( $options ) {
		if ( ! $options ) {
			return $options;
		}

		$translated_options = array();
		foreach ( $options as $key => $group ) {
			if ( isset( $group['category'] ) && $group['category'] ) {
				if ( '/' === $group['category'] ) {
					$translated_options[] = $group;
				} else {
					$translated_category = get_term_by( 'slug', $group['category'], 'product_cat' );
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
	 * Filter WOOF request data to unset WOOF filters not related to current category.
	 *
	 * @param array $data Request data.
	 *
	 * @return array Modified request data.
	 */
	public function wbc_get_request_data( $data ) {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return $data; // If too early.
		}

		// Cannot check nonce here, as WOOF does not use it.
		// @codingStandardsIgnoreStart
		if ( isset( $_POST['action'] ) && ( 'woof_draw_products' === $_POST['action'] ) ) {
			return $data;
		}
		// @codingStandardsIgnoreEnd

		$allowed_filters = $this->get_allowed_filters();

		$new_data = array();
		foreach ( $data as $key => $value ) {
			if ( false !== strpos( $key, 'pa_' ) ) {
				if ( ! in_array( $key, $allowed_filters, true ) ) {
					continue;
				}
			}
			$new_data[ $key ] = $value;
		}

		return $new_data;
	}

	/**
	 * Get allowed filters for current category.
	 *
	 * @param array $query_vars Query vars.
	 *
	 * @return array
	 */
	private function get_allowed_filters( $query_vars = null ) {
		$product_cat      = $this->get_product_cat( $query_vars );
		$category_filters = $this->get_category_filters();

		/**
		 * In theory, there could be a number of product_cat arguments.
		 * But request like
		 * http://test.kagg.eu/?post_type=product&product_cat=assumenda&product_cat=quisquam
		 * returns only 1 product-category: quisquam (the last one).
		 * It redirects to
		 * http://test.kagg.eu/product-category/quisquam/?post_type=product
		 * Conclusion: WooCommerce can show only one product category on the category page.
		 */
		// @todo - check nonce
		// @codingStandardsIgnoreStart
		if ( isset( $product_cat ) ) {
			$cats = explode( ',', $product_cat );
		} elseif ( isset( $_GET['product_cat'] ) ) {
			// Works for ajaxifyed shop
			$cats = array( $_GET['product_cat'] );
		} elseif ( isset( $_GET['really_curr_tax'] ) ) {
			// Works for widget and subcategory
			$really_curr_tax = explode( '-', $_GET['really_curr_tax'] );
			$term            = get_term( $really_curr_tax[0], $really_curr_tax[1] );
			if ( ! is_wp_error( $term ) ) {
				$cats = array( $term->slug );
			} else {
				$cats = null;
			}
		} else {
			$cats = null;
		}
		// @codingStandardsIgnoreEnd

		$key             = md5( wp_json_encode( array( $category_filters, $cats ) ) );
		$allowed_filters = wp_cache_get( $key, self::CACHE_GROUP );

		if ( false !== $allowed_filters ) {
			return $allowed_filters;
		}

		$allowed_filters        = array();
		$max_distance_to_parent = PHP_INT_MAX;
		foreach ( $category_filters as $cat => $filters ) {
			$distance_to_parent = $this->has_parent( $cat, $cats );
			if ( 0 <= $distance_to_parent ) {
				if ( $distance_to_parent < $max_distance_to_parent ) {
					$max_distance_to_parent = $distance_to_parent;
					$allowed_filters        = $filters;
				}
			}
		}
		$allowed_filters = array_unique( $allowed_filters );
		wp_cache_set( $key, $allowed_filters, self::CACHE_GROUP );

		return $allowed_filters;
	}

	/**
	 * Get product category.
	 *
	 * @param null|array $query_vars Query vars.
	 *
	 * @return mixed|null
	 */
	private function get_product_cat( $query_vars ) {
		global $wp_query;

		$product_cat = null;
		if ( null === $query_vars ) {
			if ( isset( $wp_query->query_vars['product_cat'] ) ) {
				$product_cat = $wp_query->query_vars['product_cat'];
			}
			if ( wp_doing_ajax() ) {
				$permalinks = wc_get_permalink_structure();
				$path       = untrailingslashit( wp_parse_url( wp_get_referer(), PHP_URL_PATH ) );
				if ( false !== mb_strpos( $path, $permalinks['category_base'] ) ) {
					$product_cat_arr = explode( '/', $path );
					$product_cat     = array_pop( $product_cat_arr );
				}
			}
		} else {
			if ( isset( $query_vars['product_cat'] ) ) {
				$product_cat     = $query_vars['product_cat'];
				$product_cat_arr = explode( '/', $product_cat );
				$product_cat     = array_pop( $product_cat_arr );
			}
		}

		return $product_cat;
	}

	/**
	 * Get category filters.
	 *
	 * @return array
	 */
	private function get_category_filters() {
		$key              = 'category_filters';
		$category_filters = wp_cache_get( $key, self::CACHE_GROUP );

		if ( false !== $category_filters ) {
			return $category_filters;
		}

		$category_filters = array();

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

		wp_cache_set( $key, $category_filters, self::CACHE_GROUP );

		return $category_filters;
	}

	/**
	 * Print script before WOOF form filter to change js object 'woof_current_values'.
	 */
	public function woof_print_content_before_search_form_filter() {
		$allowed_filters = $this->get_allowed_filters();

		echo '<script>';

		// @todo - check nonce
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		foreach ( $_GET as $key => $value ) {
			$key = filter_var( $key, FILTER_SANITIZE_STRING );
			if ( false !== strpos( $key, 'pa_' ) ) {
				if ( ! in_array( $key, $allowed_filters, true ) ) {
					echo 'delete woof_current_values["' . esc_html( $key ) . '"]; ';
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		echo 'jQuery(document).ready(function($){ woof_get_submit_link(); })';
		echo '</script>';
	}

	/**
	 * Filter main WordPress request to unset WOOF filters not related to current category.
	 *
	 * @param array $query_vars Query vars.
	 *
	 * @return array Filtered query vars.
	 */
	public function wbc_request_filter( $query_vars ) {
		if ( is_admin() ) {
			return $query_vars;
		}

		if ( ! $query_vars || ! isset( $query_vars['product_cat'] ) ) {
			return $query_vars;
		}

		$allowed_filters = $this->get_allowed_filters( $query_vars );

		$new_query_vars = array();
		foreach ( $query_vars as $key => $value ) {
			if ( false !== strpos( $key, 'pa_' ) ) {
				if ( ! in_array( $key, $allowed_filters, true ) ) {
					continue;
				}
			}
			$new_query_vars[ $key ] = $value;
		}

		return $new_query_vars;
	}

	/**
	 * Load plugin text domain.
	 */
	public function wbc_load_textdomain() {
		load_plugin_textdomain(
			'woof-by-category',
			false,
			dirname( plugin_basename( WOOF_BY_CATEGORY_FILE ) ) . '/languages/'
		);
	}

	/**
	 * Add settings page to the menu.
	 */
	public function add_settings_page() {
		$page_title = __( 'WOOF by Category', 'woof-by-category' );
		$menu_title = __( 'WOOF by Category', 'woof-by-category' );
		$capability = 'manage_options';
		$slug       = 'woof-by-category';
		$callback   = array( $this, 'woof_by_category_settings_page' );
		$icon       = 'dashicons-filter';
		$position   = null;
		add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon );
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

			<div class="wbc-col left">
				<form id="wbc-options" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="POST">
					<?php
					settings_fields( 'woof_by_category_group' ); // Hidden protection fields.
					do_settings_sections( 'woof-by-category' ); // Sections with options.
					submit_button();
					?>
				</form>
			</div>
			<div class="wbc-col right">
				<h2 id="donate">
					<?php echo esc_html( __( 'Donate', 'woof-by-category' ) ); ?>
				</h2>
				<p>
					<?php echo esc_html( __( 'Would you like to support the advancement of this plugin?', 'woof-by-category' ) ); ?>
				</p>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="S9UXRBU2ZKK68">
					<input
							type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif"
							name="submit" alt="PayPal - The safer, easier way to pay online!">
					<img
							alt="" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1"
							height="1">
				</form>

				<h2 id="appreciation">
					<?php echo esc_html( __( 'Your appreciation', 'woof-by-category' ) ); ?>
				</h2>
				<a
						target="_blank"
						href="https://wordpress.org/support/view/plugin-reviews/woof-by-category?rate=5#postform">
					<?php echo esc_html( __( 'Leave a ★★★★★ plugin review on WordPress.org', 'woof-by-category' ) ); ?>
				</a>
			</div>

		</div>
		<?php
	}

	/**
	 * Is current admin screen the plugin options screen.
	 *
	 * @return bool
	 */
	private function is_wbc_options_screen() {
		$current_screen = get_current_screen();

		return $current_screen && ( 'options' === $current_screen->id || self::SCREEN_ID === $current_screen->id );
	}

	/**
	 * Setup options fields.
	 */
	public function setup_fields() {
		if ( ! $this->is_wbc_options_screen() ) {
			return;
		}

		$product_categories      = array_merge(
			array(
				''  => __( '--Select Category--', 'woof-by-category' ),
				'/' => __( '-Shop Page-', 'woof-by-category' ),
			),
			$this->get_product_categories()
		);
		$this->product_cat_order = array_keys( $product_categories );
		$woof_filters            = array_merge(
			array(
				'' => __( '--Select Filters--', 'woof-by-category' ),
			),
			$this->get_woof_filters()
		);

		// Get current settings.
		$this->options = get_option( self::OPTION_NAME );

		if ( $this->options ) {
			foreach ( $this->options as $key => $group ) {
				if ( ! ( isset( $group['category'] ) && $group['category'] ) ) {
					// Remove group with empty categories.
					unset( $this->options[ $key ] );
				}
			}
		} else {
			$this->options = array();
		}

		// Sort settings array in same order as product_cat_order array,
		// i.e. in hierarchial order.
		uksort( $this->options, array( $this, 'compare_cat' ) );

		// Reindex settings array.
		$this->options = array_values( $this->options );

		// Add empty group to the end.
		$count                   = count( $this->options );
		$this->options[ $count ] = array(
			'category' => '',
			'filters'  => array(),
		);
		$count ++;

		// Save settings.
		update_option( self::OPTION_NAME, $this->options );

		for ( $i = 0; $i < $count; $i ++ ) {
			$fields = array(
				array(
					'group'        => $i,
					'uid'          => 'category',
					'label'        => __( 'Product Category', 'woof-by-category' ),
					'section'      => 'first_section',
					'type'         => 'select',
					'options'      => $product_categories,
					'placeholder'  => 'Text goes here',
					'helper'       => '',
					'supplemental' => '',
					'default'      => '',
				),
				array(
					'group'        => $i,
					'uid'          => 'filters',
					'label'        => __( 'Filters', 'woof-by-category' ),
					'section'      => 'first_section',
					'type'         => 'multiple',
					'options'      => $woof_filters,
					'placeholder'  => 'Text goes here',
					'helper'       => '',
					'supplemental' => '',
					'default'      => '',
				),
			);
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
					array( $this, 'field_callback' ),
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
	public function compare_cat( $a, $b ) {
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
	 * @param array $arguments Field list in array.
	 */
	public function field_callback( $arguments ) {
		$value = get_option( self::OPTION_NAME ); // Get current settings.
		if ( $value ) {
			$value = isset( $value[ $arguments['group'] ] [ $arguments['uid'] ] ) ?
				$value[ $arguments['group'] ] [ $arguments['uid'] ] : null;
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
						 * @noinspection HtmlUnknownAttribute
						 */
						$options_markup .= sprintf(
							'<option value="%s" %s>%s</option>',
							$key,
							selected( $value, $key, false ),
							$label
						);
					}
					printf(
						'<select name="woof_by_category_settings[%1$s][%2$s]">%3$s</select>',
						esc_html( $arguments['group'] ),
						esc_html( $arguments['uid'] ),
						wp_kses(
							$options_markup,
							array(
								'option' => array(
									'value'    => array(),
									'selected' => array(),
								),
							)
						)
					);
				}
				break;
			case 'multiple': // If it is a multiple select dropdown.
				if ( ! empty( $arguments['options'] ) && is_array( $arguments['options'] ) ) {
					$options_markup = '';
					foreach ( $arguments['options'] as $key => $label ) {
						$selected = '';
						if ( is_array( $value ) ) {
							if ( in_array( $key, $value, true ) ) {
								$selected = selected( $key, $key, false );
							}
						}
						/**
						 * %s is not an attribute
						 *
						 * @noinspection HtmlUnknownAttribute
						 */
						$options_markup .= sprintf(
							'<option value="%s" %s>%s</option>',
							$key,
							$selected,
							$label
						);
					}
					printf(
						'<select multiple="multiple" name="woof_by_category_settings[%1$s][%2$s][]">%3$s</select>',
						esc_html( $arguments['group'] ),
						esc_html( $arguments['uid'] ),
						wp_kses(
							$options_markup,
							array(
								'option' => array(
									'value'    => array(),
									'selected' => array(),
								),
							)
						)
					);
				}
				break;
			default:
				break;
		}

		// If there is help text.
		$helper = $arguments['helper'];
		if ( $helper ) {
			printf( '<span class="helper"> %s</span>', esc_html( $helper ) ); // Show it.
		}

		// If there is supplemental text.
		$supplemental = $arguments['supplemental'];
		if ( $supplemental ) {
			printf( '<p class="description">%s</p>', esc_html( $supplemental ) ); // Show it.
		}
	}

	/**
	 * Check plugin requirements. If not met, show message and deactivate plugin.
	 */
	public function check_requirements() {
		if ( ! $this->requirements_met() ) {
			add_action( 'admin_notices', array( $this, 'show_plugin_not_found_notice' ) );
			if ( is_plugin_active( plugin_basename( WOOF_BY_CATEGORY_FILE ) ) ) {
				deactivate_plugins( plugin_basename( WOOF_BY_CATEGORY_FILE ) );
				// @codingStandardsIgnoreStart
				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
				// @codingStandardsIgnoreEnd
				add_action( 'admin_notices', array( $this, 'show_deactivate_notice' ) );
			}
		}
	}

	/**
	 * Check if plugin requirements met.
	 *
	 * @return bool Requirements met.
	 */
	private function requirements_met() {
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
	 * Show required plugins not found message.
	 */
	public function show_plugin_not_found_notice() {
		$message = __( 'WOOF by Category plugin requires the following plugins installed and activated: ', 'woof-by-category' );

		$message_parts = array();
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
	 * @param string $message Message to show.
	 * @param string $class   Message class: notice notice-success notice-error notice-warning notice-info
	 *                        is-dismissible.
	 */
	private function admin_notice( $message, $class ) {
		?>
		<div class="<?php echo esc_attr( $class ); ?>">
			<p>
				<span style="display: block; margin: 0.5em 0.5em 0 0; clear: both;">
				<?php echo wp_kses( $message, wp_kses_allowed_html( 'post' ) ); ?>
				</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Get hierarchy of product categories in array.
	 *
	 * @param int $cat_id Top product category id.
	 *
	 * @return array
	 */
	private function get_product_categories( $cat_id = 0 ) {
		$cat_list = array();

		$crumbs = $this->get_product_term_crumbs( $cat_id );
		$level  = count( $crumbs );

		$crumbs_string = '';
		foreach ( $crumbs as $key => $crumb ) {
			$crumbs_string .= ' < ' . $crumb['name'] . ' (' . $crumb['count'] . ')';
		}

		$args       = array(
			'taxonomy'     => 'product_cat',
			'parent'       => $cat_id,
			'orderby'      => 'name',
			'show_count'   => true,
			'hierarchical' => true,
			'hide_empty'   => false,
		);
		$categories = get_terms( $args );
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
	private function get_product_term_crumbs( $term_id ) {
		$crumbs = array();

		$term = get_term_by( 'id', $term_id, 'product_cat' );
		if ( ! $term ) {
			return $crumbs;
		}

		while ( $term && 0 !== $term->term_id ) {
			$crumb['name']  = $term->name;
			$crumb['count'] = $term->count;
			$crumbs[]       = $crumb;
			$term           = get_term_by( 'id', $term->parent, 'product_cat' );
		}

		return $crumbs;
	}

	/**
	 * Get WOOF filters in array.
	 */
	private function get_woof_filters() {
		$filters = array();

		remove_filter( 'option_woof_settings', array( $this, 'wbc_option_woof_settings' ) );
		$woof_settings = get_option( 'woof_settings' );
		add_filter( 'option_woof_settings', array( $this, 'wbc_option_woof_settings' ) );

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
	 * @param string      $cat  Category slug to find.
	 * @param array|mixed $cats Array of category slugs or null.
	 *
	 * @return int Distance to parent in levels or -1 if parent is not found.
	 */
	private function has_parent( $cat, $cats ) {
		if ( ( '/' === $cat ) && ( null === $cats ) ) {
			return 0;
		}

		if ( null === $cats ) {
			return - 1;
		}

		foreach ( $cats as $category ) {
			$cat_object = get_term_by( 'slug', $category, 'product_cat' );
			if ( $cat_object ) {
				if ( $cat === $cat_object->slug ) {
					return 0;
				}
				$parent_id          = $cat_object->parent;
				$distance_to_parent = 0;
				while ( 0 !== $parent_id ) {
					$distance_to_parent ++;
					$cat_object = get_term_by( 'id', $parent_id, 'product_cat' );
					if ( ! $cat_object ) {
						return - 1;
					}
					if ( $cat === $cat_object->slug ) {
						return $distance_to_parent;
					}
					$parent_id = $cat_object->parent;
				}
			}
		}

		return - 1;
	}

	/**
	 * Add link to plugin setting page on plugins page.
	 *
	 * @param array $links Plugin links.
	 *
	 * @return array|mixed Plugin links
	 */
	public function add_settings_link( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=woof-by-category' ) . '" aria-label="' . esc_attr__( 'View WOOF by Category settings', 'woof-by-category' ) . '">' . esc_html__( 'Settings', 'woof-by-category' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	/**
	 * Enqueue plugin scripts.
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style(
			'woof-by-category-admin',
			WOOF_BY_CATEGORY_URL . '/css/woof-by-category-admin.css',
			array(),
			WOOF_BY_CATEGORY_VERSION
		);
	}
}
