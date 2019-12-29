# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).


## [2.3]
### Added
* Added Polylang support
### Fixed
* Fixed bugs in ajax filter
### Tested
* Tested with WordPress 5.3
* Tested with WooCommerce 3.8
* Required minimal PHP version set to 5.6

## [2.2.1]
### Fixed
* Fixed php warning in class-woof-by-category.php on line 371

## [2.2]
### Improved
* Significantly improved performance on sites with a long list of product categories.

## [2.1]
### Added
* Added breadcrumbs for categories.

## [2.0.2]
### Tested
* Tested with WordPress 5.1

## [2.0.1]
### Fixed
* Bug when plugin was installed after WPML.
### Tested
* WP up to 5.1
### Performance optimized.

## [2.0.0]
### Added
* Compatibility with WPML
### Fixed
* Fixed php warning when no settings are in WOOF

## [1.6.8]
### Tested
* Tested with WooCommerce 3.5

## [1.6.7]
### Fixed
* Attribute archive pages redirect to homepage

## [1.6.5]
### Fixed
* Attributes disappear in the WOOF widget

## [1.6.4]
### Fixed
* php warning / notice upon first activation on array_values().
### Tested
* WooCommerce 3.4

## [1.6.3]
### Fixed
* 2 php warnings upon first activation.
* Php notice on array_pop().

## [1.6.2]
### Fixed
* Filter disappearing in widget on category page during attributes selection clearing.
* Filtering of main WordPress request in admin.

## [1.6]
### Fixed
* Filter disappearing in widget on category page.

## [1.5]
### Added
* Automatic plugin deactivation if WooCommerce or WooCommerce Product Filter plugins are not activated.
### Tested
* Tested with WooCommerce 3.3.

## [1.4]
### Fixed
* Setting of proper filters in AJAX mode.

## [1.3.3]
### Fixed
* php warning during execution.

## [1.3.2]
### Fixed
* Blocking of all filters when "Try to ajaxify the shop" in WOOF is selected.

## [1.3.1]
### Added
* Auto ordering of category-filter pairs on plugin options page.

## [1.3]
### Fixed
* Sub-category overrides.
### Added
* Donate link.
* Donate button on the plugin options page.

## [1.2.1]
### Added
* Added information on WooCommerce version compatibility.

## [1.2]
### Added
* Added selection of filters for top shop page.
* Added settings link on plugin page.

### Fixed
* Fixed wrong behaviour when selected filters did not cover the whole set.
* Fixed crash when WooCommerce plugin is not activated.

## [1.1]
* Added admin messages on plugin activation.
* Added description and screenshots.

## [1.0]
* (23 August 2017). Initial Release.
