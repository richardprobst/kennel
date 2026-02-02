<?php
/**
 * Puppy Detail Shortcode.
 *
 * Displays detailed information about a single puppy.
 *
 * Usage: [canil_filhote id="123"] or [canil_filhote] (uses ?filhote=ID from URL)
 *
 * @package CanilSitePublico
 */

namespace CanilSitePublico\Shortcodes;

use CanilSitePublico\Domain\PublicPuppyService;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PuppyDetailShortcode class.
 */
class PuppyDetailShortcode extends BaseShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected string $tag = 'canil_filhote';

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
				'id'            => 0,
				'show_price'    => 'auto',
				'show_parents'  => 'yes',
				'show_gallery'  => 'yes',
				'interest_form' => 'yes',
				'class'         => '',
			),
			$atts,
			$this->tag
		);

		// Get puppy ID from attribute or query string.
		$puppy_id = absint( $atts['id'] );
		if ( 0 === $puppy_id ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public page, read-only.
			$puppy_id = absint( $_GET['filhote'] ?? 0 );
		}

		if ( 0 === $puppy_id ) {
			return '<p class="canil-error">' . esc_html__( 'Nenhum filhote especificado.', 'canil-site-publico' ) . '</p>';
		}

		$service = new PublicPuppyService();
		$puppy   = $service->get_puppy_detail( $puppy_id );

		if ( ! $puppy ) {
			return '<p class="canil-error">' . esc_html__( 'Filhote não encontrado.', 'canil-site-publico' ) . '</p>';
		}

		// Determine if showing price.
		$show_price = 'auto' === $atts['show_price']
			? $this->get_setting( 'show_price', false )
			: ( 'yes' === $atts['show_price'] );

		return $this->buffer(
			function () use ( $atts, $puppy, $show_price ) {
				$class = 'canil-puppy-detail';
				if ( ! empty( $atts['class'] ) ) {
					$class .= ' ' . sanitize_html_class( $atts['class'] );
				}

				$name       = $puppy['name'] ?? $puppy['identifier'] ?? '';
				$sex        = $puppy['sex'] ?? '';
				$color      = $puppy['color'] ?? '';
				$breed      = $puppy['breed'] ?? '';
				$status     = $puppy['status'] ?? 'available';
				$photo      = $puppy['photo_url'] ?? '';
				$photos     = $puppy['photos'] ?? array();
				$birth_date = $puppy['birth_date'] ?? '';
				$price      = $puppy['price'] ?? '';
				$notes      = $puppy['public_notes'] ?? '';
				$dam        = $puppy['dam'] ?? null;
				$sire       = $puppy['sire'] ?? null;

				// Calculate age.
				$age = $this->calculate_age( $birth_date );
				?>
				<article class="<?php echo esc_attr( $class ); ?>" data-puppy-id="<?php echo esc_attr( $puppy['id'] ); ?>">
					<div class="canil-puppy-detail__main">
						<div class="canil-puppy-detail__media">
							<?php if ( ! empty( $photo ) ) : ?>
								<div class="canil-puppy-detail__photo">
									<img src="<?php echo esc_url( $photo ); ?>" alt="<?php echo esc_attr( $name ); ?>">
								</div>
							<?php else : ?>
								<div class="canil-puppy-detail__placeholder">
									<span class="dashicons dashicons-pets"></span>
								</div>
							<?php endif; ?>

							<?php if ( 'yes' === $atts['show_gallery'] && ! empty( $photos ) ) : ?>
								<div class="canil-puppy-detail__gallery">
									<?php foreach ( $photos as $gallery_photo ) : ?>
										<div class="canil-puppy-detail__gallery-item">
											<img src="<?php echo esc_url( $gallery_photo ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy">
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>

						<div class="canil-puppy-detail__info">
							<header class="canil-puppy-detail__header">
								<h1 class="canil-puppy-detail__name"><?php echo esc_html( $name ); ?></h1>
								<?php if ( 'reserved' === $status ) : ?>
									<span class="canil-puppy-detail__badge canil-puppy-detail__badge--reserved">
										<?php esc_html_e( 'Reservado', 'canil-site-publico' ); ?>
									</span>
								<?php elseif ( 'sold' === $status ) : ?>
									<span class="canil-puppy-detail__badge canil-puppy-detail__badge--sold">
										<?php esc_html_e( 'Vendido', 'canil-site-publico' ); ?>
									</span>
								<?php endif; ?>
							</header>

							<dl class="canil-puppy-detail__specs">
								<?php if ( ! empty( $breed ) ) : ?>
									<dt><?php esc_html_e( 'Raça', 'canil-site-publico' ); ?></dt>
									<dd><?php echo esc_html( $breed ); ?></dd>
								<?php endif; ?>

								<dt><?php esc_html_e( 'Sexo', 'canil-site-publico' ); ?></dt>
								<dd>
									<?php
									echo 'male' === $sex
										? esc_html__( 'Macho', 'canil-site-publico' )
										: esc_html__( 'Fêmea', 'canil-site-publico' );
									?>
								</dd>

								<?php if ( ! empty( $color ) ) : ?>
									<dt><?php esc_html_e( 'Cor', 'canil-site-publico' ); ?></dt>
									<dd><?php echo esc_html( $color ); ?></dd>
								<?php endif; ?>

								<?php if ( ! empty( $birth_date ) ) : ?>
									<dt><?php esc_html_e( 'Nascimento', 'canil-site-publico' ); ?></dt>
									<dd><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $birth_date ) ) ); ?></dd>
								<?php endif; ?>

								<?php if ( ! empty( $age ) ) : ?>
									<dt><?php esc_html_e( 'Idade', 'canil-site-publico' ); ?></dt>
									<dd><?php echo esc_html( $age ); ?></dd>
								<?php endif; ?>
							</dl>

							<?php if ( $show_price && ! empty( $price ) ) : ?>
								<div class="canil-puppy-detail__price">
									<span class="canil-puppy-detail__price-label"><?php esc_html_e( 'Valor:', 'canil-site-publico' ); ?></span>
									<span class="canil-puppy-detail__price-value">
										<?php
										echo esc_html(
											sprintf(
												/* translators: %s: price */
												__( 'R$ %s', 'canil-site-publico' ),
												number_format( (float) $price, 2, ',', '.' )
											)
										);
										?>
									</span>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $notes ) ) : ?>
								<div class="canil-puppy-detail__notes">
									<?php echo wp_kses_post( $notes ); ?>
								</div>
							<?php endif; ?>

							<?php if ( 'available' === $status && 'yes' === $atts['interest_form'] ) : ?>
								<div class="canil-puppy-detail__cta">
									<a href="#interesse" class="canil-button canil-button--primary">
										<?php esc_html_e( 'Tenho Interesse', 'canil-site-publico' ); ?>
									</a>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<?php if ( 'yes' === $atts['show_parents'] && ( $dam || $sire ) ) : ?>
						<section class="canil-puppy-detail__parents">
							<h2><?php esc_html_e( 'Pais', 'canil-site-publico' ); ?></h2>
							<div class="canil-puppy-detail__parents-grid">
								<?php if ( $sire ) : ?>
									<div class="canil-puppy-detail__parent canil-puppy-detail__parent--sire">
										<h3><?php esc_html_e( 'Pai', 'canil-site-publico' ); ?></h3>
										<?php $this->render_parent_card( $sire ); ?>
									</div>
								<?php endif; ?>
								<?php if ( $dam ) : ?>
									<div class="canil-puppy-detail__parent canil-puppy-detail__parent--dam">
										<h3><?php esc_html_e( 'Mãe', 'canil-site-publico' ); ?></h3>
										<?php $this->render_parent_card( $dam ); ?>
									</div>
								<?php endif; ?>
							</div>
						</section>
					<?php endif; ?>

					<?php if ( 'available' === $status && 'yes' === $atts['interest_form'] ) : ?>
						<section id="interesse" class="canil-puppy-detail__interest">
							<h2><?php esc_html_e( 'Demonstrar Interesse', 'canil-site-publico' ); ?></h2>
							<?php echo do_shortcode( '[canil_interesse filhote_id="' . esc_attr( $puppy['id'] ) . '" filhote_nome="' . esc_attr( $name ) . '"]' ); ?>
						</section>
					<?php endif; ?>
				</article>
				<?php
			}
		);
	}

	/**
	 * Calculate age from birth date.
	 *
	 * @param string $birth_date Birth date (Y-m-d).
	 * @return string Age string.
	 */
	private function calculate_age( string $birth_date ): string {
		if ( empty( $birth_date ) ) {
			return '';
		}

		$birth_dt = \DateTime::createFromFormat( 'Y-m-d', $birth_date );
		if ( ! $birth_dt ) {
			return '';
		}

		$now   = new \DateTime();
		$diff  = $now->diff( $birth_dt );
		$weeks = floor( $diff->days / 7 );
		// Note: $diff->m gives months in range 0-11.
		$months = $diff->m + ( $diff->y * 12 );

		if ( $months >= 12 ) {
			$years = floor( $months / 12 );
			return sprintf(
				/* translators: %d: number of years */
				_n( '%d ano', '%d anos', $years, 'canil-site-publico' ),
				$years
			);
		} elseif ( $months >= 1 ) {
			return sprintf(
				/* translators: %d: number of months */
				_n( '%d mês', '%d meses', $months, 'canil-site-publico' ),
				$months
			);
		} else {
			return sprintf(
				/* translators: %d: number of weeks */
				_n( '%d semana', '%d semanas', $weeks, 'canil-site-publico' ),
				$weeks
			);
		}
	}

	/**
	 * Render parent card.
	 *
	 * @param array<string, mixed> $parent Parent data.
	 */
	private function render_parent_card( array $parent ): void {
		$name  = $parent['name'] ?? '';
		$photo = $parent['photo_main_url'] ?? '';
		$breed = $parent['breed'] ?? '';
		?>
		<div class="canil-parent-card">
			<?php if ( ! empty( $photo ) ) : ?>
				<div class="canil-parent-card__photo">
					<img src="<?php echo esc_url( $photo ); ?>" alt="<?php echo esc_attr( $name ); ?>">
				</div>
			<?php endif; ?>
			<div class="canil-parent-card__info">
				<span class="canil-parent-card__name"><?php echo esc_html( $name ); ?></span>
				<?php if ( ! empty( $breed ) ) : ?>
					<span class="canil-parent-card__breed"><?php echo esc_html( $breed ); ?></span>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
