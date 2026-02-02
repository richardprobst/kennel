<?php
/**
 * Plugin deactivator.
 *
 * Handles plugin deactivation tasks.
 *
 * @package CanilCore
 */

namespace CanilCore\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivator class.
 */
class Deactivator {

	/**
	 * Deactivate the plugin.
	 */
	public static function deactivate(): void {
		// Flush rewrite rules.
		flush_rewrite_rules();

		// Clear scheduled events.
		self::clear_scheduled_events();
	}

	/**
	 * Clear all scheduled cron events.
	 */
	private static function clear_scheduled_events(): void {
		// Clear any scheduled hooks here when implemented.
		// Example: wp_clear_scheduled_hook( 'canil_core_daily_cleanup' );
	}
}
