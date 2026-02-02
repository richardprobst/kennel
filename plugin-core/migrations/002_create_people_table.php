<?php
/**
 * Migration: Create people table.
 *
 * @package CanilCore
 */

namespace CanilCore\Migrations;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CreatePeopleTable migration.
 */
class CreatePeopleTable {

	/**
	 * Run the migration.
	 */
	public function up(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'canil_people';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			tenant_id BIGINT UNSIGNED NOT NULL,
			name VARCHAR(255) NOT NULL,
			email VARCHAR(255) DEFAULT NULL,
			phone VARCHAR(50) DEFAULT NULL,
			phone_secondary VARCHAR(50) DEFAULT NULL,
			type ENUM('interested', 'buyer', 'veterinarian', 'handler', 'partner', 'other') NOT NULL DEFAULT 'interested',
			address_street VARCHAR(255) DEFAULT NULL,
			address_number VARCHAR(20) DEFAULT NULL,
			address_complement VARCHAR(100) DEFAULT NULL,
			address_neighborhood VARCHAR(100) DEFAULT NULL,
			address_city VARCHAR(100) DEFAULT NULL,
			address_state VARCHAR(50) DEFAULT NULL,
			address_zip VARCHAR(20) DEFAULT NULL,
			address_country VARCHAR(50) DEFAULT 'Brasil',
			document_cpf VARCHAR(20) DEFAULT NULL,
			document_rg VARCHAR(30) DEFAULT NULL,
			preferences JSON DEFAULT NULL,
			referred_by_id BIGINT UNSIGNED DEFAULT NULL,
			notes TEXT DEFAULT NULL,
			tags JSON DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			deleted_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			INDEX idx_tenant (tenant_id),
			INDEX idx_tenant_type (tenant_id, type),
			INDEX idx_email (email),
			INDEX idx_phone (phone)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Rollback the migration.
	 */
	public function down(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'canil_people';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}
