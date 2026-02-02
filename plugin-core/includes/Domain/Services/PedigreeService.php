<?php
/**
 * Pedigree Service.
 *
 * Handles pedigree/genealogy tree building for dogs.
 *
 * @package CanilCore
 */

namespace CanilCore\Domain\Services;

use CanilCore\Domain\Exceptions\NotFoundException;
use CanilCore\Infrastructure\Repositories\DogRepository;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PedigreeService class.
 */
class PedigreeService {

	/**
	 * Dog repository.
	 *
	 * @var DogRepository
	 */
	private DogRepository $dog_repository;

	/**
	 * Maximum generations to fetch.
	 */
	private const MAX_GENERATIONS = 5;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->dog_repository = new DogRepository();
	}

	/**
	 * Get pedigree tree for a dog.
	 *
	 * @param int $dog_id     Dog ID.
	 * @param int $generations Number of generations (3-5).
	 * @return array{dog: array<string, mixed>, pedigree: array<string, mixed>, generations: int}
	 * @throws NotFoundException If dog not found.
	 */
	public function get_pedigree( int $dog_id, int $generations = 3 ): array {
		// Validate generations limit.
		$generations = max( 1, min( $generations, self::MAX_GENERATIONS ) );

		// Get the dog.
		$dog = $this->dog_repository->find_by_id( $dog_id );
		if ( ! $dog ) {
			throw new NotFoundException( __( 'Cão não encontrado.', 'canil-core' ) );
		}

		// Build pedigree tree.
		$pedigree = $this->build_pedigree_tree( $dog, $generations, 1 );

		/**
		 * Fires after pedigree is built.
		 *
		 * @param array $dog        Dog data.
		 * @param array $pedigree   Pedigree tree.
		 * @param int   $generations Number of generations.
		 */
		do_action( 'canil_core_pedigree_built', $dog, $pedigree, $generations );

		return array(
			'dog'         => $this->format_dog_data( $dog ),
			'pedigree'    => $pedigree,
			'generations' => $generations,
		);
	}

	/**
	 * Build pedigree tree recursively.
	 *
	 * @param array<string, mixed> $dog        Dog data.
	 * @param int                  $max_gen    Maximum generations.
	 * @param int                  $current_gen Current generation.
	 * @return array<string, mixed> Pedigree tree.
	 */
	private function build_pedigree_tree( array $dog, int $max_gen, int $current_gen ): array {
		$tree = array(
			'sire' => null,
			'dam'  => null,
		);

		if ( $current_gen >= $max_gen ) {
			return $tree;
		}

		// Get sire (father).
		if ( ! empty( $dog['sire_id'] ) ) {
			$sire = $this->dog_repository->find_by_id( (int) $dog['sire_id'] );
			if ( $sire ) {
				$tree['sire'] = array(
					'dog'      => $this->format_dog_data( $sire ),
					'pedigree' => $this->build_pedigree_tree( $sire, $max_gen, $current_gen + 1 ),
				);
			} else {
				$tree['sire'] = array(
					'dog'      => $this->create_unknown_ancestor( 'male' ),
					'pedigree' => array(
						'sire' => null,
						'dam'  => null,
					),
				);
			}
		}

		// Get dam (mother).
		if ( ! empty( $dog['dam_id'] ) ) {
			$dam = $this->dog_repository->find_by_id( (int) $dog['dam_id'] );
			if ( $dam ) {
				$tree['dam'] = array(
					'dog'      => $this->format_dog_data( $dam ),
					'pedigree' => $this->build_pedigree_tree( $dam, $max_gen, $current_gen + 1 ),
				);
			} else {
				$tree['dam'] = array(
					'dog'      => $this->create_unknown_ancestor( 'female' ),
					'pedigree' => array(
						'sire' => null,
						'dam'  => null,
					),
				);
			}
		}

		return $tree;
	}

	/**
	 * Format dog data for pedigree display.
	 *
	 * @param array<string, mixed> $dog Dog data.
	 * @return array<string, mixed> Formatted dog data.
	 */
	private function format_dog_data( array $dog ): array {
		return array(
			'id'                  => $dog['id'] ?? null,
			'name'                => $dog['name'] ?? '',
			'call_name'           => $dog['call_name'] ?? null,
			'registration_number' => $dog['registration_number'] ?? null,
			'breed'               => $dog['breed'] ?? '',
			'color'               => $dog['color'] ?? null,
			'sex'                 => $dog['sex'] ?? null,
			'birth_date'          => $dog['birth_date'] ?? null,
			'photo_main_url'      => $dog['photo_main_url'] ?? null,
			'titles'              => $dog['titles'] ?? array(),
			'health_tests'        => $dog['health_tests'] ?? array(),
			'sire_id'             => $dog['sire_id'] ?? null,
			'dam_id'              => $dog['dam_id'] ?? null,
		);
	}

	/**
	 * Create unknown ancestor placeholder.
	 *
	 * @param string $sex Ancestor sex.
	 * @return array<string, mixed> Unknown ancestor data.
	 */
	private function create_unknown_ancestor( string $sex ): array {
		return array(
			'id'                  => null,
			'name'                => __( 'Desconhecido', 'canil-core' ),
			'call_name'           => null,
			'registration_number' => null,
			'breed'               => null,
			'color'               => null,
			'sex'                 => $sex,
			'birth_date'          => null,
			'photo_main_url'      => null,
			'titles'              => array(),
			'health_tests'        => array(),
			'sire_id'             => null,
			'dam_id'              => null,
		);
	}

	/**
	 * Get pedigree as flat list (for PDF export).
	 *
	 * @param int $dog_id     Dog ID.
	 * @param int $generations Number of generations.
	 * @return array{dog: array<string, mixed>, ancestors: array<string, array<string, mixed>>, generation_labels: array<int, string>}
	 * @throws NotFoundException If dog not found.
	 */
	public function get_pedigree_flat( int $dog_id, int $generations = 3 ): array {
		$pedigree  = $this->get_pedigree( $dog_id, $generations );
		$ancestors = array();

		$this->flatten_pedigree( $pedigree['pedigree'], $ancestors, 1 );

		$generation_labels = array(
			1 => __( 'Pais', 'canil-core' ),
			2 => __( 'Avós', 'canil-core' ),
			3 => __( 'Bisavós', 'canil-core' ),
			4 => __( 'Trisavós', 'canil-core' ),
			5 => __( 'Tetravós', 'canil-core' ),
		);

		return array(
			'dog'               => $pedigree['dog'],
			'ancestors'         => $ancestors,
			'generation_labels' => $generation_labels,
		);
	}

	/**
	 * Flatten pedigree tree into a list.
	 *
	 * @param array<string, mixed>                 $tree       Pedigree tree.
	 * @param array<string, array<string, mixed>>& $ancestors  Ancestors list (modified by reference).
	 * @param int                                  $generation Current generation.
	 * @param string                               $position   Position in tree (S, D, SS, SD, DS, DD, etc.).
	 */
	private function flatten_pedigree(
		array $tree,
		array &$ancestors,
		int $generation,
		string $position = ''
	): void {
		// Process sire.
		if ( ! empty( $tree['sire'] ) ) {
			$sire_position               = $position . 'S';
			$ancestors[ $sire_position ] = array_merge(
				$tree['sire']['dog'],
				array(
					'generation' => $generation,
					'position'   => $sire_position,
					'role'       => $this->get_ancestor_role( $generation, 'male' ),
				)
			);

			if ( ! empty( $tree['sire']['pedigree'] ) ) {
				$this->flatten_pedigree(
					$tree['sire']['pedigree'],
					$ancestors,
					$generation + 1,
					$sire_position
				);
			}
		}

		// Process dam.
		if ( ! empty( $tree['dam'] ) ) {
			$dam_position               = $position . 'D';
			$ancestors[ $dam_position ] = array_merge(
				$tree['dam']['dog'],
				array(
					'generation' => $generation,
					'position'   => $dam_position,
					'role'       => $this->get_ancestor_role( $generation, 'female' ),
				)
			);

			if ( ! empty( $tree['dam']['pedigree'] ) ) {
				$this->flatten_pedigree(
					$tree['dam']['pedigree'],
					$ancestors,
					$generation + 1,
					$dam_position
				);
			}
		}
	}

	/**
	 * Get ancestor role label.
	 *
	 * @param int    $generation Generation number.
	 * @param string $sex        Ancestor sex.
	 * @return string Role label.
	 */
	private function get_ancestor_role( int $generation, string $sex ): string {
		$male_roles = array(
			1 => __( 'Pai', 'canil-core' ),
			2 => __( 'Avô', 'canil-core' ),
			3 => __( 'Bisavô', 'canil-core' ),
			4 => __( 'Trisavô', 'canil-core' ),
			5 => __( 'Tetravô', 'canil-core' ),
		);

		$female_roles = array(
			1 => __( 'Mãe', 'canil-core' ),
			2 => __( 'Avó', 'canil-core' ),
			3 => __( 'Bisavó', 'canil-core' ),
			4 => __( 'Trisavó', 'canil-core' ),
			5 => __( 'Tetravó', 'canil-core' ),
		);

		$roles = 'male' === $sex ? $male_roles : $female_roles;

		return $roles[ $generation ] ?? __( 'Ancestral', 'canil-core' );
	}

	/**
	 * Get offspring of a dog.
	 *
	 * @param int $dog_id Dog ID.
	 * @return array<array<string, mixed>> Offspring data.
	 * @throws NotFoundException If dog not found.
	 */
	public function get_offspring( int $dog_id ): array {
		$dog = $this->dog_repository->find_by_id( $dog_id );
		if ( ! $dog ) {
			throw new NotFoundException( __( 'Cão não encontrado.', 'canil-core' ) );
		}

		$field = 'male' === $dog['sex'] ? 'sire_id' : 'dam_id';

		$result = $this->dog_repository->find_all(
			array( $field => $dog_id ),
			1,
			100,
			'birth_date',
			'DESC'
		);

		return $result['data'];
	}

	/**
	 * Get siblings of a dog.
	 *
	 * @param int  $dog_id      Dog ID.
	 * @param bool $full_siblings Only full siblings (same sire and dam).
	 * @return array<array<string, mixed>> Siblings data.
	 * @throws NotFoundException If dog not found.
	 */
	public function get_siblings( int $dog_id, bool $full_siblings = true ): array {
		$dog = $this->dog_repository->find_by_id( $dog_id );
		if ( ! $dog ) {
			throw new NotFoundException( __( 'Cão não encontrado.', 'canil-core' ) );
		}

		$siblings = array();

		// Get siblings through sire.
		if ( ! empty( $dog['sire_id'] ) ) {
			$sire_children = $this->dog_repository->find_all(
				array( 'sire_id' => $dog['sire_id'] ),
				1,
				100
			);

			foreach ( $sire_children['data'] as $child ) {
				if ( (int) $child['id'] !== $dog_id ) {
					if ( $full_siblings ) {
						// Only add if same dam.
						if ( ! empty( $dog['dam_id'] ) && (int) ( $child['dam_id'] ?? 0 ) === (int) $dog['dam_id'] ) {
							$siblings[ $child['id'] ] = $child;
						}
					} else {
						$siblings[ $child['id'] ] = $child;
					}
				}
			}
		}

		// Get siblings through dam (if not full siblings only).
		if ( ! $full_siblings && ! empty( $dog['dam_id'] ) ) {
			$dam_children = $this->dog_repository->find_all(
				array( 'dam_id' => $dog['dam_id'] ),
				1,
				100
			);

			foreach ( $dam_children['data'] as $child ) {
				if ( (int) $child['id'] !== $dog_id ) {
					$siblings[ $child['id'] ] = $child;
				}
			}
		}

		return array_values( $siblings );
	}
}
