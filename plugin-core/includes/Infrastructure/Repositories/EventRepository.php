<?php
/**
 * Event Repository.
 *
 * Repository for event entities.
 *
 * @package CanilCore
 */

namespace CanilCore\Infrastructure\Repositories;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EventRepository class.
 */
class EventRepository extends BaseRepository {

	/**
	 * Table name (without prefix).
	 *
	 * @var string
	 */
	protected string $table_name = 'events';

	/**
	 * Get searchable columns.
	 *
	 * @return array<string> Column names.
	 */
	protected function get_search_columns(): array {
		return array( 'notes' );
	}

	/**
	 * Get JSON fields.
	 *
	 * @return array<string> Field names.
	 */
	protected function get_json_fields(): array {
		return array( 'payload', 'attachments' );
	}

	/**
	 * Find by ID with JSON decoding.
	 *
	 * @param int $id Entity ID.
	 * @return array<string, mixed>|null Entity data or null if not found.
	 */
	public function find_by_id( int $id ): ?array {
		$data = parent::find_by_id( $id );

		if ( $data ) {
			$data = $this->decode_json_fields( $data );
		}

		return $data;
	}

	/**
	 * Find all with JSON decoding.
	 *
	 * @param array<string, mixed> $filters  Filters to apply.
	 * @param int                  $page     Page number.
	 * @param int                  $per_page Items per page.
	 * @param string               $order_by Order by column.
	 * @param string               $order    Order direction.
	 * @return array{data: array<array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
	 */
	public function find_all(
		array $filters = array(),
		int $page = 1,
		int $per_page = 20,
		string $order_by = 'event_date',
		string $order = 'DESC'
	): array {
		$result = parent::find_all( $filters, $page, $per_page, $order_by, $order );

		// Decode JSON fields for each item.
		$result['data'] = array_map(
			function ( $item ) {
				return $this->decode_json_fields( $item );
			},
			$result['data']
		);

		return $result;
	}

	/**
	 * Find events by entity.
	 *
	 * @param string $entity_type Entity type (dog, litter, puppy).
	 * @param int    $entity_id   Entity ID.
	 * @return array<array<string, mixed>> Events.
	 */
	public function find_by_entity( string $entity_type, int $entity_id ): array {
		$result = $this->find_all(
			array(
				'entity_type' => $entity_type,
				'entity_id'   => $entity_id,
			),
			1,
			1000,
			'event_date',
			'DESC'
		);
		return $result['data'];
	}

	/**
	 * Find events by type.
	 *
	 * @param string $event_type Event type.
	 * @return array<array<string, mixed>> Events.
	 */
	public function find_by_type( string $event_type ): array {
		$result = $this->find_all( array( 'event_type' => $event_type ), 1, 1000 );
		return $result['data'];
	}

	/**
	 * Find upcoming events.
	 *
	 * @param int $days Number of days ahead.
	 * @return array<array<string, mixed>> Events.
	 */
	public function find_upcoming( int $days = 30 ): array {
		$tenant_id = $this->get_tenant_id();
		$today     = current_time( 'Y-m-d' );
		$end_date  = gmdate( 'Y-m-d', strtotime( "+{$days} days" ) );

		$query = "SELECT * FROM {$this->table} 
				  WHERE tenant_id = %d 
				  AND deleted_at IS NULL 
				  AND event_date >= %s 
				  AND event_date <= %s 
				  ORDER BY event_date ASC";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $this->wpdb->get_results(
			$this->wpdb->prepare( $query, $tenant_id, $today, $end_date ),
			ARRAY_A
		);

		return array_map(
			function ( $item ) {
				return $this->decode_json_fields( $item );
			},
			$results ?: array()
		);
	}

	/**
	 * Find pending reminders.
	 *
	 * @return array<array<string, mixed>> Events with pending reminders.
	 */
	public function find_pending_reminders(): array {
		$tenant_id = $this->get_tenant_id();
		$today     = current_time( 'Y-m-d' );

		$query = "SELECT * FROM {$this->table} 
				  WHERE tenant_id = %d 
				  AND deleted_at IS NULL 
				  AND reminder_date IS NOT NULL 
				  AND reminder_date <= %s 
				  AND reminder_completed = 0 
				  ORDER BY reminder_date ASC";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $this->wpdb->get_results(
			$this->wpdb->prepare( $query, $tenant_id, $today ),
			ARRAY_A
		);

		return array_map(
			function ( $item ) {
				return $this->decode_json_fields( $item );
			},
			$results ?: array()
		);
	}

	/**
	 * Find events in date range.
	 *
	 * @param string $start_date Start date (Y-m-d).
	 * @param string $end_date   End date (Y-m-d).
	 * @return array<array<string, mixed>> Events.
	 */
	public function find_in_date_range( string $start_date, string $end_date ): array {
		$tenant_id = $this->get_tenant_id();

		$query = "SELECT * FROM {$this->table} 
				  WHERE tenant_id = %d 
				  AND deleted_at IS NULL 
				  AND event_date >= %s 
				  AND event_date <= %s 
				  ORDER BY event_date ASC";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $this->wpdb->get_results(
			$this->wpdb->prepare( $query, $tenant_id, $start_date, $end_date ),
			ARRAY_A
		);

		return array_map(
			function ( $item ) {
				return $this->decode_json_fields( $item );
			},
			$results ?: array()
		);
	}

	/**
	 * Mark reminder as completed.
	 *
	 * @param int $id Event ID.
	 * @return bool True on success.
	 */
	public function mark_reminder_completed( int $id ): bool {
		return $this->update(
			$id,
			array( 'reminder_completed' => 1 )
		);
	}
}
