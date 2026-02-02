<?php
/**
 * Dog Repository.
 *
 * Repository for dog entities.
 *
 * @package CanilCore
 */

namespace CanilCore\Infrastructure\Repositories;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DogRepository class.
 */
class DogRepository extends BaseRepository {

	/**
	 * Table name (without prefix).
	 *
	 * @var string
	 */
	protected string $table_name = 'dogs';

	/**
	 * Get searchable columns.
	 *
	 * @return array<string> Column names.
	 */
	protected function get_search_columns(): array {
		return array( 'name', 'call_name', 'registration_number', 'chip_number', 'breed' );
	}

	/**
	 * Get JSON fields.
	 *
	 * @return array<string> Field names.
	 */
	protected function get_json_fields(): array {
		return array( 'photos', 'titles', 'health_tests' );
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
		string $order_by = 'created_at',
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
	 * Find dogs by status.
	 *
	 * @param string $status Dog status.
	 * @return array<array<string, mixed>> Dogs.
	 */
	public function find_by_status( string $status ): array {
		$result = $this->find_all( array( 'status' => $status ), 1, 1000 );
		return $result['data'];
	}

	/**
	 * Find dogs by sex.
	 *
	 * @param string $sex Dog sex (male/female).
	 * @return array<array<string, mixed>> Dogs.
	 */
	public function find_by_sex( string $sex ): array {
		$result = $this->find_all( array( 'sex' => $sex ), 1, 1000 );
		return $result['data'];
	}

	/**
	 * Find breeding dogs (for dropdown).
	 *
	 * @param string $sex Filter by sex (optional).
	 * @return array<array{id: int, name: string}> Dogs for dropdown.
	 */
	public function find_for_breeding( string $sex = '' ): array {
		$tenant_id = $this->get_tenant_id();

		$query = "SELECT id, name, registration_number FROM {$this->table} 
				  WHERE tenant_id = %d 
				  AND deleted_at IS NULL 
				  AND status IN ('active', 'breeding')";
		$params = array( $tenant_id );

		if ( ! empty( $sex ) ) {
			$query .= ' AND sex = %s';
			$params[] = $sex;
		}

		$query .= ' ORDER BY name ASC';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $this->wpdb->get_results( $this->wpdb->prepare( $query, ...$params ), ARRAY_A );

		return $results ?: array();
	}
}
