<?php
/**
 * Interest Form REST Controller.
 *
 * REST API controller for handling interest form submissions.
 *
 * @package CanilSitePublico
 */

namespace CanilSitePublico\Rest\Controllers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * InterestFormController class.
 */
class InterestFormController {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	private string $namespace = 'canil-site-publico/v1';

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// POST /interest - Submit interest form (public).
		register_rest_route(
			$this->namespace,
			'/interest',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'submit_interest' ),
				'permission_callback' => '__return_true', // Public endpoint.
				'args'                => array(
					'name'             => array(
						'description'       => __( 'Nome do interessado.', 'canil-site-publico' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $value ) {
							return strlen( trim( $value ) ) >= 2;
						},
					),
					'email'            => array(
						'description'       => __( 'E-mail do interessado.', 'canil-site-publico' ),
						'type'              => 'string',
						'format'            => 'email',
						'required'          => true,
						'sanitize_callback' => 'sanitize_email',
						'validate_callback' => 'is_email',
					),
					'phone'            => array(
						'description'       => __( 'Telefone do interessado.', 'canil-site-publico' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $value ) {
							// Accept phone numbers with at least 8 digits.
							$digits = preg_replace( '/[^0-9]/', '', $value );
							return strlen( $digits ) >= 8;
						},
					),
					'city'             => array(
						'description'       => __( 'Cidade do interessado.', 'canil-site-publico' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'message'          => array(
						'description'       => __( 'Mensagem do interessado.', 'canil-site-publico' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'puppy_id'         => array(
						'description'       => __( 'ID do filhote.', 'canil-site-publico' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'puppy_name'       => array(
						'description'       => __( 'Nome do filhote.', 'canil-site-publico' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'contact_whatsapp' => array(
						'description' => __( 'Aceita contato via WhatsApp.', 'canil-site-publico' ),
						'type'        => 'boolean',
						'default'     => false,
					),
					'privacy_accepted' => array(
						'description'       => __( 'Aceitou a política de privacidade.', 'canil-site-publico' ),
						'type'              => 'boolean',
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return true === $value || '1' === $value || 1 === $value;
						},
					),
				),
			)
		);
	}

	/**
	 * Submit interest form.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function submit_interest( \WP_REST_Request $request ) {
		// Get settings.
		$settings = get_option( 'canil_site_publico_settings', array() );
		$to_email = $settings['interest_form_to'] ?? get_bloginfo( 'admin_email' );

		if ( empty( $to_email ) || ! is_email( $to_email ) ) {
			return new \WP_Error(
				'config_error',
				__( 'Erro de configuração. E-mail de destino não configurado.', 'canil-site-publico' ),
				array( 'status' => 500 )
			);
		}

		// Extract form data.
		$name             = sanitize_text_field( $request->get_param( 'name' ) );
		$email            = sanitize_email( $request->get_param( 'email' ) );
		$phone            = sanitize_text_field( $request->get_param( 'phone' ) );
		$city             = sanitize_text_field( $request->get_param( 'city' ) ?? '' );
		$message          = sanitize_textarea_field( $request->get_param( 'message' ) ?? '' );
		$puppy_id         = absint( $request->get_param( 'puppy_id' ) ?? 0 );
		$puppy_name       = sanitize_text_field( $request->get_param( 'puppy_name' ) ?? '' );
		$contact_whatsapp = (bool) $request->get_param( 'contact_whatsapp' );

		// Build email subject.
		$kennel_name = $settings['kennel_name'] ?? get_bloginfo( 'name' );
		if ( ! empty( $puppy_name ) ) {
			$subject = sprintf(
				/* translators: 1: puppy name, 2: kennel name */
				__( '[Interesse] %1$s - %2$s', 'canil-site-publico' ),
				$puppy_name,
				$kennel_name
			);
		} else {
			$subject = sprintf(
				/* translators: %s: kennel name */
				__( '[Interesse] Novo contato - %s', 'canil-site-publico' ),
				$kennel_name
			);
		}

		// Build email body.
		$body = $this->build_email_body(
			array(
				'name'             => $name,
				'email'            => $email,
				'phone'            => $phone,
				'city'             => $city,
				'message'          => $message,
				'puppy_id'         => $puppy_id,
				'puppy_name'       => $puppy_name,
				'contact_whatsapp' => $contact_whatsapp,
			)
		);

		// Email headers.
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'Reply-To: ' . $name . ' <' . $email . '>',
		);

		// Send email.
		$sent = wp_mail( $to_email, $subject, $body, $headers );

		if ( ! $sent ) {
			return new \WP_Error(
				'email_error',
				__( 'Erro ao enviar e-mail. Por favor, tente novamente.', 'canil-site-publico' ),
				array( 'status' => 500 )
			);
		}

		/**
		 * Fires after interest form is submitted successfully.
		 *
		 * @param array  $data     Form data.
		 * @param string $to_email Recipient email.
		 */
		do_action(
			'canil_site_publico_interest_submitted',
			array(
				'name'             => $name,
				'email'            => $email,
				'phone'            => $phone,
				'city'             => $city,
				'message'          => $message,
				'puppy_id'         => $puppy_id,
				'puppy_name'       => $puppy_name,
				'contact_whatsapp' => $contact_whatsapp,
			),
			$to_email
		);

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Mensagem enviada com sucesso!', 'canil-site-publico' ),
			)
		);
	}

	/**
	 * Build email body HTML.
	 *
	 * @param array<string, mixed> $data Form data.
	 * @return string Email body HTML.
	 */
	private function build_email_body( array $data ): string {
		$settings    = get_option( 'canil_site_publico_settings', array() );
		$kennel_name = $settings['kennel_name'] ?? get_bloginfo( 'name' );

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<style>
				body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
				.container { max-width: 600px; margin: 0 auto; padding: 20px; }
				.header { background: #2271b1; color: white; padding: 20px; text-align: center; }
				.content { padding: 20px; background: #f9f9f9; }
				.field { margin-bottom: 15px; }
				.field-label { font-weight: bold; color: #555; }
				.field-value { margin-top: 5px; }
				.message-box { background: white; padding: 15px; border-left: 4px solid #2271b1; margin-top: 20px; }
				.footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
			</style>
		</head>
		<body>
			<div class="container">
				<div class="header">
					<h1><?php echo esc_html( $kennel_name ); ?></h1>
					<p><?php esc_html_e( 'Nova demonstração de interesse', 'canil-site-publico' ); ?></p>
				</div>
				
				<div class="content">
					<?php if ( ! empty( $data['puppy_name'] ) ) : ?>
						<div class="field">
							<div class="field-label"><?php esc_html_e( 'Filhote de interesse:', 'canil-site-publico' ); ?></div>
							<div class="field-value">
								<strong><?php echo esc_html( $data['puppy_name'] ); ?></strong>
								<?php if ( ! empty( $data['puppy_id'] ) ) : ?>
									(ID: <?php echo esc_html( $data['puppy_id'] ); ?>)
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>

					<div class="field">
						<div class="field-label"><?php esc_html_e( 'Nome:', 'canil-site-publico' ); ?></div>
						<div class="field-value"><?php echo esc_html( $data['name'] ); ?></div>
					</div>

					<div class="field">
						<div class="field-label"><?php esc_html_e( 'E-mail:', 'canil-site-publico' ); ?></div>
						<div class="field-value">
							<a href="mailto:<?php echo esc_attr( $data['email'] ); ?>"><?php echo esc_html( $data['email'] ); ?></a>
						</div>
					</div>

					<div class="field">
						<div class="field-label"><?php esc_html_e( 'Telefone:', 'canil-site-publico' ); ?></div>
						<div class="field-value">
							<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $data['phone'] ) ); ?>"><?php echo esc_html( $data['phone'] ); ?></a>
							<?php if ( $data['contact_whatsapp'] ) : ?>
								<span style="color: green;">✓ <?php esc_html_e( 'Aceita WhatsApp', 'canil-site-publico' ); ?></span>
							<?php endif; ?>
						</div>
					</div>

					<?php if ( ! empty( $data['city'] ) ) : ?>
						<div class="field">
							<div class="field-label"><?php esc_html_e( 'Cidade:', 'canil-site-publico' ); ?></div>
							<div class="field-value"><?php echo esc_html( $data['city'] ); ?></div>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $data['message'] ) ) : ?>
						<div class="message-box">
							<div class="field-label"><?php esc_html_e( 'Mensagem:', 'canil-site-publico' ); ?></div>
							<div class="field-value"><?php echo nl2br( esc_html( $data['message'] ) ); ?></div>
						</div>
					<?php endif; ?>
				</div>

				<div class="footer">
					<p><?php esc_html_e( 'Este e-mail foi enviado automaticamente através do formulário de interesse do site.', 'canil-site-publico' ); ?></p>
					<p><?php echo esc_html( current_time( 'mysql' ) ); ?></p>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
}
