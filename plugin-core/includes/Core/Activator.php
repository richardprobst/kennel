<?php
/**
 * Plugin activator.
 *
 * Handles plugin activation tasks.
 *
 * @package CanilCore
 */

namespace CanilCore\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activator class.
 */
class Activator {

	/**
	 * Activate the plugin.
	 */
	public static function activate(): void {
		self::create_capabilities();
		self::run_migrations();
		self::set_default_options();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Create plugin capabilities and roles.
	 */
	private static function create_capabilities(): void {
		$capabilities = Capabilities::get_capabilities();

		// Add capabilities to administrator role.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( $capabilities as $cap => $description ) {
				$admin->add_cap( $cap );
			}
		}

		// Create kennel_owner role with all capabilities.
		$role_exists = get_role( 'kennel_owner' );
		if ( ! $role_exists ) {
			add_role(
				'kennel_owner',
				__( 'Proprietário de Canil', 'canil-core' ),
				array_fill_keys( array_keys( $capabilities ), true )
			);
		}
	}

	/**
	 * Run database migrations.
	 */
	private static function run_migrations(): void {
		$migration_runner = new MigrationRunner();
		$migration_runner->run();
	}

	/**
	 * Set default plugin options.
	 */
	private static function set_default_options(): void {
		// Set plugin version.
		update_option( 'canil_core_version', CANIL_CORE_VERSION );

		// Set activation timestamp.
		if ( ! get_option( 'canil_core_activated_at' ) ) {
			update_option( 'canil_core_activated_at', current_time( 'mysql' ) );
		}
	}
}
