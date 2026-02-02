<?php
/**
 * Plugin class.
 *
 * Main plugin class that orchestrates all components.
 *
 * @package CanilCore
 */

namespace CanilCore\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin class.
 */
class Plugin {

	/**
	 * Initialize the plugin.
	 */
	public function run(): void {
		$this->load_dependencies();
		$this->register_hooks();
	}

	/**
	 * Load required dependencies.
	 */
	private function load_dependencies(): void {
		// Load Hooks class.
		new Hooks();
	}

	/**
	 * Register WordPress hooks.
	 */
	private function register_hooks(): void {
		// Register REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Register admin menu.
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );

		// Enqueue admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes(): void {
		// Register Dogs controller.
		$dogs_controller = new \CanilCore\Rest\Controllers\DogsController();
		$dogs_controller->register_routes();

		// Register Litters controller.
		$litters_controller = new \CanilCore\Rest\Controllers\LittersController();
		$litters_controller->register_routes();

		// Register Puppies controller.
		$puppies_controller = new \CanilCore\Rest\Controllers\PuppiesController();
		$puppies_controller->register_routes();

		// Register People controller.
		$people_controller = new \CanilCore\Rest\Controllers\PeopleController();
		$people_controller->register_routes();

		// Register Events controller.
		$events_controller = new \CanilCore\Rest\Controllers\EventsController();
		$events_controller->register_routes();
	}

	/**
	 * Register admin menu.
	 */
	public function register_admin_menu(): void {
		// Check if user has capability.
		if ( ! current_user_can( 'manage_kennel' ) ) {
			return;
		}

		add_menu_page(
			__( 'Canil', 'canil-core' ),
			__( 'Canil', 'canil-core' ),
			'manage_kennel',
			'canil-dashboard',
			array( $this, 'render_admin_page' ),
			'dashicons-pets',
			30
		);

		add_submenu_page(
			'canil-dashboard',
			__( 'Dashboard', 'canil-core' ),
			__( 'Dashboard', 'canil-core' ),
			'manage_kennel',
			'canil-dashboard',
			array( $this, 'render_admin_page' )
		);

		add_submenu_page(
			'canil-dashboard',
			__( 'Cães', 'canil-core' ),
			__( 'Cães', 'canil-core' ),
			'manage_dogs',
			'canil-dogs',
			array( $this, 'render_admin_page' )
		);

		add_submenu_page(
			'canil-dashboard',
			__( 'Ninhadas', 'canil-core' ),
			__( 'Ninhadas', 'canil-core' ),
			'manage_litters',
			'canil-litters',
			array( $this, 'render_admin_page' )
		);

		add_submenu_page(
			'canil-dashboard',
			__( 'Filhotes', 'canil-core' ),
			__( 'Filhotes', 'canil-core' ),
			'manage_puppies',
			'canil-puppies',
			array( $this, 'render_admin_page' )
		);

		add_submenu_page(
			'canil-dashboard',
			__( 'Pessoas', 'canil-core' ),
			__( 'Pessoas', 'canil-core' ),
			'manage_people',
			'canil-people',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render admin page.
	 */
	public function render_admin_page(): void {
		echo '<div id="canil-admin-root" class="wrap"></div>';
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		// Only load on our admin pages.
		if ( strpos( $hook_suffix, 'canil-' ) === false && $hook_suffix !== 'toplevel_page_canil-dashboard' ) {
			return;
		}

		// Enqueue React app (when built).
		$asset_file = CANIL_CORE_PATH . 'assets-admin/index.asset.php';
		if ( file_exists( $asset_file ) ) {
			$asset = require $asset_file;

			wp_enqueue_script(
				'canil-admin',
				CANIL_CORE_URL . 'assets-admin/index.js',
				$asset['dependencies'],
				$asset['version'],
				true
			);

			wp_enqueue_style(
				'canil-admin',
				CANIL_CORE_URL . 'assets-admin/index.css',
				array( 'wp-components' ),
				$asset['version']
			);

			// Localize script with REST API info.
			wp_localize_script(
				'canil-admin',
				'canilAdmin',
				array(
					'apiUrl'  => rest_url( 'canil/v1' ),
					'nonce'   => wp_create_nonce( 'wp_rest' ),
					'adminUrl' => admin_url(),
				)
			);
		}
	}
}
