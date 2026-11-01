<?php
/**
 * Changelog generator tests.
 *
 * @package woof-by-category
 */

namespace KAGG\WoofByCategory\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once PLUGIN_PATH . '/.github/scripts/update-changelog.php';

/**
 * Test release note synchronization.
 */
class ChangelogTest extends TestCase {

	/**
	 * The latest notes replace their previous copy and retain older releases.
	 */
	public function test_latest_release_preserves_history(): void {
		$readme = "== Changelog ==\n\n= 3.3.0 =\n* Current notes.\n\n[See changelog for all versions](https://example.test/changelog.txt).\n\n== Upgrade Notice ==\n";
		$history = "= 3.3.0 =\n* Old notes.\n\n= 3.2.0 =\n* Previous notes.\n";

		$expected = "= 3.3.0 =\n* Current notes.\n\n= 3.2.0 =\n* Previous notes.\n";
		$actual   = \wbc_generate_changelog( $readme, $history );

		self::assertSame( $expected, $actual );
		self::assertSame( $expected, \wbc_generate_changelog( $readme, $actual ) );
	}

	/**
	 * Missing release notes must fail the CI check.
	 */
	public function test_missing_release_notes_are_rejected(): void {
		$this->expectException( RuntimeException::class );

		\wbc_generate_changelog( "== Changelog ==\n\n== Upgrade Notice ==\n", '' );
	}
}
