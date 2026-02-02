<?php
/**
 * Puppies REST Controller.
 *
 * REST API controller for puppies.
 *
 * @package CanilCore
 */

namespace CanilCore\Rest\Controllers;

use CanilCore\Infrastructure\Repositories\PuppyRepository;
use CanilCore\Domain\Entities\Puppy;
use CanilCore\Helpers\Sanitizer;
use CanilCore\Helpers\Validator;
use CanilCore\Domain\Exceptions\ValidationException;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PuppiesController class.
 */
class PuppiesController extends BaseController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'puppies';

	/**
	 * Required capability.
	 *
	 * @var string
	 */
	protected string $capability = 'manage_puppies';

	/**
	 * Puppy repository.
	 *
	 * @var PuppyRepository
	 */
	private PuppyRepository $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new PuppyRepository();
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// GET /puppies - List puppies.
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

		// GET/PUT/DELETE /puppies/{id}.
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
							'description' => __( 'ID do filhote.', 'canil-core' ),
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
							'description' => __( 'ID do filhote.', 'canil-core' ),
							'type'        => 'integer',
							'required'    => true,
						),
					),
				),
			)
		);

		// GET /puppies/by-litter/{litter_id} - List puppies by litter.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/by-litter/(?P<litter_id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_by_litter' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'litter_id' => array(
						'description' => __( 'ID da ninhada.', 'canil-core' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
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
			'enum'        => Puppy::get_allowed_statuses(),
		);

		$params['sex'] = array(
			'description' => __( 'Filtrar por sexo.', 'canil-core' ),
			'type'        => 'string',
			'enum'        => Puppy::get_allowed_sexes(),
		);

		$params['litter_id'] = array(
			'description' => __( 'Filtrar por ninhada.', 'canil-core' ),
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
			'litter_id'           => array(
				'description' => __( 'ID da ninhada.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'identifier'          => array(
				'description'       => __( 'Identificador.', 'canil-core' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'name'                => array(
				'description'       => __( 'Nome registrado.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'call_name'           => array(
				'description'       => __( 'Nome de chamada.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'registration_number' => array(
				'description'       => __( 'Número de registro.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'chip_number'         => array(
				'description'       => __( 'Número do chip.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'sex'                 => array(
				'description' => __( 'Sexo.', 'canil-core' ),
				'type'        => 'string',
				'required'    => true,
				'enum'        => Puppy::get_allowed_sexes(),
			),
			'color'               => array(
				'description'       => __( 'Cor.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'markings'            => array(
				'description'       => __( 'Marcações.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'birth_weight'        => array(
				'description' => __( 'Peso ao nascer (gramas).', 'canil-core' ),
				'type'        => 'number',
			),
			'birth_order'         => array(
				'description' => __( 'Ordem de nascimento.', 'canil-core' ),
				'type'        => 'integer',
			),
			'birth_notes'         => array(
				'description'       => __( 'Observações do nascimento.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'status'              => array(
				'description' => __( 'Status.', 'canil-core' ),
				'type'        => 'string',
				'default'     => 'available',
				'enum'        => Puppy::get_allowed_statuses(),
			),
			'buyer_id'            => array(
				'description' => __( 'ID do comprador.', 'canil-core' ),
				'type'        => 'integer',
			),
			'reservation_date'    => array(
				'description' => __( 'Data da reserva.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
			),
			'sale_date'           => array(
				'description' => __( 'Data da venda.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
			),
			'delivery_date'       => array(
				'description' => __( 'Data da entrega.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
			),
			'price'               => array(
				'description' => __( 'Preço.', 'canil-core' ),
				'type'        => 'number',
			),
			'photo_main_url'      => array(
				'description'       => __( 'URL da foto principal.', 'canil-core' ),
				'type'              => 'string',
				'format'            => 'uri',
				'sanitize_callback' => 'esc_url_raw',
			),
			'photos'              => array(
				'description' => __( 'Fotos adicionais.', 'canil-core' ),
				'type'        => 'array',
			),
			'notes'               => array(
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
			'description' => __( 'ID do filhote.', 'canil-core' ),
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
			if ( $request->get_param( 'sex' ) ) {
				$filters['sex'] = Sanitizer::text( $request->get_param( 'sex' ) );
			}
			if ( $request->get_param( 'litter_id' ) ) {
				$filters['litter_id'] = Sanitizer::int( $request->get_param( 'litter_id' ) );
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
			$data = $this->repository->find_with_litter( $id );

			if ( ! $data ) {
				return new \WP_Error(
					'not_found',
					__( 'Filhote não encontrado.', 'canil-core' ),
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
			$this->validate_puppy( $data );

			// Create.
			$id = $this->repository->insert( $data );

			// Fire action.
			do_action( 'canil_core_after_save_puppy', $id, $data, 'create' );

			// Get created item.
			$puppy = $this->repository->find_by_id( $id );

			return new \WP_REST_Response(
				array( 'data' => $puppy ),
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
					__( 'Filhote não encontrado.', 'canil-core' ),
					array( 'status' => 404 )
				);
			}

			// Update.
			$this->repository->update( $id, $data );

			// Fire action.
			do_action( 'canil_core_after_save_puppy', $id, $data, 'update' );

			// Get updated item.
			$puppy = $this->repository->find_by_id( $id );

			return $this->item_response( $puppy );
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
					__( 'Filhote não encontrado.', 'canil-core' ),
					array( 'status' => 404 )
				);
			}

			// Delete (soft delete).
			$this->repository->delete( $id );

			// Fire action.
			do_action( 'canil_core_after_delete_puppy', $id );

			return $this->success_response( __( 'Filhote excluído com sucesso.', 'canil-core' ) );
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get puppies by litter.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_by_litter( \WP_REST_Request $request ) {
		try {
			$litter_id = absint( $request->get_param( 'litter_id' ) );
			$puppies   = $this->repository->find_by_litter( $litter_id );

			return new \WP_REST_Response(
				array( 'data' => $puppies )
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
		$text_fields = array(
			'identifier',
			'name',
			'call_name',
			'registration_number',
			'chip_number',
			'color',
			'markings',
		);

		foreach ( $text_fields as $field ) {
			$value = $request->get_param( $field );
			if ( null !== $value ) {
				$data[ $field ] = Sanitizer::text( $value );
			}
		}

		// Integer fields.
		$int_fields = array( 'litter_id', 'birth_order', 'buyer_id' );

		foreach ( $int_fields as $field ) {
			$value = $request->get_param( $field );
			if ( null !== $value && '' !== $value ) {
				$data[ $field ] = Sanitizer::int( $value );
			}
		}

		// Float fields.
		$float_fields = array( 'birth_weight', 'price' );

		foreach ( $float_fields as $field ) {
			$value = $request->get_param( $field );
			if ( null !== $value && '' !== $value ) {
				$data[ $field ] = Sanitizer::float( $value );
			}
		}

		// Enum fields.
		$sex = $request->get_param( 'sex' );
		if ( null !== $sex ) {
			$data['sex'] = Sanitizer::enum( $sex, Puppy::get_allowed_sexes() );
		}

		$status = $request->get_param( 'status' );
		if ( null !== $status ) {
			$data['status'] = Sanitizer::enum( $status, Puppy::get_allowed_statuses(), 'available' );
		}

		// Date fields.
		$date_fields = array( 'reservation_date', 'sale_date', 'delivery_date' );

		foreach ( $date_fields as $field ) {
			$value = $request->get_param( $field );
			if ( null !== $value && '' !== $value ) {
				$data[ $field ] = Sanitizer::date( $value );
			}
		}

		// URL fields.
		$photo_main_url = $request->get_param( 'photo_main_url' );
		if ( null !== $photo_main_url ) {
			$data['photo_main_url'] = Sanitizer::url( $photo_main_url );
		}

		// JSON/Array fields.
		$photos = $request->get_param( 'photos' );
		if ( null !== $photos ) {
			$data['photos'] = Sanitizer::json( $photos ) ?? array();
		}

		// Textarea fields.
		$textarea_fields = array( 'birth_notes', 'notes' );
		foreach ( $textarea_fields as $field ) {
			$value = $request->get_param( $field );
			if ( null !== $value ) {
				$data[ $field ] = Sanitizer::textarea( $value );
			}
		}

		return $data;
	}

	/**
	 * Validate puppy data.
	 *
	 * @param array<string, mixed> $data Puppy data.
	 * @throws ValidationException If validation fails.
	 */
	private function validate_puppy( array $data ): void {
		$validator = Validator::make( $data )
			->required( 'litter_id', __( 'A ninhada é obrigatória.', 'canil-core' ) )
			->required( 'identifier', __( 'O identificador é obrigatório.', 'canil-core' ) )
			->required( 'sex', __( 'O sexo é obrigatório.', 'canil-core' ) )
			->in( 'sex', Puppy::get_allowed_sexes(), __( 'Sexo inválido.', 'canil-core' ) )
			->in( 'status', Puppy::get_allowed_statuses(), __( 'Status inválido.', 'canil-core' ) );

		if ( $validator->fails() ) {
			throw new ValidationException( $validator->get_errors() );
		}
	}
}
