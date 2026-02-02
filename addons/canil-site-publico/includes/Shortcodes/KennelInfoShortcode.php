<?php
/**
 * Kennel Info Shortcode.
 *
 * Displays kennel information.
 *
 * Usage: [canil_info]
 *
 * @package CanilSitePublico
 */

namespace CanilSitePublico\Shortcodes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * KennelInfoShortcode class.
 */
class KennelInfoShortcode extends BaseShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected string $tag = 'canil_info';

	/**
	 * Render the shortcode.
	 *
	 * @param array<string, mixed>|string $atts    Shortcode attributes.
	 * @param string|null                 $content Shortcode content.
	 * @return string Rendered HTML.
	 */
	public function render( $atts, ?string $content = null ): string {
		$atts = shortcode_atts(
			array(
				'show_name'        => 'yes',
				'show_description' => 'yes',
				'show_contact'     => 'yes',
				'show_social'      => 'yes',
				'class'            => '',
			),
			$atts,
			$this->tag
		);

		$settings = $this->get_settings();

		return $this->buffer(
			function () use ( $atts, $settings ) {
				$class = 'canil-info';
				if ( ! empty( $atts['class'] ) ) {
					$class .= ' ' . sanitize_html_class( $atts['class'] );
				}
				?>
				<div class="<?php echo esc_attr( $class ); ?>">
					<?php if ( 'yes' === $atts['show_name'] && ! empty( $settings['kennel_name'] ) ) : ?>
						<h2 class="canil-info__name"><?php echo esc_html( $settings['kennel_name'] ); ?></h2>
					<?php endif; ?>

					<?php if ( 'yes' === $atts['show_description'] && ! empty( $settings['kennel_description'] ) ) : ?>
						<div class="canil-info__description">
							<?php echo wp_kses_post( $settings['kennel_description'] ); ?>
						</div>
					<?php endif; ?>

					<?php if ( 'yes' === $atts['show_contact'] && $this->has_contact_info( $settings ) ) : ?>
						<div class="canil-info__contact">
							<h3><?php esc_html_e( 'Contato', 'canil-site-publico' ); ?></h3>
							<ul class="canil-info__contact-list">
								<?php if ( ! empty( $settings['kennel_address'] ) ) : ?>
									<li class="canil-info__address">
										<span class="dashicons dashicons-location"></span>
										<?php echo nl2br( esc_html( $settings['kennel_address'] ) ); ?>
									</li>
								<?php endif; ?>

								<?php if ( ! empty( $settings['kennel_phone'] ) ) : ?>
									<li class="canil-info__phone">
										<span class="dashicons dashicons-phone"></span>
										<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $settings['kennel_phone'] ) ); ?>">
											<?php echo esc_html( $settings['kennel_phone'] ); ?>
										</a>
									</li>
								<?php endif; ?>

								<?php if ( ! empty( $settings['kennel_whatsapp'] ) ) : ?>
									<li class="canil-info__whatsapp">
										<span class="dashicons dashicons-whatsapp"></span>
										<a href="https://wa.me/<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $settings['kennel_whatsapp'] ) ); ?>" target="_blank" rel="noopener">
											<?php echo esc_html( $settings['kennel_whatsapp'] ); ?>
										</a>
									</li>
								<?php endif; ?>

								<?php if ( ! empty( $settings['kennel_email'] ) ) : ?>
									<li class="canil-info__email">
										<span class="dashicons dashicons-email"></span>
										<a href="mailto:<?php echo esc_attr( $settings['kennel_email'] ); ?>">
											<?php echo esc_html( $settings['kennel_email'] ); ?>
										</a>
									</li>
								<?php endif; ?>
							</ul>
						</div>
					<?php endif; ?>

					<?php if ( 'yes' === $atts['show_social'] && $this->has_social_info( $settings ) ) : ?>
						<div class="canil-info__social">
							<h3><?php esc_html_e( 'Redes Sociais', 'canil-site-publico' ); ?></h3>
							<ul class="canil-info__social-list">
								<?php if ( ! empty( $settings['kennel_instagram'] ) ) : ?>
									<li class="canil-info__instagram">
										<a href="https://instagram.com/<?php echo esc_attr( ltrim( $settings['kennel_instagram'], '@' ) ); ?>" target="_blank" rel="noopener">
											<span class="dashicons dashicons-instagram"></span>
											<?php echo esc_html( '@' . ltrim( $settings['kennel_instagram'], '@' ) ); ?>
										</a>
									</li>
								<?php endif; ?>

								<?php if ( ! empty( $settings['kennel_facebook'] ) ) : ?>
									<li class="canil-info__facebook">
										<a href="<?php echo esc_url( $settings['kennel_facebook'] ); ?>" target="_blank" rel="noopener">
											<span class="dashicons dashicons-facebook-alt"></span>
											<?php esc_html_e( 'Facebook', 'canil-site-publico' ); ?>
										</a>
									</li>
								<?php endif; ?>
							</ul>
						</div>
					<?php endif; ?>
				</div>
				<?php
			}
		);
	}

	/**
	 * Check if contact info is available.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return bool True if has contact info.
	 */
	private function has_contact_info( array $settings ): bool {
		return ! empty( $settings['kennel_address'] )
			|| ! empty( $settings['kennel_phone'] )
			|| ! empty( $settings['kennel_whatsapp'] )
			|| ! empty( $settings['kennel_email'] );
	}

	/**
	 * Check if social info is available.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return bool True if has social info.
	 */
	private function has_social_info( array $settings ): bool {
		return ! empty( $settings['kennel_instagram'] )
			|| ! empty( $settings['kennel_facebook'] );
	}
}
