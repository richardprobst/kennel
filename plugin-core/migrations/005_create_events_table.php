<?php
/**
 * Migration: Create events table.
 *
 * @package CanilCore
 */

namespace CanilCore\Migrations;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CreateEventsTable migration.
 */
class CreateEventsTable {

	/**
	 * Run the migration.
	 */
	public function up(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'canil_events';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			tenant_id BIGINT UNSIGNED NOT NULL,
			entity_type ENUM('dog', 'litter', 'puppy') NOT NULL,
			entity_id BIGINT UNSIGNED NOT NULL,
			event_type VARCHAR(50) NOT NULL,
			event_date DATETIME NOT NULL,
			event_end_date DATETIME DEFAULT NULL,
			payload JSON NOT NULL,
			reminder_date DATETIME DEFAULT NULL,
			reminder_completed TINYINT(1) DEFAULT 0,
			notes TEXT DEFAULT NULL,
			attachments JSON DEFAULT NULL,
			created_by BIGINT UNSIGNED DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			deleted_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			INDEX idx_tenant (tenant_id),
			INDEX idx_entity (entity_type, entity_id),
			INDEX idx_tenant_entity (tenant_id, entity_type, entity_id),
			INDEX idx_event_type (event_type),
			INDEX idx_event_date (event_date),
			INDEX idx_reminder (reminder_date, reminder_completed)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Rollback the migration.
	 */
	public function down(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'canil_events';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}
