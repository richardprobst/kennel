<?php
/**
 * Migration: Create litters table.
 *
 * @package CanilCore
 */

namespace CanilCore\Migrations;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CreateLittersTable migration.
 */
class CreateLittersTable {

	/**
	 * Run the migration.
	 */
	public function up(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'canil_litters';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			tenant_id BIGINT UNSIGNED NOT NULL,
			name VARCHAR(255) DEFAULT NULL,
			litter_letter CHAR(1) DEFAULT NULL,
			dam_id BIGINT UNSIGNED NOT NULL,
			sire_id BIGINT UNSIGNED NOT NULL,
			status ENUM('planned', 'confirmed', 'pregnant', 'born', 'weaned', 'closed', 'cancelled') NOT NULL DEFAULT 'planned',
			heat_start_date DATE DEFAULT NULL,
			mating_date DATE DEFAULT NULL,
			mating_type ENUM('natural', 'artificial_fresh', 'artificial_frozen') DEFAULT NULL,
			pregnancy_confirmed_date DATE DEFAULT NULL,
			expected_birth_date DATE DEFAULT NULL,
			actual_birth_date DATE DEFAULT NULL,
			birth_type ENUM('natural', 'cesarean', 'assisted') DEFAULT NULL,
			puppies_born_count TINYINT UNSIGNED DEFAULT 0,
			puppies_alive_count TINYINT UNSIGNED DEFAULT 0,
			males_count TINYINT UNSIGNED DEFAULT 0,
			females_count TINYINT UNSIGNED DEFAULT 0,
			veterinarian_id BIGINT UNSIGNED DEFAULT NULL,
			notes TEXT DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			deleted_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			INDEX idx_tenant (tenant_id),
			INDEX idx_tenant_status (tenant_id, status),
			INDEX idx_dam (dam_id),
			INDEX idx_sire (sire_id),
			INDEX idx_birth_date (actual_birth_date)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Rollback the migration.
	 */
	public function down(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'canil_litters';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}
