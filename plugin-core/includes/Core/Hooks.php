<?php
/**
 * Plugin hooks.
 *
 * Registers extensibility hooks for add-ons.
 *
 * @package CanilCore
 */

namespace CanilCore\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks class.
 */
class Hooks {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_filters();
	}

	/**
	 * Register filters for extensibility.
	 */
	private function register_filters(): void {
		// Event types filter.
		add_filter( 'canil_core_event_types', array( $this, 'get_default_event_types' ) );

		// Dog statuses filter.
		add_filter( 'canil_core_dog_statuses', array( $this, 'get_default_dog_statuses' ) );

		// Litter statuses filter.
		add_filter( 'canil_core_litter_statuses', array( $this, 'get_default_litter_statuses' ) );

		// Puppy statuses filter.
		add_filter( 'canil_core_puppy_statuses', array( $this, 'get_default_puppy_statuses' ) );

		// Person types filter.
		add_filter( 'canil_core_person_types', array( $this, 'get_default_person_types' ) );
	}

	/**
	 * Get default event types.
	 *
	 * @param array<string, string> $types Existing types.
	 * @return array<string, string> Event types.
	 */
	public function get_default_event_types( array $types ): array {
		$defaults = array(
			// Reproduction.
			'heat'             => __( 'Cio', 'canil-core' ),
			'mating'           => __( 'Cobertura/Inseminação', 'canil-core' ),
			'pregnancy_test'   => __( 'Teste de Gestação', 'canil-core' ),
			'birth'            => __( 'Nascimento', 'canil-core' ),

			// Health.
			'vaccine'          => __( 'Vacina', 'canil-core' ),
			'deworming'        => __( 'Vermífugo', 'canil-core' ),
			'exam'             => __( 'Exame', 'canil-core' ),
			'medication'       => __( 'Medicação', 'canil-core' ),
			'surgery'          => __( 'Cirurgia', 'canil-core' ),
			'vet_visit'        => __( 'Consulta Veterinária', 'canil-core' ),

			// Weighing.
			'weighing'         => __( 'Pesagem', 'canil-core' ),

			// Other.
			'grooming'         => __( 'Banho/Tosa', 'canil-core' ),
			'training'         => __( 'Treino', 'canil-core' ),
			'show'             => __( 'Exposição', 'canil-core' ),
			'note'             => __( 'Anotação', 'canil-core' ),
		);

		return array_merge( $defaults, $types );
	}

	/**
	 * Get default dog statuses.
	 *
	 * @param array<string, string> $statuses Existing statuses.
	 * @return array<string, string> Dog statuses.
	 */
	public function get_default_dog_statuses( array $statuses ): array {
		$defaults = array(
			'active'   => __( 'Ativo', 'canil-core' ),
			'breeding' => __( 'Reprodutor(a)', 'canil-core' ),
			'retired'  => __( 'Aposentado', 'canil-core' ),
			'sold'     => __( 'Vendido', 'canil-core' ),
			'deceased' => __( 'Falecido', 'canil-core' ),
			'coowned'  => __( 'Co-propriedade', 'canil-core' ),
		);

		return array_merge( $defaults, $statuses );
	}

	/**
	 * Get default litter statuses.
	 *
	 * @param array<string, string> $statuses Existing statuses.
	 * @return array<string, string> Litter statuses.
	 */
	public function get_default_litter_statuses( array $statuses ): array {
		$defaults = array(
			'planned'   => __( 'Planejada', 'canil-core' ),
			'confirmed' => __( 'Confirmada', 'canil-core' ),
			'pregnant'  => __( 'Em Gestação', 'canil-core' ),
			'born'      => __( 'Nascida', 'canil-core' ),
			'weaned'    => __( 'Desmamada', 'canil-core' ),
			'closed'    => __( 'Encerrada', 'canil-core' ),
			'cancelled' => __( 'Cancelada', 'canil-core' ),
		);

		return array_merge( $defaults, $statuses );
	}

	/**
	 * Get default puppy statuses.
	 *
	 * @param array<string, string> $statuses Existing statuses.
	 * @return array<string, string> Puppy statuses.
	 */
	public function get_default_puppy_statuses( array $statuses ): array {
		$defaults = array(
			'available' => __( 'Disponível', 'canil-core' ),
			'reserved'  => __( 'Reservado', 'canil-core' ),
			'sold'      => __( 'Vendido', 'canil-core' ),
			'retained'  => __( 'Retido', 'canil-core' ),
			'deceased'  => __( 'Falecido', 'canil-core' ),
			'returned'  => __( 'Devolvido', 'canil-core' ),
		);

		return array_merge( $defaults, $statuses );
	}

	/**
	 * Get default person types.
	 *
	 * @param array<string, string> $types Existing types.
	 * @return array<string, string> Person types.
	 */
	public function get_default_person_types( array $types ): array {
		$defaults = array(
			'interested'   => __( 'Interessado', 'canil-core' ),
			'buyer'        => __( 'Comprador', 'canil-core' ),
			'veterinarian' => __( 'Veterinário', 'canil-core' ),
			'handler'      => __( 'Handler', 'canil-core' ),
			'partner'      => __( 'Parceiro', 'canil-core' ),
			'other'        => __( 'Outro', 'canil-core' ),
		);

		return array_merge( $defaults, $types );
	}
}
