<?php
/**
 * Migration runner.
 *
 * Handles database migrations.
 *
 * @package CanilCore
 */

namespace CanilCore\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MigrationRunner class.
 */
class MigrationRunner {

	/**
	 * Option name for storing migration version.
	 */
	private const VERSION_OPTION = 'canil_core_db_version';

	/**
	 * Migrations directory.
	 *
	 * @var string
	 */
	private string $migrations_path;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->migrations_path = CANIL_CORE_PATH . 'migrations/';
	}

	/**
	 * Run pending migrations.
	 */
	public function run(): void {
		$current_version = $this->get_current_version();
		$migrations      = $this->get_pending_migrations( $current_version );

		foreach ( $migrations as $version => $migration_file ) {
			$this->run_migration( $version, $migration_file );
		}
	}

	/**
	 * Get current database version.
	 *
	 * @return string Current version (e.g., "001").
	 */
	public function get_current_version(): string {
		return get_option( self::VERSION_OPTION, '000' );
	}

	/**
	 * Get pending migrations.
	 *
	 * @param string $current_version Current database version.
	 * @return array<string, string> Array of version => file path.
	 */
	private function get_pending_migrations( string $current_version ): array {
		$migrations = array();
		$files      = glob( $this->migrations_path . '*.php' );

		if ( ! $files ) {
			return $migrations;
		}

		foreach ( $files as $file ) {
			$filename = basename( $file, '.php' );

			// Extract version from filename (e.g., "001_create_dogs_table" -> "001").
			$version = substr( $filename, 0, 3 );

			if ( $version > $current_version ) {
				$migrations[ $version ] = $file;
			}
		}

		// Sort by version.
		ksort( $migrations );

		return $migrations;
	}

	/**
	 * Run a single migration.
	 *
	 * @param string $version        Migration version.
	 * @param string $migration_file Path to migration file.
	 */
	private function run_migration( string $version, string $migration_file ): void {
		require_once $migration_file;

		// Get class name from filename.
		$filename   = basename( $migration_file, '.php' );
		$parts      = explode( '_', $filename, 2 );
		$class_part = isset( $parts[1] ) ? $parts[1] : $filename;

		// Convert snake_case to PascalCase.
		$class_name = str_replace( '_', '', ucwords( $class_part, '_' ) );
		$full_class = 'CanilCore\\Migrations\\' . $class_name;

		if ( class_exists( $full_class ) ) {
			$migration = new $full_class();

			if ( method_exists( $migration, 'up' ) ) {
				$migration->up();
			}
		}

		// Update version.
		update_option( self::VERSION_OPTION, $version );
	}

	/**
	 * Rollback to a specific version.
	 *
	 * @param string $target_version Target version to rollback to.
	 */
	public function rollback( string $target_version ): void {
		$current_version = $this->get_current_version();

		if ( $target_version >= $current_version ) {
			return;
		}

		$files = glob( $this->migrations_path . '*.php' );

		if ( ! $files ) {
			return;
		}

		// Sort descending for rollback.
		rsort( $files );

		foreach ( $files as $file ) {
			$filename = basename( $file, '.php' );
			$version  = substr( $filename, 0, 3 );

			if ( $version <= $target_version ) {
				break;
			}

			if ( $version <= $current_version ) {
				$this->run_rollback( $version, $file );
			}
		}

		update_option( self::VERSION_OPTION, $target_version );
	}

	/**
	 * Run rollback for a single migration.
	 *
	 * @param string $version        Migration version.
	 * @param string $migration_file Path to migration file.
	 */
	private function run_rollback( string $version, string $migration_file ): void {
		require_once $migration_file;

		$filename   = basename( $migration_file, '.php' );
		$parts      = explode( '_', $filename, 2 );
		$class_part = isset( $parts[1] ) ? $parts[1] : $filename;
		$class_name = str_replace( '_', '', ucwords( $class_part, '_' ) );
		$full_class = 'CanilCore\\Migrations\\' . $class_name;

		if ( class_exists( $full_class ) ) {
			$migration = new $full_class();

			if ( method_exists( $migration, 'down' ) ) {
				$migration->down();
			}
		}
	}
}
