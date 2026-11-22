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
	 * Several readme releases merge into the archive without duplicate headings.
	 */
	public function test_readme_releases_preserve_history(): void {
		$readme  = "== Changelog ==\n\n= 3.3.0 =\n* Current notes.\n\n= 3.0 =\n* Restored notes.\n\n";
		$readme .= "[See changelog for all versions](https://example.test/changelog.txt).\n\n== Upgrade Notice ==\n";
		$history = "= 3.3.0 =\n* Old notes.\n\n= 3.2.0 =\n* Previous notes.\n\n= 2.18 =\n* Legacy notes.\n";

		$expected = "= 3.3.0 =\n* Current notes.\n\n= 3.2.0 =\n* Previous notes.\n\n= 3.0 =\n* Restored notes.\n\n= 2.18 =\n* Legacy notes.\n";
		$actual   = wbc_generate_changelog( $readme, $history );

		self::assertSame( $expected, $actual );
		self::assertSame( $expected, wbc_generate_changelog( $readme, $actual ) );
	}

	/**
	 * Missing release notes must fail the CI check.
	 */
	public function test_missing_release_notes_are_rejected(): void {
		$this->expectException( RuntimeException::class );

		wbc_generate_changelog( "== Changelog ==\n\n== Upgrade Notice ==\n", '' );
	}
}
