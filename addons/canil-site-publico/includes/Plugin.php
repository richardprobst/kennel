<?php
/**
 * Plugin class for Canil Site Público.
 *
 * Main class that orchestrates all add-on components.
 *
 * @package CanilSitePublico
 */

namespace CanilSitePublico;

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
		// Load shortcodes.
		new Shortcodes\KennelInfoShortcode();
		new Shortcodes\PuppiesListShortcode();
		new Shortcodes\PuppyDetailShortcode();
		new Shortcodes\InterestFormShortcode();
	}

	/**
	 * Register WordPress hooks.
	 */
	private function register_hooks(): void {
		// Register REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Register admin menu.
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 99 );

		// Enqueue frontend styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Enqueue admin styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Add settings link to plugin page.
		add_filter(
			'plugin_action_links_' . CANIL_SITE_PUBLICO_BASENAME,
			array( $this, 'add_settings_link' )
		);

		// Register settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes(): void {
		$puppies_controller = new Rest\Controllers\PublicPuppiesController();
		$puppies_controller->register_routes();

		$interest_controller = new Rest\Controllers\InterestFormController();
		$interest_controller->register_routes();
	}

	/**
	 * Register admin menu.
	 */
	public function register_admin_menu(): void {
		add_submenu_page(
			'canil-dashboard',
			__( 'Site Público', 'canil-site-publico' ),
			__( 'Site Público', 'canil-site-publico' ),
			'manage_kennel',
			'canil-site-publico',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page(): void {
		// Check user capability.
		if ( ! current_user_can( 'manage_kennel' ) ) {
			wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'canil-site-publico' ) );
		}

		// Include settings template.
		include CANIL_SITE_PUBLICO_PATH . 'includes/Settings/settings-page.php';
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_frontend_assets(): void {
		// Only load on pages with our shortcodes.
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		$has_shortcode = has_shortcode( $post->post_content, 'canil_info' )
			|| has_shortcode( $post->post_content, 'canil_filhotes' )
			|| has_shortcode( $post->post_content, 'canil_filhote' )
			|| has_shortcode( $post->post_content, 'canil_interesse' );

		if ( ! $has_shortcode ) {
			return;
		}

		wp_enqueue_style(
			'canil-site-publico',
			CANIL_SITE_PUBLICO_URL . 'assets/css/frontend.css',
			array(),
			CANIL_SITE_PUBLICO_VERSION
		);

		wp_enqueue_script(
			'canil-site-publico',
			CANIL_SITE_PUBLICO_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			CANIL_SITE_PUBLICO_VERSION,
			true
		);

		wp_localize_script(
			'canil-site-publico',
			'canilSitePublico',
			array(
				'apiUrl' => rest_url( 'canil-site-publico/v1' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
				'i18n'   => array(
					'sending'      => __( 'Enviando...', 'canil-site-publico' ),
					'success'      => __( 'Mensagem enviada com sucesso!', 'canil-site-publico' ),
					'error'        => __( 'Erro ao enviar mensagem. Tente novamente.', 'canil-site-publico' ),
					'required'     => __( 'Este campo é obrigatório.', 'canil-site-publico' ),
					'invalidEmail' => __( 'E-mail inválido.', 'canil-site-publico' ),
					'invalidPhone' => __( 'Telefone inválido.', 'canil-site-publico' ),
				),
			)
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( 'canil_page_canil-site-publico' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'canil-site-publico-admin',
			CANIL_SITE_PUBLICO_URL . 'assets/css/admin.css',
			array(),
			CANIL_SITE_PUBLICO_VERSION
		);
	}

	/**
	 * Add settings link to plugin actions.
	 *
	 * @param array<string> $links Plugin action links.
	 * @return array<string> Modified links.
	 */
	public function add_settings_link( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=canil-site-publico' ),
			__( 'Configurações', 'canil-site-publico' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings(): void {
		register_setting(
			'canil_site_publico_settings',
			'canil_site_publico_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array<string, mixed> $input Settings input.
	 * @return array<string, mixed> Sanitized settings.
	 */
	public function sanitize_settings( array $input ): array {
		$sanitized = array();

		$sanitized['kennel_name']        = sanitize_text_field( $input['kennel_name'] ?? '' );
		$sanitized['kennel_description'] = wp_kses_post( $input['kennel_description'] ?? '' );
		$sanitized['kennel_address']     = sanitize_textarea_field( $input['kennel_address'] ?? '' );
		$sanitized['kennel_phone']       = sanitize_text_field( $input['kennel_phone'] ?? '' );
		$sanitized['kennel_email']       = sanitize_email( $input['kennel_email'] ?? '' );
		$sanitized['kennel_whatsapp']    = sanitize_text_field( $input['kennel_whatsapp'] ?? '' );
		$sanitized['kennel_instagram']   = sanitize_text_field( $input['kennel_instagram'] ?? '' );
		$sanitized['kennel_facebook']    = esc_url_raw( $input['kennel_facebook'] ?? '' );
		$sanitized['show_price']         = ! empty( $input['show_price'] );
		$sanitized['default_price']      = sanitize_text_field( $input['default_price'] ?? '' );
		$sanitized['interest_form_to']   = sanitize_email( $input['interest_form_to'] ?? '' );
		$sanitized['breeds_filter']      = ! empty( $input['breeds_filter'] );
		$sanitized['sex_filter']         = ! empty( $input['sex_filter'] );
		$sanitized['color_filter']       = ! empty( $input['color_filter'] );

		return $sanitized;
	}
}
