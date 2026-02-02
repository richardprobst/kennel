<?php
/**
 * Litters REST Controller.
 *
 * REST API controller for litters.
 *
 * @package CanilCore
 */

namespace CanilCore\Rest\Controllers;

use CanilCore\Infrastructure\Repositories\LitterRepository;
use CanilCore\Domain\Entities\Litter;
use CanilCore\Helpers\Sanitizer;
use CanilCore\Helpers\Validator;
use CanilCore\Domain\Exceptions\ValidationException;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LittersController class.
 */
class LittersController extends BaseController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'litters';

	/**
	 * Required capability.
	 *
	 * @var string
	 */
	protected string $capability = 'manage_litters';

	/**
	 * Litter repository.
	 *
	 * @var LitterRepository
	 */
	private LitterRepository $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new LitterRepository();
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// GET /litters - List litters.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_create_args(),
				),
			)
		);

		// GET/PUT/DELETE /litters/{id}.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id' => array(
							'description' => __( 'ID da ninhada.', 'canil-core' ),
							'type'        => 'integer',
							'required'    => true,
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_update_args(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id' => array(
							'description' => __( 'ID da ninhada.', 'canil-core' ),
							'type'        => 'integer',
							'required'    => true,
						),
					),
				),
			)
		);

		// GET /litters/dropdown - List litters for dropdown.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/dropdown',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_dropdown' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Get collection params.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	protected function get_collection_params(): array {
		$params = parent::get_collection_params();

		$params['status'] = array(
			'description' => __( 'Filtrar por status.', 'canil-core' ),
			'type'        => 'string',
			'enum'        => Litter::get_allowed_statuses(),
		);

		$params['dam_id'] = array(
			'description' => __( 'Filtrar por matriz.', 'canil-core' ),
			'type'        => 'integer',
		);

		$params['sire_id'] = array(
			'description' => __( 'Filtrar por reprodutor.', 'canil-core' ),
			'type'        => 'integer',
		);

		return $params;
	}

	/**
	 * Get create arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_create_args(): array {
		return array(
			'name'                     => array(
				'description'       => __( 'Nome da ninhada.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'litter_letter'            => array(
				'description'       => __( 'Letra da ninhada.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'dam_id'                   => array(
				'description' => __( 'ID da matriz.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'sire_id'                  => array(
				'description' => __( 'ID do reprodutor.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'status'                   => array(
				'description' => __( 'Status.', 'canil-core' ),
				'type'        => 'string',
				'default'     => 'planned',
				'enum'        => Litter::get_allowed_statuses(),
			),
			'heat_start_date'          => array(
				'description' => __( 'Data início do cio.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
			),
			'mating_date'              => array(
				'description' => __( 'Data da cobertura.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
			),
			'mating_type'              => array(
				'description' => __( 'Tipo de cobertura.', 'canil-core' ),
				'type'        => 'string',
				'enum'        => array_merge( array( '' ), Litter::get_allowed_mating_types() ),
			),
			'pregnancy_confirmed_date' => array(
				'description' => __( 'Data confirmação gestação.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
			),
			'expected_birth_date'      => array(
				'description' => __( 'Data prevista do parto.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
			),
			'actual_birth_date'        => array(
				'description' => __( 'Data do parto.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
			),
			'birth_type'               => array(
				'description' => __( 'Tipo de parto.', 'canil-core' ),
				'type'        => 'string',
				'enum'        => array_merge( array( '' ), Litter::get_allowed_birth_types() ),
			),
			'puppies_born_count'       => array(
				'description' => __( 'Quantidade de filhotes nascidos.', 'canil-core' ),
				'type'        => 'integer',
			),
			'puppies_alive_count'      => array(
				'description' => __( 'Quantidade de filhotes vivos.', 'canil-core' ),
				'type'        => 'integer',
			),
			'males_count'              => array(
				'description' => __( 'Quantidade de machos.', 'canil-core' ),
				'type'        => 'integer',
			),
			'females_count'            => array(
				'description' => __( 'Quantidade de fêmeas.', 'canil-core' ),
				'type'        => 'integer',
			),
			'veterinarian_id'          => array(
				'description' => __( 'ID do veterinário.', 'canil-core' ),
				'type'        => 'integer',
			),
			'notes'                    => array(
				'description'       => __( 'Observações.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
	}

	/**
	 * Get update arguments.
	 *
	 * @return array<string, array<string, mixed>> Arguments schema.
	 */
	private function get_update_args(): array {
		$args = $this->get_create_args();

		// Make all fields optional for update.
		foreach ( $args as $key => $arg ) {
			$args[ $key ]['required'] = false;
		}

		$args['id'] = array(
			'description' => __( 'ID da ninhada.', 'canil-core' ),
			'type'        => 'integer',
			'required'    => true,
		);

		return $args;
	}

	/**
	 * Get items (list).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_items( \WP_REST_Request $request ) {
		try {
			$pagination = $this->get_pagination_args( $request );
			$filters    = array();

			// Get filters from request.
			if ( $request->get_param( 'search' ) ) {
				$filters['search'] = Sanitizer::text( $request->get_param( 'search' ) );
			}
			if ( $request->get_param( 'status' ) ) {
				$filters['status'] = Sanitizer::text( $request->get_param( 'status' ) );
			}
			if ( $request->get_param( 'dam_id' ) ) {
				$filters['dam_id'] = Sanitizer::int( $request->get_param( 'dam_id' ) );
			}
			if ( $request->get_param( 'sire_id' ) ) {
				$filters['sire_id'] = Sanitizer::int( $request->get_param( 'sire_id' ) );
			}

			$order_by = Sanitizer::text( $request->get_param( 'order_by' ) ) ?: 'created_at';
			$order    = Sanitizer::text( $request->get_param( 'order' ) ) ?: 'DESC';

			$result = $this->repository->find_all(
				$filters,
				$pagination['page'],
				$pagination['per_page'],
				$order_by,
				$order
			);

			return $this->paginated_response( $result );
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get single item.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_item( \WP_REST_Request $request ) {
		try {
			$id   = absint( $request->get_param( 'id' ) );
			$data = $this->repository->find_with_parents( $id );

			if ( ! $data ) {
				return new \WP_Error(
					'not_found',
					__( 'Ninhada não encontrada.', 'canil-core' ),
					array( 'status' => 404 )
				);
			}

			return $this->item_response( $data );
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Create item.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function create_item( \WP_REST_Request $request ) {
		try {
			$data = $this->sanitize_input( $request );

			// Validate.
			$this->validate_litter( $data );

			// Auto-calculate expected birth date if mating date is provided.
			if ( ! empty( $data['mating_date'] ) && empty( $data['expected_birth_date'] ) ) {
				$mating                      = new \DateTimeImmutable( $data['mating_date'] );
				$expected                    = $mating->modify( '+63 days' );
				$data['expected_birth_date'] = $expected->format( 'Y-m-d' );
			}

			// Create.
			$id = $this->repository->insert( $data );

			// Fire action.
			do_action( 'canil_core_after_save_litter', $id, $data, 'create' );

			// Get created item.
			$litter = $this->repository->find_by_id( $id );

			return new \WP_REST_Response(
				array( 'data' => $litter ),
				201
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Update item.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function update_item( \WP_REST_Request $request ) {
		try {
			$id   = absint( $request->get_param( 'id' ) );
			$data = $this->sanitize_input( $request );

			// Check exists.
			$existing = $this->repository->find_by_id( $id );
			if ( ! $existing ) {
				return new \WP_Error(
					'not_found',
					__( 'Ninhada não encontrada.', 'canil-core' ),
					array( 'status' => 404 )
				);
			}

			// Update.
			$this->repository->update( $id, $data );

			// Fire action.
			do_action( 'canil_core_after_save_litter', $id, $data, 'update' );

			// Get updated item.
			$litter = $this->repository->find_by_id( $id );

			return $this->item_response( $litter );
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Delete item.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function delete_item( \WP_REST_Request $request ) {
		try {
			$id = absint( $request->get_param( 'id' ) );

			// Check exists.
			$existing = $this->repository->find_by_id( $id );
			if ( ! $existing ) {
				return new \WP_Error(
					'not_found',
					__( 'Ninhada não encontrada.', 'canil-core' ),
					array( 'status' => 404 )
				);
			}

			// Delete (soft delete).
			$this->repository->delete( $id );

			// Fire action.
			do_action( 'canil_core_after_delete_litter', $id );

			return $this->success_response( __( 'Ninhada excluída com sucesso.', 'canil-core' ) );
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get litters for dropdown.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_dropdown( \WP_REST_Request $request ) {
		try {
			$litters = $this->repository->find_for_dropdown();

			return new \WP_REST_Response(
				array( 'data' => $litters )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Sanitize input data.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array<string, mixed> Sanitized data.
	 */
	private function sanitize_input( \WP_REST_Request $request ): array {
		$data = array();

		// Text fields.
		$text_fields = array( 'name', 'litter_letter' );

		foreach ( $text_fields as $field ) {
			$value = $request->get_param( $field );
			if ( null !== $value ) {
				$data[ $field ] = Sanitizer::text( $value );
			}
		}

		// Integer fields.
		$int_fields = array(
			'dam_id',
			'sire_id',
			'puppies_born_count',
			'puppies_alive_count',
			'males_count',
			'females_count',
			'veterinarian_id',
		);

		foreach ( $int_fields as $field ) {
			$value = $request->get_param( $field );
			if ( null !== $value && '' !== $value ) {
				$data[ $field ] = Sanitizer::int( $value );
			}
		}

		// Enum fields.
		$status = $request->get_param( 'status' );
		if ( null !== $status ) {
			$data['status'] = Sanitizer::enum( $status, Litter::get_allowed_statuses(), 'planned' );
		}

		$mating_type = $request->get_param( 'mating_type' );
		if ( null !== $mating_type && '' !== $mating_type ) {
			$data['mating_type'] = Sanitizer::enum( $mating_type, Litter::get_allowed_mating_types() );
		}

		$birth_type = $request->get_param( 'birth_type' );
		if ( null !== $birth_type && '' !== $birth_type ) {
			$data['birth_type'] = Sanitizer::enum( $birth_type, Litter::get_allowed_birth_types() );
		}

		// Date fields.
		$date_fields = array(
			'heat_start_date',
			'mating_date',
			'pregnancy_confirmed_date',
			'expected_birth_date',
			'actual_birth_date',
		);

		foreach ( $date_fields as $field ) {
			$value = $request->get_param( $field );
			if ( null !== $value && '' !== $value ) {
				$data[ $field ] = Sanitizer::date( $value );
			}
		}

		// Textarea fields.
		$notes = $request->get_param( 'notes' );
		if ( null !== $notes ) {
			$data['notes'] = Sanitizer::textarea( $notes );
		}

		return $data;
	}

	/**
	 * Validate litter data.
	 *
	 * @param array<string, mixed> $data Litter data.
	 * @throws ValidationException If validation fails.
	 */
	private function validate_litter( array $data ): void {
		$validator = Validator::make( $data )
			->required( 'dam_id', __( 'A matriz é obrigatória.', 'canil-core' ) )
			->required( 'sire_id', __( 'O reprodutor é obrigatório.', 'canil-core' ) )
			->in( 'status', Litter::get_allowed_statuses(), __( 'Status inválido.', 'canil-core' ) );

		if ( $validator->fails() ) {
			throw new ValidationException( $validator->get_errors() );
		}
	}
}
