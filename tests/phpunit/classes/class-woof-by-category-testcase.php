<?php
/**
 * Woof_By_Category_TestCase class file.
 *
 * @package woof-by-category
 */

use PHPUnit\Framework\TestCase;
use tad\FunctionMocker\FunctionMocker;

/**
 * Class Woof_By_Category_TestCase
 */
abstract class Woof_By_Category_TestCase extends TestCase {

	/**
	 * Setup test
	 */
	public function setUp(): void {
		FunctionMocker::setUp();
		parent::setUp();
		WP_Mock::setUp();
	}

	/**
	 * End test
	 */
	public function tearDown(): void {
		WP_Mock::tearDown();
		Mockery::close();
		parent::tearDown();
		FunctionMocker::tearDown();
	}

	/**
	 * Get an object protected property.
	 *
	 * @param object $object        Object.
	 * @param string $property_name Property name.
	 *
	 * @return mixed
	 *
	 * @throws ReflectionException Reflection exception.
	 */
	protected function get_protected_property( $object, $property_name ) {
		$reflection_class = new ReflectionClass( $object );

		$property = $reflection_class->getProperty( $property_name );
		$property->setAccessible( true );
		$value = $property->getValue( $object );
		$property->setAccessible( false );

		return $value;
	}

	/**
	 * Set an object protected property.
	 *
	 * @param object $object        Object.
	 * @param string $property_name Property name.
	 * @param mixed  $value         Property vale.
	 *
	 * @throws ReflectionException Reflection exception.
	 */
	protected function set_protected_property( $object, $property_name, $value ) {
		$reflection_class = new ReflectionClass( $object );

		$property = $reflection_class->getProperty( $property_name );
		$property->setAccessible( true );
		$property->setValue( $object, $value );
		$property->setAccessible( false );
	}

	/**
	 * Set an object protected method accessibility.
	 *
	 * @param object $object      Object.
	 * @param string $method_name Property name.
	 * @param bool   $accessible  Property vale.
	 *
	 * @return ReflectionMethod
	 *
	 * @throws ReflectionException Reflection exception.
	 */
	protected function set_method_accessibility( $object, $method_name, $accessible = true ) {
		$reflection_class = new ReflectionClass( $object );

		$method = $reflection_class->getMethod( $method_name );
		$method->setAccessible( $accessible );

		return $method;
	}

	/**
	 * Retrieve metadata from a file.
	 *
	 * Searches for metadata in the first 8 KB of a file, such as a plugin or theme.
	 * Each piece of metadata must be on its own line. Fields can not span multiple
	 * lines, the value will get cut at the end of the first line.
	 *
	 * If the file data is not within that first 8 KB, then the author should correct
	 * their plugin file and move the data headers to the top.
	 *
	 * @link  https://codex.wordpress.org/File_Header
	 *
	 * @since 2.9.0
	 *
	 * @param string $file            Absolute path to the file.
	 * @param array  $default_headers List of headers, in the format `array( 'HeaderKey' => 'Header Name' )`.
	 * @param string $context         Optional. If specified adds filter hook {@see 'extra_$context_headers'}.
	 *                                Default empty.
	 *
	 * @return string[] Array of file header values keyed by header name.
	 */
	protected function get_file_data( $file, $default_headers, $context = '' ) {
		// We don't need to write to the file, so just open for reading.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$fp = fopen( $file, 'rb' );

		if ( $fp ) {
			// Pull only the first 8 KB of the file in.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fread
			$file_data = fread( $fp, 8 * KB_IN_BYTES );

			// PHP will close file handle, but we are good citizens.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			fclose( $fp );
		} else {
			$file_data = '';
		}

		// Make sure we catch CR-only line endings.
		$file_data = str_replace( "\r", "\n", $file_data );

		/**
		 * Filters extra file headers by context.
		 *
		 * The dynamic portion of the hook name, `$context`, refers to
		 * the context where extra headers might be loaded.
		 *
		 * @param array $extra_context_headers Empty array by default.
		 *
		 * @since 2.9.0
		 */
		$extra_headers = $context ? apply_filters( "extra_{$context}_headers", [] ) : [];
		if ( $extra_headers ) {
			$extra_headers = array_combine( $extra_headers, $extra_headers ); // Keys equal values.
			$all_headers   = array_merge( $extra_headers, (array) $default_headers );
		} else {
			$all_headers = $default_headers;
		}

		foreach ( $all_headers as $field => $regex ) {
			if ( preg_match( '/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match ) && $match[1] ) {
				$all_headers[ $field ] = $this->cleanup_header_comment( $match[1] );
			} else {
				$all_headers[ $field ] = '';
			}
		}

		return $all_headers;
	}

	/**
	 * Strip close comment and close php tags from file headers used by WP.
	 *
	 * @since 2.8.0
	 * @access private
	 *
	 * @see https://core.trac.wordpress.org/ticket/8497
	 *
	 * @param string $str Header comment to clean up.
	 * @return string
	 */
	private function cleanup_header_comment( $str ) {
		return trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $str ) );
	}
}
