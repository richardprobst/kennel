<?php
/**
 * Litter Repository.
 *
 * Repository for litter entities.
 *
 * @package CanilCore
 */

namespace CanilCore\Infrastructure\Repositories;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LitterRepository class.
 */
class LitterRepository extends BaseRepository {

	/**
	 * Table name (without prefix).
	 *
	 * @var string
	 */
	protected string $table_name = 'litters';

	/**
	 * Get searchable columns.
	 *
	 * @return array<string> Column names.
	 */
	protected function get_search_columns(): array {
		return array( 'name', 'litter_letter' );
	}

	/**
	 * Get JSON fields.
	 *
	 * @return array<string> Field names.
	 */
	protected function get_json_fields(): array {
		return array();
	}

	/**
	 * Find by ID with additional data.
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
	 * Find litters by status.
	 *
	 * @param string $status Litter status.
	 * @return array<array<string, mixed>> Litters.
	 */
	public function find_by_status( string $status ): array {
		$result = $this->find_all( array( 'status' => $status ), 1, 1000 );
		return $result['data'];
	}

	/**
	 * Find litters by dam.
	 *
	 * @param int $dam_id Dam ID.
	 * @return array<array<string, mixed>> Litters.
	 */
	public function find_by_dam( int $dam_id ): array {
		$result = $this->find_all( array( 'dam_id' => $dam_id ), 1, 1000 );
		return $result['data'];
	}

	/**
	 * Find litters by sire.
	 *
	 * @param int $sire_id Sire ID.
	 * @return array<array<string, mixed>> Litters.
	 */
	public function find_by_sire( int $sire_id ): array {
		$result = $this->find_all( array( 'sire_id' => $sire_id ), 1, 1000 );
		return $result['data'];
	}

	/**
	 * Find litters for dropdown.
	 *
	 * @return array<array{id: int, name: string}> Litters for dropdown.
	 */
	public function find_for_dropdown(): array {
		$tenant_id = $this->get_tenant_id();

		$query = "SELECT id, name, litter_letter, status FROM {$this->table} 
				  WHERE tenant_id = %d 
				  AND deleted_at IS NULL 
				  ORDER BY created_at DESC";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $tenant_id ), ARRAY_A );

		return $results ?: array();
	}

	/**
	 * Find with parents data.
	 *
	 * @param int $id Litter ID.
	 * @return array<string, mixed>|null Litter with parents data.
	 */
	public function find_with_parents( int $id ): ?array {
		$tenant_id = $this->get_tenant_id();

		$query = "SELECT l.*, 
				  d.name as dam_name, d.breed as dam_breed,
				  s.name as sire_name, s.breed as sire_breed
				  FROM {$this->table} l
				  LEFT JOIN {$this->wpdb->prefix}canil_dogs d ON l.dam_id = d.id
				  LEFT JOIN {$this->wpdb->prefix}canil_dogs s ON l.sire_id = s.id
				  WHERE l.id = %d AND l.tenant_id = %d AND l.deleted_at IS NULL";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$row = $this->wpdb->get_row( $this->wpdb->prepare( $query, $id, $tenant_id ), ARRAY_A );

		return $row ?: null;
	}
}
