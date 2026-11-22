<?php
/**
 * Generate changelog.txt from the readme.txt release notes.
 *
 * @package woof-by-category
 */

/**
 * Merge readme releases with the archived history by version.
 *
 * @param string $readme    Readme content.
 * @param string $changelog Changelog content.
 *
 * @return string
 * @throws RuntimeException When the readme has no release notes.
 */
function wbc_generate_changelog( string $readme, string $changelog ): string {
	$readme    = str_replace( [ "\r\n", "\r" ], "\n", $readme );
	$changelog = str_replace( [ "\r\n", "\r" ], "\n", $changelog );

	if ( ! preg_match( '/^== Changelog ==[ \t]*$\n(.*?)(?=^== [^=\n]+ ==[ \t]*$|\z)/ms', $readme, $section ) ) {
		throw new RuntimeException( 'No Changelog section found in readme.txt.' );
	}

	$notes    = preg_split( '/^\[See changelog for all versions\]/m', $section[1], 2 )[0];
	$releases = wbc_parse_changelog_releases( $notes );

	if ( ! $releases ) {
		throw new RuntimeException( 'No release notes found in readme.txt.' );
	}

	$releases += wbc_parse_changelog_releases( $changelog );
	uksort(
		$releases,
		static function ( string $first, string $second ): int {
			return version_compare( $second, $first );
		}
	);

	return implode( "\n\n", $releases ) . "\n";
}

/**
 * Extract versioned release sections, including legacy two-part versions.
 *
 * @param string $notes Changelog text with normalized line endings.
 *
 * @return string[]
 */
function wbc_parse_changelog_releases( string $notes ): array {
	$pattern = '/^= ([0-9]+(?:\.[0-9]+){1,2}(?:-[^\s=]+)?) =[ \t]*\n.*?(?=^= [^=\n]+ =[ \t]*$|\z)/ms';
	preg_match_all( $pattern, $notes, $matches, PREG_SET_ORDER );

	$releases = [];

	foreach ( $matches as $match ) {
		$releases[ $match[1] ] = rtrim( $match[0], "\n" );
	}

	return $releases;
}

/**
 * Read a tracked file from the Git index, including partially staged changes.
 *
 * @param string $file Tracked file name.
 *
 * @return string
 * @throws RuntimeException When the file cannot be read.
 */
function wbc_read_staged_changelog_file( string $file ): string {
	exec( 'git show ' . escapeshellarg( ':' . $file ), $lines, $exit_code );

	if ( 0 !== $exit_code ) {
		throw new RuntimeException( 'Could not read the staged ' . $file . '.' );
	}

	return implode( "\n", $lines ) . "\n";
}

/**
 * Generate the changelog or check that it is current.
 *
 * @param string[] $arguments Command arguments.
 *
 * @return int
 */
function wbc_update_changelog( array $arguments ): int {
	if ( array_diff( $arguments, [ '--check', '--staged' ] ) ) {
		fwrite( STDERR, "Usage: php .github/scripts/update-changelog.php [--check|--staged]\n" );

		return 1;
	}

	$root = dirname( __DIR__, 2 );
	chdir( $root );

	try {
		if ( in_array( '--staged', $arguments, true ) ) {
			$readme    = wbc_read_staged_changelog_file( 'readme.txt' );
			$changelog = wbc_read_staged_changelog_file( 'changelog.txt' );
		} else {
			$readme    = file_get_contents( $root . '/readme.txt' );
			$changelog = file_get_contents( $root . '/changelog.txt' );
		}

		if ( false === $readme || false === $changelog ) {
			throw new RuntimeException( 'Could not read readme.txt or changelog.txt.' );
		}

		$generated = wbc_generate_changelog( $readme, $changelog );

		if ( in_array( '--check', $arguments, true ) ) {
			if ( $generated !== str_replace( [ "\r\n", "\r" ], "\n", $changelog ) ) {
				fwrite( STDERR, "changelog.txt is out of date. Run composer changelog and commit the result.\n" );

				return 1;
			}

			echo "Changelog is up to date.\n";

			return 0;
		}

		if ( false === file_put_contents( $root . '/changelog.txt', $generated ) ) {
			throw new RuntimeException( 'Could not write changelog.txt.' );
		}
	} catch ( RuntimeException $exception ) {
		fwrite( STDERR, $exception->getMessage() . "\n" );

		return 1;
	}

	echo "Changelog generated from readme.txt.\n";

	return 0;
}

if ( isset( $argv[0] ) && realpath( $argv[0] ) === __FILE__ ) {
	exit( wbc_update_changelog( array_slice( $argv, 1 ) ) );
}
