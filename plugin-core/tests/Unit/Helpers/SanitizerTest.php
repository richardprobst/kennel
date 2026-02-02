<?php
/**
 * Unit tests for Sanitizer.
 *
 * @package CanilCore
 */

namespace CanilCore\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use CanilCore\Helpers\Sanitizer;

/**
 * SanitizerTest class.
 */
class SanitizerTest extends TestCase {

	/**
	 * Test text sanitization.
	 */
	public function test_text(): void {
		$this->assertEquals( 'Hello World', Sanitizer::text( '  Hello World  ' ) );
		$this->assertEquals( 'Test', Sanitizer::text( '<script>Test</script>' ) );
		$this->assertEquals( '', Sanitizer::text( null ) );
		$this->assertEquals( '', Sanitizer::text( 123 ) );
	}

	/**
	 * Test email sanitization.
	 */
	public function test_email(): void {
		$this->assertEquals( 'test@example.com', Sanitizer::email( 'test@example.com' ) );
		$this->assertEquals( 'test@example.com', Sanitizer::email( ' test@example.com ' ) );
		$this->assertEquals( '', Sanitizer::email( 'invalid-email' ) );
	}

	/**
	 * Test integer sanitization.
	 */
	public function test_int(): void {
		$this->assertEquals( 42, Sanitizer::int( '42' ) );
		$this->assertEquals( 42, Sanitizer::int( 42.7 ) );
		$this->assertEquals( 0, Sanitizer::int( -5 ) ); // absint returns 0 for negative
		$this->assertEquals( 0, Sanitizer::int( 'abc' ) );
	}

	/**
	 * Test float sanitization.
	 */
	public function test_float(): void {
		$this->assertEquals( 42.5, Sanitizer::float( '42.5' ) );
		$this->assertEquals( 42.0, Sanitizer::float( 42 ) );
		$this->assertEquals( -5.5, Sanitizer::float( '-5.5' ) );
	}

	/**
	 * Test URL sanitization.
	 */
	public function test_url(): void {
		$this->assertEquals( 'https://example.com', Sanitizer::url( 'https://example.com' ) );
		$this->assertEquals( 'http://example.com/path', Sanitizer::url( 'http://example.com/path' ) );
		$this->assertEquals( '', Sanitizer::url( null ) );
	}

	/**
	 * Test date sanitization.
	 */
	public function test_date(): void {
		$this->assertEquals( '2024-01-15', Sanitizer::date( '2024-01-15' ) );
		$this->assertNull( Sanitizer::date( 'invalid' ) );
		$this->assertNull( Sanitizer::date( '' ) );
		$this->assertNull( Sanitizer::date( null ) );
	}

	/**
	 * Test enum sanitization.
	 */
	public function test_enum(): void {
		$allowed = array( 'active', 'inactive', 'pending' );

		$this->assertEquals( 'active', Sanitizer::enum( 'active', $allowed ) );
		$this->assertEquals( 'pending', Sanitizer::enum( 'pending', $allowed ) );
		$this->assertNull( Sanitizer::enum( 'unknown', $allowed ) );
		$this->assertEquals( 'default', Sanitizer::enum( 'unknown', $allowed, 'default' ) );
	}

	/**
	 * Test JSON sanitization.
	 */
	public function test_json(): void {
		// Array input.
		$array = array( 'key' => 'value' );
		$this->assertEquals( $array, Sanitizer::json( $array ) );

		// JSON string input.
		$json_string = '{"key": "value"}';
		$this->assertEquals( array( 'key' => 'value' ), Sanitizer::json( $json_string ) );

		// Invalid JSON.
		$this->assertNull( Sanitizer::json( 'not json' ) );
		$this->assertNull( Sanitizer::json( null ) );
	}

	/**
	 * Test phone sanitization.
	 */
	public function test_phone(): void {
		$this->assertEquals( '+5511999999999', Sanitizer::phone( '+55 (11) 99999-9999' ) );
		$this->assertEquals( '11999999999', Sanitizer::phone( '(11) 99999-9999' ) );
		$this->assertEquals( '', Sanitizer::phone( null ) );
	}
}
