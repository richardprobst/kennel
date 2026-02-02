<?php
/**
 * Calendar REST Controller.
 *
 * REST API controller for calendar/agenda functionality.
 *
 * @package CanilCore
 */

namespace CanilCore\Rest\Controllers;

use CanilCore\Infrastructure\Repositories\EventRepository;
use CanilCore\Domain\Entities\Event;
use CanilCore\Helpers\Sanitizer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CalendarController class.
 */
class CalendarController extends BaseController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'calendar';

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
	 * Event colors by category (Material Design palette).
	 *
	 * - Reproduction (pink/red): heat, mating, pregnancy_test, birth
	 * - Health (green): vaccine, deworming, exam, medication, surgery, vet_visit
	 * - Weighing (blue): weighing
	 * - Other (gray): grooming, training, show, note
	 */
	private const COLOR_REPRODUCTION = '#e91e63';
	private const COLOR_HEALTH       = '#4caf50';
	private const COLOR_WEIGHING     = '#2196f3';
	private const COLOR_OTHER        = '#9e9e9e';

	/**
	 * Event type labels.
	 *
	 * @var array<string, string>
	 */
	private array $event_type_labels = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository        = new EventRepository();
		$this->event_type_labels = $this->get_event_type_labels();
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// GET /calendar/events - Get events for calendar display.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/events',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_calendar_events' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'start_date'  => array(
						'description' => __( 'Data inicial (Y-m-d).', 'canil-core' ),
						'type'        => 'string',
						'format'      => 'date',
						'required'    => true,
					),
					'end_date'    => array(
						'description' => __( 'Data final (Y-m-d).', 'canil-core' ),
						'type'        => 'string',
						'format'      => 'date',
						'required'    => true,
					),
					'event_types' => array(
						'description' => __( 'Tipos de evento (separados por vírgula).', 'canil-core' ),
						'type'        => 'string',
					),
				),
			)
		);

		// GET /calendar/summary - Get summary/stats for a date range.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/summary',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_calendar_summary' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'start_date' => array(
						'description' => __( 'Data inicial (Y-m-d).', 'canil-core' ),
						'type'        => 'string',
						'format'      => 'date',
						'required'    => true,
					),
					'end_date'   => array(
						'description' => __( 'Data final (Y-m-d).', 'canil-core' ),
						'type'        => 'string',
						'format'      => 'date',
						'required'    => true,
					),
				),
			)
		);

		// GET /calendar/date/{date} - Get all events for a specific date.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/date/(?P<date>[0-9]{4}-[0-9]{2}-[0-9]{2})',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_events_by_date' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'date' => array(
						'description' => __( 'Data (Y-m-d).', 'canil-core' ),
						'type'        => 'string',
						'format'      => 'date',
						'required'    => true,
					),
				),
			)
		);
	}

	/**
	 * Get calendar events for display.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_calendar_events( \WP_REST_Request $request ) {
		try {
			$start_date = Sanitizer::date( $request->get_param( 'start_date' ) );
			$end_date   = Sanitizer::date( $request->get_param( 'end_date' ) );

			// Validate dates.
			if ( ! $start_date || ! $end_date ) {
				return new \WP_Error(
					'invalid_dates',
					__( 'Datas inválidas. Use o formato Y-m-d.', 'canil-core' ),
					array( 'status' => 400 )
				);
			}

			// Get events from repository.
			$events = $this->repository->find_in_date_range( $start_date, $end_date );

			// Filter by event types if provided.
			$event_types_param = $request->get_param( 'event_types' );
			if ( $event_types_param ) {
				$event_types = array_map(
					function ( $type ) {
						return Sanitizer::text( trim( $type ) );
					},
					explode( ',', $event_types_param )
				);

				// Validate event types.
				$allowed_types = Event::get_allowed_event_types();
				$event_types   = array_filter(
					$event_types,
					function ( $type ) use ( $allowed_types ) {
						return in_array( $type, $allowed_types, true );
					}
				);

				if ( ! empty( $event_types ) ) {
					$events = array_filter(
						$events,
						function ( $event ) use ( $event_types ) {
							return in_array( $event['event_type'], $event_types, true );
						}
					);
					$events = array_values( $events );
				}
			}

			// Format events for calendar display.
			$calendar_events = array_map(
				function ( $event ) {
					return $this->format_calendar_event( $event );
				},
				$events
			);

			return new \WP_REST_Response(
				array( 'data' => $calendar_events )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get calendar summary for a date range.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_calendar_summary( \WP_REST_Request $request ) {
		try {
			$start_date = Sanitizer::date( $request->get_param( 'start_date' ) );
			$end_date   = Sanitizer::date( $request->get_param( 'end_date' ) );

			// Validate dates.
			if ( ! $start_date || ! $end_date ) {
				return new \WP_Error(
					'invalid_dates',
					__( 'Datas inválidas. Use o formato Y-m-d.', 'canil-core' ),
					array( 'status' => 400 )
				);
			}

			// Get events from repository.
			$events = $this->repository->find_in_date_range( $start_date, $end_date );

			// Count events by type.
			$counts_by_type = array();
			foreach ( $events as $event ) {
				$type = $event['event_type'];
				if ( ! isset( $counts_by_type[ $type ] ) ) {
					$counts_by_type[ $type ] = 0;
				}
				++$counts_by_type[ $type ];
			}

			// Get pending reminders.
			$reminders      = $this->repository->find_pending_reminders();
			$today          = current_time( 'Y-m-d' );
			$upcoming_count = 0;
			$overdue_count  = 0;

			foreach ( $reminders as $reminder ) {
				$reminder_date = substr( $reminder['reminder_date'] ?? '', 0, 10 );
				if ( $reminder_date < $today ) {
					++$overdue_count;
				} else {
					++$upcoming_count;
				}
			}

			return new \WP_REST_Response(
				array(
					'data' => array(
						'counts_by_type'     => $counts_by_type,
						'total_events'       => count( $events ),
						'upcoming_reminders' => $upcoming_count,
						'overdue_reminders'  => $overdue_count,
					),
				)
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Get events by specific date.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_events_by_date( \WP_REST_Request $request ) {
		try {
			$date = Sanitizer::date( $request->get_param( 'date' ) );

			// Validate date.
			if ( ! $date ) {
				return new \WP_Error(
					'invalid_date',
					__( 'Data inválida. Use o formato Y-m-d.', 'canil-core' ),
					array( 'status' => 400 )
				);
			}

			// Get events for this date (same start and end).
			$events = $this->repository->find_in_date_range( $date, $date );

			return new \WP_REST_Response(
				array( 'data' => $events )
			);
		} catch ( \Throwable $e ) {
			return $this->handle_exception( $e );
		}
	}

	/**
	 * Format event for calendar display.
	 *
	 * @param array<string, mixed> $event Event data.
	 * @return array<string, mixed> Formatted calendar event.
	 */
	private function format_calendar_event( array $event ): array {
		$event_type = $event['event_type'] ?? '';
		$payload    = $event['payload'] ?? array();

		// Generate title.
		$title = $this->generate_event_title( $event_type, $payload );

		// Get event date (date portion only).
		$start = substr( $event['event_date'] ?? '', 0, 10 );
		$end   = $event['event_end_date']
			? substr( $event['event_end_date'], 0, 10 )
			: $start;

		return array(
			'id'          => (int) ( $event['id'] ?? 0 ),
			'title'       => $title,
			'start'       => $start,
			'end'         => $end,
			'color'       => $this->get_event_color( $event_type ),
			'event_type'  => $event_type,
			'entity_type' => $event['entity_type'] ?? '',
			'entity_id'   => (int) ( $event['entity_id'] ?? 0 ),
			'payload'     => $payload,
		);
	}

	/**
	 * Generate event title based on type and payload.
	 *
	 * @param string               $event_type Event type.
	 * @param array<string, mixed> $payload    Event payload.
	 * @return string Event title.
	 */
	private function generate_event_title( string $event_type, array $payload ): string {
		$type_label = $this->event_type_labels[ $event_type ] ?? ucfirst( $event_type );

		// Try to get entity name from payload.
		$entity_name = '';
		if ( ! empty( $payload['dog_name'] ) ) {
			$entity_name = $payload['dog_name'];
		} elseif ( ! empty( $payload['puppy_name'] ) ) {
			$entity_name = $payload['puppy_name'];
		} elseif ( ! empty( $payload['litter_name'] ) ) {
			$entity_name = $payload['litter_name'];
		} elseif ( ! empty( $payload['name'] ) ) {
			$entity_name = $payload['name'];
		}

		// Build title.
		if ( ! empty( $entity_name ) ) {
			return sprintf( '%s - %s', $type_label, $entity_name );
		}

		// Add specific details based on event type.
		switch ( $event_type ) {
			case Event::TYPE_VACCINE:
				if ( ! empty( $payload['vaccine_name'] ) ) {
					return sprintf( '%s: %s', $type_label, $payload['vaccine_name'] );
				}
				break;

			case Event::TYPE_MEDICATION:
				if ( ! empty( $payload['medication_name'] ) ) {
					return sprintf( '%s: %s', $type_label, $payload['medication_name'] );
				}
				break;

			case Event::TYPE_EXAM:
				if ( ! empty( $payload['exam_type'] ) ) {
					return sprintf( '%s: %s', $type_label, $payload['exam_type'] );
				}
				break;

			case Event::TYPE_WEIGHING:
				if ( ! empty( $payload['weight'] ) ) {
					$unit = $payload['weight_unit'] ?? 'kg';
					return sprintf( '%s: %s %s', $type_label, $payload['weight'], $unit );
				}
				break;

			case Event::TYPE_SHOW:
				if ( ! empty( $payload['show_name'] ) ) {
					return sprintf( '%s: %s', $type_label, $payload['show_name'] );
				}
				break;
		}

		return $type_label;
	}

	/**
	 * Get event color by type.
	 *
	 * @param string $event_type Event type.
	 * @return string Color hex code.
	 */
	private function get_event_color( string $event_type ): string {
		// Reproduction events.
		$reproduction_types = array(
			Event::TYPE_HEAT,
			Event::TYPE_MATING,
			Event::TYPE_PREGNANCY_TEST,
			Event::TYPE_BIRTH,
		);

		if ( in_array( $event_type, $reproduction_types, true ) ) {
			return self::COLOR_REPRODUCTION;
		}

		// Health events.
		$health_types = array(
			Event::TYPE_VACCINE,
			Event::TYPE_DEWORMING,
			Event::TYPE_EXAM,
			Event::TYPE_MEDICATION,
			Event::TYPE_SURGERY,
			Event::TYPE_VET_VISIT,
		);

		if ( in_array( $event_type, $health_types, true ) ) {
			return self::COLOR_HEALTH;
		}

		// Weighing events.
		if ( Event::TYPE_WEIGHING === $event_type ) {
			return self::COLOR_WEIGHING;
		}

		// Other events (grooming, training, show, note).
		return self::COLOR_OTHER;
	}

	/**
	 * Get event type labels.
	 *
	 * @return array<string, string> Event type labels.
	 */
	private function get_event_type_labels(): array {
		return array(
			// Reproduction.
			Event::TYPE_HEAT           => __( 'Cio', 'canil-core' ),
			Event::TYPE_MATING         => __( 'Acasalamento', 'canil-core' ),
			Event::TYPE_PREGNANCY_TEST => __( 'Teste de Prenhez', 'canil-core' ),
			Event::TYPE_BIRTH          => __( 'Nascimento', 'canil-core' ),
			// Health.
			Event::TYPE_VACCINE        => __( 'Vacina', 'canil-core' ),
			Event::TYPE_DEWORMING      => __( 'Vermífugo', 'canil-core' ),
			Event::TYPE_EXAM           => __( 'Exame', 'canil-core' ),
			Event::TYPE_MEDICATION     => __( 'Medicação', 'canil-core' ),
			Event::TYPE_SURGERY        => __( 'Cirurgia', 'canil-core' ),
			Event::TYPE_VET_VISIT      => __( 'Consulta Veterinária', 'canil-core' ),
			// Other.
			Event::TYPE_WEIGHING       => __( 'Pesagem', 'canil-core' ),
			Event::TYPE_GROOMING       => __( 'Banho/Tosa', 'canil-core' ),
			Event::TYPE_TRAINING       => __( 'Treinamento', 'canil-core' ),
			Event::TYPE_SHOW           => __( 'Exposição', 'canil-core' ),
			Event::TYPE_NOTE           => __( 'Nota', 'canil-core' ),
		);
	}
}
