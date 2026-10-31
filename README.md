# WOOF by Category

WOOF by Category displays different [WooCommerce Product Filter (WOOF)](https://wordpress.org/plugins/woocommerce-products-filter/) filters on different product category pages.

![Plugin banner](./.wordpress-org/banner-772x250.png)

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer
- WooCommerce and WooCommerce Product Filter (WOOF)

The [WordPress.org readme](readme.txt) contains installation instructions, usage notes, and the latest release notes. Older notes are in [changelog.txt](changelog.txt).

## Development

Clone this repository into `wp-content/plugins/woof-by-category`, then install the PHP dependencies:

```sh
composer install
```

Run the unit tests and PHP coding standards checks:

```sh
composer unit
composer phpcs
```

The plugin has no JavaScript build step or `package.json`. Its admin stylesheet is maintained directly in `assets/css`.

Composer installs the repository's pre-commit hook. In an existing checkout, run `composer hooks:install` to enable it. The hook copies staged release notes from `readme.txt` into `changelog.txt`; run `composer changelog` to refresh the working copy manually, or `composer changelog:check` to verify it.

## Release

The version in `woof-by-category.php`, the stable tag and release notes in `readme.txt`, the latest entry in `changelog.txt`, and the test version in `tests/unit/bootstrap.php` must agree. CI checks the changelog and runs PHP tests on supported versions. Publishing a GitHub release triggers the workflow that installs production Composer dependencies, deploys the plugin to WordPress.org, and attaches the plugin ZIP to the GitHub release. A push to `trunk` updates WordPress.org assets and readme separately.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
