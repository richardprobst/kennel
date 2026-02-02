<?php
/**
 * Weighing Service.
 *
 * Handles weight tracking for dogs and puppies.
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
 * WeighingService class.
 */
class WeighingService {

	/**
	 * Allowed weight units.
	 */
	public const UNIT_GRAMS     = 'g';
	public const UNIT_KILOGRAMS = 'kg';
	public const UNIT_POUNDS    = 'lb';

	/**
	 * Weight type constants.
	 */
	public const TYPE_BIRTH_WEIGHT = 'birth_weight';
	public const TYPE_WEEKLY       = 'weekly';
	public const TYPE_MONTHLY      = 'monthly';
	public const TYPE_GENERAL      = 'general';

	/**
	 * Conversion factors to grams.
	 *
	 * @var array<string, float>
	 */
	private const CONVERSION_TO_GRAMS = array(
		self::UNIT_GRAMS     => 1.0,
		self::UNIT_KILOGRAMS => 1000.0,
		self::UNIT_POUNDS    => 453.592,
	);

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
	 * Record a weight measurement for an entity.
	 *
	 * @param int    $entity_id   Entity ID.
	 * @param string $entity_type Entity type (dog, puppy).
	 * @param string $date        Weighing date (Y-m-d).
	 * @param float  $weight      Weight value.
	 * @param string $unit        Weight unit (g, kg, lb).
	 * @param string $notes       Optional notes.
	 * @param string $type        Optional weight type (birth_weight, weekly, monthly, general).
	 * @return array{event: array<string, mixed>, message: string}
	 * @throws ValidationException If validation fails.
	 */
	public function record_weight(
		int $entity_id,
		string $entity_type,
		string $date,
		float $weight,
		string $unit = self::UNIT_KILOGRAMS,
		string $notes = '',
		string $type = self::TYPE_GENERAL
	): array {
		// Validate entity exists.
		$this->validate_entity_exists( $entity_id, $entity_type );

		// Validate weight.
		if ( $weight <= 0 ) {
			throw new ValidationException(
				array( 'weight' => __( 'O peso deve ser maior que zero.', 'canil-core' ) )
			);
		}

		// Validate unit.
		$allowed_units = self::get_allowed_units();
		$unit          = Sanitizer::enum( $unit, $allowed_units, self::UNIT_KILOGRAMS );

		// Validate type.
		$allowed_types = self::get_allowed_weight_types();
		$type          = Sanitizer::enum( $type, $allowed_types, self::TYPE_GENERAL );

		// Sanitize date.
		$sanitized_date = Sanitizer::date( $date );
		if ( ! $sanitized_date ) {
			throw new ValidationException(
				array( 'date' => __( 'Data inválida.', 'canil-core' ) )
			);
		}

		// Build payload.
		$payload = array(
			'weight'      => $weight,
			'weight_unit' => $unit,
			'type'        => $type,
		);

		// Create event data.
		$event_data = array(
			'entity_type' => $entity_type,
			'entity_id'   => $entity_id,
			'event_type'  => Event::TYPE_WEIGHING,
			'event_date'  => $sanitized_date,
			'payload'     => $payload,
			'notes'       => Sanitizer::textarea( $notes ),
			'created_by'  => get_current_user_id(),
		);

		$event_id = $this->event_repository->insert( $event_data );
		$event    = $this->event_repository->find_by_id( $event_id );

		/**
		 * Fires after a weight is recorded.
		 *
		 * @param array  $event       Event data.
		 * @param int    $entity_id   Entity ID.
		 * @param string $entity_type Entity type.
		 */
		do_action( 'canil_core_weight_recorded', $event, $entity_id, $entity_type );

		return array(
			'event'   => $event,
			'message' => __( 'Peso registrado com sucesso.', 'canil-core' ),
		);
	}

	/**
	 * Get weight history for an entity.
	 *
	 * @param int    $entity_id   Entity ID.
	 * @param string $entity_type Entity type (dog, puppy).
	 * @return array<array<string, mixed>> Weighing events ordered by date descending.
	 * @throws NotFoundException   If entity not found.
	 * @throws ValidationException If entity type is invalid.
	 */
	public function get_weight_history( int $entity_id, string $entity_type ): array {
		$this->validate_entity_exists( $entity_id, $entity_type );

		// Get all events for this entity.
		$all_events = $this->event_repository->find_by_entity( $entity_type, $entity_id );

		// Filter to only weighing events.
		$weight_events = array_filter(
			$all_events,
			function ( $event ) {
				return Event::TYPE_WEIGHING === $event['event_type'];
			}
		);

		// Sort by date descending (find_by_entity already orders by event_date DESC).
		return array_values( $weight_events );
	}

	/**
	 * Get weight evolution data formatted for charts.
	 *
	 * @param int    $entity_id   Entity ID.
	 * @param string $entity_type Entity type (dog, puppy).
	 * @param string $target_unit Target unit for all weights (default: kg).
	 * @return array<array{date: string, weight: float, unit: string}> Weight data points ordered by date ascending.
	 * @throws NotFoundException   If entity not found.
	 * @throws ValidationException If entity type is invalid.
	 */
	public function get_weight_evolution( int $entity_id, string $entity_type, string $target_unit = self::UNIT_KILOGRAMS ): array {
		$history = $this->get_weight_history( $entity_id, $entity_type );

		// Reverse to get ascending order (oldest first).
		$history = array_reverse( $history );

		$evolution = array();

		foreach ( $history as $event ) {
			$payload = $event['payload'] ?? array();
			$weight  = (float) ( $payload['weight'] ?? 0 );
			$unit    = $payload['weight_unit'] ?? self::UNIT_KILOGRAMS;

			// Convert weight to target unit.
			$converted_weight = $this->convert_weight( $weight, $unit, $target_unit );

			// Extract date only (remove time portion).
			$date = self::extract_event_date( $event['event_date'] );

			$evolution[] = array(
				'date'   => $date,
				'weight' => round( $converted_weight, 2 ),
				'unit'   => $target_unit,
			);
		}

		return $evolution;
	}

	/**
	 * Get the most recent weight measurement for an entity.
	 *
	 * @param int    $entity_id   Entity ID.
	 * @param string $entity_type Entity type (dog, puppy).
	 * @return array<string, mixed>|null Latest weight event or null if none found.
	 * @throws NotFoundException   If entity not found.
	 * @throws ValidationException If entity type is invalid.
	 */
	public function get_latest_weight( int $entity_id, string $entity_type ): ?array {
		$history = $this->get_weight_history( $entity_id, $entity_type );

		// First item is the most recent (ordered by date DESC).
		return ! empty( $history ) ? $history[0] : null;
	}

	/**
	 * Record weights for multiple puppies in a litter at once.
	 *
	 * @param int    $litter_id     Litter ID.
	 * @param array  $puppy_weights Array of puppy weight data [{puppy_id: int, weight: float, unit?: string, notes?: string}].
	 * @param string $date          Weighing date (Y-m-d).
	 * @param string $type          Weight type (weekly, monthly, etc.).
	 * @return array{events: array<array<string, mixed>>, message: string}
	 * @throws NotFoundException   If litter or puppy not found.
	 * @throws ValidationException If validation fails.
	 */
	public function batch_record_weights(
		int $litter_id,
		array $puppy_weights,
		string $date,
		string $type = self::TYPE_WEEKLY
	): array {
		// Validate litter exists.
		$litter = $this->litter_repository->find_by_id( $litter_id );
		if ( ! $litter ) {
			throw new NotFoundException( __( 'Ninhada não encontrada.', 'canil-core' ) );
		}

		// Validate date.
		$sanitized_date = Sanitizer::date( $date );
		if ( ! $sanitized_date ) {
			throw new ValidationException(
				array( 'date' => __( 'Data inválida.', 'canil-core' ) )
			);
		}

		// Validate we have puppy weights.
		if ( empty( $puppy_weights ) ) {
			throw new ValidationException(
				array( 'puppy_weights' => __( 'Nenhum peso de filhote fornecido.', 'canil-core' ) )
			);
		}

		// Get all puppies from this litter for validation.
		$litter_puppies   = $this->puppy_repository->find_by_litter( $litter_id );
		$litter_puppy_ids = array_column( $litter_puppies, 'id' );

		$created_events = array();

		foreach ( $puppy_weights as $weight_data ) {
			$puppy_id = Sanitizer::int( $weight_data['puppy_id'] ?? 0 );
			$weight   = Sanitizer::float( $weight_data['weight'] ?? 0 );
			$unit     = $weight_data['unit'] ?? self::UNIT_KILOGRAMS;
			$notes    = $weight_data['notes'] ?? '';

			// Validate puppy belongs to this litter.
			if ( ! in_array( $puppy_id, $litter_puppy_ids, true ) ) {
				throw new ValidationException(
					array(
						'puppy_id' => sprintf(
							/* translators: %d: puppy ID */
							__( 'O filhote ID %d não pertence a esta ninhada.', 'canil-core' ),
							$puppy_id
						),
					)
				);
			}

			// Record weight (skip if zero).
			if ( $weight > 0 ) {
				$result           = $this->record_weight(
					$puppy_id,
					Event::ENTITY_PUPPY,
					$sanitized_date,
					$weight,
					$unit,
					$notes,
					$type
				);
				$created_events[] = $result['event'];
			}
		}

		/**
		 * Fires after batch weights are recorded for a litter.
		 *
		 * @param int   $litter_id      Litter ID.
		 * @param array $created_events Created events.
		 * @param array $litter         Litter data.
		 */
		do_action( 'canil_core_batch_weights_recorded', $litter_id, $created_events, $litter );

		return array(
			'events'  => $created_events,
			'message' => sprintf(
				/* translators: %d: number of weights recorded */
				_n(
					'%d peso registrado com sucesso.',
					'%d pesos registrados com sucesso.',
					count( $created_events ),
					'canil-core'
				),
				count( $created_events )
			),
		);
	}

	/**
	 * Get all puppy weights for a litter.
	 *
	 * @param int         $litter_id Litter ID.
	 * @param string|null $date      Optional date filter (Y-m-d).
	 * @return array<array<string, mixed>> Array of puppy data with their weight events.
	 * @throws NotFoundException If litter not found.
	 */
	public function get_litter_weights( int $litter_id, ?string $date = null ): array {
		// Validate litter exists.
		$litter = $this->litter_repository->find_by_id( $litter_id );
		if ( ! $litter ) {
			throw new NotFoundException( __( 'Ninhada não encontrada.', 'canil-core' ) );
		}

		// Get all puppies from this litter.
		$puppies = $this->puppy_repository->find_by_litter( $litter_id );

		$result = array();

		foreach ( $puppies as $puppy ) {
			$puppy_id = (int) $puppy['id'];

			// Get weight events for this puppy.
			$all_events = $this->event_repository->find_by_entity( Event::ENTITY_PUPPY, $puppy_id );

			$weight_events = array_filter(
				$all_events,
				function ( $event ) use ( $date ) {
					if ( Event::TYPE_WEIGHING !== $event['event_type'] ) {
						return false;
					}

					// Filter by date if provided.
					if ( null !== $date ) {
						$event_date = self::extract_event_date( $event['event_date'] );
						return $event_date === $date;
					}

					return true;
				}
			);

			$result[] = array(
				'puppy'   => $puppy,
				'weights' => array_values( $weight_events ),
			);
		}

		return $result;
	}

	/**
	 * Calculate weight gain over a period.
	 *
	 * @param int    $entity_id   Entity ID.
	 * @param string $entity_type Entity type (dog, puppy).
	 * @param int    $days        Number of days to look back (default: 7).
	 * @return array{gain: float|null, gain_percentage: float|null, start_weight: float|null, end_weight: float|null, unit: string, period_days: int}
	 * @throws NotFoundException   If entity not found.
	 * @throws ValidationException If entity type is invalid.
	 */
	public function calculate_weight_gain( int $entity_id, string $entity_type, int $days = 7 ): array {
		$this->validate_entity_exists( $entity_id, $entity_type );

		// Get weight history.
		$history = $this->get_weight_history( $entity_id, $entity_type );

		if ( empty( $history ) ) {
			return array(
				'gain'            => null,
				'gain_percentage' => null,
				'start_weight'    => null,
				'end_weight'      => null,
				'unit'            => self::UNIT_KILOGRAMS,
				'period_days'     => $days,
			);
		}

		// Calculate date threshold.
		$threshold_date = DateHelper::today()->sub( new \DateInterval( "P{$days}D" ) )->format( 'Y-m-d' );

		// Get the most recent weight.
		$latest_event   = $history[0];
		$latest_payload = $latest_event['payload'] ?? array();
		$latest_weight  = (float) ( $latest_payload['weight'] ?? 0 );
		$latest_unit    = $latest_payload['weight_unit'] ?? self::UNIT_KILOGRAMS;

		// Convert to kg for consistency.
		$end_weight_kg = $this->convert_weight( $latest_weight, $latest_unit, self::UNIT_KILOGRAMS );

		// Find the weight closest to the threshold date.
		$start_weight_kg = null;
		$start_event     = null;

		foreach ( $history as $event ) {
			$event_date = self::extract_event_date( $event['event_date'] );

			if ( $event_date <= $threshold_date ) {
				$start_event     = $event;
				$start_payload   = $event['payload'] ?? array();
				$start_weight    = (float) ( $start_payload['weight'] ?? 0 );
				$start_unit      = $start_payload['weight_unit'] ?? self::UNIT_KILOGRAMS;
				$start_weight_kg = $this->convert_weight( $start_weight, $start_unit, self::UNIT_KILOGRAMS );
				break;
			}
		}

		// If no weight found before threshold, use the oldest available.
		if ( null === $start_weight_kg && count( $history ) > 1 ) {
			$oldest_event    = $history[ count( $history ) - 1 ];
			$oldest_payload  = $oldest_event['payload'] ?? array();
			$oldest_weight   = (float) ( $oldest_payload['weight'] ?? 0 );
			$oldest_unit     = $oldest_payload['weight_unit'] ?? self::UNIT_KILOGRAMS;
			$start_weight_kg = $this->convert_weight( $oldest_weight, $oldest_unit, self::UNIT_KILOGRAMS );
		}

		// Calculate gain.
		$gain            = null;
		$gain_percentage = null;

		if ( null !== $start_weight_kg && $start_weight_kg > 0 ) {
			$gain            = round( $end_weight_kg - $start_weight_kg, 3 );
			$gain_percentage = round( ( $gain / $start_weight_kg ) * 100, 2 );
		}

		return array(
			'gain'            => $gain,
			'gain_percentage' => $gain_percentage,
			'start_weight'    => $start_weight_kg ? round( $start_weight_kg, 3 ) : null,
			'end_weight'      => round( $end_weight_kg, 3 ),
			'unit'            => self::UNIT_KILOGRAMS,
			'period_days'     => $days,
		);
	}

	/**
	 * Convert weight between units.
	 *
	 * @param float  $weight    Weight value.
	 * @param string $from_unit Source unit (g, kg, lb).
	 * @param string $to_unit   Target unit (g, kg, lb).
	 * @return float Converted weight.
	 */
	public function convert_weight( float $weight, string $from_unit, string $to_unit ): float {
		if ( $from_unit === $to_unit ) {
			return $weight;
		}

		// Convert to grams first.
		$from_factor = self::CONVERSION_TO_GRAMS[ $from_unit ] ?? 1.0;
		$in_grams    = $weight * $from_factor;

		// Convert from grams to target unit.
		$to_factor = self::CONVERSION_TO_GRAMS[ $to_unit ] ?? 1.0;

		return $in_grams / $to_factor;
	}

	/**
	 * Get allowed weight units.
	 *
	 * @return array<string> Allowed unit values.
	 */
	public static function get_allowed_units(): array {
		return array(
			self::UNIT_GRAMS,
			self::UNIT_KILOGRAMS,
			self::UNIT_POUNDS,
		);
	}

	/**
	 * Get allowed weight types.
	 *
	 * @return array<string> Allowed weight type values.
	 */
	public static function get_allowed_weight_types(): array {
		return array(
			self::TYPE_BIRTH_WEIGHT,
			self::TYPE_WEEKLY,
			self::TYPE_MONTHLY,
			self::TYPE_GENERAL,
		);
	}

	/**
	 * Extract date portion from event datetime string.
	 *
	 * @param string $datetime Datetime string (Y-m-d H:i:s).
	 * @return string Date string (Y-m-d).
	 */
	private static function extract_event_date( string $datetime ): string {
		return substr( $datetime, 0, 10 );
	}

	/**
	 * Validate that an entity exists.
	 *
	 * @param int    $entity_id   Entity ID.
	 * @param string $entity_type Entity type (dog, puppy).
	 * @return void
	 * @throws NotFoundException   If entity not found.
	 * @throws ValidationException If entity type is invalid.
	 */
	private function validate_entity_exists( int $entity_id, string $entity_type ): void {
		// Validate entity type (only dog and puppy support weighing).
		$allowed_types = array( Event::ENTITY_DOG, Event::ENTITY_PUPPY );
		if ( ! in_array( $entity_type, $allowed_types, true ) ) {
			throw new ValidationException(
				array( 'entity_type' => __( 'Tipo de entidade inválido. Apenas cães e filhotes podem ser pesados.', 'canil-core' ) )
			);
		}

		switch ( $entity_type ) {
			case Event::ENTITY_DOG:
				if ( ! $this->dog_repository->find_by_id( $entity_id ) ) {
					throw new NotFoundException( __( 'Cão não encontrado.', 'canil-core' ) );
				}
				break;

			case Event::ENTITY_PUPPY:
				if ( ! $this->puppy_repository->find_by_id( $entity_id ) ) {
					throw new NotFoundException( __( 'Filhote não encontrado.', 'canil-core' ) );
				}
				break;
		}
	}
}
