<?php
/**
 * Not found exception.
 *
 * Thrown when entity is not found.
 *
 * @package CanilCore
 */

namespace CanilCore\Domain\Exceptions;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NotFoundException class.
 */
class NotFoundException extends DomainException {

	/**
	 * Constructor.
	 *
	 * @param string $message Error message.
	 */
	public function __construct( string $message = '' ) {
		if ( empty( $message ) ) {
			$message = __( 'Registro não encontrado.', 'canil-core' );
		}

		parent::__construct( $message, 'not_found', 404 );
	}
}
