<?php
/**
 * Events REST Controller.
 *
 * REST API controller for events.
 *
 * @package CanilCore
 */

namespace CanilCore\Rest\Controllers;

use CanilCore\Infrastructure\Repositories\EventRepository;
use CanilCore\Domain\Entities\Event;
use CanilCore\Helpers\Sanitizer;
use CanilCore\Helpers\Validator;
use CanilCore\Domain\Exceptions\ValidationException;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EventsController class.
 */
class EventsController extends BaseController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'events';

	/**
	 * Required capability.
	 *
	 * @var string
	 */
	protected string $capability = 'manage_kennel';

	/**
	 * Event repository.
	 *
	 * @var EventRepository
	 */
	private EventRepository $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new EventRepository();
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// GET /events - List events.
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

		// GET/PUT/DELETE /events/{id}.
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
							'description' => __( 'ID do evento.', 'canil-core' ),
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
							'description' => __( 'ID do evento.', 'canil-core' ),
							'type'        => 'integer',
							'required'    => true,
						),
					),
				),
			)
		);

		// GET /events/by-entity/{entity_type}/{entity_id} - Events by entity.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/by-entity/(?P<entity_type>[a-z]+)/(?P<entity_id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_by_entity' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'entity_type' => array(
						'description' => __( 'Tipo de entidade.', 'canil-core' ),
						'type'        => 'string',
						'required'    => true,
						'enum'        => Event::get_allowed_entity_types(),
					),
					'entity_id'   => array(
						'description' => __( 'ID da entidade.', 'canil-core' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
			)
		);

		// GET /events/upcoming - Upcoming events.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/upcoming',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_upcoming' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'days' => array(
						'description' => __( 'Número de dias.', 'canil-core' ),
						'type'        => 'integer',
						'default'     => 30,
					),
				),
			)
		);

		// GET /events/reminders - Pending reminders.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/reminders',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_reminders' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// POST /events/{id}/complete-reminder - Mark reminder as completed.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/complete-reminder',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'complete_reminder' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'id' => array(
						'description' => __( 'ID do evento.', 'canil-core' ),
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

		$params['entity_type'] = array(
			'description' => __( 'Filtrar por tipo de entidade.', 'canil-core' ),
			'type'        => 'string',
			'enum'        => Event::get_allowed_entity_types(),
		);

		$params['entity_id'] = array(
			'description' => __( 'Filtrar por ID de entidade.', 'canil-core' ),
			'type'        => 'integer',
		);

		$params['event_type'] = array(
			'description' => __( 'Filtrar por tipo de evento.', 'canil-core' ),
			'type'        => 'string',
			'enum'        => Event::get_allowed_event_types(),
		);

		$params['date_from'] = array(
			'description' => __( 'Data inicial.', 'canil-core' ),
			'type'        => 'string',
			'format'      => 'date',
		);

		$params['date_to'] = array(
			'description' => __( 'Data final.', 'canil-core' ),
			'type'        => 'string',
			'format'      => 'date',
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
			'entity_type'        => array(
				'description' => __( 'Tipo de entidade.', 'canil-core' ),
				'type'        => 'string',
				'required'    => true,
				'enum'        => Event::get_allowed_entity_types(),
			),
			'entity_id'          => array(
				'description' => __( 'ID da entidade.', 'canil-core' ),
				'type'        => 'integer',
				'required'    => true,
			),
			'event_type'         => array(
				'description' => __( 'Tipo de evento.', 'canil-core' ),
				'type'        => 'string',
				'required'    => true,
				'enum'        => Event::get_allowed_event_types(),
			),
			'event_date'         => array(
				'description' => __( 'Data do evento.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date-time',
				'required'    => true,
			),
			'event_end_date'     => array(
				'description' => __( 'Data final do evento.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date-time',
			),
			'payload'            => array(
				'description' => __( 'Dados adicionais.', 'canil-core' ),
				'type'        => 'object',
			),
			'reminder_date'      => array(
				'description' => __( 'Data do lembrete.', 'canil-core' ),
				'type'        => 'string',
				'format'      => 'date-time',
			),
			'reminder_completed' => array(
				'description' => __( 'Lembrete concluído.', 'canil-core' ),
				'type'        => 'boolean',
				'default'     => false,
			),
			'notes'              => array(
				'description'       => __( 'Observações.', 'canil-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'attachments'        => array(
				'description' => __( 'Anexos.', 'canil-core' ),
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
			'description' => __( 'ID do evento.', 'canil-core' ),
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
			if ( $request->get_param( 'entity_type' ) ) {
				$filters['entity_type'] = Sanitizer::text( $request->get_param( 'entity_type' ) );
			}
			if ( $request->get_param( 'entity_id' ) ) {
				$filters['entity_id'] = Sanitizer::int( $request->get_param( 'entity_id' ) );
			}
			if ( $request->get_param( 'event_type' ) ) {
				$filters['event_type'] = Sanitizer::text( $request->get_param( 'event_type' ) );
			}
			if ( $request->get_param( 'date_from' ) ) {
				$filters['date_from'] = Sanitizer::date( $request->get_param( 'date_from' ) );
			}
			if ( $request->get_param( 'date_to' ) ) {
				$filters['date_to'] = Sanitizer::date( $request->get_param( 'date_to' ) );
			}

			$order_by = Sanitizer::text( $request->get_param( 'order_by' ) ) ?: 'event_date';
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
					__( 'Evento não encontrado.', 'canil-core' ),
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
			$this->validate_event( $data );

			// Add created_by.
			$data['created_by'] = get_current_user_id();

			// Create.
			$id = $this->repository->insert( $data );

			// Fire action.
			do_action( 'canil_core_event_created', $id, $data );

			// Get created item.
			$event = $this->repository->find_by_id( $id );

			return new \WP_REST_Response(
				array( 'data' => $event ),
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
					__( 'Evento não encontrado.', 'canil-core' ),
					array( 'status' => 404 )
				);
			}

			// Update.
			$this->repository->update( $id, $data );

			// Fire action.
			do_action( 'canil_core_event_updated', $id, $data );

			// Get updated item.
			$event = $this->repository->find_by_id( $id );

			return $this->item_response( $event );
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
					__( 'Evento não encontrado.', 'canil-core' ),
					array( 'status' => 404 )
				);
			}

			// Delete (soft delete).
			$this->repository->delete( $id );

			// Fire action.
			do_action( 'canil_core_event_deleted', $id );

			return $this->success_response( __( 'Evento excluído com sucesso.', 'canil-core' ) );
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get events by entity.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_by_entity( \WP_REST_Request $request ) {
		try {
			$entity_type = Sanitizer::text( $request->get_param( 'entity_type' ) );
			$entity_id   = absint( $request->get_param( 'entity_id' ) );

			$events = $this->repository->find_by_entity( $entity_type, $entity_id );

			return new \WP_REST_Response(
				array( 'data' => $events )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get upcoming events.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_upcoming( \WP_REST_Request $request ) {
		try {
			$days   = absint( $request->get_param( 'days' ) ) ?: 30;
			$events = $this->repository->find_upcoming( $days );

			return new \WP_REST_Response(
				array( 'data' => $events )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get pending reminders.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_reminders( \WP_REST_Request $request ) {
		try {
			$reminders = $this->repository->find_pending_reminders();

			return new \WP_REST_Response(
				array( 'data' => $reminders )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Complete a reminder.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function complete_reminder( \WP_REST_Request $request ) {
		try {
			$id = absint( $request->get_param( 'id' ) );

			// Check exists.
			$existing = $this->repository->find_by_id( $id );
			if ( ! $existing ) {
				return new \WP_Error(
					'not_found',
					__( 'Evento não encontrado.', 'canil-core' ),
					array( 'status' => 404 )
				);
			}

			// Mark as completed.
			$this->repository->mark_reminder_completed( $id );

			return $this->success_response( __( 'Lembrete marcado como concluído.', 'canil-core' ) );
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

		// Enum fields.
		$entity_type = $request->get_param( 'entity_type' );
		if ( null !== $entity_type ) {
			$data['entity_type'] = Sanitizer::enum( $entity_type, Event::get_allowed_entity_types() );
		}

		$event_type = $request->get_param( 'event_type' );
		if ( null !== $event_type ) {
			$data['event_type'] = Sanitizer::enum( $event_type, Event::get_allowed_event_types() );
		}

		// Integer fields.
		$entity_id = $request->get_param( 'entity_id' );
		if ( null !== $entity_id ) {
			$data['entity_id'] = Sanitizer::int( $entity_id );
		}

		// DateTime fields.
		$event_date = $request->get_param( 'event_date' );
		if ( null !== $event_date && '' !== $event_date ) {
			$data['event_date'] = Sanitizer::datetime( $event_date );
		}

		$event_end_date = $request->get_param( 'event_end_date' );
		if ( null !== $event_end_date && '' !== $event_end_date ) {
			$data['event_end_date'] = Sanitizer::datetime( $event_end_date );
		}

		$reminder_date = $request->get_param( 'reminder_date' );
		if ( null !== $reminder_date && '' !== $reminder_date ) {
			$data['reminder_date'] = Sanitizer::datetime( $reminder_date );
		}

		// Boolean fields.
		$reminder_completed = $request->get_param( 'reminder_completed' );
		if ( null !== $reminder_completed ) {
			$data['reminder_completed'] = (bool) $reminder_completed ? 1 : 0;
		}

		// JSON/Array fields.
		$payload = $request->get_param( 'payload' );
		if ( null !== $payload ) {
			$data['payload'] = Sanitizer::json( $payload ) ?? array();
		}

		$attachments = $request->get_param( 'attachments' );
		if ( null !== $attachments ) {
			$data['attachments'] = Sanitizer::json( $attachments ) ?? array();
		}

		// Textarea fields.
		$notes = $request->get_param( 'notes' );
		if ( null !== $notes ) {
			$data['notes'] = Sanitizer::textarea( $notes );
		}

		return $data;
	}

	/**
	 * Validate event data.
	 *
	 * @param array<string, mixed> $data Event data.
	 * @throws ValidationException If validation fails.
	 */
	private function validate_event( array $data ): void {
		$validator = Validator::make( $data )
			->required( 'entity_type', __( 'O tipo de entidade é obrigatório.', 'canil-core' ) )
			->required( 'entity_id', __( 'O ID da entidade é obrigatório.', 'canil-core' ) )
			->required( 'event_type', __( 'O tipo de evento é obrigatório.', 'canil-core' ) )
			->required( 'event_date', __( 'A data do evento é obrigatória.', 'canil-core' ) )
			->in( 'entity_type', Event::get_allowed_entity_types(), __( 'Tipo de entidade inválido.', 'canil-core' ) )
			->in( 'event_type', Event::get_allowed_event_types(), __( 'Tipo de evento inválido.', 'canil-core' ) );

		if ( $validator->fails() ) {
			throw new ValidationException( $validator->get_errors() );
		}
	}
}
