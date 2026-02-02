<?php
/**
 * Plugin Name:       Canil Core
 * Plugin URI:        https://github.com/richardprobst/kennel
 * Description:       Sistema de gestão de canil de criação - Core Plugin (SaaS Multi-tenant)
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Richard Probst
 * Author URI:        https://github.com/richardprobst
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       canil-core
 * Domain Path:       /languages
 *
 * @package CanilCore
 */

namespace CanilCore;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 */
define( 'CANIL_CORE_VERSION', '1.0.0' );

/**
 * Plugin directory path.
 */
define( 'CANIL_CORE_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'CANIL_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'CANIL_CORE_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Minimum PHP version required.
 */
define( 'CANIL_CORE_MIN_PHP', '8.1' );

/**
 * Minimum WordPress version required.
 */
define( 'CANIL_CORE_MIN_WP', '6.0' );

/**
 * Autoloader.
 */
require_once CANIL_CORE_PATH . 'includes/autoload.php';

/**
 * Check system requirements.
 *
 * @return bool True if requirements are met.
 */
function canil_core_check_requirements(): bool {
	$errors = array();

	// Check PHP version.
	if ( version_compare( PHP_VERSION, CANIL_CORE_MIN_PHP, '<' ) ) {
		$errors[] = sprintf(
			/* translators: 1: Required PHP version, 2: Current PHP version */
			__( 'Canil Core requires PHP %1$s or higher. You are running PHP %2$s.', 'canil-core' ),
			CANIL_CORE_MIN_PHP,
			PHP_VERSION
		);
	}

	// Check WordPress version.
	global $wp_version;
	if ( version_compare( $wp_version, CANIL_CORE_MIN_WP, '<' ) ) {
		$errors[] = sprintf(
			/* translators: 1: Required WordPress version, 2: Current WordPress version */
			__( 'Canil Core requires WordPress %1$s or higher. You are running WordPress %2$s.', 'canil-core' ),
			CANIL_CORE_MIN_WP,
			$wp_version
		);
	}

	if ( ! empty( $errors ) ) {
		add_action(
			'admin_notices',
			function () use ( $errors ) {
				foreach ( $errors as $error ) {
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html( $error )
					);
				}
			}
		);
		return false;
	}

	return true;
}

/**
 * Plugin activation hook.
 */
function canil_core_activate(): void {
	if ( ! canil_core_check_requirements() ) {
		deactivate_plugins( CANIL_CORE_BASENAME );
		wp_die(
			esc_html__( 'Canil Core cannot be activated. Please check the requirements.', 'canil-core' ),
			'Plugin Activation Error',
			array( 'back_link' => true )
		);
	}

	Core\Activator::activate();
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\canil_core_activate' );

/**
 * Plugin deactivation hook.
 */
function canil_core_deactivate(): void {
	Core\Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\canil_core_deactivate' );

/**
 * Initialize the plugin.
 */
function canil_core_init(): void {
	if ( ! canil_core_check_requirements() ) {
		return;
	}

	// Initialize the plugin.
	$plugin = new Core\Plugin();
	$plugin->run();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\canil_core_init' );
