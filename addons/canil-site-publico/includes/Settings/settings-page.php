<?php
/**
 * Settings page template.
 *
 * @package CanilSitePublico
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = get_option( 'canil_site_publico_settings', array() );

// Handle form submission.
if ( isset( $_POST['canil_site_publico_save'] ) && check_admin_referer( 'canil_site_publico_settings' ) ) {
	$settings = array(
		'kennel_name'        => sanitize_text_field( wp_unslash( $_POST['kennel_name'] ?? '' ) ),
		'kennel_description' => wp_kses_post( wp_unslash( $_POST['kennel_description'] ?? '' ) ),
		'kennel_address'     => sanitize_textarea_field( wp_unslash( $_POST['kennel_address'] ?? '' ) ),
		'kennel_phone'       => sanitize_text_field( wp_unslash( $_POST['kennel_phone'] ?? '' ) ),
		'kennel_email'       => sanitize_email( wp_unslash( $_POST['kennel_email'] ?? '' ) ),
		'kennel_whatsapp'    => sanitize_text_field( wp_unslash( $_POST['kennel_whatsapp'] ?? '' ) ),
		'kennel_instagram'   => sanitize_text_field( wp_unslash( $_POST['kennel_instagram'] ?? '' ) ),
		'kennel_facebook'    => esc_url_raw( wp_unslash( $_POST['kennel_facebook'] ?? '' ) ),
		'show_price'         => isset( $_POST['show_price'] ),
		'default_price'      => sanitize_text_field( wp_unslash( $_POST['default_price'] ?? '' ) ),
		'interest_form_to'   => sanitize_email( wp_unslash( $_POST['interest_form_to'] ?? '' ) ),
		'breeds_filter'      => isset( $_POST['breeds_filter'] ),
		'sex_filter'         => isset( $_POST['sex_filter'] ),
		'color_filter'       => isset( $_POST['color_filter'] ),
	);

	update_option( 'canil_site_publico_settings', $settings );

	echo '<div class="notice notice-success"><p>' . esc_html__( 'Configurações salvas com sucesso!', 'canil-site-publico' ) . '</p></div>';
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Site Público - Configurações', 'canil-site-publico' ); ?></h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'canil_site_publico_settings' ); ?>

		<h2 class="title"><?php esc_html_e( 'Informações do Canil', 'canil-site-publico' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Estas informações serão exibidas no site público através do shortcode [canil_info].', 'canil-site-publico' ); ?></p>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="kennel_name"><?php esc_html_e( 'Nome do Canil', 'canil-site-publico' ); ?></label>
				</th>
				<td>
					<input type="text" id="kennel_name" name="kennel_name" class="regular-text" 
						value="<?php echo esc_attr( $settings['kennel_name'] ?? '' ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="kennel_description"><?php esc_html_e( 'Descrição', 'canil-site-publico' ); ?></label>
				</th>
				<td>
					<?php
					wp_editor(
						$settings['kennel_description'] ?? '',
						'kennel_description',
						array(
							'textarea_name' => 'kennel_description',
							'textarea_rows' => 8,
							'media_buttons' => true,
							'teeny'         => false,
						)
					);
					?>
					<p class="description"><?php esc_html_e( 'Apresentação do canil para visitantes.', 'canil-site-publico' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="kennel_address"><?php esc_html_e( 'Endereço', 'canil-site-publico' ); ?></label>
				</th>
				<td>
					<textarea id="kennel_address" name="kennel_address" class="large-text" rows="3"><?php echo esc_textarea( $settings['kennel_address'] ?? '' ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="kennel_phone"><?php esc_html_e( 'Telefone', 'canil-site-publico' ); ?></label>
				</th>
				<td>
					<input type="tel" id="kennel_phone" name="kennel_phone" class="regular-text" 
						value="<?php echo esc_attr( $settings['kennel_phone'] ?? '' ); ?>"
						placeholder="(00) 0000-0000">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="kennel_whatsapp"><?php esc_html_e( 'WhatsApp', 'canil-site-publico' ); ?></label>
				</th>
				<td>
					<input type="tel" id="kennel_whatsapp" name="kennel_whatsapp" class="regular-text" 
						value="<?php echo esc_attr( $settings['kennel_whatsapp'] ?? '' ); ?>"
						placeholder="+55 00 00000-0000">
					<p class="description"><?php esc_html_e( 'Número com código do país para links diretos.', 'canil-site-publico' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="kennel_email"><?php esc_html_e( 'E-mail', 'canil-site-publico' ); ?></label>
				</th>
				<td>
					<input type="email" id="kennel_email" name="kennel_email" class="regular-text" 
						value="<?php echo esc_attr( $settings['kennel_email'] ?? '' ); ?>">
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Redes Sociais', 'canil-site-publico' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="kennel_instagram"><?php esc_html_e( 'Instagram', 'canil-site-publico' ); ?></label>
				</th>
				<td>
					<input type="text" id="kennel_instagram" name="kennel_instagram" class="regular-text" 
						value="<?php echo esc_attr( $settings['kennel_instagram'] ?? '' ); ?>"
						placeholder="@seucabenel">
					<p class="description"><?php esc_html_e( 'Nome de usuário (com ou sem @).', 'canil-site-publico' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="kennel_facebook"><?php esc_html_e( 'Facebook', 'canil-site-publico' ); ?></label>
				</th>
				<td>
					<input type="url" id="kennel_facebook" name="kennel_facebook" class="large-text" 
						value="<?php echo esc_url( $settings['kennel_facebook'] ?? '' ); ?>"
						placeholder="https://facebook.com/seucanil">
					<p class="description"><?php esc_html_e( 'URL completa da página.', 'canil-site-publico' ); ?></p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Exibição de Filhotes', 'canil-site-publico' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Filtros', 'canil-site-publico' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="checkbox" name="breeds_filter" value="1" 
								<?php checked( $settings['breeds_filter'] ?? true ); ?>>
							<?php esc_html_e( 'Exibir filtro por raça', 'canil-site-publico' ); ?>
						</label><br>
						<label>
							<input type="checkbox" name="sex_filter" value="1" 
								<?php checked( $settings['sex_filter'] ?? true ); ?>>
							<?php esc_html_e( 'Exibir filtro por sexo', 'canil-site-publico' ); ?>
						</label><br>
						<label>
							<input type="checkbox" name="color_filter" value="1" 
								<?php checked( $settings['color_filter'] ?? true ); ?>>
							<?php esc_html_e( 'Exibir filtro por cor', 'canil-site-publico' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Preços', 'canil-site-publico' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="checkbox" name="show_price" value="1" 
								<?php checked( $settings['show_price'] ?? false ); ?>>
							<?php esc_html_e( 'Exibir preços dos filhotes', 'canil-site-publico' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="default_price"><?php esc_html_e( 'Preço Padrão', 'canil-site-publico' ); ?></label>
				</th>
				<td>
					<input type="text" id="default_price" name="default_price" class="regular-text" 
						value="<?php echo esc_attr( $settings['default_price'] ?? '' ); ?>"
						placeholder="5000.00">
					<p class="description"><?php esc_html_e( 'Usado quando o filhote não tem preço definido.', 'canil-site-publico' ); ?></p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Formulário de Interesse', 'canil-site-publico' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="interest_form_to"><?php esc_html_e( 'E-mail de Destino', 'canil-site-publico' ); ?></label>
				</th>
				<td>
					<input type="email" id="interest_form_to" name="interest_form_to" class="regular-text" 
						value="<?php echo esc_attr( $settings['interest_form_to'] ?? get_bloginfo( 'admin_email' ) ); ?>">
					<p class="description"><?php esc_html_e( 'E-mail que receberá as demonstrações de interesse.', 'canil-site-publico' ); ?></p>
				</td>
			</tr>
		</table>

		<hr>

		<h2 class="title"><?php esc_html_e( 'Shortcodes Disponíveis', 'canil-site-publico' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Use estes shortcodes em suas páginas para exibir o conteúdo do canil:', 'canil-site-publico' ); ?></p>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Shortcode', 'canil-site-publico' ); ?></th>
					<th><?php esc_html_e( 'Descrição', 'canil-site-publico' ); ?></th>
					<th><?php esc_html_e( 'Parâmetros', 'canil-site-publico' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>[canil_info]</code></td>
					<td><?php esc_html_e( 'Exibe informações do canil (nome, descrição, contato, redes sociais).', 'canil-site-publico' ); ?></td>
					<td>
						<code>show_name</code>, <code>show_description</code>, <code>show_contact</code>, <code>show_social</code>
					</td>
				</tr>
				<tr>
					<td><code>[canil_filhotes]</code></td>
					<td><?php esc_html_e( 'Lista de filhotes disponíveis com filtros.', 'canil-site-publico' ); ?></td>
					<td>
						<code>status</code>, <code>limit</code>, <code>columns</code>, <code>show_filters</code>, <code>show_price</code>, <code>detail_page</code>
					</td>
				</tr>
				<tr>
					<td><code>[canil_filhote]</code></td>
					<td><?php esc_html_e( 'Detalhe de um filhote específico.', 'canil-site-publico' ); ?></td>
					<td>
						<code>id</code>, <code>show_price</code>, <code>show_parents</code>, <code>show_gallery</code>, <code>interest_form</code>
					</td>
				</tr>
				<tr>
					<td><code>[canil_interesse]</code></td>
					<td><?php esc_html_e( 'Formulário de demonstração de interesse.', 'canil-site-publico' ); ?></td>
					<td>
						<code>filhote_id</code>, <code>filhote_nome</code>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<input type="submit" name="canil_site_publico_save" class="button button-primary" 
				value="<?php esc_attr_e( 'Salvar Configurações', 'canil-site-publico' ); ?>">
		</p>
	</form>
</div>
