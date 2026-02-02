<?php
/**
 * Base Shortcode class.
 *
 * Abstract base class for all shortcodes.
 *
 * @package CanilSitePublico
 */

namespace CanilSitePublico\Shortcodes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BaseShortcode abstract class.
 */
abstract class BaseShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected string $tag = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! empty( $this->tag ) ) {
			add_shortcode( $this->tag, array( $this, 'render' ) );
		}
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array<string, mixed>|string $atts    Shortcode attributes.
	 * @param string|null                 $content Shortcode content.
	 * @return string Rendered HTML.
	 */
	abstract public function render( $atts, ?string $content = null ): string;

	/**
	 * Get plugin settings.
	 *
	 * @return array<string, mixed> Settings.
	 */
	protected function get_settings(): array {
		$settings = get_option( 'canil_site_publico_settings', array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Get a specific setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed Setting value.
	 */
	protected function get_setting( string $key, $default = '' ) {
		$settings = $this->get_settings();
		return $settings[ $key ] ?? $default;
	}

	/**
	 * Start output buffering and return result.
	 *
	 * @param callable $callback Function that outputs HTML.
	 * @return string Buffered output.
	 */
	protected function buffer( callable $callback ): string {
		ob_start();
		$callback();
		return ob_get_clean();
	}
}
