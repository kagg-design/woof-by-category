<?php
/**
 * Plugin Name: WOOF by Category
 * Plugin URI: https://wordpress.org/plugins/woof-by-category/
 * Description: WooCommerce Product Filter (WOOF) extension to display set of filters depending on current product category page.
 * Author: KAGG Design
 * Version: 1.6.4
 * Author URI: https://kagg.eu/en/
 * Requires at least: 4.4
 * Tested up to: 5.0
 * WC requires at least: 3.0
 * WC tested up to: 3.4
 *
 * Text Domain: woof-by-category
 * Domain Path: /languages/
 *
 * @package woof-by-category
 * @author KAGG Design
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Woof_By_Category class.
 *
 * @class Woof_By_Category
 * @version 1.6.4
 */
class Woof_By_Category {
	/**
	 * @var string Filepath of main plugin file.
	 */
	public $file;

	/**
	 * @var string Plugin version.
	 */
	public $version;

	/**
	 * @var string Absolute plugin path.
	 */
	public $plugin_path;

	/**
	 * @var string Absolute plugin URL.
	 */
	public $plugin_url;

	/**
	 * @var array Required plugins.
	 */
	protected $required_plugins = array();

	/**
	 * @var array Plugin options.
	 */
	private $options;

	/**
	 * @var array Order of product categories.
	 */
	private $product_cat_order;

	/**
	 * Woof_By_Category constructor.
	 *
	 * @param string $file Filepath of main plugin file.
	 * @param string $version Main plugin version.
	 */
	public function __construct( $file, $version ) {
		$this->file    = $file;
		$this->version = $version;

		$this->plugin_path = trailingslashit( plugin_dir_path( $this->file ) );
		$this->plugin_url  = trailingslashit( plugin_dir_url( $this->file ) );

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

		add_action( 'admin_init', array( $this, 'check_requirements' ) );

		foreach ( $this->required_plugins as $required_plugin ) {
			if ( ! class_exists( $required_plugin['class'] ) ) {
				return;
			}
		}

		add_filter( 'option_woof_settings', array( $this, 'wbc_option_woof_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ), array(
			$this,
			'add_settings_link',
		), 10, 2 );
		add_action( 'admin_init', array( $this, 'setup_fields' ) );
		add_action( 'plugins_loaded', array( $this, 'wbc_load_textdomain' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'request', array( $this, 'wbc_request_filter' ) );
		add_filter( 'woof_get_request_data', array( $this, 'wbc_get_request_data' ), 10, 1 );
		add_filter( 'woof_print_content_before_search_form', array(
			$this,
			'woof_print_content_before_search_form_filter',
		) );
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

		foreach ( $value['tax'] as $filter => $filter_value ) {
			if ( ! in_array( $filter, $allowed_filters, true ) ) {
				unset( $value['tax'][ $filter ] );
			}
		}

		return $value;
	}

	/**
	 * Filter WOOF request data to unset WOOF filters not related to current category.
	 *
	 * @param array $data Request data.
	 *
	 * @return array Modified request data.
	 */
	public function wbc_get_request_data( $data ) {
		global $wp_taxonomies;

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
		global $wp_query;

		if ( null === $query_vars ) {
			if ( isset( $wp_query->query_vars['product_cat'] ) ) {
				$product_cat = $wp_query->query_vars['product_cat'];
			}
		} else {
			if ( isset( $query_vars['product_cat'] ) ) {
				$product_cat     = $query_vars['product_cat'];
				$product_cat_arr = explode( '/', $product_cat );
				$product_cat     = array_pop( $product_cat_arr );
			}
		}

		// Get current settings.
		$options = get_option( 'woof_by_category_settings' );

		$category_filters = array();
		if ( $options ) {
			foreach ( $options as $key => $group ) {
				if ( $group['category'] ) { // Ignore empty groups.
					if ( isset( $group['filters'] ) ) {
						$category_filters[ $group['category'] ] = array_values( $group['filters'] );
					} else {
						$category_filters[ $group['category'] ] = null;
					}
				}
			}
		}

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

		return $allowed_filters;
	}

	/**
	 * Print script before WOOF form filter to change js object 'woof_current_values'.
	 */
	public function woof_print_content_before_search_form_filter() {
		$allowed_filters = $this->get_allowed_filters();

		echo '<script>';

		// @todo - check nonce
		// @codingStandardsIgnoreStart
		foreach ( $_GET as $key => $value ) {
			if ( false !== strpos( $key, 'pa_' ) ) {
				if ( ! in_array( $key, $allowed_filters, true ) ) {
					echo 'delete woof_current_values["' . esc_html( $key ) . '"]; ';
				}
			}
		}
		// @codingStandardsIgnoreEnd

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
		load_plugin_textdomain( 'woof-by-category', false,
			dirname( plugin_basename( $this->file ) ) . '/languages/'
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
				<form id="wbc-options" action="options.php" method="POST">
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
							alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1"
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
	 * Setup options fields.
	 */
	public function setup_fields() {
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
		$this->options = get_option( 'woof_by_category_settings' );
		if ( $this->options ) {
			foreach ( $this->options as $key => $value ) {
				if ( ! $value['category'] ) {
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
		update_option( 'woof_by_category_settings', $this->options );

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
			add_settings_section( 'first_section', __( 'Categories and Filters', 'woof-by-category' ), '',
				'woof-by-category'
			);
			foreach ( $fields as $field ) {
				register_setting( 'woof_by_category_group', 'woof_by_category_settings' );
				add_settings_field( $field['uid'] . $i, $field['label'], array(
					$this,
					'field_callback',
				), 'woof-by-category', $field['section'], $field );
			}
		} // End for().
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
		$value = get_option( 'woof_by_category_settings' ); // Get current settings.
		if ( $value ) {
			$value = $value[ $arguments['group'] ] [ $arguments['uid'] ];
		} else { // If no value exists.
			$value = $arguments['default']; // Set to our default.
		}

		// Check which type of field we want.
		switch ( $arguments['type'] ) {
			case 'select': // If it is a select dropdown.
				if ( ! empty( $arguments['options'] ) && is_array( $arguments['options'] ) ) {
					$options_markup = '';
					foreach ( $arguments['options'] as $key => $label ) {
						$options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key,
							selected( $value, $key, false ), $label
						);
					}
					printf(
						'<select name="woof_by_category_settings[%1$s][%2$s]">%3$s</select>',
						esc_html( $arguments['group'] ),
						esc_html( $arguments['uid'] ),
						wp_kses( $options_markup,
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
						$options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key,
							$selected, $label
						);
					}
					printf(
						'<select multiple="multiple" name="woof_by_category_settings[%1$s][%2$s][]">%3$s</select>',
						esc_html( $arguments['group'] ),
						esc_html( $arguments['uid'] ),
						wp_kses( $options_markup,
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
		} // End switch().

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
			if ( is_plugin_active( plugin_basename( $this->file ) ) ) {
				deactivate_plugins( plugin_basename( $this->file ) );
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
	 * @param string $class Message class: notice notice-success notice-error notice-warning notice-info is-dismissible
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

		if ( 0 === $cat_id ) {
			$level = 0;
		} else {
			$level = $this->get_product_cat_level( $cat_id ) + 1;
		}

		$taxonomy     = 'product_cat';
		$parent       = $cat_id;
		$orderby      = 'name';
		$count        = true;
		$hierarchical = true;
		$hide_empty   = false;

		$args       = array(
			'taxonomy'     => $taxonomy,
			'parent'       => $parent,
			'orderby'      => $orderby,
			'show_count'   => $count,
			'hierarchical' => $hierarchical,
			'hide_empty'   => $hide_empty,
		);
		$categories = get_terms( $args );
		foreach ( $categories as $cat ) {
			$cat_list[ $cat->slug ] = str_repeat( '&nbsp;', $level * 2 ) . $cat->name . ' (' . $cat->count . ')';
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
	 * Get hierarchy level of the product category.
	 *
	 * @param int $cat_id Product category id.
	 *
	 * @return int
	 */
	private function get_product_cat_level( $cat_id ) {
		$level = 0;
		$cat   = get_term_by( 'id', $cat_id, 'product_cat' );
		if ( ! $cat ) {
			return $level;
		}

		while ( 0 !== $cat->parent ) {
			$level ++;
			$cat = get_term_by( 'id', $cat->parent, 'product_cat' );
		}

		return $level;
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
	 * @param string $cat Category slug to find.
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
	 * @param array $links Plugin links
	 * @param string $file Plugin file basename
	 *
	 * @return array|mixed Plugin links
	 */
	public function add_settings_link( $links, $file ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=woof-by-category' ) . '" aria-label="' . esc_attr__( 'View WOOF by Category settings', 'woof-by-category' ) . '">' . esc_html__( 'Settings', 'woof-by-category' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	/**
	 * Enqueue plugin scripts.
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'woof-by-category-admin', $this->plugin_url . 'css/woof-by-category-admin.css', array(), $this->version );
	}
}

new Woof_By_Category( __FILE__, '1.6.4' );
