<?php
/**
 * Health Service.
 *
 * Handles health-related business logic: vaccines, deworming, exams, medications, surgeries, vet visits.
 *
 * @package CanilCore
 */

namespace CanilCore\Domain\Services;

use CanilCore\Domain\Entities\Event;
use CanilCore\Domain\Exceptions\NotFoundException;
use CanilCore\Domain\Exceptions\ValidationException;
use CanilCore\Helpers\DateHelper;
use CanilCore\Helpers\Sanitizer;
use CanilCore\Infrastructure\Repositories\DogRepository;
use CanilCore\Infrastructure\Repositories\EventRepository;
use CanilCore\Infrastructure\Repositories\LitterRepository;
use CanilCore\Infrastructure\Repositories\PuppyRepository;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HealthService class.
 */
class HealthService {

	/**
	 * Dog repository.
	 *
	 * @var DogRepository
	 */
	private DogRepository $dog_repository;

	/**
	 * Litter repository.
	 *
	 * @var LitterRepository
	 */
	private LitterRepository $litter_repository;

	/**
	 * Event repository.
	 *
	 * @var EventRepository
	 */
	private EventRepository $event_repository;

	/**
	 * Puppy repository.
	 *
	 * @var PuppyRepository
	 */
	private PuppyRepository $puppy_repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->dog_repository    = new DogRepository();
		$this->litter_repository = new LitterRepository();
		$this->event_repository  = new EventRepository();
		$this->puppy_repository  = new PuppyRepository();
	}

	/**
	 * Record a vaccine event.
	 *
	 * @param int    $entity_id    Entity ID.
	 * @param string $entity_type  Entity type (dog, litter, puppy).
	 * @param string $event_date   Vaccine date (Y-m-d).
	 * @param array  $vaccine_data Vaccine data (name, manufacturer, batch, next_dose_date).
	 * @return array{event: array<string, mixed>, message: string}
	 * @throws ValidationException If validation fails.
	 */
	public function record_vaccine( int $entity_id, string $entity_type, string $event_date, array $vaccine_data ): array {
		$this->validate_entity_exists( $entity_id, $entity_type );

		// Sanitize vaccine data.
		$payload = array(
			'name'           => Sanitizer::text( $vaccine_data['name'] ?? '' ),
			'manufacturer'   => Sanitizer::text( $vaccine_data['manufacturer'] ?? '' ),
			'batch'          => Sanitizer::text( $vaccine_data['batch'] ?? '' ),
			'next_dose_date' => Sanitizer::date( $vaccine_data['next_dose_date'] ?? null ),
		);

		// Validate required field.
		if ( empty( $payload['name'] ) ) {
			throw new ValidationException(
				array( 'name' => __( 'O nome da vacina é obrigatório.', 'canil-core' ) )
			);
		}

		// Create event data.
		$event_data = array(
			'entity_type' => $entity_type,
			'entity_id'   => $entity_id,
			'event_type'  => Event::TYPE_VACCINE,
			'event_date'  => $event_date,
			'payload'     => $payload,
			'notes'       => Sanitizer::textarea( $vaccine_data['notes'] ?? '' ),
			'created_by'  => get_current_user_id(),
		);

		// Set reminder date based on next_dose_date.
		if ( ! empty( $payload['next_dose_date'] ) ) {
			$event_data['reminder_date'] = $payload['next_dose_date'];
		}

		$event_id = $this->event_repository->insert( $event_data );
		$event    = $this->event_repository->find_by_id( $event_id );

		/**
		 * Fires after a vaccine is recorded.
		 *
		 * @param array $event Event data.
		 */
		do_action( 'canil_core_vaccine_recorded', $event );

		return array(
			'event'   => $event,
			'message' => __( 'Vacina registrada com sucesso.', 'canil-core' ),
		);
	}

	/**
	 * Record a deworming event.
	 *
	 * @param int    $entity_id      Entity ID.
	 * @param string $entity_type    Entity type (dog, litter, puppy).
	 * @param string $event_date     Deworming date (Y-m-d).
	 * @param array  $deworming_data Deworming data (product, dosage, next_dose_date).
	 * @return array{event: array<string, mixed>, message: string}
	 * @throws ValidationException If validation fails.
	 */
	public function record_deworming( int $entity_id, string $entity_type, string $event_date, array $deworming_data ): array {
		$this->validate_entity_exists( $entity_id, $entity_type );

		// Sanitize deworming data.
		$payload = array(
			'product'        => Sanitizer::text( $deworming_data['product'] ?? '' ),
			'dosage'         => Sanitizer::text( $deworming_data['dosage'] ?? '' ),
			'next_dose_date' => Sanitizer::date( $deworming_data['next_dose_date'] ?? null ),
		);

		// Validate required field.
		if ( empty( $payload['product'] ) ) {
			throw new ValidationException(
				array( 'product' => __( 'O produto de vermifugação é obrigatório.', 'canil-core' ) )
			);
		}

		// Create event data.
		$event_data = array(
			'entity_type' => $entity_type,
			'entity_id'   => $entity_id,
			'event_type'  => Event::TYPE_DEWORMING,
			'event_date'  => $event_date,
			'payload'     => $payload,
			'notes'       => Sanitizer::textarea( $deworming_data['notes'] ?? '' ),
			'created_by'  => get_current_user_id(),
		);

		// Set reminder date based on next_dose_date.
		if ( ! empty( $payload['next_dose_date'] ) ) {
			$event_data['reminder_date'] = $payload['next_dose_date'];
		}

		$event_id = $this->event_repository->insert( $event_data );
		$event    = $this->event_repository->find_by_id( $event_id );

		/**
		 * Fires after a deworming is recorded.
		 *
		 * @param array $event Event data.
		 */
		do_action( 'canil_core_deworming_recorded', $event );

		return array(
			'event'   => $event,
			'message' => __( 'Vermifugação registrada com sucesso.', 'canil-core' ),
		);
	}

	/**
	 * Record an exam event.
	 *
	 * @param int    $entity_id   Entity ID.
	 * @param string $entity_type Entity type (dog, litter, puppy).
	 * @param string $event_date  Exam date (Y-m-d).
	 * @param array  $exam_data   Exam data (type, result, attachments).
	 * @return array{event: array<string, mixed>, message: string}
	 * @throws ValidationException If validation fails.
	 */
	public function record_exam( int $entity_id, string $entity_type, string $event_date, array $exam_data ): array {
		$this->validate_entity_exists( $entity_id, $entity_type );

		// Sanitize attachments.
		$attachments = array();
		if ( ! empty( $exam_data['attachments'] ) && is_array( $exam_data['attachments'] ) ) {
			$attachments = Sanitizer::array( $exam_data['attachments'], array( Sanitizer::class, 'url' ) );
		}

		// Sanitize exam data.
		$payload = array(
			'type'   => Sanitizer::text( $exam_data['type'] ?? '' ),
			'result' => Sanitizer::textarea( $exam_data['result'] ?? '' ),
		);

		// Validate required field.
		if ( empty( $payload['type'] ) ) {
			throw new ValidationException(
				array( 'type' => __( 'O tipo de exame é obrigatório.', 'canil-core' ) )
			);
		}

		// Create event data.
		$event_data = array(
			'entity_type' => $entity_type,
			'entity_id'   => $entity_id,
			'event_type'  => Event::TYPE_EXAM,
			'event_date'  => $event_date,
			'payload'     => $payload,
			'attachments' => $attachments,
			'notes'       => Sanitizer::textarea( $exam_data['notes'] ?? '' ),
			'created_by'  => get_current_user_id(),
		);

		$event_id = $this->event_repository->insert( $event_data );
		$event    = $this->event_repository->find_by_id( $event_id );

		/**
		 * Fires after an exam is recorded.
		 *
		 * @param array $event Event data.
		 */
		do_action( 'canil_core_exam_recorded', $event );

		return array(
			'event'   => $event,
			'message' => __( 'Exame registrado com sucesso.', 'canil-core' ),
		);
	}

	/**
	 * Record a medication event.
	 *
	 * @param int    $entity_id       Entity ID.
	 * @param string $entity_type     Entity type (dog, litter, puppy).
	 * @param string $event_date      Medication start date (Y-m-d).
	 * @param array  $medication_data Medication data (name, dosage, frequency, end_date).
	 * @return array{event: array<string, mixed>, message: string}
	 * @throws ValidationException If validation fails.
	 */
	public function record_medication( int $entity_id, string $entity_type, string $event_date, array $medication_data ): array {
		$this->validate_entity_exists( $entity_id, $entity_type );

		// Sanitize medication data.
		$payload = array(
			'name'      => Sanitizer::text( $medication_data['name'] ?? '' ),
			'dosage'    => Sanitizer::text( $medication_data['dosage'] ?? '' ),
			'frequency' => Sanitizer::text( $medication_data['frequency'] ?? '' ),
			'end_date'  => Sanitizer::date( $medication_data['end_date'] ?? null ),
		);

		// Validate required field.
		if ( empty( $payload['name'] ) ) {
			throw new ValidationException(
				array( 'name' => __( 'O nome do medicamento é obrigatório.', 'canil-core' ) )
			);
		}

		// Create event data.
		$event_data = array(
			'entity_type' => $entity_type,
			'entity_id'   => $entity_id,
			'event_type'  => Event::TYPE_MEDICATION,
			'event_date'  => $event_date,
			'payload'     => $payload,
			'notes'       => Sanitizer::textarea( $medication_data['notes'] ?? '' ),
			'created_by'  => get_current_user_id(),
		);

		// Set event end date if provided.
		if ( ! empty( $payload['end_date'] ) ) {
			$event_data['event_end_date'] = $payload['end_date'];
		}

		$event_id = $this->event_repository->insert( $event_data );
		$event    = $this->event_repository->find_by_id( $event_id );

		/**
		 * Fires after a medication is recorded.
		 *
		 * @param array $event Event data.
		 */
		do_action( 'canil_core_medication_recorded', $event );

		return array(
			'event'   => $event,
			'message' => __( 'Medicação registrada com sucesso.', 'canil-core' ),
		);
	}

	/**
	 * Record a surgery event.
	 *
	 * @param int    $entity_id    Entity ID.
	 * @param string $entity_type  Entity type (dog, litter, puppy).
	 * @param string $event_date   Surgery date (Y-m-d).
	 * @param array  $surgery_data Surgery data (type, veterinarian, notes).
	 * @return array{event: array<string, mixed>, message: string}
	 * @throws ValidationException If validation fails.
	 */
	public function record_surgery( int $entity_id, string $entity_type, string $event_date, array $surgery_data ): array {
		$this->validate_entity_exists( $entity_id, $entity_type );

		// Sanitize surgery data.
		$payload = array(
			'type'         => Sanitizer::text( $surgery_data['type'] ?? '' ),
			'veterinarian' => Sanitizer::text( $surgery_data['veterinarian'] ?? '' ),
		);

		// Validate required field.
		if ( empty( $payload['type'] ) ) {
			throw new ValidationException(
				array( 'type' => __( 'O tipo de cirurgia é obrigatório.', 'canil-core' ) )
			);
		}

		// Create event data.
		$event_data = array(
			'entity_type' => $entity_type,
			'entity_id'   => $entity_id,
			'event_type'  => Event::TYPE_SURGERY,
			'event_date'  => $event_date,
			'payload'     => $payload,
			'notes'       => Sanitizer::textarea( $surgery_data['notes'] ?? '' ),
			'created_by'  => get_current_user_id(),
		);

		$event_id = $this->event_repository->insert( $event_data );
		$event    = $this->event_repository->find_by_id( $event_id );

		/**
		 * Fires after a surgery is recorded.
		 *
		 * @param array $event Event data.
		 */
		do_action( 'canil_core_surgery_recorded', $event );

		return array(
			'event'   => $event,
			'message' => __( 'Cirurgia registrada com sucesso.', 'canil-core' ),
		);
	}

	/**
	 * Record a vet visit event.
	 *
	 * @param int    $entity_id   Entity ID.
	 * @param string $entity_type Entity type (dog, litter, puppy).
	 * @param string $event_date  Visit date (Y-m-d).
	 * @param array  $visit_data  Visit data (reason, veterinarian, diagnosis, treatment, next_visit_date).
	 * @return array{event: array<string, mixed>, message: string}
	 * @throws NotFoundException If entity not found.
	 */
	public function record_vet_visit( int $entity_id, string $entity_type, string $event_date, array $visit_data ): array {
		$this->validate_entity_exists( $entity_id, $entity_type );

		// Sanitize visit data.
		$payload = array(
			'reason'          => Sanitizer::text( $visit_data['reason'] ?? '' ),
			'veterinarian'    => Sanitizer::text( $visit_data['veterinarian'] ?? '' ),
			'diagnosis'       => Sanitizer::textarea( $visit_data['diagnosis'] ?? '' ),
			'treatment'       => Sanitizer::textarea( $visit_data['treatment'] ?? '' ),
			'next_visit_date' => Sanitizer::date( $visit_data['next_visit_date'] ?? null ),
		);

		// Create event data.
		$event_data = array(
			'entity_type' => $entity_type,
			'entity_id'   => $entity_id,
			'event_type'  => Event::TYPE_VET_VISIT,
			'event_date'  => $event_date,
			'payload'     => $payload,
			'notes'       => Sanitizer::textarea( $visit_data['notes'] ?? '' ),
			'created_by'  => get_current_user_id(),
		);

		// Set reminder date based on next_visit_date.
		if ( ! empty( $payload['next_visit_date'] ) ) {
			$event_data['reminder_date'] = $payload['next_visit_date'];
		}

		$event_id = $this->event_repository->insert( $event_data );
		$event    = $this->event_repository->find_by_id( $event_id );

		/**
		 * Fires after a vet visit is recorded.
		 *
		 * @param array $event Event data.
		 */
		do_action( 'canil_core_vet_visit_recorded', $event );

		return array(
			'event'   => $event,
			'message' => __( 'Consulta veterinária registrada com sucesso.', 'canil-core' ),
		);
	}

	/**
	 * Get health history for an entity.
	 *
	 * @param int    $entity_id   Entity ID.
	 * @param string $entity_type Entity type (dog, litter, puppy).
	 * @return array<array<string, mixed>> Health events.
	 * @throws NotFoundException If entity not found.
	 */
	public function get_health_history( int $entity_id, string $entity_type ): array {
		$this->validate_entity_exists( $entity_id, $entity_type );

		// Get all events for this entity.
		$all_events = $this->event_repository->find_by_entity( $entity_type, $entity_id );

		// Filter to only health events.
		$health_events = array_filter(
			$all_events,
			function ( $event ) {
				return in_array(
					$event['event_type'],
					array(
						Event::TYPE_VACCINE,
						Event::TYPE_DEWORMING,
						Event::TYPE_EXAM,
						Event::TYPE_MEDICATION,
						Event::TYPE_SURGERY,
						Event::TYPE_VET_VISIT,
					),
					true
				);
			}
		);

		return array_values( $health_events );
	}

	/**
	 * Get upcoming vaccines based on next_dose_date.
	 *
	 * @param int $days Number of days ahead to look.
	 * @return array<array<string, mixed>> Upcoming vaccine events.
	 */
	public function get_upcoming_vaccines( int $days = 30 ): array {
		return $this->get_upcoming_health_events( Event::TYPE_VACCINE, $days );
	}

	/**
	 * Get upcoming dewormings based on next_dose_date.
	 *
	 * @param int $days Number of days ahead to look.
	 * @return array<array<string, mixed>> Upcoming deworming events.
	 */
	public function get_upcoming_dewormings( int $days = 30 ): array {
		return $this->get_upcoming_health_events( Event::TYPE_DEWORMING, $days );
	}

	/**
	 * Get overdue health events (vaccines and dewormings past their reminder date).
	 *
	 * @return array<array<string, mixed>> Overdue health events.
	 */
	public function get_overdue_health_events(): array {
		// Get all pending reminders.
		$pending = $this->event_repository->find_pending_reminders();

		// Filter to only health events (vaccines and dewormings).
		$overdue = array_filter(
			$pending,
			function ( $event ) {
				return in_array(
					$event['event_type'],
					array(
						Event::TYPE_VACCINE,
						Event::TYPE_DEWORMING,
					),
					true
				);
			}
		);

		return array_values( $overdue );
	}

	/**
	 * Validate that an entity exists.
	 *
	 * @param int    $entity_id   Entity ID.
	 * @param string $entity_type Entity type (dog, litter, puppy).
	 * @return void
	 * @throws NotFoundException   If entity not found.
	 * @throws ValidationException If entity type is invalid.
	 */
	private function validate_entity_exists( int $entity_id, string $entity_type ): void {
		// Validate entity type.
		$allowed_types = Event::get_allowed_entity_types();
		if ( ! in_array( $entity_type, $allowed_types, true ) ) {
			throw new ValidationException(
				array( 'entity_type' => __( 'Tipo de entidade inválido.', 'canil-core' ) )
			);
		}

		switch ( $entity_type ) {
			case Event::ENTITY_DOG:
				if ( ! $this->dog_repository->find_by_id( $entity_id ) ) {
					throw new NotFoundException( __( 'Cão não encontrado.', 'canil-core' ) );
				}
				break;

			case Event::ENTITY_LITTER:
				if ( ! $this->litter_repository->find_by_id( $entity_id ) ) {
					throw new NotFoundException( __( 'Ninhada não encontrada.', 'canil-core' ) );
				}
				break;

			case Event::ENTITY_PUPPY:
				if ( ! $this->puppy_repository->find_by_id( $entity_id ) ) {
					throw new NotFoundException( __( 'Filhote não encontrado.', 'canil-core' ) );
				}
				break;
		}
	}

	/**
	 * Get upcoming health events based on reminder_date.
	 *
	 * @param string $event_type Event type (vaccine, deworming).
	 * @param int    $days       Number of days ahead to look.
	 * @return array<array<string, mixed>> Upcoming events.
	 */
	private function get_upcoming_health_events( string $event_type, int $days ): array {
		$today    = DateHelper::today()->format( 'Y-m-d' );
		$end_date = DateHelper::today()->add( new \DateInterval( "P{$days}D" ) )->format( 'Y-m-d' );

		// Get all events of this type.
		$events = $this->event_repository->find_by_type( $event_type );

		// Filter by reminder_date range and not completed.
		return array_filter(
			$events,
			function ( $event ) use ( $today, $end_date ) {
				// Must have a reminder date.
				if ( empty( $event['reminder_date'] ) ) {
					return false;
				}

				// Must not be completed.
				if ( ! empty( $event['reminder_completed'] ) ) {
					return false;
				}

				// Must be within the date range.
				$reminder_date = substr( $event['reminder_date'], 0, 10 );
				return $reminder_date >= $today && $reminder_date <= $end_date;
			}
		);
	}
}
