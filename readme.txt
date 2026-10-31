=== WOOF by Category ===
Contributors: kaggdesign
Donate link: https://www.paypal.me/kagg
Tags: woocommerce, filter, woocommerce products filter, filter category
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 3.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WooCommerce Product Filter (WOOF) extension to display a set of filters depending on the current product category page.

== Description ==

WOOF by Category is a WooCommerce Product Filter (WOOF) extension, which allows users to set up different WOOF filters in different categories.

Plugin has an Options page on the backend to set up relationships between any WooCommerce product category and any set of WOOF filters. Only selected filters will be shown on the selected category page and its children. Please see screenshots.

It is possible to set up any number of category->filters pairs.

== Installation ==

= Minimum Requirements =

* PHP version 7.4 or greater
* WooCommerce 3.0 or greater
* WooCommerce Product Filter (WOOF) plugin

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself, and you don’t need to leave your web browser. To do an automatic installation of WOOF by Category, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field, type “WOOF by Category” and click Search Plugins. Once you’ve found our plugin, you can view details about it such as the point release, rating and description. Most importantly, you can install it by simply clicking “Install Now”.

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should work like a charm; as always, though, ensure you back up your site just in case.

== Frequently Asked Questions ==

= How to use additional product taxonomies, not only product_cat?

Add this code to your theme's `functions.php` file:

`
function wbc_product_categories_filter( $categories ) {
	return array_merge( $categories, [ 'tax-1', 'tax-2' ] );
}

add_filter( 'wbc_product_categories', 'wbc_product_categories_filter' );
`

where `tax-1`, `tax-2` are additional product taxonomies to use with the plugin.

= Where can I get support or talk to other users? =

If you get stuck, you can ask for help in the [WOOF by Category Plugin Forum](https://wordpress.org/support/plugin/woof-by-category).

== Screenshots ==

1. The WOOF by Category settings panel.
2. Plugin filters (Color and Material) on an "Assumenda" category page.
3. Plugin filters (Size and Weight) on a "Quisquam" category page.

== Changelog ==
= 3.3.0 =
* Minimal PHP version is now 7.4.
* Minimal WordPress version is now 6.0.
* Tested with WooCommerce 11.2.1.
* Updated the test and release infrastructure.

[See changelog for all versions](https://plugins.svn.wordpress.org/woof-by-category/trunk/changelog.txt).

== Upgrade Notice ==

= 1.1 =
1.1 is a description and translation update mostly.
