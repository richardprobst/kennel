<?php
/**
 * Public Puppies REST Controller.
 *
 * REST API controller for public puppy listings.
 *
 * @package CanilSitePublico
 */

namespace CanilSitePublico\Rest\Controllers;

use CanilSitePublico\Domain\PublicPuppyService;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PublicPuppiesController class.
 */
class PublicPuppiesController {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	private string $namespace = 'canil-site-publico/v1';

	/**
	 * Puppy service.
	 *
	 * @var PublicPuppyService
	 */
	private PublicPuppyService $service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->service = new PublicPuppyService();
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// GET /puppies - List available puppies (public).
		register_rest_route(
			$this->namespace,
			'/puppies',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_puppies' ),
				'permission_callback' => '__return_true', // Public endpoint.
				'args'                => array(
					'status' => array(
						'description' => __( 'Status do filhote.', 'canil-site-publico' ),
						'type'        => 'string',
						'enum'        => array( 'available', 'reserved' ),
						'default'     => 'available',
					),
					'breed'  => array(
						'description' => __( 'Raça do filhote.', 'canil-site-publico' ),
						'type'        => 'string',
					),
					'sex'    => array(
						'description' => __( 'Sexo do filhote.', 'canil-site-publico' ),
						'type'        => 'string',
						'enum'        => array( 'male', 'female' ),
					),
					'color'  => array(
						'description' => __( 'Cor do filhote.', 'canil-site-publico' ),
						'type'        => 'string',
					),
					'limit'  => array(
						'description' => __( 'Número máximo de resultados.', 'canil-site-publico' ),
						'type'        => 'integer',
						'default'     => 12,
						'minimum'     => 1,
						'maximum'     => 100,
					),
				),
			)
		);

		// GET /puppies/{id} - Get single puppy detail (public).
		register_rest_route(
			$this->namespace,
			'/puppies/(?P<id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_puppy' ),
				'permission_callback' => '__return_true', // Public endpoint.
				'args'                => array(
					'id' => array(
						'description' => __( 'ID do filhote.', 'canil-site-publico' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
			)
		);

		// GET /puppies/filters - Get available filter options.
		register_rest_route(
			$this->namespace,
			'/puppies/filters',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_filters' ),
				'permission_callback' => '__return_true', // Public endpoint.
			)
		);
	}

	/**
	 * Get puppies list.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response object.
	 */
	public function get_puppies( \WP_REST_Request $request ): \WP_REST_Response {
		$filters = array(
			'status' => sanitize_text_field( $request->get_param( 'status' ) ?? 'available' ),
		);

		// Add optional filters.
		if ( $request->get_param( 'breed' ) ) {
			$filters['breed'] = sanitize_text_field( $request->get_param( 'breed' ) );
		}
		if ( $request->get_param( 'sex' ) ) {
			$filters['sex'] = sanitize_text_field( $request->get_param( 'sex' ) );
		}
		if ( $request->get_param( 'color' ) ) {
			$filters['color'] = sanitize_text_field( $request->get_param( 'color' ) );
		}

		$limit   = absint( $request->get_param( 'limit' ) ?? 12 );
		$puppies = $this->service->get_available_puppies( $filters, $limit );

		return new \WP_REST_Response(
			array(
				'data'  => $puppies,
				'total' => count( $puppies ),
			)
		);
	}

	/**
	 * Get single puppy detail.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_puppy( \WP_REST_Request $request ) {
		$puppy_id = absint( $request->get_param( 'id' ) );
		$puppy    = $this->service->get_puppy_detail( $puppy_id );

		if ( ! $puppy ) {
			return new \WP_Error(
				'not_found',
				__( 'Filhote não encontrado.', 'canil-site-publico' ),
				array( 'status' => 404 )
			);
		}

		return new \WP_REST_Response( array( 'data' => $puppy ) );
	}

	/**
	 * Get available filter options.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response object.
	 */
	public function get_filters( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'data' => array(
					'breeds' => $this->service->get_available_breeds(),
					'colors' => $this->service->get_available_colors(),
					'sexes'  => array(
						array(
							'value' => 'male',
							'label' => __( 'Macho', 'canil-site-publico' ),
						),
						array(
							'value' => 'female',
							'label' => __( 'Fêmea', 'canil-site-publico' ),
						),
					),
				),
			)
		);
	}
}
