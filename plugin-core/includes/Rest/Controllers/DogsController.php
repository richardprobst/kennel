<?php
/**
 * Dogs REST Controller.
 *
 * REST API controller for dogs.
 *
 * @package CanilCore
 */

namespace CanilCore\Rest\Controllers;

use CanilCore\Infrastructure\Repositories\DogRepository;
use CanilCore\Domain\Entities\Dog;
use CanilCore\Helpers\Sanitizer;
use CanilCore\Helpers\Validator;
use CanilCore\Domain\Exceptions\ValidationException;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DogsController class.
 */
class DogsController extends BaseController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'dogs';

	/**
	 * Required capability.
	 *
	 * @var string
	 */
	protected string $capability = 'manage_dogs';

	/**
	 * Dog repository.
	 *
	 * @var DogRepository
	 */
	private DogRepository $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new DogRepository();
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// GET /dogs - List dogs.
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

		// GET/PUT/DELETE /dogs/{id}.
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
							'description' => __( 'ID do cão.', 'canil-core' ),
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
							'description' => __( 'ID do cão.', 'canil-core' ),
							'type'        => 'integer',
							'required'    => true,
						),
					),
				),
			)
		);

		// GET /dogs/breeding - List dogs for breeding dropdown.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/breeding',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_breeding_dogs' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'sex' => array(
						'description' => __( 'Filtrar por sexo.', 'canil-core' ),
						'type'        => 'string',
						'enum'        => array( 'male', 'female', '' ),
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
			'enum'        => Dog::get_allowed_statuses(),
		);

		$params['sex'] = array(
			'description' => __( 'Filtrar por sexo.', 'canil-core' ),
			'type'        => 'string',
			'enum'        => Dog::get_allowed_sexes(),
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
			'name'                => array(
				'description'       => __( 'Nome do cão.', 'canil-core' ),
				'type'              => 'string',
				'required'          => true,
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
			'tattoo'              => array(
				'description'       => __( 'Tatuagem.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'breed'               => array(
				'description'       => __( 'Raça.', 'canil-core' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'variety'             => array(
				'description'       => __( 'Variedade.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
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
			'birth_date'          => array(
				'description' => __( 'Data de nascimento.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date',
				'required'    => true,
			),
			'sex'                 => array(
				'description' => __( 'Sexo.', 'canil-core' ),
				'type'        => 'string',
				'required'    => true,
				'enum'        => Dog::get_allowed_sexes(),
			),
			'status'              => array(
				'description' => __( 'Status.', 'canil-core' ),
				'type'        => 'string',
				'default'     => 'active',
				'enum'        => Dog::get_allowed_statuses(),
			),
			'sire_id'             => array(
				'description' => __( 'ID do pai.', 'canil-core' ),
				'type'        => 'integer',
			),
			'dam_id'              => array(
				'description' => __( 'ID da mãe.', 'canil-core' ),
				'type'        => 'integer',
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
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'url'     => array( 'type' => 'string' ),
						'caption' => array( 'type' => 'string' ),
						'order'   => array( 'type' => 'integer' ),
					),
				),
			),
			'titles'              => array(
				'description' => __( 'Títulos.', 'canil-core' ),
				'type'        => 'array',
			),
			'health_tests'        => array(
				'description' => __( 'Exames de saúde.', 'canil-core' ),
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
			'description' => __( 'ID do cão.', 'canil-core' ),
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
			$data = $this->repository->find_by_id( $id );

			if ( ! $data ) {
				return new \WP_Error(
					'not_found',
					__( 'Cão não encontrado.', 'canil-core' ),
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
			$this->validate_dog( $data );

			// Create.
			$id = $this->repository->insert( $data );

			// Fire action.
			do_action( 'canil_core_after_save_dog', $id, $data, 'create' );

			// Get created item.
			$dog = $this->repository->find_by_id( $id );

			return new \WP_REST_Response(
				array( 'data' => $dog ),
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
					__( 'Cão não encontrado.', 'canil-core' ),
					array( 'status' => 404 )
				);
			}

			// Update.
			$this->repository->update( $id, $data );

			// Fire action.
			do_action( 'canil_core_after_save_dog', $id, $data, 'update' );

			// Get updated item.
			$dog = $this->repository->find_by_id( $id );

			return $this->item_response( $dog );
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
					__( 'Cão não encontrado.', 'canil-core' ),
					array( 'status' => 404 )
				);
			}

			// Delete (soft delete).
			$this->repository->delete( $id );

			// Fire action.
			do_action( 'canil_core_after_delete_dog', $id );

			return $this->success_response( __( 'Cão excluído com sucesso.', 'canil-core' ) );
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get breeding dogs for dropdown.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_breeding_dogs( \WP_REST_Request $request ) {
		try {
			$sex  = Sanitizer::text( $request->get_param( 'sex' ) );
			$dogs = $this->repository->find_for_breeding( $sex );

			return new \WP_REST_Response(
				array( 'data' => $dogs )
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
			'name',
			'call_name',
			'registration_number',
			'chip_number',
			'tattoo',
			'breed',
			'variety',
			'color',
			'markings',
		);

		foreach ( $text_fields as $field ) {
			$value = $request->get_param( $field );
			if ( null !== $value ) {
				$data[ $field ] = Sanitizer::text( $value );
			}
		}

		// Enum fields.
		$sex = $request->get_param( 'sex' );
		if ( null !== $sex ) {
			$data['sex'] = Sanitizer::enum( $sex, Dog::get_allowed_sexes() );
		}

		$status = $request->get_param( 'status' );
		if ( null !== $status ) {
			$data['status'] = Sanitizer::enum( $status, Dog::get_allowed_statuses(), 'active' );
		}

		// Date fields.
		$birth_date = $request->get_param( 'birth_date' );
		if ( null !== $birth_date ) {
			$data['birth_date'] = Sanitizer::date( $birth_date );
		}

		$death_date = $request->get_param( 'death_date' );
		if ( null !== $death_date ) {
			$data['death_date'] = Sanitizer::date( $death_date );
		}

		// Integer fields.
		$sire_id = $request->get_param( 'sire_id' );
		if ( null !== $sire_id && '' !== $sire_id ) {
			$data['sire_id'] = Sanitizer::int( $sire_id );
		}

		$dam_id = $request->get_param( 'dam_id' );
		if ( null !== $dam_id && '' !== $dam_id ) {
			$data['dam_id'] = Sanitizer::int( $dam_id );
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

		$titles = $request->get_param( 'titles' );
		if ( null !== $titles ) {
			$data['titles'] = Sanitizer::json( $titles ) ?? array();
		}

		$health_tests = $request->get_param( 'health_tests' );
		if ( null !== $health_tests ) {
			$data['health_tests'] = Sanitizer::json( $health_tests ) ?? array();
		}

		// Textarea fields.
		$notes = $request->get_param( 'notes' );
		if ( null !== $notes ) {
			$data['notes'] = Sanitizer::textarea( $notes );
		}

		return $data;
	}

	/**
	 * Validate dog data.
	 *
	 * @param array<string, mixed> $data Dog data.
	 * @throws ValidationException If validation fails.
	 */
	private function validate_dog( array $data ): void {
		$validator = Validator::make( $data )
			->required( 'name', __( 'O nome é obrigatório.', 'canil-core' ) )
			->min_length( 'name', 2, __( 'O nome deve ter pelo menos 2 caracteres.', 'canil-core' ) )
			->required( 'breed', __( 'A raça é obrigatória.', 'canil-core' ) )
			->required( 'birth_date', __( 'A data de nascimento é obrigatória.', 'canil-core' ) )
			->date( 'birth_date', 'Y-m-d', __( 'Data de nascimento inválida.', 'canil-core' ) )
			->required( 'sex', __( 'O sexo é obrigatório.', 'canil-core' ) )
			->in( 'sex', Dog::get_allowed_sexes(), __( 'Sexo inválido.', 'canil-core' ) )
			->in( 'status', Dog::get_allowed_statuses(), __( 'Status inválido.', 'canil-core' ) );

		if ( $validator->fails() ) {
			throw new ValidationException( $validator->get_errors() );
		}
	}
}
