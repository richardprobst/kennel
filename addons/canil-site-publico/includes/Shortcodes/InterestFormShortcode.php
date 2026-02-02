<?php
/**
 * Interest Form Shortcode.
 *
 * Displays a form for visitors to express interest in a puppy.
 *
 * Usage: [canil_interesse filhote_id="123" filhote_nome="Max"]
 *
 * @package CanilSitePublico
 */

namespace CanilSitePublico\Shortcodes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * InterestFormShortcode class.
 */
class InterestFormShortcode extends BaseShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected string $tag = 'canil_interesse';

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
				'filhote_id'   => 0,
				'filhote_nome' => '',
				'class'        => '',
			),
			$atts,
			$this->tag
		);

		$puppy_id   = absint( $atts['filhote_id'] );
		$puppy_name = sanitize_text_field( $atts['filhote_nome'] );

		return $this->buffer(
			function () use ( $atts, $puppy_id, $puppy_name ) {
				$class = 'canil-interest-form';
				if ( ! empty( $atts['class'] ) ) {
					$class .= ' ' . sanitize_html_class( $atts['class'] );
				}

				$form_id = 'canil-interest-form-' . wp_unique_id();
				?>
				<div class="<?php echo esc_attr( $class ); ?>">
					<form id="<?php echo esc_attr( $form_id ); ?>" class="canil-form" data-puppy-id="<?php echo esc_attr( $puppy_id ); ?>">
						<?php wp_nonce_field( 'canil_interest_form', 'canil_interest_nonce' ); ?>
						
						<?php if ( $puppy_id > 0 ) : ?>
							<input type="hidden" name="puppy_id" value="<?php echo esc_attr( $puppy_id ); ?>">
						<?php endif; ?>

						<?php if ( ! empty( $puppy_name ) ) : ?>
							<input type="hidden" name="puppy_name" value="<?php echo esc_attr( $puppy_name ); ?>">
						<?php endif; ?>

						<div class="canil-form__group">
							<label for="<?php echo esc_attr( $form_id ); ?>-name" class="canil-form__label">
								<?php esc_html_e( 'Nome', 'canil-site-publico' ); ?> <span class="required">*</span>
							</label>
							<input 
								type="text" 
								id="<?php echo esc_attr( $form_id ); ?>-name" 
								name="name" 
								class="canil-form__input" 
								required 
								minlength="2"
								maxlength="100"
								placeholder="<?php esc_attr_e( 'Seu nome completo', 'canil-site-publico' ); ?>"
							>
						</div>

						<div class="canil-form__group">
							<label for="<?php echo esc_attr( $form_id ); ?>-email" class="canil-form__label">
								<?php esc_html_e( 'E-mail', 'canil-site-publico' ); ?> <span class="required">*</span>
							</label>
							<input 
								type="email" 
								id="<?php echo esc_attr( $form_id ); ?>-email" 
								name="email" 
								class="canil-form__input" 
								required 
								maxlength="100"
								placeholder="<?php esc_attr_e( 'seu@email.com', 'canil-site-publico' ); ?>"
							>
						</div>

						<div class="canil-form__group">
							<label for="<?php echo esc_attr( $form_id ); ?>-phone" class="canil-form__label">
								<?php esc_html_e( 'Telefone/WhatsApp', 'canil-site-publico' ); ?> <span class="required">*</span>
							</label>
							<input 
								type="tel" 
								id="<?php echo esc_attr( $form_id ); ?>-phone" 
								name="phone" 
								class="canil-form__input" 
								required 
								maxlength="20"
								placeholder="<?php esc_attr_e( '(00) 00000-0000', 'canil-site-publico' ); ?>"
							>
						</div>

						<div class="canil-form__group">
							<label for="<?php echo esc_attr( $form_id ); ?>-city" class="canil-form__label">
								<?php esc_html_e( 'Cidade/Estado', 'canil-site-publico' ); ?>
							</label>
							<input 
								type="text" 
								id="<?php echo esc_attr( $form_id ); ?>-city" 
								name="city" 
								class="canil-form__input" 
								maxlength="100"
								placeholder="<?php esc_attr_e( 'Ex: São Paulo/SP', 'canil-site-publico' ); ?>"
							>
						</div>

						<div class="canil-form__group">
							<label for="<?php echo esc_attr( $form_id ); ?>-message" class="canil-form__label">
								<?php esc_html_e( 'Mensagem', 'canil-site-publico' ); ?>
							</label>
							<textarea 
								id="<?php echo esc_attr( $form_id ); ?>-message" 
								name="message" 
								class="canil-form__textarea" 
								rows="4"
								maxlength="1000"
								placeholder="<?php esc_attr_e( 'Conte-nos um pouco sobre você e por que deseja este filhote...', 'canil-site-publico' ); ?>"
							></textarea>
						</div>

						<div class="canil-form__group canil-form__group--checkbox">
							<label class="canil-form__checkbox">
								<input type="checkbox" name="contact_whatsapp" value="1">
								<span><?php esc_html_e( 'Aceito receber contato via WhatsApp', 'canil-site-publico' ); ?></span>
							</label>
						</div>

						<div class="canil-form__group canil-form__group--checkbox">
							<label class="canil-form__checkbox">
								<input type="checkbox" name="privacy_accepted" value="1" required>
								<span>
									<?php
									printf(
										/* translators: %s: privacy policy link */
										esc_html__( 'Li e aceito a %s', 'canil-site-publico' ),
										sprintf(
											'<a href="%s" target="_blank">%s</a>',
											esc_url( get_privacy_policy_url() ),
											esc_html__( 'Política de Privacidade', 'canil-site-publico' )
										)
									);
									?>
									<span class="required">*</span>
								</span>
							</label>
						</div>

						<div class="canil-form__group canil-form__group--submit">
							<button type="submit" class="canil-button canil-button--primary canil-form__submit">
								<?php esc_html_e( 'Enviar Interesse', 'canil-site-publico' ); ?>
							</button>
						</div>

						<div class="canil-form__messages" aria-live="polite">
							<div class="canil-form__success" style="display: none;">
								<?php esc_html_e( 'Mensagem enviada com sucesso! Entraremos em contato em breve.', 'canil-site-publico' ); ?>
							</div>
							<div class="canil-form__error" style="display: none;">
								<?php esc_html_e( 'Erro ao enviar mensagem. Por favor, tente novamente.', 'canil-site-publico' ); ?>
							</div>
						</div>
					</form>
				</div>
				<?php
			}
		);
	}
}
