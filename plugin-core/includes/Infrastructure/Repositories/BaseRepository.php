<?php
/**
 * Base repository.
 *
 * Abstract base class for all repositories with tenant isolation.
 *
 * @package CanilCore
 */

namespace CanilCore\Infrastructure\Repositories;

use CanilCore\Domain\Exceptions\UnauthorizedException;
use CanilCore\Domain\Exceptions\NotFoundException;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BaseRepository class.
 */
abstract class BaseRepository {

	/**
	 * WordPress database instance.
	 *
	 * @var \wpdb
	 */
	protected \wpdb $wpdb;

	/**
	 * Table name (without prefix).
	 *
	 * @var string
	 */
	protected string $table_name;

	/**
	 * Full table name (with prefix).
	 *
	 * @var string
	 */
	protected string $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = $wpdb->prefix . 'canil_' . $this->table_name;
	}

	/**
	 * Get tenant ID from current user.
	 *
	 * CRITICAL: This NEVER accepts tenant_id from request.
	 * Always retrieves from authenticated user.
	 *
	 * @return int Tenant ID.
	 * @throws UnauthorizedException If user not authenticated.
	 */
	protected function get_tenant_id(): int {
		$tenant_id = get_current_user_id();

		if ( 0 === $tenant_id ) {
			throw new UnauthorizedException( __( 'User not authenticated', 'canil-core' ) );
		}

		return $tenant_id;
	}

	/**
	 * Find by ID.
	 *
	 * @param int $id Entity ID.
	 * @return array<string, mixed>|null Entity data or null if not found.
	 */
	public function find_by_id( int $id ): ?array {
		$tenant_id = $this->get_tenant_id();

		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->table} WHERE id = %d AND tenant_id = %d AND deleted_at IS NULL",
			$id,
			$tenant_id
		);

		$row = $this->wpdb->get_row( $query, ARRAY_A );

		return $row ?: null;
	}

	/**
	 * Find all with pagination.
	 *
	 * @param array<string, mixed> $filters  Filters to apply.
	 * @param int                  $page     Page number.
	 * @param int                  $per_page Items per page.
	 * @param string               $order_by Order by column.
	 * @param string               $order    Order direction (ASC/DESC).
	 * @return array{data: array<array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
	 */
	public function find_all(
		array $filters = array(),
		int $page = 1,
		int $per_page = 20,
		string $order_by = 'created_at',
		string $order = 'DESC'
	): array {
		$tenant_id = $this->get_tenant_id();
		$offset    = ( $page - 1 ) * $per_page;

		// Sanitize order direction.
		$order = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';

		// Build WHERE clause.
		$where = $this->build_where_clause( $filters, $tenant_id );

		// Get total count.
		$count_query = "SELECT COUNT(*) FROM {$this->table} {$where['clause']}";
		$total       = (int) $this->wpdb->get_var( $this->wpdb->prepare( $count_query, ...$where['values'] ) );

		// Get paginated results.
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->table} {$where['clause']} ORDER BY {$order_by} {$order} LIMIT %d OFFSET %d",
			...array_merge( $where['values'], array( $per_page, $offset ) )
		);

		$data = $this->wpdb->get_results( $query, ARRAY_A ) ?: array();

		return array(
			'data'        => $data,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => (int) ceil( $total / $per_page ),
		);
	}

	/**
	 * Build WHERE clause from filters.
	 *
	 * @param array<string, mixed> $filters   Filters to apply.
	 * @param int                  $tenant_id Tenant ID.
	 * @return array{clause: string, values: array<mixed>}
	 */
	protected function build_where_clause( array $filters, int $tenant_id ): array {
		$conditions = array( 'tenant_id = %d', 'deleted_at IS NULL' );
		$values     = array( $tenant_id );

		foreach ( $filters as $key => $value ) {
			if ( null === $value || '' === $value ) {
				continue;
			}

			// Handle special filters.
			switch ( $key ) {
				case 'search':
					$search_columns = $this->get_search_columns();
					if ( ! empty( $search_columns ) ) {
						$search_conditions = array();
						foreach ( $search_columns as $column ) {
							$search_conditions[] = "{$column} LIKE %s";
							$values[]            = '%' . $this->wpdb->esc_like( $value ) . '%';
						}
						$conditions[] = '(' . implode( ' OR ', $search_conditions ) . ')';
					}
					break;

				case 'status':
				case 'sex':
				case 'type':
				case 'entity_type':
				case 'event_type':
					$conditions[] = "{$key} = %s";
					$values[]     = $value;
					break;

				case 'entity_id':
				case 'litter_id':
				case 'sire_id':
				case 'dam_id':
				case 'buyer_id':
					$conditions[] = "{$key} = %d";
					$values[]     = (int) $value;
					break;

				case 'date_from':
					$conditions[] = 'created_at >= %s';
					$values[]     = $value;
					break;

				case 'date_to':
					$conditions[] = 'created_at <= %s';
					$values[]     = $value;
					break;
			}
		}

		return array(
			'clause' => 'WHERE ' . implode( ' AND ', $conditions ),
			'values' => $values,
		);
	}

	/**
	 * Get searchable columns for LIKE queries.
	 *
	 * Override in child classes to define searchable columns.
	 *
	 * @return array<string> Column names.
	 */
	protected function get_search_columns(): array {
		return array( 'name' );
	}

	/**
	 * Insert a new record.
	 *
	 * @param array<string, mixed> $data Entity data.
	 * @return int Inserted ID.
	 */
	public function insert( array $data ): int {
		$tenant_id = $this->get_tenant_id();

		// Force tenant_id from server.
		$data['tenant_id'] = $tenant_id;
		$data['created_at'] = current_time( 'mysql' );
		$data['updated_at'] = current_time( 'mysql' );

		// Handle JSON fields.
		$data = $this->encode_json_fields( $data );

		$this->wpdb->insert( $this->table, $data );

		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Update a record.
	 *
	 * @param int                  $id   Entity ID.
	 * @param array<string, mixed> $data Entity data.
	 * @return bool True on success.
	 * @throws NotFoundException If entity not found.
	 */
	public function update( int $id, array $data ): bool {
		$tenant_id = $this->get_tenant_id();

		// Check entity exists and belongs to tenant.
		$exists = $this->find_by_id( $id );
		if ( ! $exists ) {
			throw new NotFoundException( __( 'Record not found', 'canil-core' ) );
		}

		// Remove tenant_id from update data (never allow changing tenant).
		unset( $data['tenant_id'] );
		unset( $data['id'] );

		$data['updated_at'] = current_time( 'mysql' );

		// Handle JSON fields.
		$data = $this->encode_json_fields( $data );

		$result = $this->wpdb->update(
			$this->table,
			$data,
			array(
				'id'        => $id,
				'tenant_id' => $tenant_id,
			)
		);

		return false !== $result;
	}

	/**
	 * Soft delete a record.
	 *
	 * @param int $id Entity ID.
	 * @return bool True on success.
	 * @throws NotFoundException If entity not found.
	 */
	public function delete( int $id ): bool {
		$tenant_id = $this->get_tenant_id();

		// Check entity exists and belongs to tenant.
		$exists = $this->find_by_id( $id );
		if ( ! $exists ) {
			throw new NotFoundException( __( 'Record not found', 'canil-core' ) );
		}

		$result = $this->wpdb->update(
			$this->table,
			array(
				'deleted_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			),
			array(
				'id'        => $id,
				'tenant_id' => $tenant_id,
			)
		);

		return false !== $result;
	}

	/**
	 * Hard delete a record (permanent).
	 *
	 * @param int $id Entity ID.
	 * @return bool True on success.
	 * @throws NotFoundException If entity not found.
	 */
	public function hard_delete( int $id ): bool {
		$tenant_id = $this->get_tenant_id();

		$result = $this->wpdb->delete(
			$this->table,
			array(
				'id'        => $id,
				'tenant_id' => $tenant_id,
			)
		);

		return false !== $result;
	}

	/**
	 * Restore a soft-deleted record.
	 *
	 * @param int $id Entity ID.
	 * @return bool True on success.
	 */
	public function restore( int $id ): bool {
		$tenant_id = $this->get_tenant_id();

		$result = $this->wpdb->update(
			$this->table,
			array(
				'deleted_at' => null,
				'updated_at' => current_time( 'mysql' ),
			),
			array(
				'id'        => $id,
				'tenant_id' => $tenant_id,
			)
		);

		return false !== $result;
	}

	/**
	 * Count records.
	 *
	 * @param array<string, mixed> $filters Filters to apply.
	 * @return int Total count.
	 */
	public function count( array $filters = array() ): int {
		$tenant_id = $this->get_tenant_id();
		$where     = $this->build_where_clause( $filters, $tenant_id );

		$query = "SELECT COUNT(*) FROM {$this->table} {$where['clause']}";

		return (int) $this->wpdb->get_var( $this->wpdb->prepare( $query, ...$where['values'] ) );
	}

	/**
	 * Encode JSON fields.
	 *
	 * @param array<string, mixed> $data Entity data.
	 * @return array<string, mixed> Data with JSON fields encoded.
	 */
	protected function encode_json_fields( array $data ): array {
		$json_fields = $this->get_json_fields();

		foreach ( $json_fields as $field ) {
			if ( isset( $data[ $field ] ) && is_array( $data[ $field ] ) ) {
				$data[ $field ] = wp_json_encode( $data[ $field ] );
			}
		}

		return $data;
	}

	/**
	 * Decode JSON fields.
	 *
	 * @param array<string, mixed> $data Entity data.
	 * @return array<string, mixed> Data with JSON fields decoded.
	 */
	protected function decode_json_fields( array $data ): array {
		$json_fields = $this->get_json_fields();

		foreach ( $json_fields as $field ) {
			if ( isset( $data[ $field ] ) && is_string( $data[ $field ] ) ) {
				$decoded = json_decode( $data[ $field ], true );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					$data[ $field ] = $decoded;
				}
			}
		}

		return $data;
	}

	/**
	 * Get JSON fields.
	 *
	 * Override in child classes to define JSON fields.
	 *
	 * @return array<string> Field names.
	 */
	protected function get_json_fields(): array {
		return array();
	}
}
