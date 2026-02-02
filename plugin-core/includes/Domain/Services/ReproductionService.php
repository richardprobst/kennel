<?php
/**
 * Reproduction Service.
 *
 * Handles the reproduction workflow: heat → mating → pregnancy → birth.
 *
 * @package CanilCore
 */

namespace CanilCore\Domain\Services;

use CanilCore\Domain\Entities\Event;
use CanilCore\Domain\Entities\Litter;
use CanilCore\Domain\Exceptions\ValidationException;
use CanilCore\Domain\Exceptions\NotFoundException;
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
 * ReproductionService class.
 */
class ReproductionService {

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
	 * Register heat start for a female dog.
	 *
	 * @param int    $dam_id    Female dog ID.
	 * @param string $heat_date Heat start date (Y-m-d).
	 * @param string $notes     Optional notes.
	 * @return array{event: array<string, mixed>, message: string}
	 * @throws ValidationException If validation fails.
	 * @throws NotFoundException   If dog not found.
	 */
	public function start_heat( int $dam_id, string $heat_date, string $notes = '' ): array {
		// Validate dog exists and is female.
		$dog = $this->dog_repository->find_by_id( $dam_id );
		if ( ! $dog ) {
			throw new NotFoundException( __( 'Cão não encontrado.', 'canil-core' ) );
		}

		if ( 'female' !== $dog['sex'] ) {
			throw new ValidationException(
				array( 'dam_id' => __( 'Apenas fêmeas podem entrar no cio.', 'canil-core' ) )
			);
		}

		// Create heat event.
		$event_data = array(
			'entity_type' => Event::ENTITY_DOG,
			'entity_id'   => $dam_id,
			'event_type'  => Event::TYPE_HEAT,
			'event_date'  => $heat_date,
			'payload'     => array(
				'heat_start_date' => $heat_date,
				'dog_name'        => $dog['name'],
			),
			'notes'       => $notes,
			'created_by'  => get_current_user_id(),
		);

		$event_id = $this->event_repository->insert( $event_data );
		$event    = $this->event_repository->find_by_id( $event_id );

		/**
		 * Fires after a heat is started.
		 *
		 * @param array $dog   Dog data.
		 * @param array $event Event data.
		 */
		do_action( 'canil_core_heat_started', $dog, $event );

		return array(
			'event'   => $event,
			'message' => __( 'Cio registrado com sucesso.', 'canil-core' ),
		);
	}

	/**
	 * Record a mating and create a litter.
	 *
	 * @param int    $dam_id      Female dog ID.
	 * @param int    $sire_id     Male dog ID.
	 * @param string $mating_date Mating date (Y-m-d).
	 * @param array  $details     Additional details (mating_type, notes, etc.).
	 * @return array{litter: array<string, mixed>, event: array<string, mixed>, message: string}
	 * @throws ValidationException If validation fails.
	 * @throws NotFoundException   If dog not found.
	 */
	public function record_mating( int $dam_id, int $sire_id, string $mating_date, array $details = array() ): array {
		// Validate dam exists and is female.
		$dam = $this->dog_repository->find_by_id( $dam_id );
		if ( ! $dam ) {
			throw new NotFoundException( __( 'Matriz não encontrada.', 'canil-core' ) );
		}
		if ( 'female' !== $dam['sex'] ) {
			throw new ValidationException(
				array( 'dam_id' => __( 'A matriz deve ser uma fêmea.', 'canil-core' ) )
			);
		}

		// Validate sire exists and is male.
		$sire = $this->dog_repository->find_by_id( $sire_id );
		if ( ! $sire ) {
			throw new NotFoundException( __( 'Reprodutor não encontrado.', 'canil-core' ) );
		}
		if ( 'male' !== $sire['sex'] ) {
			throw new ValidationException(
				array( 'sire_id' => __( 'O reprodutor deve ser um macho.', 'canil-core' ) )
			);
		}

		// Calculate expected birth date.
		$mating_dt       = DateHelper::parse_iso( $mating_date );
		$expected_birth  = DateHelper::calculate_expected_birth( $mating_dt );
		$expected_string = $expected_birth->format( 'Y-m-d' );

		// Create litter.
		$litter_data = array(
			'name'                => sprintf(
				/* translators: 1: dam name, 2: sire name */
				__( 'Ninhada %1$s x %2$s', 'canil-core' ),
				$dam['name'],
				$sire['name']
			),
			'dam_id'              => $dam_id,
			'sire_id'             => $sire_id,
			'status'              => Litter::STATUS_CONFIRMED,
			'mating_date'         => $mating_date,
			'mating_type'         => $details['mating_type'] ?? Litter::MATING_NATURAL,
			'expected_birth_date' => $expected_string,
			'notes'               => $details['notes'] ?? '',
		);

		// Add heat start date if provided.
		if ( ! empty( $details['heat_start_date'] ) ) {
			$litter_data['heat_start_date'] = $details['heat_start_date'];
		}

		$litter_id = $this->litter_repository->insert( $litter_data );
		$litter    = $this->litter_repository->find_by_id( $litter_id );

		// Create mating event for the dam.
		$event_data = array(
			'entity_type' => Event::ENTITY_DOG,
			'entity_id'   => $dam_id,
			'event_type'  => Event::TYPE_MATING,
			'event_date'  => $mating_date,
			'payload'     => array(
				'litter_id'           => $litter_id,
				'sire_id'             => $sire_id,
				'sire_name'           => $sire['name'],
				'dam_name'            => $dam['name'],
				'mating_type'         => $litter_data['mating_type'],
				'expected_birth_date' => $expected_string,
			),
			'notes'       => $details['notes'] ?? '',
			'created_by'  => get_current_user_id(),
		);

		// Set reminder for expected birth.
		$event_data['reminder_date'] = $expected_string;

		$event_id = $this->event_repository->insert( $event_data );
		$event    = $this->event_repository->find_by_id( $event_id );

		// Also create a mating event for the litter.
		$litter_event_data                = $event_data;
		$litter_event_data['entity_type'] = Event::ENTITY_LITTER;
		$litter_event_data['entity_id']   = $litter_id;
		$this->event_repository->insert( $litter_event_data );

		/**
		 * Fires after a mating is recorded.
		 *
		 * @param array $litter Litter data.
		 * @param array $event  Event data.
		 */
		do_action( 'canil_core_mating_recorded', $litter, $event );

		return array(
			'litter'  => $litter,
			'event'   => $event,
			'message' => sprintf(
				/* translators: %s: expected birth date */
				__( 'Cobertura registrada. Previsão de parto: %s', 'canil-core' ),
				DateHelper::format( $expected_string )
			),
		);
	}

	/**
	 * Confirm pregnancy for a litter.
	 *
	 * @param int    $litter_id         Litter ID.
	 * @param string $confirmation_date Pregnancy confirmation date (Y-m-d).
	 * @param string $method            Confirmation method (ultrasound, palpation, etc.).
	 * @param string $notes             Optional notes.
	 * @return array{litter: array<string, mixed>, event: array<string, mixed>, message: string}
	 * @throws NotFoundException If litter not found.
	 */
	public function confirm_pregnancy(
		int $litter_id,
		string $confirmation_date,
		string $method = 'ultrasound',
		string $notes = ''
	): array {
		// Validate litter exists.
		$litter = $this->litter_repository->find_by_id( $litter_id );
		if ( ! $litter ) {
			throw new NotFoundException( __( 'Ninhada não encontrada.', 'canil-core' ) );
		}

		// Update litter status.
		$this->litter_repository->update(
			$litter_id,
			array(
				'status'                   => Litter::STATUS_PREGNANT,
				'pregnancy_confirmed_date' => $confirmation_date,
			)
		);

		// Create pregnancy test event.
		$event_data = array(
			'entity_type' => Event::ENTITY_LITTER,
			'entity_id'   => $litter_id,
			'event_type'  => Event::TYPE_PREGNANCY_TEST,
			'event_date'  => $confirmation_date,
			'payload'     => array(
				'result'  => 'positive',
				'method'  => $method,
				'dam_id'  => $litter['dam_id'],
				'sire_id' => $litter['sire_id'],
			),
			'notes'       => $notes,
			'created_by'  => get_current_user_id(),
		);

		$event_id = $this->event_repository->insert( $event_data );
		$event    = $this->event_repository->find_by_id( $event_id );

		// Also create event for the dam.
		$dam_event_data                = $event_data;
		$dam_event_data['entity_type'] = Event::ENTITY_DOG;
		$dam_event_data['entity_id']   = $litter['dam_id'];
		$this->event_repository->insert( $dam_event_data );

		// Refresh litter data.
		$litter = $this->litter_repository->find_by_id( $litter_id );

		/**
		 * Fires after pregnancy is confirmed.
		 *
		 * @param array $litter Litter data.
		 * @param array $event  Event data.
		 */
		do_action( 'canil_core_pregnancy_confirmed', $litter, $event );

		return array(
			'litter'  => $litter,
			'event'   => $event,
			'message' => __( 'Gestação confirmada com sucesso.', 'canil-core' ),
		);
	}

	/**
	 * Record birth and create puppies.
	 *
	 * @param int    $litter_id    Litter ID.
	 * @param string $birth_date   Birth date (Y-m-d).
	 * @param array  $birth_data   Birth information (birth_type, notes, etc.).
	 * @param array  $puppies_data Array of puppy data.
	 * @return array{litter: array<string, mixed>, puppies: array<array<string, mixed>>, event: array<string, mixed>, message: string}
	 * @throws NotFoundException If litter not found.
	 */
	public function record_birth(
		int $litter_id,
		string $birth_date,
		array $birth_data = array(),
		array $puppies_data = array()
	): array {
		// Validate litter exists.
		$litter = $this->litter_repository->find_with_parents( $litter_id );
		if ( ! $litter ) {
			throw new NotFoundException( __( 'Ninhada não encontrada.', 'canil-core' ) );
		}

		// Count puppies by sex.
		$males_count   = 0;
		$females_count = 0;
		foreach ( $puppies_data as $puppy ) {
			if ( 'male' === ( $puppy['sex'] ?? '' ) ) {
				++$males_count;
			} else {
				++$females_count;
			}
		}

		$total_puppies = count( $puppies_data );
		$alive_count   = 0;

		foreach ( $puppies_data as $puppy ) {
			if ( ! isset( $puppy['status'] ) || 'deceased' !== $puppy['status'] ) {
				++$alive_count;
			}
		}

		// Update litter.
		$this->litter_repository->update(
			$litter_id,
			array(
				'status'              => Litter::STATUS_BORN,
				'actual_birth_date'   => $birth_date,
				'birth_type'          => $birth_data['birth_type'] ?? Litter::BIRTH_NATURAL,
				'puppies_born_count'  => $total_puppies,
				'puppies_alive_count' => $alive_count,
				'males_count'         => $males_count,
				'females_count'       => $females_count,
			)
		);

		// Create puppies.
		$created_puppies = array();
		foreach ( $puppies_data as $index => $puppy_data ) {
			// Generate default identifier based on sex and order.
			$default_identifier = sprintf(
				'%s-%d',
				( $puppy_data['sex'] ?? 'male' ) === 'male' ? 'M' : 'F',
				$index + 1
			);

			$puppy_insert = array(
				'litter_id'  => $litter_id,
				'identifier' => $puppy_data['identifier'] ?? $default_identifier,
				'name'       => $puppy_data['name'] ?? null,
				'sex'        => $puppy_data['sex'] ?? 'male',
				'color'      => $puppy_data['color'] ?? null,
				'status'     => $puppy_data['status'] ?? 'available',
				'notes'      => $puppy_data['notes'] ?? null,
			);

			// Add birth weight if provided.
			if ( ! empty( $puppy_data['birth_weight'] ) ) {
				$puppy_insert['birth_weight'] = (float) $puppy_data['birth_weight'];
			}

			// Add birth order.
			$puppy_insert['birth_order'] = $index + 1;

			$puppy_id          = $this->puppy_repository->insert( $puppy_insert );
			$created_puppies[] = $this->puppy_repository->find_by_id( $puppy_id );

			// Create weighing event if birth weight provided.
			if ( ! empty( $puppy_data['birth_weight'] ) ) {
				$this->event_repository->insert(
					array(
						'entity_type' => Event::ENTITY_PUPPY,
						'entity_id'   => $puppy_id,
						'event_type'  => Event::TYPE_WEIGHING,
						'event_date'  => $birth_date,
						'payload'     => array(
							'weight'      => absint( $puppy_data['birth_weight'] ),
							'weight_unit' => 'g',
							'type'        => 'birth_weight',
						),
						'created_by'  => get_current_user_id(),
					)
				);
			}
		}

		// Create birth event for litter.
		$event_data = array(
			'entity_type' => Event::ENTITY_LITTER,
			'entity_id'   => $litter_id,
			'event_type'  => Event::TYPE_BIRTH,
			'event_date'  => $birth_date,
			'payload'     => array(
				'birth_type'          => $birth_data['birth_type'] ?? Litter::BIRTH_NATURAL,
				'puppies_born_count'  => $total_puppies,
				'puppies_alive_count' => $alive_count,
				'males_count'         => $males_count,
				'females_count'       => $females_count,
				'dam_id'              => $litter['dam_id'],
				'dam_name'            => $litter['dam_name'] ?? '',
				'sire_id'             => $litter['sire_id'],
				'sire_name'           => $litter['sire_name'] ?? '',
			),
			'notes'       => $birth_data['notes'] ?? '',
			'created_by'  => get_current_user_id(),
		);

		$event_id = $this->event_repository->insert( $event_data );
		$event    = $this->event_repository->find_by_id( $event_id );

		// Also create birth event for the dam.
		$dam_event_data                = $event_data;
		$dam_event_data['entity_type'] = Event::ENTITY_DOG;
		$dam_event_data['entity_id']   = $litter['dam_id'];
		$this->event_repository->insert( $dam_event_data );

		// Refresh litter data.
		$litter = $this->litter_repository->find_by_id( $litter_id );

		/**
		 * Fires after birth is recorded.
		 *
		 * @param array $litter          Litter data.
		 * @param array $created_puppies Created puppies.
		 * @param array $event           Birth event.
		 */
		do_action( 'canil_core_birth_recorded', $litter, $created_puppies, $event );

		return array(
			'litter'  => $litter,
			'puppies' => $created_puppies,
			'event'   => $event,
			'message' => sprintf(
				/* translators: %d: number of puppies born */
				_n(
					'%d filhote registrado com sucesso.',
					'%d filhotes registrados com sucesso.',
					$total_puppies,
					'canil-core'
				),
				$total_puppies
			),
		);
	}

	/**
	 * Cancel a litter.
	 *
	 * @param int    $litter_id Litter ID.
	 * @param string $reason    Cancellation reason.
	 * @return array{litter: array<string, mixed>, message: string}
	 * @throws NotFoundException If litter not found.
	 */
	public function cancel_litter( int $litter_id, string $reason = '' ): array {
		$litter = $this->litter_repository->find_by_id( $litter_id );
		if ( ! $litter ) {
			throw new NotFoundException( __( 'Ninhada não encontrada.', 'canil-core' ) );
		}

		$this->litter_repository->update(
			$litter_id,
			array(
				'status' => Litter::STATUS_CANCELLED,
				'notes'  => $reason ?: $litter['notes'],
			)
		);

		$litter = $this->litter_repository->find_by_id( $litter_id );

		/**
		 * Fires after a litter is cancelled.
		 *
		 * @param array  $litter Litter data.
		 * @param string $reason Cancellation reason.
		 */
		do_action( 'canil_core_litter_cancelled', $litter, $reason );

		return array(
			'litter'  => $litter,
			'message' => __( 'Ninhada cancelada.', 'canil-core' ),
		);
	}

	/**
	 * Get litter timeline (all events).
	 *
	 * @param int $litter_id Litter ID.
	 * @return array<array<string, mixed>> Timeline events.
	 * @throws NotFoundException If litter not found.
	 */
	public function get_litter_timeline( int $litter_id ): array {
		$litter = $this->litter_repository->find_by_id( $litter_id );
		if ( ! $litter ) {
			throw new NotFoundException( __( 'Ninhada não encontrada.', 'canil-core' ) );
		}

		return $this->event_repository->find_by_entity( Event::ENTITY_LITTER, $litter_id );
	}

	/**
	 * Get dog reproduction history.
	 *
	 * @param int $dog_id Dog ID.
	 * @return array{events: array<array<string, mixed>>, litters: array<array<string, mixed>>}
	 * @throws NotFoundException If dog not found.
	 */
	public function get_dog_reproduction_history( int $dog_id ): array {
		$dog = $this->dog_repository->find_by_id( $dog_id );
		if ( ! $dog ) {
			throw new NotFoundException( __( 'Cão não encontrado.', 'canil-core' ) );
		}

		// Get all reproduction events for this dog.
		$all_events = $this->event_repository->find_by_entity( Event::ENTITY_DOG, $dog_id );

		$reproduction_events = array_filter(
			$all_events,
			function ( $event ) {
				return in_array(
					$event['event_type'],
					array(
						Event::TYPE_HEAT,
						Event::TYPE_MATING,
						Event::TYPE_PREGNANCY_TEST,
						Event::TYPE_BIRTH,
					),
					true
				);
			}
		);

		// Get litters.
		$litters = array();
		if ( 'female' === $dog['sex'] ) {
			$litters = $this->litter_repository->find_by_dam( $dog_id );
		} else {
			$litters = $this->litter_repository->find_by_sire( $dog_id );
		}

		return array(
			'events'  => array_values( $reproduction_events ),
			'litters' => $litters,
		);
	}

	/**
	 * Get upcoming births (expected birth dates).
	 *
	 * @param int $days Days ahead to look.
	 * @return array<array<string, mixed>> Litters with upcoming births.
	 */
	public function get_upcoming_births( int $days = 30 ): array {
		$today    = DateHelper::today()->format( 'Y-m-d' );
		$end_date = DateHelper::today()->add( new \DateInterval( "P{$days}D" ) )->format( 'Y-m-d' );

		// Get pregnant litters.
		$pregnant  = $this->litter_repository->find_by_status( Litter::STATUS_PREGNANT );
		$confirmed = $this->litter_repository->find_by_status( Litter::STATUS_CONFIRMED );

		$litters = array_merge( $pregnant, $confirmed );

		// Filter by expected birth date range.
		return array_filter(
			$litters,
			function ( $litter ) use ( $today, $end_date ) {
				if ( empty( $litter['expected_birth_date'] ) ) {
					return false;
				}
				return $litter['expected_birth_date'] >= $today
					&& $litter['expected_birth_date'] <= $end_date;
			}
		);
	}
}
