<?php
/**
 * Unit tests for DateHelper.
 *
 * @package CanilCore
 */

namespace CanilCore\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use CanilCore\Helpers\DateHelper;

/**
 * DateHelperTest class.
 */
class DateHelperTest extends TestCase {

	/**
	 * Test parse ISO date.
	 */
	public function test_parse_iso_with_date(): void {
		$date = DateHelper::parse_iso( '2024-01-15' );

		$this->assertNotNull( $date );
		$this->assertEquals( '2024-01-15', $date->format( 'Y-m-d' ) );
	}

	/**
	 * Test parse ISO datetime.
	 */
	public function test_parse_iso_with_datetime(): void {
		$date = DateHelper::parse_iso( '2024-01-15 10:30:00' );

		$this->assertNotNull( $date );
		$this->assertEquals( '2024-01-15', $date->format( 'Y-m-d' ) );
		$this->assertEquals( '10:30:00', $date->format( 'H:i:s' ) );
	}

	/**
	 * Test parse ISO with null.
	 */
	public function test_parse_iso_with_null(): void {
		$date = DateHelper::parse_iso( null );

		$this->assertNull( $date );
	}

	/**
	 * Test parse ISO with empty string.
	 */
	public function test_parse_iso_with_empty(): void {
		$date = DateHelper::parse_iso( '' );

		$this->assertNull( $date );
	}

	/**
	 * Test calculate expected birth date.
	 */
	public function test_calculate_expected_birth(): void {
		$mating_date = new \DateTimeImmutable( '2024-01-01' );
		$expected    = DateHelper::calculate_expected_birth( $mating_date );

		$this->assertEquals( '2024-03-04', $expected->format( 'Y-m-d' ) ); // 63 days later
	}

	/**
	 * Test calculate expected birth with custom days.
	 */
	public function test_calculate_expected_birth_custom_days(): void {
		$mating_date = new \DateTimeImmutable( '2024-01-01' );
		$expected    = DateHelper::calculate_expected_birth( $mating_date, 65 );

		$this->assertEquals( '2024-03-06', $expected->format( 'Y-m-d' ) ); // 65 days later
	}

	/**
	 * Test calculate age.
	 */
	public function test_calculate_age(): void {
		$birth_date = new \DateTimeImmutable( '2022-06-15' );
		$reference  = new \DateTimeImmutable( '2024-08-20' );
		$age        = DateHelper::calculate_age( $birth_date, $reference );

		$this->assertEquals( 2, $age['years'] );
		$this->assertEquals( 2, $age['months'] );
		$this->assertEquals( 5, $age['days'] );
	}

	/**
	 * Test to_db format.
	 */
	public function test_to_db(): void {
		$date = new \DateTimeImmutable( '2024-01-15 10:30:00' );

		$this->assertEquals( '2024-01-15 10:30:00', DateHelper::to_db( $date ) );
	}

	/**
	 * Test format.
	 */
	public function test_format(): void {
		$date = new \DateTimeImmutable( '2024-01-15' );

		$this->assertEquals( '15/01/2024', DateHelper::format( $date ) );
		$this->assertEquals( '2024-01-15', DateHelper::format( $date, 'Y-m-d' ) );
	}

	/**
	 * Test is_past.
	 */
	public function test_is_past(): void {
		$past   = new \DateTimeImmutable( '2020-01-01' );
		$future = new \DateTimeImmutable( '2030-01-01' );

		$this->assertTrue( DateHelper::is_past( $past ) );
		$this->assertFalse( DateHelper::is_past( $future ) );
	}

	/**
	 * Test is_future.
	 */
	public function test_is_future(): void {
		$past   = new \DateTimeImmutable( '2020-01-01' );
		$future = new \DateTimeImmutable( '2030-01-01' );

		$this->assertFalse( DateHelper::is_future( $past ) );
		$this->assertTrue( DateHelper::is_future( $future ) );
	}
}
