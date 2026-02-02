<?php
/**
 * Migration: Create dogs table.
 *
 * @package CanilCore
 */

namespace CanilCore\Migrations;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CreateDogsTable migration.
 */
class CreateDogsTable {

	/**
	 * Run the migration.
	 */
	public function up(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'canil_dogs';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			tenant_id BIGINT UNSIGNED NOT NULL,
			name VARCHAR(255) NOT NULL,
			call_name VARCHAR(100) DEFAULT NULL,
			registration_number VARCHAR(100) DEFAULT NULL,
			chip_number VARCHAR(50) DEFAULT NULL,
			tattoo VARCHAR(50) DEFAULT NULL,
			breed VARCHAR(100) NOT NULL,
			variety VARCHAR(100) DEFAULT NULL,
			color VARCHAR(100) DEFAULT NULL,
			markings VARCHAR(255) DEFAULT NULL,
			birth_date DATE NOT NULL,
			death_date DATE DEFAULT NULL,
			sex ENUM('male', 'female') NOT NULL,
			status ENUM('active', 'breeding', 'retired', 'sold', 'deceased', 'coowned') NOT NULL DEFAULT 'active',
			sire_id BIGINT UNSIGNED DEFAULT NULL,
			dam_id BIGINT UNSIGNED DEFAULT NULL,
			photo_main_url VARCHAR(500) DEFAULT NULL,
			photos JSON DEFAULT NULL,
			titles JSON DEFAULT NULL,
			health_tests JSON DEFAULT NULL,
			notes TEXT DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			deleted_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			INDEX idx_tenant (tenant_id),
			INDEX idx_tenant_status (tenant_id, status),
			INDEX idx_tenant_sex (tenant_id, sex),
			INDEX idx_tenant_breed (tenant_id, breed(50)),
			INDEX idx_sire (sire_id),
			INDEX idx_dam (dam_id),
			INDEX idx_chip (chip_number),
			INDEX idx_registration (registration_number)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Rollback the migration.
	 */
	public function down(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'canil_dogs';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}
