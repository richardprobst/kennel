<?php
/**
 * Public Puppy Service.
 *
 * Service for fetching publicly available puppy data.
 * Uses Canil Core's repositories while respecting public visibility rules.
 *
 * @package CanilSitePublico
 */

namespace CanilSitePublico\Domain;

use CanilCore\Infrastructure\Repositories\PuppyRepository;
use CanilCore\Infrastructure\Repositories\LitterRepository;
use CanilCore\Infrastructure\Repositories\DogRepository;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PublicPuppyService class.
 */
class PublicPuppyService {

	/**
	 * Puppy repository.
	 *
	 * @var PuppyRepository
	 */
	private PuppyRepository $puppy_repository;

	/**
	 * Litter repository.
	 *
	 * @var LitterRepository
	 */
	private LitterRepository $litter_repository;

	/**
	 * Dog repository.
	 *
	 * @var DogRepository
	 */
	private DogRepository $dog_repository;

	/**
	 * Statuses visible to public.
	 *
	 * @var array<string>
	 */
	private const PUBLIC_STATUSES = array( 'available', 'reserved' );

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->puppy_repository  = new PuppyRepository();
		$this->litter_repository = new LitterRepository();
		$this->dog_repository    = new DogRepository();
	}

	/**
	 * Get available puppies for public display.
	 *
	 * @param array<string, mixed> $filters Filters (status, breed, sex, color).
	 * @param int                  $limit   Maximum number of results.
	 * @return array<array<string, mixed>> Puppies data.
	 */
	public function get_available_puppies( array $filters = array(), int $limit = 100 ): array {
		// Build query filters.
		$query_filters = array();

		// Status filter (only allow public statuses).
		$status = $filters['status'] ?? 'available';
		if ( ! in_array( $status, self::PUBLIC_STATUSES, true ) ) {
			$status = 'available';
		}
		$query_filters['status'] = $status;

		// Additional filters.
		if ( ! empty( $filters['breed'] ) ) {
			$query_filters['breed'] = sanitize_text_field( $filters['breed'] );
		}
		if ( ! empty( $filters['sex'] ) && in_array( $filters['sex'], array( 'male', 'female' ), true ) ) {
			$query_filters['sex'] = $filters['sex'];
		}
		if ( ! empty( $filters['color'] ) ) {
			$query_filters['color'] = sanitize_text_field( $filters['color'] );
		}

		// Get puppies from repository.
		$result  = $this->puppy_repository->find_all( $query_filters, 1, $limit, 'created_at', 'DESC' );
		$puppies = $result['data'] ?? array();

		// Enrich with litter/parent data.
		$enriched = array();
		foreach ( $puppies as $puppy ) {
			$enriched[] = $this->enrich_puppy_data( $puppy );
		}

		return $enriched;
	}

	/**
	 * Get detailed puppy data for public display.
	 *
	 * @param int $puppy_id Puppy ID.
	 * @return array<string, mixed>|null Puppy data or null if not found/not public.
	 */
	public function get_puppy_detail( int $puppy_id ): ?array {
		$puppy = $this->puppy_repository->find_by_id( $puppy_id );

		if ( ! $puppy ) {
			return null;
		}

		// Check if status is publicly visible.
		$status = $puppy['status'] ?? '';
		if ( ! in_array( $status, array( 'available', 'reserved', 'sold' ), true ) ) {
			return null;
		}

		return $this->enrich_puppy_data( $puppy, true );
	}

	/**
	 * Get available breeds from current puppies.
	 *
	 * @return array<string> Unique breeds.
	 */
	public function get_available_breeds(): array {
		$puppies = $this->get_available_puppies( array(), 1000 );
		$breeds  = array();

		foreach ( $puppies as $puppy ) {
			$breed = $puppy['breed'] ?? '';
			if ( ! empty( $breed ) && ! in_array( $breed, $breeds, true ) ) {
				$breeds[] = $breed;
			}
		}

		sort( $breeds );
		return $breeds;
	}

	/**
	 * Get available colors from current puppies.
	 *
	 * @return array<string> Unique colors.
	 */
	public function get_available_colors(): array {
		$puppies = $this->get_available_puppies( array(), 1000 );
		$colors  = array();

		foreach ( $puppies as $puppy ) {
			$color = $puppy['color'] ?? '';
			if ( ! empty( $color ) && ! in_array( $color, $colors, true ) ) {
				$colors[] = $color;
			}
		}

		sort( $colors );
		return $colors;
	}

	/**
	 * Enrich puppy data with related information.
	 *
	 * @param array<string, mixed> $puppy       Raw puppy data.
	 * @param bool                 $include_full Include full parent details.
	 * @return array<string, mixed> Enriched data.
	 */
	private function enrich_puppy_data( array $puppy, bool $include_full = false ): array {
		$enriched = array(
			'id'         => (int) ( $puppy['id'] ?? 0 ),
			'identifier' => $puppy['identifier'] ?? '',
			'name'       => $puppy['name'] ?? $puppy['identifier'] ?? '',
			'sex'        => $puppy['sex'] ?? '',
			'color'      => $puppy['color'] ?? '',
			'status'     => $puppy['status'] ?? 'available',
			'photo_url'  => $this->get_puppy_photo( $puppy ),
			'birth_date' => null,
			'breed'      => '',
			'price'      => $puppy['price'] ?? null,
		);

		// Get litter for birth date and breed.
		$litter_id = (int) ( $puppy['litter_id'] ?? 0 );
		if ( $litter_id > 0 ) {
			$litter = $this->litter_repository->find_with_parents( $litter_id );
			if ( $litter ) {
				$enriched['birth_date'] = $litter['actual_birth_date'] ?? null;

				// Get breed from dam.
				if ( ! empty( $litter['dam_id'] ) ) {
					$dam = $this->dog_repository->find_by_id( (int) $litter['dam_id'] );
					if ( $dam ) {
						$enriched['breed'] = $dam['breed'] ?? '';

						if ( $include_full ) {
							$enriched['dam'] = $this->format_parent_data( $dam );
						}
					}
				}

				// Get sire.
				if ( $include_full && ! empty( $litter['sire_id'] ) ) {
					$sire = $this->dog_repository->find_by_id( (int) $litter['sire_id'] );
					if ( $sire ) {
						$enriched['sire'] = $this->format_parent_data( $sire );
					}
				}
			}
		}

		// Add full details for detail view.
		if ( $include_full ) {
			$enriched['photos']       = $this->get_puppy_gallery( $puppy );
			$enriched['public_notes'] = $puppy['public_notes'] ?? '';
		}

		return $enriched;
	}

	/**
	 * Get puppy main photo URL.
	 *
	 * @param array<string, mixed> $puppy Puppy data.
	 * @return string Photo URL or empty string.
	 */
	private function get_puppy_photo( array $puppy ): string {
		// Check for main photo.
		if ( ! empty( $puppy['photo_main_url'] ) ) {
			return $puppy['photo_main_url'];
		}

		// Check photos array.
		$photos = $puppy['photos'] ?? array();
		if ( is_string( $photos ) ) {
			$photos = json_decode( $photos, true ) ?? array();
		}
		if ( ! empty( $photos ) && is_array( $photos ) ) {
			return reset( $photos );
		}

		return '';
	}

	/**
	 * Get puppy gallery photos.
	 *
	 * @param array<string, mixed> $puppy Puppy data.
	 * @return array<string> Photo URLs.
	 */
	private function get_puppy_gallery( array $puppy ): array {
		$photos = $puppy['photos'] ?? array();
		if ( is_string( $photos ) ) {
			$photos = json_decode( $photos, true ) ?? array();
		}
		return is_array( $photos ) ? $photos : array();
	}

	/**
	 * Format parent dog data for public display.
	 *
	 * @param array<string, mixed> $dog Dog data.
	 * @return array<string, mixed> Formatted parent data.
	 */
	private function format_parent_data( array $dog ): array {
		return array(
			'id'             => (int) ( $dog['id'] ?? 0 ),
			'name'           => $dog['name'] ?? '',
			'breed'          => $dog['breed'] ?? '',
			'color'          => $dog['color'] ?? '',
			'photo_main_url' => $dog['photo_main_url'] ?? '',
		);
	}
}
