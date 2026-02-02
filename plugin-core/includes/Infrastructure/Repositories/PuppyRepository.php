<?php
/**
 * Puppy Repository.
 *
 * Repository for puppy entities.
 *
 * @package CanilCore
 */

namespace CanilCore\Infrastructure\Repositories;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PuppyRepository class.
 */
class PuppyRepository extends BaseRepository {

	/**
	 * Table name (without prefix).
	 *
	 * @var string
	 */
	protected string $table_name = 'puppies';

	/**
	 * Get searchable columns.
	 *
	 * @return array<string> Column names.
	 */
	protected function get_search_columns(): array {
		return array( 'identifier', 'name', 'call_name', 'registration_number', 'chip_number' );
	}

	/**
	 * Get JSON fields.
	 *
	 * @return array<string> Field names.
	 */
	protected function get_json_fields(): array {
		return array( 'photos' );
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
	 * Find puppies by litter.
	 *
	 * @param int $litter_id Litter ID.
	 * @return array<array<string, mixed>> Puppies.
	 */
	public function find_by_litter( int $litter_id ): array {
		$result = $this->find_all( array( 'litter_id' => $litter_id ), 1, 100 );
		return $result['data'];
	}

	/**
	 * Find puppies by status.
	 *
	 * @param string $status Puppy status.
	 * @return array<array<string, mixed>> Puppies.
	 */
	public function find_by_status( string $status ): array {
		$result = $this->find_all( array( 'status' => $status ), 1, 1000 );
		return $result['data'];
	}

	/**
	 * Find puppies by buyer.
	 *
	 * @param int $buyer_id Buyer ID.
	 * @return array<array<string, mixed>> Puppies.
	 */
	public function find_by_buyer( int $buyer_id ): array {
		$result = $this->find_all( array( 'buyer_id' => $buyer_id ), 1, 1000 );
		return $result['data'];
	}

	/**
	 * Count puppies by status in a litter.
	 *
	 * @param int $litter_id Litter ID.
	 * @return array<string, int> Status counts.
	 */
	public function count_by_status_in_litter( int $litter_id ): array {
		$tenant_id = $this->get_tenant_id();

		$query = "SELECT status, COUNT(*) as count FROM {$this->table} 
				  WHERE tenant_id = %d AND litter_id = %d AND deleted_at IS NULL 
				  GROUP BY status";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $tenant_id, $litter_id ), ARRAY_A );

		$counts = array();
		foreach ( $results as $row ) {
			$counts[ $row['status'] ] = (int) $row['count'];
		}

		return $counts;
	}

	/**
	 * Find with litter data.
	 *
	 * @param int $id Puppy ID.
	 * @return array<string, mixed>|null Puppy with litter data.
	 */
	public function find_with_litter( int $id ): ?array {
		$tenant_id = $this->get_tenant_id();

		$query = "SELECT p.*, 
				  l.name as litter_name, l.litter_letter,
				  d.name as dam_name, s.name as sire_name
				  FROM {$this->table} p
				  LEFT JOIN {$this->wpdb->prefix}canil_litters l ON p.litter_id = l.id
				  LEFT JOIN {$this->wpdb->prefix}canil_dogs d ON l.dam_id = d.id
				  LEFT JOIN {$this->wpdb->prefix}canil_dogs s ON l.sire_id = s.id
				  WHERE p.id = %d AND p.tenant_id = %d AND p.deleted_at IS NULL";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$row = $this->wpdb->get_row( $this->wpdb->prepare( $query, $id, $tenant_id ), ARRAY_A );

		if ( $row ) {
			$row = $this->decode_json_fields( $row );
		}

		return $row ?: null;
	}
}
