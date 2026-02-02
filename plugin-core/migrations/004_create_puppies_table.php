<?php
/**
 * Migration: Create puppies table.
 *
 * @package CanilCore
 */

namespace CanilCore\Migrations;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CreatePuppiesTable migration.
 */
class CreatePuppiesTable {

	/**
	 * Run the migration.
	 */
	public function up(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'canil_puppies';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			tenant_id BIGINT UNSIGNED NOT NULL,
			litter_id BIGINT UNSIGNED NOT NULL,
			identifier VARCHAR(50) NOT NULL,
			name VARCHAR(255) DEFAULT NULL,
			call_name VARCHAR(100) DEFAULT NULL,
			registration_number VARCHAR(100) DEFAULT NULL,
			chip_number VARCHAR(50) DEFAULT NULL,
			sex ENUM('male', 'female') NOT NULL,
			color VARCHAR(100) DEFAULT NULL,
			markings VARCHAR(255) DEFAULT NULL,
			birth_weight DECIMAL(6,2) DEFAULT NULL,
			birth_order TINYINT UNSIGNED DEFAULT NULL,
			birth_notes TEXT DEFAULT NULL,
			status ENUM('available', 'reserved', 'sold', 'retained', 'deceased', 'returned') NOT NULL DEFAULT 'available',
			buyer_id BIGINT UNSIGNED DEFAULT NULL,
			reservation_date DATE DEFAULT NULL,
			sale_date DATE DEFAULT NULL,
			delivery_date DATE DEFAULT NULL,
			price DECIMAL(10,2) DEFAULT NULL,
			photo_main_url VARCHAR(500) DEFAULT NULL,
			photos JSON DEFAULT NULL,
			notes TEXT DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			deleted_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			INDEX idx_tenant (tenant_id),
			INDEX idx_tenant_status (tenant_id, status),
			INDEX idx_litter (litter_id),
			INDEX idx_buyer (buyer_id),
			INDEX idx_chip (chip_number)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Rollback the migration.
	 */
	public function down(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'canil_puppies';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}
