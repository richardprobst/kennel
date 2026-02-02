<?php
/**
 * Date helper.
 *
 * Provides date manipulation utilities.
 *
 * @package CanilCore
 */

namespace CanilCore\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DateHelper class.
 */
class DateHelper {

	/**
	 * Default gestation period in days.
	 */
	public const GESTATION_DAYS = 63;

	/**
	 * Parse an ISO 8601 date string.
	 *
	 * @param string|null $date Date string.
	 * @return \DateTimeImmutable|null Parsed date or null.
	 */
	public static function parse_iso( ?string $date ): ?\DateTimeImmutable {
		if ( empty( $date ) ) {
			return null;
		}

		// Try various formats.
		$formats = array(
			'Y-m-d',
			'Y-m-d H:i:s',
			\DateTimeInterface::ATOM,
			\DateTimeInterface::ISO8601,
		);

		foreach ( $formats as $format ) {
			$parsed = \DateTimeImmutable::createFromFormat( $format, $date );
			if ( false !== $parsed ) {
				return $parsed;
			}
		}

		// Try strtotime as fallback.
		$timestamp = strtotime( $date );
		if ( false !== $timestamp ) {
			return ( new \DateTimeImmutable() )->setTimestamp( $timestamp );
		}

		return null;
	}

	/**
	 * Format a date for database storage.
	 *
	 * @param \DateTimeInterface|string|null $date Date to format.
	 * @return string|null Formatted date or null.
	 */
	public static function to_db( \DateTimeInterface|string|null $date ): ?string {
		if ( null === $date ) {
			return null;
		}

		if ( is_string( $date ) ) {
			$date = self::parse_iso( $date );
		}

		if ( null === $date ) {
			return null;
		}

		return $date->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Format a date for display.
	 *
	 * @param \DateTimeInterface|string|null $date   Date to format.
	 * @param string                         $format PHP date format.
	 * @return string|null Formatted date or null.
	 */
	public static function format( \DateTimeInterface|string|null $date, string $format = 'd/m/Y' ): ?string {
		if ( null === $date ) {
			return null;
		}

		if ( is_string( $date ) ) {
			$date = self::parse_iso( $date );
		}

		if ( null === $date ) {
			return null;
		}

		return $date->format( $format );
	}

	/**
	 * Calculate expected birth date from mating date.
	 *
	 * @param \DateTimeInterface|string $mating_date Mating date.
	 * @param int                       $days        Gestation days (default 63).
	 * @return \DateTimeImmutable Expected birth date.
	 */
	public static function calculate_expected_birth(
		\DateTimeInterface|string $mating_date,
		int $days = self::GESTATION_DAYS
	): \DateTimeImmutable {
		if ( is_string( $mating_date ) ) {
			$mating_date = self::parse_iso( $mating_date );
		}

		if ( null === $mating_date ) {
			throw new \InvalidArgumentException( 'Invalid mating date' );
		}

		return $mating_date->add( new \DateInterval( "P{$days}D" ) );
	}

	/**
	 * Calculate age in years, months, and days.
	 *
	 * @param \DateTimeInterface|string      $birth_date Birth date.
	 * @param \DateTimeInterface|string|null $reference  Reference date (default: today).
	 * @return array{years: int, months: int, days: int} Age components.
	 */
	public static function calculate_age(
		\DateTimeInterface|string $birth_date,
		\DateTimeInterface|string|null $reference = null
	): array {
		if ( is_string( $birth_date ) ) {
			$birth_date = self::parse_iso( $birth_date );
		}

		if ( null === $birth_date ) {
			return array(
				'years'  => 0,
				'months' => 0,
				'days'   => 0,
			);
		}

		if ( null === $reference ) {
			$reference = new \DateTimeImmutable();
		} elseif ( is_string( $reference ) ) {
			$reference = self::parse_iso( $reference );
		}

		$interval = $birth_date->diff( $reference );

		return array(
			'years'  => $interval->y,
			'months' => $interval->m,
			'days'   => $interval->d,
		);
	}

	/**
	 * Format age as a human-readable string.
	 *
	 * @param \DateTimeInterface|string $birth_date Birth date.
	 * @return string Formatted age string.
	 */
	public static function format_age( \DateTimeInterface|string $birth_date ): string {
		$age = self::calculate_age( $birth_date );

		$parts = array();

		if ( $age['years'] > 0 ) {
			$parts[] = sprintf(
				/* translators: %d: number of years */
				_n( '%d ano', '%d anos', $age['years'], 'canil-core' ),
				$age['years']
			);
		}

		if ( $age['months'] > 0 ) {
			$parts[] = sprintf(
				/* translators: %d: number of months */
				_n( '%d mês', '%d meses', $age['months'], 'canil-core' ),
				$age['months']
			);
		}

		if ( empty( $parts ) ) {
			$parts[] = sprintf(
				/* translators: %d: number of days */
				_n( '%d dia', '%d dias', $age['days'], 'canil-core' ),
				$age['days']
			);
		}

		return implode( ' e ', $parts );
	}

	/**
	 * Check if a date is in the past.
	 *
	 * @param \DateTimeInterface|string $date Date to check.
	 * @return bool True if date is in the past.
	 */
	public static function is_past( \DateTimeInterface|string $date ): bool {
		if ( is_string( $date ) ) {
			$date = self::parse_iso( $date );
		}

		if ( null === $date ) {
			return false;
		}

		return $date < new \DateTimeImmutable();
	}

	/**
	 * Check if a date is in the future.
	 *
	 * @param \DateTimeInterface|string $date Date to check.
	 * @return bool True if date is in the future.
	 */
	public static function is_future( \DateTimeInterface|string $date ): bool {
		if ( is_string( $date ) ) {
			$date = self::parse_iso( $date );
		}

		if ( null === $date ) {
			return false;
		}

		return $date > new \DateTimeImmutable();
	}

	/**
	 * Get current datetime.
	 *
	 * @return \DateTimeImmutable Current datetime.
	 */
	public static function now(): \DateTimeImmutable {
		return new \DateTimeImmutable();
	}

	/**
	 * Get today's date (time set to midnight).
	 *
	 * @return \DateTimeImmutable Today's date.
	 */
	public static function today(): \DateTimeImmutable {
		return ( new \DateTimeImmutable() )->setTime( 0, 0, 0 );
	}
}
