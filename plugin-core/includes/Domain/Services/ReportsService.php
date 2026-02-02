<?php
/**
 * Reports Service.
 *
 * Handles report generation for kennel data.
 *
 * @package CanilCore
 */

namespace CanilCore\Domain\Services;

use CanilCore\Domain\Entities\Dog;
use CanilCore\Domain\Entities\Event;
use CanilCore\Domain\Entities\Litter;
use CanilCore\Domain\Entities\Puppy;
use CanilCore\Helpers\DateHelper;
use CanilCore\Infrastructure\Repositories\DogRepository;
use CanilCore\Infrastructure\Repositories\EventRepository;
use CanilCore\Infrastructure\Repositories\LitterRepository;
use CanilCore\Infrastructure\Repositories\PuppyRepository;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ReportsService class.
 */
class ReportsService {

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
	 * Puppy repository.
	 *
	 * @var PuppyRepository
	 */
	private PuppyRepository $puppy_repository;

	/**
	 * Event repository.
	 *
	 * @var EventRepository
	 */
	private EventRepository $event_repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->dog_repository    = new DogRepository();
		$this->litter_repository = new LitterRepository();
		$this->puppy_repository  = new PuppyRepository();
		$this->event_repository  = new EventRepository();
	}

	/**
	 * Get plantel (herd) report.
	 *
	 * @param array<string, mixed> $filters Filters (status, sex).
	 * @return array{data: array<array<string, mixed>>, summary: array<string, int>, generated_at: string}
	 */
	public function get_plantel_report( array $filters = array() ): array {
		$result = $this->dog_repository->find_all( $filters, 1, 1000, 'name', 'ASC' );
		$dogs   = $result['data'];

		// Calculate summary.
		$summary = array(
			'total'    => count( $dogs ),
			'males'    => 0,
			'females'  => 0,
			'active'   => 0,
			'breeding' => 0,
			'retired'  => 0,
			'coowned'  => 0,
		);

		foreach ( $dogs as $dog ) {
			if ( 'male' === ( $dog['sex'] ?? '' ) ) {
				++$summary['males'];
			} else {
				++$summary['females'];
			}

			$status = $dog['status'] ?? 'active';
			if ( isset( $summary[ $status ] ) ) {
				++$summary[ $status ];
			}
		}

		/**
		 * Fires after plantel report is generated.
		 *
		 * @param array $dogs    Dogs data.
		 * @param array $summary Report summary.
		 */
		do_action( 'canil_core_plantel_report_generated', $dogs, $summary );

		return array(
			'data'         => $dogs,
			'summary'      => $summary,
			'generated_at' => DateHelper::now()->format( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Get litters report.
	 *
	 * @param string|null $start_date Start date (Y-m-d).
	 * @param string|null $end_date   End date (Y-m-d).
	 * @param string|null $status     Filter by status.
	 * @return array{data: array<array<string, mixed>>, summary: array<string, mixed>, generated_at: string}
	 */
	public function get_litters_report(
		?string $start_date = null,
		?string $end_date = null,
		?string $status = null
	): array {
		$filters = array();

		if ( $status ) {
			$filters['status'] = $status;
		}

		$result  = $this->litter_repository->find_all( $filters, 1, 1000, 'mating_date', 'DESC' );
		$litters = $result['data'];

		// Filter by date range.
		if ( $start_date || $end_date ) {
			$litters = array_filter(
				$litters,
				function ( $litter ) use ( $start_date, $end_date ) {
					$date = $litter['mating_date'] ?? $litter['created_at'] ?? null;
					if ( ! $date ) {
						return true;
					}
					$date = substr( $date, 0, 10 );
					if ( $start_date && $date < $start_date ) {
						return false;
					}
					if ( $end_date && $date > $end_date ) {
						return false;
					}
					return true;
				}
			);
		}

		// Calculate summary.
		$summary = array(
			'total'               => count( $litters ),
			'by_status'           => array(),
			'total_puppies_born'  => 0,
			'total_puppies_alive' => 0,
		);

		foreach ( $litters as $litter ) {
			$litter_status = $litter['status'] ?? 'unknown';
			if ( ! isset( $summary['by_status'][ $litter_status ] ) ) {
				$summary['by_status'][ $litter_status ] = 0;
			}
			++$summary['by_status'][ $litter_status ];

			$summary['total_puppies_born']  += (int) ( $litter['puppies_born_count'] ?? 0 );
			$summary['total_puppies_alive'] += (int) ( $litter['puppies_alive_count'] ?? 0 );
		}

		/**
		 * Fires after litters report is generated.
		 *
		 * @param array $litters Litters data.
		 * @param array $summary Report summary.
		 */
		do_action( 'canil_core_litters_report_generated', $litters, $summary );

		return array(
			'data'         => array_values( $litters ),
			'summary'      => $summary,
			'generated_at' => DateHelper::now()->format( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Get puppies report.
	 *
	 * @param string|null $status Filter by status (available, reserved, sold, retained, deceased).
	 * @return array{data: array<array<string, mixed>>, summary: array<string, int>, generated_at: string}
	 */
	public function get_puppies_report( ?string $status = null ): array {
		$filters = array();

		if ( $status ) {
			$filters['status'] = $status;
		}

		$result  = $this->puppy_repository->find_all( $filters, 1, 1000, 'created_at', 'DESC' );
		$puppies = $result['data'];

		// Calculate summary.
		$summary = array(
			'total'     => count( $puppies ),
			'by_status' => array(
				'available' => 0,
				'reserved'  => 0,
				'sold'      => 0,
				'retained'  => 0,
				'deceased'  => 0,
			),
			'males'     => 0,
			'females'   => 0,
		);

		foreach ( $puppies as $puppy ) {
			$puppy_status = $puppy['status'] ?? 'available';
			if ( isset( $summary['by_status'][ $puppy_status ] ) ) {
				++$summary['by_status'][ $puppy_status ];
			}

			if ( 'male' === ( $puppy['sex'] ?? '' ) ) {
				++$summary['males'];
			} else {
				++$summary['females'];
			}
		}

		/**
		 * Fires after puppies report is generated.
		 *
		 * @param array $puppies Puppies data.
		 * @param array $summary Report summary.
		 */
		do_action( 'canil_core_puppies_report_generated', $puppies, $summary );

		return array(
			'data'         => $puppies,
			'summary'      => $summary,
			'generated_at' => DateHelper::now()->format( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Get health events report.
	 *
	 * @param string|null $start_date  Start date (Y-m-d).
	 * @param string|null $end_date    End date (Y-m-d).
	 * @param string|null $event_type  Filter by event type (vaccine, deworming, exam, etc.).
	 * @param string|null $entity_type Filter by entity type (dog, puppy, litter).
	 * @return array{data: array<array<string, mixed>>, summary: array<string, mixed>, generated_at: string}
	 */
	public function get_health_report(
		?string $start_date = null,
		?string $end_date = null,
		?string $event_type = null,
		?string $entity_type = null
	): array {
		// Get health event types.
		$health_types = array(
			Event::TYPE_VACCINE,
			Event::TYPE_DEWORMING,
			Event::TYPE_EXAM,
			Event::TYPE_MEDICATION,
			Event::TYPE_SURGERY,
			Event::TYPE_VET_VISIT,
		);

		// Filter to specific type if provided.
		if ( $event_type && in_array( $event_type, $health_types, true ) ) {
			$health_types = array( $event_type );
		}

		$all_events = array();

		// Get events for each type.
		foreach ( $health_types as $type ) {
			$events     = $this->event_repository->find_by_type( $type );
			$all_events = array_merge( $all_events, $events );
		}

		// Filter by entity type.
		if ( $entity_type ) {
			$all_events = array_filter(
				$all_events,
				fn( $event ) => ( $event['entity_type'] ?? '' ) === $entity_type
			);
		}

		// Filter by date range.
		if ( $start_date || $end_date ) {
			$all_events = array_filter(
				$all_events,
				function ( $event ) use ( $start_date, $end_date ) {
					$date = substr( $event['event_date'] ?? '', 0, 10 );
					if ( ! $date ) {
						return true;
					}
					if ( $start_date && $date < $start_date ) {
						return false;
					}
					if ( $end_date && $date > $end_date ) {
						return false;
					}
					return true;
				}
			);
		}

		// Sort by date descending.
		usort(
			$all_events,
			fn( $a, $b ) => strcmp( $b['event_date'] ?? '', $a['event_date'] ?? '' )
		);

		// Calculate summary.
		$summary = array(
			'total'          => count( $all_events ),
			'by_type'        => array(),
			'by_entity_type' => array(),
		);

		foreach ( $all_events as $event ) {
			$type = $event['event_type'] ?? 'unknown';
			if ( ! isset( $summary['by_type'][ $type ] ) ) {
				$summary['by_type'][ $type ] = 0;
			}
			++$summary['by_type'][ $type ];

			$entity_type_val = $event['entity_type'] ?? 'unknown';
			if ( ! isset( $summary['by_entity_type'][ $entity_type_val ] ) ) {
				$summary['by_entity_type'][ $entity_type_val ] = 0;
			}
			++$summary['by_entity_type'][ $entity_type_val ];
		}

		/**
		 * Fires after health report is generated.
		 *
		 * @param array $all_events Health events.
		 * @param array $summary    Report summary.
		 */
		do_action( 'canil_core_health_report_generated', $all_events, $summary );

		return array(
			'data'         => array_values( $all_events ),
			'summary'      => $summary,
			'generated_at' => DateHelper::now()->format( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Export data to CSV format.
	 *
	 * @param array<array<string, mixed>> $data       Data to export.
	 * @param array<string>               $columns    Column names.
	 * @param array<string>               $headers    Column headers.
	 * @return string CSV content.
	 */
	public function export_to_csv( array $data, array $columns, array $headers ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Using php://temp memory stream
		$output = fopen( 'php://temp', 'r+' );

		// Write headers.
		fputcsv( $output, $headers );

		// Write data rows.
		foreach ( $data as $row ) {
			$values = array();
			foreach ( $columns as $column ) {
				$value = $row[ $column ] ?? '';
				// Handle arrays/objects.
				if ( is_array( $value ) ) {
					$value = wp_json_encode( $value );
				}
				$values[] = $value;
			}
			fputcsv( $output, $values );
		}

		rewind( $output );
		$csv = stream_get_contents( $output );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Using php://temp memory stream
		fclose( $output );

		return $csv;
	}

	/**
	 * Get plantel report as CSV.
	 *
	 * @param array<string, mixed> $filters Filters.
	 * @return string CSV content.
	 */
	public function get_plantel_csv( array $filters = array() ): string {
		$report = $this->get_plantel_report( $filters );

		$columns = array( 'name', 'breed', 'sex', 'birth_date', 'status', 'registration_number', 'chip_number', 'color' );
		$headers = array(
			__( 'Nome', 'canil-core' ),
			__( 'Raça', 'canil-core' ),
			__( 'Sexo', 'canil-core' ),
			__( 'Nascimento', 'canil-core' ),
			__( 'Status', 'canil-core' ),
			__( 'Registro', 'canil-core' ),
			__( 'Chip', 'canil-core' ),
			__( 'Cor', 'canil-core' ),
		);

		return $this->export_to_csv( $report['data'], $columns, $headers );
	}

	/**
	 * Get litters report as CSV.
	 *
	 * @param string|null $start_date Start date.
	 * @param string|null $end_date   End date.
	 * @param string|null $status     Status filter.
	 * @return string CSV content.
	 */
	public function get_litters_csv(
		?string $start_date = null,
		?string $end_date = null,
		?string $status = null
	): string {
		$report = $this->get_litters_report( $start_date, $end_date, $status );

		$columns = array(
			'name',
			'dam_name',
			'sire_name',
			'mating_date',
			'expected_birth_date',
			'actual_birth_date',
			'status',
			'puppies_born_count',
			'puppies_alive_count',
		);
		$headers = array(
			__( 'Nome', 'canil-core' ),
			__( 'Matriz', 'canil-core' ),
			__( 'Reprodutor', 'canil-core' ),
			__( 'Data Cobertura', 'canil-core' ),
			__( 'Previsão Parto', 'canil-core' ),
			__( 'Data Parto', 'canil-core' ),
			__( 'Status', 'canil-core' ),
			__( 'Nascidos', 'canil-core' ),
			__( 'Vivos', 'canil-core' ),
		);

		return $this->export_to_csv( $report['data'], $columns, $headers );
	}

	/**
	 * Get puppies report as CSV.
	 *
	 * @param string|null $status Status filter.
	 * @return string CSV content.
	 */
	public function get_puppies_csv( ?string $status = null ): string {
		$report = $this->get_puppies_report( $status );

		$columns = array( 'identifier', 'name', 'sex', 'color', 'status', 'litter_name', 'birth_weight', 'chip_number' );
		$headers = array(
			__( 'Identificador', 'canil-core' ),
			__( 'Nome', 'canil-core' ),
			__( 'Sexo', 'canil-core' ),
			__( 'Cor', 'canil-core' ),
			__( 'Status', 'canil-core' ),
			__( 'Ninhada', 'canil-core' ),
			__( 'Peso Nascer', 'canil-core' ),
			__( 'Chip', 'canil-core' ),
		);

		return $this->export_to_csv( $report['data'], $columns, $headers );
	}

	/**
	 * Get health report as CSV.
	 *
	 * @param string|null $start_date  Start date.
	 * @param string|null $end_date    End date.
	 * @param string|null $event_type  Event type filter.
	 * @param string|null $entity_type Entity type filter.
	 * @return string CSV content.
	 */
	public function get_health_csv(
		?string $start_date = null,
		?string $end_date = null,
		?string $event_type = null,
		?string $entity_type = null
	): string {
		$report = $this->get_health_report( $start_date, $end_date, $event_type, $entity_type );

		$columns = array( 'event_date', 'event_type', 'entity_type', 'entity_id', 'notes' );
		$headers = array(
			__( 'Data', 'canil-core' ),
			__( 'Tipo', 'canil-core' ),
			__( 'Entidade', 'canil-core' ),
			__( 'ID Entidade', 'canil-core' ),
			__( 'Observações', 'canil-core' ),
		);

		return $this->export_to_csv( $report['data'], $columns, $headers );
	}
}
