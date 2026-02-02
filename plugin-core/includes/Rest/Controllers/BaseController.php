<?php
/**
 * Base REST Controller.
 *
 * Abstract base class for all REST API controllers.
 *
 * @package CanilCore
 */

namespace CanilCore\Rest\Controllers;

use CanilCore\Domain\Exceptions\DomainException;
use CanilCore\Domain\Exceptions\UnauthorizedException;
use CanilCore\Domain\Exceptions\ForbiddenException;
use CanilCore\Domain\Exceptions\NotFoundException;
use CanilCore\Domain\Exceptions\ValidationException;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BaseController class.
 */
abstract class BaseController {

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected string $namespace = 'canil/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = '';

	/**
	 * Required capability.
	 *
	 * @var string
	 */
	protected string $capability = 'manage_kennel';

	/**
	 * Register routes.
	 */
	abstract public function register_routes(): void;

	/**
	 * Check if user has permission.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool True if user has permission.
	 */
	public function check_permission( \WP_REST_Request $request ): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		return current_user_can( $this->capability );
	}

	/**
	 * Handle exception and return WP_Error.
	 *
	 * @param \Throwable $e Exception.
	 * @return \WP_Error Error response.
	 */
	protected function handle_exception( \Throwable $e ): \WP_Error {
		if ( $e instanceof DomainException ) {
			return $e->to_wp_error();
		}

		// Log unexpected errors.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Canil Core Error: ' . $e->getMessage() );
		}

		return new \WP_Error(
			'internal_error',
			__( 'Ocorreu um erro interno.', 'canil-core' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Format paginated response.
	 *
	 * @param array<string, mixed> $result Repository result with data and meta.
	 * @return \WP_REST_Response Response object.
	 */
	protected function paginated_response( array $result ): \WP_REST_Response {
		$response = new \WP_REST_Response(
			array(
				'data' => $result['data'],
				'meta' => array(
					'total'       => $result['total'],
					'page'        => $result['page'],
					'per_page'    => $result['per_page'],
					'total_pages' => $result['total_pages'],
				),
			)
		);

		return $response;
	}

	/**
	 * Format single item response.
	 *
	 * @param array<string, mixed> $data Entity data.
	 * @return \WP_REST_Response Response object.
	 */
	protected function item_response( array $data ): \WP_REST_Response {
		return new \WP_REST_Response(
			array( 'data' => $data )
		);
	}

	/**
	 * Format success response.
	 *
	 * @param string $message Success message.
	 * @param int    $status  HTTP status code.
	 * @return \WP_REST_Response Response object.
	 */
	protected function success_response( string $message, int $status = 200 ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => $message,
			),
			$status
		);
	}

	/**
	 * Get pagination args from request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array{page: int, per_page: int}
	 */
	protected function get_pagination_args( \WP_REST_Request $request ): array {
		$page     = absint( $request->get_param( 'page' ) ) ?: 1;
		$per_page = absint( $request->get_param( 'per_page' ) ) ?: 20;

		// Limit per_page to reasonable values.
		$per_page = min( max( $per_page, 1 ), 100 );

		return array(
			'page'     => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * Get common list arguments schema.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	protected function get_collection_params(): array {
		return array(
			'page'     => array(
				'description' => __( 'Página atual.', 'canil-core' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			),
			'per_page' => array(
				'description' => __( 'Itens por página.', 'canil-core' ),
				'type'        => 'integer',
				'default'     => 20,
				'minimum'     => 1,
				'maximum'     => 100,
			),
			'search'   => array(
				'description' => __( 'Termo de busca.', 'canil-core' ),
				'type'        => 'string',
			),
			'order_by' => array(
				'description' => __( 'Campo para ordenação.', 'canil-core' ),
				'type'        => 'string',
				'default'     => 'created_at',
			),
			'order'    => array(
				'description' => __( 'Direção da ordenação.', 'canil-core' ),
				'type'        => 'string',
				'default'     => 'DESC',
				'enum'        => array( 'ASC', 'DESC' ),
			),
		);
	}
}
