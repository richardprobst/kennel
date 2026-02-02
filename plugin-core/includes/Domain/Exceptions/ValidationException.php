<?php
/**
 * Validation exception.
 *
 * Thrown when validation fails.
 *
 * @package CanilCore
 */

namespace CanilCore\Domain\Exceptions;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ValidationException class.
 */
class ValidationException extends DomainException {

	/**
	 * Validation errors.
	 *
	 * @var array<string, string>
	 */
	private array $errors;

	/**
	 * Constructor.
	 *
	 * @param array<string, string> $errors  Validation errors.
	 * @param string                $message Error message.
	 */
	public function __construct( array $errors, string $message = '' ) {
		$this->errors = $errors;

		if ( empty( $message ) ) {
			$message = __( 'Erro de validação.', 'canil-core' );
		}

		parent::__construct( $message, 'validation_error', 422 );
	}

	/**
	 * Get validation errors.
	 *
	 * @return array<string, string> Validation errors.
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Convert to WP_Error.
	 *
	 * @return \WP_Error WordPress error object.
	 */
	public function to_wp_error(): \WP_Error {
		return new \WP_Error(
			$this->get_error_code(),
			$this->getMessage(),
			array(
				'status' => $this->getCode(),
				'errors' => $this->errors,
			)
		);
	}
}
