<?php
/**
 * Person Repository.
 *
 * Repository for person entities.
 *
 * @package CanilCore
 */

namespace CanilCore\Infrastructure\Repositories;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PersonRepository class.
 */
class PersonRepository extends BaseRepository {

	/**
	 * Table name (without prefix).
	 *
	 * @var string
	 */
	protected string $table_name = 'people';

	/**
	 * Get searchable columns.
	 *
	 * @return array<string> Column names.
	 */
	protected function get_search_columns(): array {
		return array( 'name', 'email', 'phone', 'address_city' );
	}

	/**
	 * Get JSON fields.
	 *
	 * @return array<string> Field names.
	 */
	protected function get_json_fields(): array {
		return array( 'preferences', 'tags' );
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
	 * Find people by type.
	 *
	 * @param string $type Person type.
	 * @return array<array<string, mixed>> People.
	 */
	public function find_by_type( string $type ): array {
		$result = $this->find_all( array( 'type' => $type ), 1, 1000 );
		return $result['data'];
	}

	/**
	 * Find veterinarians for dropdown.
	 *
	 * @return array<array{id: int, name: string}> Veterinarians for dropdown.
	 */
	public function find_veterinarians(): array {
		$tenant_id = $this->get_tenant_id();

		$query = "SELECT id, name, phone FROM {$this->table} 
				  WHERE tenant_id = %d 
				  AND type = 'veterinarian'
				  AND deleted_at IS NULL 
				  ORDER BY name ASC";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $tenant_id ), ARRAY_A );

		return $results ?: array();
	}

	/**
	 * Find buyers for dropdown.
	 *
	 * @return array<array{id: int, name: string}> Buyers for dropdown.
	 */
	public function find_buyers(): array {
		$tenant_id = $this->get_tenant_id();

		$query = "SELECT id, name, phone FROM {$this->table} 
				  WHERE tenant_id = %d 
				  AND type IN ('buyer', 'interested')
				  AND deleted_at IS NULL 
				  ORDER BY name ASC";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $tenant_id ), ARRAY_A );

		return $results ?: array();
	}

	/**
	 * Find for dropdown.
	 *
	 * @param string $type Filter by type (optional).
	 * @return array<array{id: int, name: string}> People for dropdown.
	 */
	public function find_for_dropdown( string $type = '' ): array {
		$tenant_id = $this->get_tenant_id();

		$query  = "SELECT id, name, phone, type FROM {$this->table} 
				  WHERE tenant_id = %d 
				  AND deleted_at IS NULL";
		$params = array( $tenant_id );

		if ( ! empty( $type ) ) {
			$query   .= ' AND type = %s';
			$params[] = $type;
		}

		$query .= ' ORDER BY name ASC';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $this->wpdb->get_results( $this->wpdb->prepare( $query, ...$params ), ARRAY_A );

		return $results ?: array();
	}

	/**
	 * Find by email.
	 *
	 * @param string $email Email address.
	 * @return array<string, mixed>|null Person data or null if not found.
	 */
	public function find_by_email( string $email ): ?array {
		$tenant_id = $this->get_tenant_id();

		$query = "SELECT * FROM {$this->table} 
				  WHERE tenant_id = %d AND email = %s AND deleted_at IS NULL";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$row = $this->wpdb->get_row( $this->wpdb->prepare( $query, $tenant_id, $email ), ARRAY_A );

		if ( $row ) {
			$row = $this->decode_json_fields( $row );
		}

		return $row ?: null;
	}
}
