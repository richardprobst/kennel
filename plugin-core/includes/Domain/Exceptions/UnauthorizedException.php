<?php
/**
 * Unauthorized exception.
 *
 * Thrown when user is not authenticated.
 *
 * @package CanilCore
 */

namespace CanilCore\Domain\Exceptions;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UnauthorizedException class.
 */
class UnauthorizedException extends DomainException {

	/**
	 * Constructor.
	 *
	 * @param string $message Error message.
	 */
	public function __construct( string $message = '' ) {
		if ( empty( $message ) ) {
			$message = __( 'Autenticação necessária.', 'canil-core' );
		}

		parent::__construct( $message, 'unauthorized', 401 );
	}
}
