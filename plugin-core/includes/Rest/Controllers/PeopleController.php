<?php
/**
 * People REST Controller.
 *
 * REST API controller for people.
 *
 * @package CanilCore
 */

namespace CanilCore\Rest\Controllers;

use CanilCore\Infrastructure\Repositories\PersonRepository;
use CanilCore\Domain\Entities\Person;
use CanilCore\Helpers\Sanitizer;
use CanilCore\Helpers\Validator;
use CanilCore\Domain\Exceptions\ValidationException;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PeopleController class.
 */
class PeopleController extends BaseController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'people';

	/**
	 * Required capability.
	 *
	 * @var string
	 */
	protected string $capability = 'manage_people';

	/**
	 * Person repository.
	 *
	 * @var PersonRepository
	 */
	private PersonRepository $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new PersonRepository();
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// GET /people - List people.
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

		// GET/PUT/DELETE /people/{id}.
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
							'description' => __( 'ID da pessoa.', 'canil-core' ),
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
							'description' => __( 'ID da pessoa.', 'canil-core' ),
							'type'        => 'integer',
							'required'    => true,
						),
					),
				),
			)
		);

		// GET /people/dropdown - List people for dropdown.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/dropdown',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_dropdown' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'type' => array(
						'description' => __( 'Filtrar por tipo.', 'canil-core' ),
						'type'        => 'string',
						'enum'        => array_merge( array( '' ), Person::get_allowed_types() ),
					),
				),
			)
		);

		// GET /people/veterinarians - List veterinarians.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/veterinarians',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_veterinarians' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// GET /people/buyers - List buyers.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/buyers',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_buyers' ),
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

		$params['type'] = array(
			'description' => __( 'Filtrar por tipo.', 'canil-core' ),
			'type'        => 'string',
			'enum'        => Person::get_allowed_types(),
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
			'name'                 => array(
				'description'       => __( 'Nome.', 'canil-core' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'email'                => array(
				'description'       => __( 'E-mail.', 'canil-core' ),
				'type'              => 'string',
				'format'            => 'email',
				'sanitize_callback' => 'sanitize_email',
			),
			'phone'                => array(
				'description'       => __( 'Telefone.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'phone_secondary'      => array(
				'description'       => __( 'Telefone secundário.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'type'                 => array(
				'description' => __( 'Tipo.', 'canil-core' ),
				'type'        => 'string',
				'default'     => 'interested',
				'enum'        => Person::get_allowed_types(),
			),
			'address_street'       => array(
				'description'       => __( 'Logradouro.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'address_number'       => array(
				'description'       => __( 'Número.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'address_complement'   => array(
				'description'       => __( 'Complemento.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'address_neighborhood' => array(
				'description'       => __( 'Bairro.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'address_city'         => array(
				'description'       => __( 'Cidade.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'address_state'        => array(
				'description'       => __( 'Estado.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'address_zip'          => array(
				'description'       => __( 'CEP.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'address_country'      => array(
				'description'       => __( 'País.', 'canil-core' ),
				'type'              => 'string',
				'default'           => 'Brasil',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'document_cpf'         => array(
				'description'       => __( 'CPF.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'document_rg'          => array(
				'description'       => __( 'RG.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'preferences'          => array(
				'description' => __( 'Preferências.', 'canil-core' ),
				'type'        => 'object',
			),
			'referred_by_id'       => array(
				'description' => __( 'ID de quem indicou.', 'canil-core' ),
				'type'        => 'integer',
			),
			'notes'                => array(
				'description'       => __( 'Observações.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'tags'                 => array(
				'description' => __( 'Tags.', 'canil-core' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
				),
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
			'description' => __( 'ID da pessoa.', 'canil-core' ),
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
			if ( $request->get_param( 'type' ) ) {
				$filters['type'] = Sanitizer::text( $request->get_param( 'type' ) );
			}

			$order_by = Sanitizer::text( $request->get_param( 'order_by' ) ) ?: 'name';
			$order    = Sanitizer::text( $request->get_param( 'order' ) ) ?: 'ASC';

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
					__( 'Pessoa não encontrada.', 'canil-core' ),
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
			$this->validate_person( $data );

			// Create.
			$id = $this->repository->insert( $data );

			// Fire action.
			do_action( 'canil_core_after_save_person', $id, $data, 'create' );

			// Get created item.
			$person = $this->repository->find_by_id( $id );

			return new \WP_REST_Response(
				array( 'data' => $person ),
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
					__( 'Pessoa não encontrada.', 'canil-core' ),
					array( 'status' => 404 )
				);
			}

			// Update.
			$this->repository->update( $id, $data );

			// Fire action.
			do_action( 'canil_core_after_save_person', $id, $data, 'update' );

			// Get updated item.
			$person = $this->repository->find_by_id( $id );

			return $this->item_response( $person );
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
					__( 'Pessoa não encontrada.', 'canil-core' ),
					array( 'status' => 404 )
				);
			}

			// Delete (soft delete).
			$this->repository->delete( $id );

			// Fire action.
			do_action( 'canil_core_after_delete_person', $id );

			return $this->success_response( __( 'Pessoa excluída com sucesso.', 'canil-core' ) );
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get people for dropdown.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_dropdown( \WP_REST_Request $request ) {
		try {
			$type   = Sanitizer::text( $request->get_param( 'type' ) );
			$people = $this->repository->find_for_dropdown( $type );

			return new \WP_REST_Response(
				array( 'data' => $people )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get veterinarians.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_veterinarians( \WP_REST_Request $request ) {
		try {
			$veterinarians = $this->repository->find_veterinarians();

			return new \WP_REST_Response(
				array( 'data' => $veterinarians )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get buyers.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_buyers( \WP_REST_Request $request ) {
		try {
			$buyers = $this->repository->find_buyers();

			return new \WP_REST_Response(
				array( 'data' => $buyers )
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
			'phone',
			'phone_secondary',
			'address_street',
			'address_number',
			'address_complement',
			'address_neighborhood',
			'address_city',
			'address_state',
			'address_zip',
			'address_country',
			'document_cpf',
			'document_rg',
		);

		foreach ( $text_fields as $field ) {
			$value = $request->get_param( $field );
			if ( null !== $value ) {
				$data[ $field ] = Sanitizer::text( $value );
			}
		}

		// Email field.
		$email = $request->get_param( 'email' );
		if ( null !== $email ) {
			$data['email'] = Sanitizer::email( $email );
		}

		// Enum fields.
		$type = $request->get_param( 'type' );
		if ( null !== $type ) {
			$data['type'] = Sanitizer::enum( $type, Person::get_allowed_types(), 'interested' );
		}

		// Integer fields.
		$referred_by_id = $request->get_param( 'referred_by_id' );
		if ( null !== $referred_by_id && '' !== $referred_by_id ) {
			$data['referred_by_id'] = Sanitizer::int( $referred_by_id );
		}

		// JSON/Array fields.
		$preferences = $request->get_param( 'preferences' );
		if ( null !== $preferences ) {
			$data['preferences'] = Sanitizer::json( $preferences ) ?? array();
		}

		$tags = $request->get_param( 'tags' );
		if ( null !== $tags ) {
			$data['tags'] = Sanitizer::json( $tags ) ?? array();
		}

		// Textarea fields.
		$notes = $request->get_param( 'notes' );
		if ( null !== $notes ) {
			$data['notes'] = Sanitizer::textarea( $notes );
		}

		return $data;
	}

	/**
	 * Validate person data.
	 *
	 * @param array<string, mixed> $data Person data.
	 * @throws ValidationException If validation fails.
	 */
	private function validate_person( array $data ): void {
		$validator = Validator::make( $data )
			->required( 'name', __( 'O nome é obrigatório.', 'canil-core' ) )
			->min_length( 'name', 2, __( 'O nome deve ter pelo menos 2 caracteres.', 'canil-core' ) )
			->email( 'email', __( 'E-mail inválido.', 'canil-core' ) )
			->in( 'type', Person::get_allowed_types(), __( 'Tipo inválido.', 'canil-core' ) );

		if ( $validator->fails() ) {
			throw new ValidationException( $validator->get_errors() );
		}
	}
}
