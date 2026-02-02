<?php
/**
 * Validator helper.
 *
 * Provides validation methods for input data.
 *
 * @package CanilCore
 */

namespace CanilCore\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validator class.
 */
class Validator {

	/**
	 * Validation errors.
	 *
	 * @var array<string, string>
	 */
	private array $errors = array();

	/**
	 * Data being validated.
	 *
	 * @var array<string, mixed>
	 */
	private array $data = array();

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $data Data to validate.
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Validate that a field is required.
	 *
	 * @param string $field   Field name.
	 * @param string $message Error message.
	 * @return self
	 */
	public function required( string $field, string $message = '' ): self {
		$value = $this->get_value( $field );

		if ( empty( $value ) && '0' !== $value && 0 !== $value ) {
			/* translators: %s: field name */
			$this->add_error( $field, $message ?: sprintf( __( 'O campo %s é obrigatório.', 'canil-core' ), $field ) );
		}

		return $this;
	}

	/**
	 * Validate that a field is a valid email.
	 *
	 * @param string $field   Field name.
	 * @param string $message Error message.
	 * @return self
	 */
	public function email( string $field, string $message = '' ): self {
		$value = $this->get_value( $field );

		if ( ! empty( $value ) && ! is_email( $value ) ) {
			$this->add_error( $field, $message ?: __( 'O email informado é inválido.', 'canil-core' ) );
		}

		return $this;
	}

	/**
	 * Validate that a field has a minimum length.
	 *
	 * @param string $field   Field name.
	 * @param int    $min     Minimum length.
	 * @param string $message Error message.
	 * @return self
	 */
	public function min_length( string $field, int $min, string $message = '' ): self {
		$value = $this->get_value( $field );

		if ( is_string( $value ) && strlen( $value ) < $min ) {
			$this->add_error(
				$field,
				/* translators: 1: field name, 2: minimum length */
				$message ?: sprintf( __( 'O campo %1$s deve ter no mínimo %2$d caracteres.', 'canil-core' ), $field, $min )
			);
		}

		return $this;
	}

	/**
	 * Validate that a field has a maximum length.
	 *
	 * @param string $field   Field name.
	 * @param int    $max     Maximum length.
	 * @param string $message Error message.
	 * @return self
	 */
	public function max_length( string $field, int $max, string $message = '' ): self {
		$value = $this->get_value( $field );

		if ( is_string( $value ) && strlen( $value ) > $max ) {
			$this->add_error(
				$field,
				/* translators: 1: field name, 2: maximum length */
				$message ?: sprintf( __( 'O campo %1$s deve ter no máximo %2$d caracteres.', 'canil-core' ), $field, $max )
			);
		}

		return $this;
	}

	/**
	 * Validate that a field is a valid date.
	 *
	 * @param string $field   Field name.
	 * @param string $format  Expected format.
	 * @param string $message Error message.
	 * @return self
	 */
	public function date( string $field, string $format = 'Y-m-d', string $message = '' ): self {
		$value = $this->get_value( $field );

		if ( ! empty( $value ) ) {
			$date = \DateTimeImmutable::createFromFormat( $format, $value );

			if ( false === $date || $date->format( $format ) !== $value ) {
				$this->add_error( $field, $message ?: __( 'A data informada é inválida.', 'canil-core' ) );
			}
		}

		return $this;
	}

	/**
	 * Validate that a field is in a list of allowed values.
	 *
	 * @param string        $field   Field name.
	 * @param array<string> $allowed Allowed values.
	 * @param string        $message Error message.
	 * @return self
	 */
	public function in( string $field, array $allowed, string $message = '' ): self {
		$value = $this->get_value( $field );

		if ( ! empty( $value ) && ! in_array( $value, $allowed, true ) ) {
			$this->add_error(
				$field,
				/* translators: %s: field name */
				$message ?: sprintf( __( 'O valor do campo %s é inválido.', 'canil-core' ), $field )
			);
		}

		return $this;
	}

	/**
	 * Validate that a field is numeric.
	 *
	 * @param string $field   Field name.
	 * @param string $message Error message.
	 * @return self
	 */
	public function numeric( string $field, string $message = '' ): self {
		$value = $this->get_value( $field );

		if ( ! empty( $value ) && ! is_numeric( $value ) ) {
			$this->add_error(
				$field,
				/* translators: %s: field name */
				$message ?: sprintf( __( 'O campo %s deve ser numérico.', 'canil-core' ), $field )
			);
		}

		return $this;
	}

	/**
	 * Validate that a field is a positive integer.
	 *
	 * @param string $field   Field name.
	 * @param string $message Error message.
	 * @return self
	 */
	public function positive_int( string $field, string $message = '' ): self {
		$value = $this->get_value( $field );

		if ( ! empty( $value ) && ( ! is_numeric( $value ) || (int) $value <= 0 ) ) {
			$this->add_error(
				$field,
				/* translators: %s: field name */
				$message ?: sprintf( __( 'O campo %s deve ser um número inteiro positivo.', 'canil-core' ), $field )
			);
		}

		return $this;
	}

	/**
	 * Validate that a field is a valid URL.
	 *
	 * @param string $field   Field name.
	 * @param string $message Error message.
	 * @return self
	 */
	public function url( string $field, string $message = '' ): self {
		$value = $this->get_value( $field );

		if ( ! empty( $value ) && ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
			$this->add_error( $field, $message ?: __( 'A URL informada é inválida.', 'canil-core' ) );
		}

		return $this;
	}

	/**
	 * Get value from data.
	 *
	 * @param string $field Field name (supports dot notation).
	 * @return mixed Field value.
	 */
	private function get_value( string $field ): mixed {
		$keys  = explode( '.', $field );
		$value = $this->data;

		foreach ( $keys as $key ) {
			if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) {
				return null;
			}
			$value = $value[ $key ];
		}

		return $value;
	}

	/**
	 * Add an error.
	 *
	 * @param string $field   Field name.
	 * @param string $message Error message.
	 */
	private function add_error( string $field, string $message ): void {
		if ( ! isset( $this->errors[ $field ] ) ) {
			$this->errors[ $field ] = $message;
		}
	}

	/**
	 * Check if validation passed.
	 *
	 * @return bool True if no errors.
	 */
	public function passes(): bool {
		return empty( $this->errors );
	}

	/**
	 * Check if validation failed.
	 *
	 * @return bool True if there are errors.
	 */
	public function fails(): bool {
		return ! $this->passes();
	}

	/**
	 * Get all errors.
	 *
	 * @return array<string, string> Validation errors.
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Get first error message.
	 *
	 * @return string|null First error message or null.
	 */
	public function get_first_error(): ?string {
		return ! empty( $this->errors ) ? reset( $this->errors ) : null;
	}

	/**
	 * Create a new validator instance.
	 *
	 * @param array<string, mixed> $data Data to validate.
	 * @return self New validator instance.
	 */
	public static function make( array $data ): self {
		return new self( $data );
	}
}
