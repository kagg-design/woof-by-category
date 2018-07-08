=== WOOF by Category ===
Contributors: kaggdesign
Donate link: https://www.paypal.me/kagg
Tags: woocommerce, filter, woocommerce products filter, filter category
Requires at least: 4.4
Tested up to: 5.0
Stable tag: 1.6.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WooCommerce Product Filter (WOOF) extension to display set of filters depending on current product category page.

== Description ==

WOOF by Category is a WooCommerce Product Filter (WOOF) extension, which allows user to set up different WOOF filters in different categories.

Plugin has options page on the backend, to setup relationships between any WooCommerce product category and any set of WOOF filters. Only selected filters will be shown on the selected category page and its children. Please see screenshots.

It is possible to set up any number of category->filters pairs.

== Installation ==

= Minimum Requirements =

* PHP version 5.5 or greater (PHP 5.6 or greater is recommended)
* MySQL version 5.0 or greater (MySQL 5.6 or greater is recommended)
* WooCommerce 3.0 or greater
* WooCommerce Product Filter (WOOF) plugin

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of WOOF by Category, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “WOOF by Category” and click Search Plugins. Once you’ve found our plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

== Frequently Asked Questions ==

= Where can I get support or talk to other users? =

If you get stuck, you can ask for help in the [WOOF by Category Plugin Forum](https://wordpress.org/support/plugin/woof-by-category).

== Screenshots ==

1. The WOOF by Category settings panel.
2. Plugins filters (Color and Material) on an "Assumenda" category page.
3. Plugins filters (Size and Weight) on a "Quisquam" category page.

== Changelog ==


= 1.6.4 =
* Fixed php warning / notice upon first activation on array_values().
* Tested with WooCommerce 3.4

= 1.6.3 =
* Fixed 2 php warnings upon first activation.
* Fixed php notice on array_pop().

= 1.6.2 =
* Fixed filter disappearing in widget on category page during attributes selection clearing.
* Fixed filtering of main WordPress request in admin.

= 1.6 =
* Fixed filter disappearing in widget on category page.

= 1.5 =
* Added automatic plugin deactivation if WooCommerce or WooCommerce Product Filter plugins are not activated.
* Tested with WooCommerce 3.3.

= 1.4 =
* Fixed setting of proper filters in AJAX mode.

= 1.3.3 =
* Fixed php warning during execution.

= 1.3.2 =
* Fixed blocking of all filters when "Try to ajaxify the shop" in WOOF is selected.

= 1.3.1 =
* Added auto ordering of category-filter pairs on plugin options page.

= 1.3 =
* Fixed sub-category overrides.
* Added donate link.
* Added donate button on the plugin options page.

= 1.2.1 =
* Added information on WooCommerce version compatibility.

= 1.2 =
* Added selection of filters for top shop page.
* Added settings link on plugin page.
* Fixed wrong behaviour when selected filters did not cover the whole set.
* Fixed crash when WooCommerce plugin is not activated.

= 1.1 =
* Added admin messages on plugin activation.
* Added description and screenshots.

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.1 =
1.1 is a description and translation update mostly.