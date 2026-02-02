<?php
/**
 * Forbidden exception.
 *
 * Thrown when user lacks permission.
 *
 * @package CanilCore
 */

namespace CanilCore\Domain\Exceptions;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ForbiddenException class.
 */
class ForbiddenException extends DomainException {

	/**
	 * Constructor.
	 *
	 * @param string $message Error message.
	 */
	public function __construct( string $message = '' ) {
		if ( empty( $message ) ) {
			$message = __( 'Você não tem permissão para realizar esta ação.', 'canil-core' );
		}

		parent::__construct( $message, 'forbidden', 403 );
	}
}
