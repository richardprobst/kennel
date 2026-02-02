<?php
/**
 * Pedigree REST Controller.
 *
 * REST API controller for pedigree/genealogy.
 *
 * @package CanilCore
 */

namespace CanilCore\Rest\Controllers;

use CanilCore\Domain\Services\PedigreeService;
use CanilCore\Helpers\Sanitizer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PedigreeController class.
 */
class PedigreeController extends BaseController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'pedigree';

	/**
	 * Required capability.
	 *
	 * @var string
	 */
	protected string $capability = 'manage_dogs';

	/**
	 * Pedigree service.
	 *
	 * @var PedigreeService
	 */
	private PedigreeService $service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->service = new PedigreeService();
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// GET /pedigree/{id} - Get pedigree tree for a dog.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_pedigree' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'id'          => array(
						'description' => __( 'ID do cão.', 'canil-core' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'generations' => array(
						'description' => __( 'Número de gerações (1-5).', 'canil-core' ),
						'type'        => 'integer',
						'default'     => 3,
						'minimum'     => 1,
						'maximum'     => 5,
					),
				),
			)
		);

		// GET /pedigree/{id}/flat - Get pedigree as flat list (for PDF).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/flat',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_pedigree_flat' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'id'          => array(
						'description' => __( 'ID do cão.', 'canil-core' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'generations' => array(
						'description' => __( 'Número de gerações (1-5).', 'canil-core' ),
						'type'        => 'integer',
						'default'     => 3,
						'minimum'     => 1,
						'maximum'     => 5,
					),
				),
			)
		);

		// GET /pedigree/{id}/offspring - Get offspring of a dog.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/offspring',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_offspring' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'id' => array(
						'description' => __( 'ID do cão.', 'canil-core' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
			)
		);

		// GET /pedigree/{id}/siblings - Get siblings of a dog.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/siblings',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_siblings' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'id'            => array(
						'description' => __( 'ID do cão.', 'canil-core' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'full_siblings' => array(
						'description' => __( 'Somente irmãos completos (mesmo pai e mãe).', 'canil-core' ),
						'type'        => 'boolean',
						'default'     => true,
					),
				),
			)
		);
	}

	/**
	 * Get pedigree tree for a dog.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_pedigree( \WP_REST_Request $request ) {
		try {
			$id          = absint( $request->get_param( 'id' ) );
			$generations = Sanitizer::int( $request->get_param( 'generations' ) ) ?: 3;

			$result = $this->service->get_pedigree( $id, $generations );

			return new \WP_REST_Response(
				array( 'data' => $result )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get pedigree as flat list.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_pedigree_flat( \WP_REST_Request $request ) {
		try {
			$id          = absint( $request->get_param( 'id' ) );
			$generations = Sanitizer::int( $request->get_param( 'generations' ) ) ?: 3;

			$result = $this->service->get_pedigree_flat( $id, $generations );

			return new \WP_REST_Response(
				array( 'data' => $result )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get offspring of a dog.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_offspring( \WP_REST_Request $request ) {
		try {
			$id = absint( $request->get_param( 'id' ) );

			$offspring = $this->service->get_offspring( $id );

			return new \WP_REST_Response(
				array( 'data' => $offspring )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get siblings of a dog.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_siblings( \WP_REST_Request $request ) {
		try {
			$id            = absint( $request->get_param( 'id' ) );
			$full_siblings = (bool) $request->get_param( 'full_siblings' );

			$siblings = $this->service->get_siblings( $id, $full_siblings );

			return new \WP_REST_Response(
				array( 'data' => $siblings )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}
}
