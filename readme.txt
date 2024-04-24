=== WOOF by Category ===
Contributors: kaggdesign
Donate link: https://www.paypal.me/kagg
Tags: woocommerce, filter, woocommerce products filter, filter category
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.0
Stable tag: 3.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WooCommerce Product Filter (WOOF) extension to display a set of filters depending on the current product category page.

== Description ==

WOOF by Category is a WooCommerce Product Filter (WOOF) extension, which allows users to set up different WOOF filters in different categories.

Plugin has Options page on the backend, to set up relationships between any WooCommerce product category and any set of WOOF filters. Only selected filters will be shown on the selected category page and its children. Please see screenshots.

It is possible to set up any number of category->filters pairs.

== Installation ==

= Minimum Requirements =

* PHP version 7.0 or greater (PHP 8.0 or greater is recommended)
* MySQL version 5.5 or greater (MySQL 5.6 or greater is recommended)
* WooCommerce 3.0 or greater
* WooCommerce Product Filter (WOOF) plugin

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself, and you don’t need to leave your web browser. To do an automatic installation of WOOF by Category, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field, type “WOOF by Category” and click Search Plugins. Once you’ve found our plugin, you can view details about it such as the point release, rating and description. Most importantly, you can install it by simply clicking “Install Now”.

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should work like a charm; as always though, ensure you back up your site just in case.

== Frequently Asked Questions ==

= How to use additional product taxonomies, not only product_cat?

Add this code to your theme's `functions.php` file:

`
function wbc_product_categories_filter( $categories ) {
	return array_merge( $categories, [ 'tax-1', 'tax-2' ] );
}

add_filter( 'wbc_product_categories', 'wbc_product_categories_filter' );
`

where `tax-1`, `tax-2`, are additional product taxonomies to use with the plugin.

= Where can I get support or talk to other users? =

If you get stuck, you can ask for help in the [WOOF by Category Plugin Forum](https://wordpress.org/support/plugin/woof-by-category).

== Screenshots ==

1. The WOOF by Category settings panel.
2. Plugin filters (Color and Material) on an "Assumenda" category page.
3. Plugin filters (Size and Weight) on a "Quisquam" category page.

== Changelog ==

= 3.1.1 =
* Tested with WordPress 6.5.
* Tested with WooCommerce 8.8.
* Fixed deprecation message caused by FILTER_SANITIZE_STRING constant.

= 3.1.0 =
* Tested with WordPress 6.2.
* Tested with WooCommerce 8.6.
* The minimum required WordPress version is now 5.0

* = 3.0.1 =
* Dropped support for PHP 5.6. The minimum required PHP version is now 7.0.
* Tested with WordPress 6.3.
* Tested with WooCommerce 7.9.

= 2.18 =
* Tested with WordPress 6.2.
* Tested with WooCommerce 7.5.
* Tested with PHP 8.2.
* Added compatibility with WC High-Performance order storage (COT) feature.

= 2.17 =
* Tested with WordPress 6.1
* Tested with WooCommerce 7.1

= 2.16 =
* Tested with WordPress 6.0
* Tested with WooCommerce 6.5

= 2.15 =
* Tested with WordPress 5.8
* Tested with WooCommerce 5.5

= 2.14 =
* Tested with WordPress 5.7

= 2.13 =
* Tested with WooCommerce 5.0

= 2.12 =
* Added default filters.
* Tested with WooCommerce 4.9

= 2.11 =
* Tested with WordPress 5.6
* Tests added for PHP 8.0

= 2.10 =
* Tested with WooCommerce 4.7

= 2.9.2 =
* Fixed bug with auto_shortcode and additional taxonomies in woof widget.

= 2.9.1 =
* Fixed bug with distinguishing of widget and shortcode on the page

= 2.9 =
* Tested with WooCommerce 4.5
* Added ability to work with WOOF shortcodes

= 2.8 =
* Tested with WordPress 5.5 and WooCommerce 4.4

= 2.7 =
* Fixed the bug with not showing filters on a single product page.

= 2.6.1 =
* Fixed the bug with disappearing filters on shop page with ajax.

= 2.6 =
* Added filter to use additional product taxonomies, not product_cat only

= 2.5.1 =
* Fixed php warning in woof_sort_terms_before_out_filter().

= 2.5 =
* Added filtering of unused terms to improve performance

= 2.4 =
* Added compatibility with WOOF v2.2.3

= 2.3 =
* Added Polylang support
* Fixed bugs in ajax filter
* Tested with WordPress 5.3
* Tested with WooCommerce 3.8
* Required minimal PHP version set to 5.6

= 2.2.1 =
* Fixed php warning in class-woof-by-category.php on line 371

= 2.2 =
* Significantly improved performance on sites with a long list of product categories.
* Tested with WordPress 5.2
* Tested with WooCommerce 3.6

= 2.1 =
* Added breadcrumbs for categories.

= 2.0.2 =
* Tested with WordPress 5.1

= 2.0.1 =
* Fixed - Bug when plugin was installed after WPML.
* Tested up to 5.1
* Performance optimized.

= 2.0.0 =
* Compatibility with WPML.
* Fixed php warning when no settings are in WOOF.

= 1.6.8 =
* Tested with WooCommerce 3.5

= 1.6.7 =
* Fixed - Attribute archive pages redirect to homepage.

= 1.6.5 =
* Fixed - Attributes disappear in the WOOF widget.

= 1.6.4 =
* Fixed php warning / notice upon first activation on array_values().
* Tested with WooCommerce 3.4

= 1.6.3 =
* Fixed 2 php warnings upon first activation.
* Fixed php notice on array_pop().

= 1.6.2 =
* Fixed filter disappearing in the widget on category page during attribute selection clearing.
* Fixed filtering of the main WordPress request in admin.

= 1.6 =
* Fixed filter disappearing in the widget on the category page.

= 1.5 =
* Added automatic plugin deactivation if WooCommerce or WooCommerce Product Filter plugins are not activated.
* Tested with WooCommerce 3.3.

= 1.4 =
* Fixed the setting of proper filters in AJAX mode.

= 1.3.3 =
* Fixed php warning during execution.

= 1.3.2 =
* Fixed blocking of all filters when "Try to ajaxify the shop" in WOOF is selected.

= 1.3.1 =
* Added auto ordering of category-filter pairs on the plugin options page.

= 1.3 =
* Fixed sub-category overrides.
* Added donate link.
* Added donate button on the plugin options page.

= 1.2.1 =
* Added information on WooCommerce version compatibility.

= 1.2 =
* Added selection of filters for top shop page.
* Added settings link on plugin page.
* Fixed wrong behavior when selected filters did not cover the whole set.
* Fixed crash when WooCommerce plugin is not activated.

= 1.1 =
* Added admin messages on plugin activation.
* Added description and screenshots.

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.1 =
1.1 is a description and translation update mostly.
