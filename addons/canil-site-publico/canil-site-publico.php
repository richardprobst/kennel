<?php
/**
 * Plugin Name:       Canil Site Público
 * Plugin URI:        https://github.com/richardprobst/kennel
 * Description:       Add-on para Canil Core - Páginas públicas do canil e vitrine de filhotes
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Richard Probst
 * Author URI:        https://github.com/richardprobst
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       canil-site-publico
 * Domain Path:       /languages
 *
 * @package CanilSitePublico
 */

namespace CanilSitePublico;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 */
define( 'CANIL_SITE_PUBLICO_VERSION', '1.0.0' );

/**
 * Plugin directory path.
 */
define( 'CANIL_SITE_PUBLICO_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'CANIL_SITE_PUBLICO_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'CANIL_SITE_PUBLICO_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Required Canil Core version.
 */
define( 'CANIL_SITE_PUBLICO_REQUIRED_CORE', '1.0.0' );

/**
 * Autoloader for the add-on.
 */
spl_autoload_register(
	function ( string $class_name ): void {
		$namespace = 'CanilSitePublico\\';

		if ( 0 !== strpos( $class_name, $namespace ) ) {
			return;
		}

		$relative_class = substr( $class_name, strlen( $namespace ) );
		$file           = CANIL_SITE_PUBLICO_PATH . 'includes/' . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

/**
 * Check if Canil Core is active and compatible.
 *
 * @return bool|string True if compatible, error message if not.
 */
function canil_site_publico_check_core() {
	// Check if Canil Core is active.
	if ( ! defined( 'CANIL_CORE_VERSION' ) ) {
		return __( 'Canil Site Público requer o plugin Canil Core ativo.', 'canil-site-publico' );
	}

	// Check version compatibility.
	if ( version_compare( CANIL_CORE_VERSION, CANIL_SITE_PUBLICO_REQUIRED_CORE, '<' ) ) {
		return sprintf(
			/* translators: 1: Required version, 2: Current version */
			__( 'Canil Site Público requer Canil Core versão %1$s ou superior. Versão atual: %2$s', 'canil-site-publico' ),
			CANIL_SITE_PUBLICO_REQUIRED_CORE,
			CANIL_CORE_VERSION
		);
	}

	return true;
}

/**
 * Display admin notice for missing/incompatible core.
 *
 * @param string $message Error message.
 */
function canil_site_publico_admin_notice( string $message ): void {
	add_action(
		'admin_notices',
		function () use ( $message ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html( $message )
			);
		}
	);
}

/**
 * Plugin activation hook.
 */
function canil_site_publico_activate(): void {
	$check = canil_site_publico_check_core();
	if ( true !== $check ) {
		deactivate_plugins( CANIL_SITE_PUBLICO_BASENAME );
		wp_die(
			esc_html( $check ),
			'Plugin Activation Error',
			array( 'back_link' => true )
		);
	}

	// Register default options.
	$default_options = array(
		'kennel_name'        => get_bloginfo( 'name' ),
		'kennel_description' => '',
		'kennel_address'     => '',
		'kennel_phone'       => '',
		'kennel_email'       => get_bloginfo( 'admin_email' ),
		'kennel_whatsapp'    => '',
		'kennel_instagram'   => '',
		'kennel_facebook'    => '',
		'show_price'         => false,
		'default_price'      => '',
		'interest_form_to'   => get_bloginfo( 'admin_email' ),
		'breeds_filter'      => true,
		'sex_filter'         => true,
		'color_filter'       => true,
	);

	if ( false === get_option( 'canil_site_publico_settings' ) ) {
		add_option( 'canil_site_publico_settings', $default_options );
	}

	// Flush rewrite rules for custom endpoints.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\canil_site_publico_activate' );

/**
 * Plugin deactivation hook.
 */
function canil_site_publico_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\canil_site_publico_deactivate' );

/**
 * Initialize the plugin.
 */
function canil_site_publico_init(): void {
	// Check core compatibility.
	$check = canil_site_publico_check_core();
	if ( true !== $check ) {
		canil_site_publico_admin_notice( $check );
		return;
	}

	// Initialize plugin components.
	$plugin = new Plugin();
	$plugin->run();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\canil_site_publico_init', 20 ); // After Canil Core (priority 10).
