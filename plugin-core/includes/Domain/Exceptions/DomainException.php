<?php
/**
 * Domain exception.
 *
 * Base exception for domain errors.
 *
 * @package CanilCore
 */

namespace CanilCore\Domain\Exceptions;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DomainException class.
 */
class DomainException extends \Exception {

	/**
	 * Error code for the exception.
	 *
	 * @var string
	 */
	protected string $error_code;

	/**
	 * Constructor.
	 *
	 * @param string          $message    Error message.
	 * @param string          $error_code Error code.
	 * @param int             $code       HTTP status code.
	 * @param \Throwable|null $previous   Previous exception.
	 */
	public function __construct(
		string $message,
		string $error_code = 'domain_error',
		int $code = 400,
		?\Throwable $previous = null
	) {
		parent::__construct( $message, $code, $previous );
		$this->error_code = $error_code;
	}

	/**
	 * Get the error code.
	 *
	 * @return string Error code.
	 */
	public function get_error_code(): string {
		return $this->error_code;
	}

	/**
	 * Convert to WP_Error.
	 *
	 * @return \WP_Error WordPress error object.
	 */
	public function to_wp_error(): \WP_Error {
		return new \WP_Error(
			$this->error_code,
			$this->getMessage(),
			array( 'status' => $this->getCode() )
		);
	}
}
