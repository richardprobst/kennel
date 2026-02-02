<?php
/**
 * Migration: Create settings table.
 *
 * @package CanilCore
 */

namespace CanilCore\Migrations;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CreateSettingsTable migration.
 */
class CreateSettingsTable {

	/**
	 * Run the migration.
	 */
	public function up(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'canil_settings';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			tenant_id BIGINT UNSIGNED NOT NULL,
			setting_key VARCHAR(100) NOT NULL,
			setting_value TEXT DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_tenant_key (tenant_id, setting_key),
			INDEX idx_tenant (tenant_id)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Rollback the migration.
	 */
	public function down(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'canil_settings';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}
