<?php
/**
 * Puppies List Shortcode.
 *
 * Displays a list of available puppies.
 *
 * Usage: [canil_filhotes status="available" limit="12" columns="3"]
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
 * PuppiesListShortcode class.
 */
class PuppiesListShortcode extends BaseShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected string $tag = 'canil_filhotes';

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
				'status'       => 'available',
				'limit'        => 12,
				'columns'      => 3,
				'show_filters' => 'yes',
				'show_price'   => 'auto',
				'detail_page'  => '',
				'class'        => '',
			),
			$atts,
			$this->tag
		);

		$service = new PublicPuppyService();
		$filters = array(
			'status' => sanitize_text_field( $atts['status'] ),
		);

		$puppies = $service->get_available_puppies( $filters, absint( $atts['limit'] ) );
		$breeds  = $service->get_available_breeds();
		$colors  = $service->get_available_colors();

		// Determine if showing price.
		$show_price = 'auto' === $atts['show_price']
			? $this->get_setting( 'show_price', false )
			: ( 'yes' === $atts['show_price'] );

		// Determine if showing filters.
		$show_filters = 'yes' === $atts['show_filters'];

		return $this->buffer(
			function () use ( $atts, $puppies, $breeds, $colors, $show_price, $show_filters ) {
				$class = 'canil-puppies';
				if ( ! empty( $atts['class'] ) ) {
					$class .= ' ' . sanitize_html_class( $atts['class'] );
				}
				$columns = absint( $atts['columns'] );
				if ( $columns < 1 || $columns > 6 ) {
					$columns = 3;
				}
				?>
				<div class="<?php echo esc_attr( $class ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>">
					<?php if ( $show_filters && ( count( $breeds ) > 1 || count( $colors ) > 1 ) ) : ?>
						<div class="canil-puppies__filters">
							<?php if ( $this->get_setting( 'breeds_filter', true ) && count( $breeds ) > 1 ) : ?>
								<div class="canil-puppies__filter">
									<label for="canil-filter-breed"><?php esc_html_e( 'Raça', 'canil-site-publico' ); ?></label>
									<select id="canil-filter-breed" class="canil-filter" data-filter="breed">
										<option value=""><?php esc_html_e( 'Todas', 'canil-site-publico' ); ?></option>
										<?php foreach ( $breeds as $breed ) : ?>
											<option value="<?php echo esc_attr( $breed ); ?>"><?php echo esc_html( $breed ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							<?php endif; ?>

							<?php if ( $this->get_setting( 'sex_filter', true ) ) : ?>
								<div class="canil-puppies__filter">
									<label for="canil-filter-sex"><?php esc_html_e( 'Sexo', 'canil-site-publico' ); ?></label>
									<select id="canil-filter-sex" class="canil-filter" data-filter="sex">
										<option value=""><?php esc_html_e( 'Todos', 'canil-site-publico' ); ?></option>
										<option value="male"><?php esc_html_e( 'Macho', 'canil-site-publico' ); ?></option>
										<option value="female"><?php esc_html_e( 'Fêmea', 'canil-site-publico' ); ?></option>
									</select>
								</div>
							<?php endif; ?>

							<?php if ( $this->get_setting( 'color_filter', true ) && count( $colors ) > 1 ) : ?>
								<div class="canil-puppies__filter">
									<label for="canil-filter-color"><?php esc_html_e( 'Cor', 'canil-site-publico' ); ?></label>
									<select id="canil-filter-color" class="canil-filter" data-filter="color">
										<option value=""><?php esc_html_e( 'Todas', 'canil-site-publico' ); ?></option>
										<?php foreach ( $colors as $color ) : ?>
											<option value="<?php echo esc_attr( $color ); ?>"><?php echo esc_html( $color ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( empty( $puppies ) ) : ?>
						<div class="canil-puppies__empty">
							<p><?php esc_html_e( 'Nenhum filhote disponível no momento.', 'canil-site-publico' ); ?></p>
							<p><?php esc_html_e( 'Entre em contato para saber sobre futuras ninhadas.', 'canil-site-publico' ); ?></p>
						</div>
					<?php else : ?>
						<div class="canil-puppies__grid" style="--columns: <?php echo esc_attr( $columns ); ?>">
							<?php foreach ( $puppies as $puppy ) : ?>
								<?php $this->render_puppy_card( $puppy, $show_price, $atts['detail_page'] ); ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
				<?php
			}
		);
	}

	/**
	 * Render a single puppy card.
	 *
	 * @param array<string, mixed> $puppy       Puppy data.
	 * @param bool                 $show_price  Whether to show price.
	 * @param string               $detail_page Detail page URL.
	 */
	private function render_puppy_card( array $puppy, bool $show_price, string $detail_page ): void {
		$id         = absint( $puppy['id'] ?? 0 );
		$name       = $puppy['name'] ?? $puppy['identifier'] ?? '';
		$sex        = $puppy['sex'] ?? '';
		$color      = $puppy['color'] ?? '';
		$breed      = $puppy['breed'] ?? '';
		$status     = $puppy['status'] ?? 'available';
		$photo      = $puppy['photo_url'] ?? '';
		$birth_date = $puppy['birth_date'] ?? '';
		$price      = $puppy['price'] ?? '';

		// Calculate age.
		$age = '';
		if ( ! empty( $birth_date ) ) {
			$birth_dt = \DateTime::createFromFormat( 'Y-m-d', $birth_date );
			if ( $birth_dt ) {
				$now    = new \DateTime();
				$diff   = $now->diff( $birth_dt );
				$weeks  = floor( $diff->days / 7 );
				$months = $diff->m + ( $diff->y * 12 );
				if ( $months >= 1 ) {
					$age = sprintf(
						/* translators: %d: number of months */
						_n( '%d mês', '%d meses', $months, 'canil-site-publico' ),
						$months
					);
				} else {
					$age = sprintf(
						/* translators: %d: number of weeks */
						_n( '%d semana', '%d semanas', $weeks, 'canil-site-publico' ),
						$weeks
					);
				}
			}
		}

		// Build detail URL.
		$detail_url = '';
		if ( ! empty( $detail_page ) ) {
			$detail_url = add_query_arg( 'filhote', $id, $detail_page );
		}
		?>
		<article class="canil-puppy-card" 
			data-puppy-id="<?php echo esc_attr( $id ); ?>"
			data-breed="<?php echo esc_attr( $breed ); ?>"
			data-sex="<?php echo esc_attr( $sex ); ?>"
			data-color="<?php echo esc_attr( $color ); ?>"
			data-status="<?php echo esc_attr( $status ); ?>">
			
			<div class="canil-puppy-card__image">
				<?php if ( ! empty( $photo ) ) : ?>
					<img src="<?php echo esc_url( $photo ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy">
				<?php else : ?>
					<div class="canil-puppy-card__placeholder">
						<span class="dashicons dashicons-pets"></span>
					</div>
				<?php endif; ?>

				<?php if ( 'reserved' === $status ) : ?>
					<span class="canil-puppy-card__badge canil-puppy-card__badge--reserved">
						<?php esc_html_e( 'Reservado', 'canil-site-publico' ); ?>
					</span>
				<?php endif; ?>
			</div>

			<div class="canil-puppy-card__content">
				<h3 class="canil-puppy-card__name">
					<?php if ( ! empty( $detail_url ) ) : ?>
						<a href="<?php echo esc_url( $detail_url ); ?>"><?php echo esc_html( $name ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $name ); ?>
					<?php endif; ?>
				</h3>

				<ul class="canil-puppy-card__details">
					<?php if ( ! empty( $breed ) ) : ?>
						<li class="canil-puppy-card__breed"><?php echo esc_html( $breed ); ?></li>
					<?php endif; ?>

					<li class="canil-puppy-card__sex">
						<?php
						echo 'male' === $sex
							? esc_html__( 'Macho', 'canil-site-publico' )
							: esc_html__( 'Fêmea', 'canil-site-publico' );
						?>
					</li>

					<?php if ( ! empty( $color ) ) : ?>
						<li class="canil-puppy-card__color"><?php echo esc_html( $color ); ?></li>
					<?php endif; ?>

					<?php if ( ! empty( $age ) ) : ?>
						<li class="canil-puppy-card__age"><?php echo esc_html( $age ); ?></li>
					<?php endif; ?>
				</ul>

				<?php if ( $show_price && ! empty( $price ) ) : ?>
					<div class="canil-puppy-card__price">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: price */
								__( 'R$ %s', 'canil-site-publico' ),
								number_format( (float) $price, 2, ',', '.' )
							)
						);
						?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $detail_url ) ) : ?>
					<a href="<?php echo esc_url( $detail_url ); ?>" class="canil-puppy-card__link">
						<?php esc_html_e( 'Ver mais', 'canil-site-publico' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}
}
