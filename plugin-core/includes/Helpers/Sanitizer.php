<?php
/**
 * Sanitizer helper.
 *
 * Provides sanitization methods for input data.
 *
 * @package CanilCore
 */

namespace CanilCore\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitizer class.
 */
class Sanitizer {

	/**
	 * Sanitize a string field.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return string Sanitized string.
	 */
	public static function text( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}
		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize an email field.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return string Sanitized email.
	 */
	public static function email( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}
		return sanitize_email( $value );
	}

	/**
	 * Sanitize a textarea field.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return string Sanitized text.
	 */
	public static function textarea( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}
		return sanitize_textarea_field( $value );
	}

	/**
	 * Sanitize an integer.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return int Sanitized integer.
	 */
	public static function int( mixed $value ): int {
		return absint( $value );
	}

	/**
	 * Sanitize a float.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return float Sanitized float.
	 */
	public static function float( mixed $value ): float {
		return (float) filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
	}

	/**
	 * Sanitize a URL.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return string Sanitized URL.
	 */
	public static function url( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}
		return esc_url_raw( $value );
	}

	/**
	 * Sanitize a date string.
	 *
	 * @param mixed  $value  Value to sanitize.
	 * @param string $format Expected format.
	 * @return string|null Sanitized date or null if invalid.
	 */
	public static function date( mixed $value, string $format = 'Y-m-d' ): ?string {
		if ( ! is_string( $value ) || empty( $value ) ) {
			return null;
		}

		$date = \DateTimeImmutable::createFromFormat( $format, $value );

		if ( false === $date ) {
			// Try ISO 8601 format.
			$date = \DateTimeImmutable::createFromFormat( \DateTimeInterface::ATOM, $value );
		}

		if ( false === $date ) {
			// Try datetime format.
			$date = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $value );
		}

		return $date ? $date->format( $format ) : null;
	}

	/**
	 * Sanitize a datetime string.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return string|null Sanitized datetime or null if invalid.
	 */
	public static function datetime( mixed $value ): ?string {
		return self::date( $value, 'Y-m-d H:i:s' );
	}

	/**
	 * Sanitize an array of values.
	 *
	 * @param mixed    $value    Value to sanitize.
	 * @param callable $callback Sanitization callback for each item.
	 * @return array<mixed> Sanitized array.
	 */
	public static function array( mixed $value, callable $callback ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_map( $callback, $value );
	}

	/**
	 * Sanitize a JSON string.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return array<mixed>|null Decoded array or null if invalid.
	 */
	public static function json( mixed $value ): ?array {
		if ( is_array( $value ) ) {
			return $value;
		}

		if ( ! is_string( $value ) ) {
			return null;
		}

		$decoded = json_decode( $value, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return null;
		}

		return $decoded;
	}

	/**
	 * Sanitize an enum value.
	 *
	 * @param mixed         $value   Value to sanitize.
	 * @param array<string> $allowed Allowed values.
	 * @param string|null   $default Default value if not in allowed.
	 * @return string|null Sanitized value or default.
	 */
	public static function enum( mixed $value, array $allowed, ?string $default = null ): ?string {
		$value = self::text( $value );

		if ( in_array( $value, $allowed, true ) ) {
			return $value;
		}

		return $default;
	}

	/**
	 * Sanitize a phone number.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return string Sanitized phone number.
	 */
	public static function phone( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		// Remove all non-numeric characters except + at the start.
		$phone = preg_replace( '/[^0-9+]/', '', $value );

		return $phone ?? '';
	}
}
