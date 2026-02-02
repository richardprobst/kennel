<?php
/**
 * Dog entity.
 *
 * Represents a dog in the kennel.
 *
 * @package CanilCore
 */

namespace CanilCore\Domain\Entities;

use CanilCore\Helpers\DateHelper;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dog class.
 */
class Dog extends BaseEntity {

	/**
	 * Dog status constants.
	 */
	public const STATUS_ACTIVE   = 'active';
	public const STATUS_BREEDING = 'breeding';
	public const STATUS_RETIRED  = 'retired';
	public const STATUS_SOLD     = 'sold';
	public const STATUS_DECEASED = 'deceased';
	public const STATUS_COOWNED  = 'coowned';

	/**
	 * Dog sex constants.
	 */
	public const SEX_MALE   = 'male';
	public const SEX_FEMALE = 'female';

	/**
	 * Dog name.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * Call name (nickname).
	 *
	 * @var string|null
	 */
	private ?string $call_name = null;

	/**
	 * Registration number (CBKC, AKC, etc.).
	 *
	 * @var string|null
	 */
	private ?string $registration_number = null;

	/**
	 * Microchip number.
	 *
	 * @var string|null
	 */
	private ?string $chip_number = null;

	/**
	 * Tattoo number.
	 *
	 * @var string|null
	 */
	private ?string $tattoo = null;

	/**
	 * Breed.
	 *
	 * @var string
	 */
	private string $breed;

	/**
	 * Variety (coat, size).
	 *
	 * @var string|null
	 */
	private ?string $variety = null;

	/**
	 * Color.
	 *
	 * @var string|null
	 */
	private ?string $color = null;

	/**
	 * Markings.
	 *
	 * @var string|null
	 */
	private ?string $markings = null;

	/**
	 * Birth date.
	 *
	 * @var \DateTimeImmutable
	 */
	private \DateTimeImmutable $birth_date;

	/**
	 * Death date.
	 *
	 * @var \DateTimeImmutable|null
	 */
	private ?\DateTimeImmutable $death_date = null;

	/**
	 * Sex (male/female).
	 *
	 * @var string
	 */
	private string $sex;

	/**
	 * Status.
	 *
	 * @var string
	 */
	private string $status = self::STATUS_ACTIVE;

	/**
	 * Sire (father) ID.
	 *
	 * @var int|null
	 */
	private ?int $sire_id = null;

	/**
	 * Dam (mother) ID.
	 *
	 * @var int|null
	 */
	private ?int $dam_id = null;

	/**
	 * Main photo URL.
	 *
	 * @var string|null
	 */
	private ?string $photo_main_url = null;

	/**
	 * Additional photos.
	 *
	 * @var array<array{url: string, caption?: string, order?: int}>
	 */
	private array $photos = array();

	/**
	 * Titles.
	 *
	 * @var array<array{title: string, organization: string, date: string}>
	 */
	private array $titles = array();

	/**
	 * Health tests.
	 *
	 * @var array<array{test: string, result: string, date: string, lab?: string}>
	 */
	private array $health_tests = array();

	/**
	 * Notes.
	 *
	 * @var string|null
	 */
	private ?string $notes = null;

	// Getters and setters.

	/**
	 * Get name.
	 *
	 * @return string Dog name.
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Set name.
	 *
	 * @param string $name Dog name.
	 * @return self
	 */
	public function set_name( string $name ): self {
		$this->name = $name;
		return $this;
	}

	/**
	 * Get call name.
	 *
	 * @return string|null Call name.
	 */
	public function get_call_name(): ?string {
		return $this->call_name;
	}

	/**
	 * Set call name.
	 *
	 * @param string|null $call_name Call name.
	 * @return self
	 */
	public function set_call_name( ?string $call_name ): self {
		$this->call_name = $call_name;
		return $this;
	}

	/**
	 * Get registration number.
	 *
	 * @return string|null Registration number.
	 */
	public function get_registration_number(): ?string {
		return $this->registration_number;
	}

	/**
	 * Set registration number.
	 *
	 * @param string|null $registration_number Registration number.
	 * @return self
	 */
	public function set_registration_number( ?string $registration_number ): self {
		$this->registration_number = $registration_number;
		return $this;
	}

	/**
	 * Get chip number.
	 *
	 * @return string|null Chip number.
	 */
	public function get_chip_number(): ?string {
		return $this->chip_number;
	}

	/**
	 * Set chip number.
	 *
	 * @param string|null $chip_number Chip number.
	 * @return self
	 */
	public function set_chip_number( ?string $chip_number ): self {
		$this->chip_number = $chip_number;
		return $this;
	}

	/**
	 * Get tattoo.
	 *
	 * @return string|null Tattoo.
	 */
	public function get_tattoo(): ?string {
		return $this->tattoo;
	}

	/**
	 * Set tattoo.
	 *
	 * @param string|null $tattoo Tattoo.
	 * @return self
	 */
	public function set_tattoo( ?string $tattoo ): self {
		$this->tattoo = $tattoo;
		return $this;
	}

	/**
	 * Get breed.
	 *
	 * @return string Breed.
	 */
	public function get_breed(): string {
		return $this->breed;
	}

	/**
	 * Set breed.
	 *
	 * @param string $breed Breed.
	 * @return self
	 */
	public function set_breed( string $breed ): self {
		$this->breed = $breed;
		return $this;
	}

	/**
	 * Get variety.
	 *
	 * @return string|null Variety.
	 */
	public function get_variety(): ?string {
		return $this->variety;
	}

	/**
	 * Set variety.
	 *
	 * @param string|null $variety Variety.
	 * @return self
	 */
	public function set_variety( ?string $variety ): self {
		$this->variety = $variety;
		return $this;
	}

	/**
	 * Get color.
	 *
	 * @return string|null Color.
	 */
	public function get_color(): ?string {
		return $this->color;
	}

	/**
	 * Set color.
	 *
	 * @param string|null $color Color.
	 * @return self
	 */
	public function set_color( ?string $color ): self {
		$this->color = $color;
		return $this;
	}

	/**
	 * Get markings.
	 *
	 * @return string|null Markings.
	 */
	public function get_markings(): ?string {
		return $this->markings;
	}

	/**
	 * Set markings.
	 *
	 * @param string|null $markings Markings.
	 * @return self
	 */
	public function set_markings( ?string $markings ): self {
		$this->markings = $markings;
		return $this;
	}

	/**
	 * Get birth date.
	 *
	 * @return \DateTimeImmutable Birth date.
	 */
	public function get_birth_date(): \DateTimeImmutable {
		return $this->birth_date;
	}

	/**
	 * Set birth date.
	 *
	 * @param \DateTimeImmutable $birth_date Birth date.
	 * @return self
	 */
	public function set_birth_date( \DateTimeImmutable $birth_date ): self {
		$this->birth_date = $birth_date;
		return $this;
	}

	/**
	 * Get death date.
	 *
	 * @return \DateTimeImmutable|null Death date.
	 */
	public function get_death_date(): ?\DateTimeImmutable {
		return $this->death_date;
	}

	/**
	 * Set death date.
	 *
	 * @param \DateTimeImmutable|null $death_date Death date.
	 * @return self
	 */
	public function set_death_date( ?\DateTimeImmutable $death_date ): self {
		$this->death_date = $death_date;
		return $this;
	}

	/**
	 * Get sex.
	 *
	 * @return string Sex.
	 */
	public function get_sex(): string {
		return $this->sex;
	}

	/**
	 * Set sex.
	 *
	 * @param string $sex Sex.
	 * @return self
	 */
	public function set_sex( string $sex ): self {
		$this->sex = $sex;
		return $this;
	}

	/**
	 * Check if dog is male.
	 *
	 * @return bool True if male.
	 */
	public function is_male(): bool {
		return self::SEX_MALE === $this->sex;
	}

	/**
	 * Check if dog is female.
	 *
	 * @return bool True if female.
	 */
	public function is_female(): bool {
		return self::SEX_FEMALE === $this->sex;
	}

	/**
	 * Get status.
	 *
	 * @return string Status.
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Set status.
	 *
	 * @param string $status Status.
	 * @return self
	 */
	public function set_status( string $status ): self {
		$this->status = $status;
		return $this;
	}

	/**
	 * Get sire (father) ID.
	 *
	 * @return int|null Sire ID.
	 */
	public function get_sire_id(): ?int {
		return $this->sire_id;
	}

	/**
	 * Set sire (father) ID.
	 *
	 * @param int|null $sire_id Sire ID.
	 * @return self
	 */
	public function set_sire_id( ?int $sire_id ): self {
		$this->sire_id = $sire_id;
		return $this;
	}

	/**
	 * Get dam (mother) ID.
	 *
	 * @return int|null Dam ID.
	 */
	public function get_dam_id(): ?int {
		return $this->dam_id;
	}

	/**
	 * Set dam (mother) ID.
	 *
	 * @param int|null $dam_id Dam ID.
	 * @return self
	 */
	public function set_dam_id( ?int $dam_id ): self {
		$this->dam_id = $dam_id;
		return $this;
	}

	/**
	 * Get main photo URL.
	 *
	 * @return string|null Photo URL.
	 */
	public function get_photo_main_url(): ?string {
		return $this->photo_main_url;
	}

	/**
	 * Set main photo URL.
	 *
	 * @param string|null $photo_main_url Photo URL.
	 * @return self
	 */
	public function set_photo_main_url( ?string $photo_main_url ): self {
		$this->photo_main_url = $photo_main_url;
		return $this;
	}

	/**
	 * Get photos.
	 *
	 * @return array<array{url: string, caption?: string, order?: int}> Photos.
	 */
	public function get_photos(): array {
		return $this->photos;
	}

	/**
	 * Set photos.
	 *
	 * @param array<array{url: string, caption?: string, order?: int}> $photos Photos.
	 * @return self
	 */
	public function set_photos( array $photos ): self {
		$this->photos = $photos;
		return $this;
	}

	/**
	 * Get titles.
	 *
	 * @return array<array{title: string, organization: string, date: string}> Titles.
	 */
	public function get_titles(): array {
		return $this->titles;
	}

	/**
	 * Set titles.
	 *
	 * @param array<array{title: string, organization: string, date: string}> $titles Titles.
	 * @return self
	 */
	public function set_titles( array $titles ): self {
		$this->titles = $titles;
		return $this;
	}

	/**
	 * Get health tests.
	 *
	 * @return array<array{test: string, result: string, date: string, lab?: string}> Health tests.
	 */
	public function get_health_tests(): array {
		return $this->health_tests;
	}

	/**
	 * Set health tests.
	 *
	 * @param array<array{test: string, result: string, date: string, lab?: string}> $health_tests Health tests.
	 * @return self
	 */
	public function set_health_tests( array $health_tests ): self {
		$this->health_tests = $health_tests;
		return $this;
	}

	/**
	 * Get notes.
	 *
	 * @return string|null Notes.
	 */
	public function get_notes(): ?string {
		return $this->notes;
	}

	/**
	 * Set notes.
	 *
	 * @param string|null $notes Notes.
	 * @return self
	 */
	public function set_notes( ?string $notes ): self {
		$this->notes = $notes;
		return $this;
	}

	/**
	 * Get age as formatted string.
	 *
	 * @return string Formatted age.
	 */
	public function get_age(): string {
		return DateHelper::format_age( $this->birth_date );
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed> Entity data.
	 */
	public function to_array(): array {
		return array(
			'id'                  => $this->id,
			'tenant_id'           => $this->tenant_id,
			'name'                => $this->name,
			'call_name'           => $this->call_name,
			'registration_number' => $this->registration_number,
			'chip_number'         => $this->chip_number,
			'tattoo'              => $this->tattoo,
			'breed'               => $this->breed,
			'variety'             => $this->variety,
			'color'               => $this->color,
			'markings'            => $this->markings,
			'birth_date'          => $this->birth_date->format( 'Y-m-d' ),
			'death_date'          => $this->death_date?->format( 'Y-m-d' ),
			'sex'                 => $this->sex,
			'status'              => $this->status,
			'sire_id'             => $this->sire_id,
			'dam_id'              => $this->dam_id,
			'photo_main_url'      => $this->photo_main_url,
			'photos'              => $this->photos,
			'titles'              => $this->titles,
			'health_tests'        => $this->health_tests,
			'notes'               => $this->notes,
			'created_at'          => $this->created_at?->format( 'Y-m-d H:i:s' ),
			'updated_at'          => $this->updated_at?->format( 'Y-m-d H:i:s' ),
			'deleted_at'          => $this->deleted_at?->format( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Create from array.
	 *
	 * @param array<string, mixed> $data Entity data.
	 * @return static New entity instance.
	 */
	public static function from_array( array $data ): static {
		$entity = new static();

		if ( isset( $data['id'] ) ) {
			$entity->set_id( (int) $data['id'] );
		}

		$entity->set_tenant_id( (int) ( $data['tenant_id'] ?? 0 ) );
		$entity->set_name( $data['name'] ?? '' );
		$entity->set_call_name( $data['call_name'] ?? null );
		$entity->set_registration_number( $data['registration_number'] ?? null );
		$entity->set_chip_number( $data['chip_number'] ?? null );
		$entity->set_tattoo( $data['tattoo'] ?? null );
		$entity->set_breed( $data['breed'] ?? '' );
		$entity->set_variety( $data['variety'] ?? null );
		$entity->set_color( $data['color'] ?? null );
		$entity->set_markings( $data['markings'] ?? null );
		$entity->set_sex( $data['sex'] ?? self::SEX_MALE );
		$entity->set_status( $data['status'] ?? self::STATUS_ACTIVE );
		$entity->set_sire_id( isset( $data['sire_id'] ) ? (int) $data['sire_id'] : null );
		$entity->set_dam_id( isset( $data['dam_id'] ) ? (int) $data['dam_id'] : null );
		$entity->set_photo_main_url( $data['photo_main_url'] ?? null );
		$entity->set_notes( $data['notes'] ?? null );

		// Handle arrays/JSON.
		$entity->set_photos( is_array( $data['photos'] ?? null ) ? $data['photos'] : array() );
		$entity->set_titles( is_array( $data['titles'] ?? null ) ? $data['titles'] : array() );
		$entity->set_health_tests( is_array( $data['health_tests'] ?? null ) ? $data['health_tests'] : array() );

		// Handle dates.
		$birth_date = DateHelper::parse_iso( $data['birth_date'] ?? null );
		if ( $birth_date ) {
			$entity->set_birth_date( $birth_date );
		}

		$death_date = DateHelper::parse_iso( $data['death_date'] ?? null );
		$entity->set_death_date( $death_date );

		$created_at = DateHelper::parse_iso( $data['created_at'] ?? null );
		$entity->set_created_at( $created_at );

		$updated_at = DateHelper::parse_iso( $data['updated_at'] ?? null );
		$entity->set_updated_at( $updated_at );

		$deleted_at = DateHelper::parse_iso( $data['deleted_at'] ?? null );
		$entity->set_deleted_at( $deleted_at );

		return $entity;
	}

	/**
	 * Get allowed statuses.
	 *
	 * @return array<string> Allowed status values.
	 */
	public static function get_allowed_statuses(): array {
		return array(
			self::STATUS_ACTIVE,
			self::STATUS_BREEDING,
			self::STATUS_RETIRED,
			self::STATUS_SOLD,
			self::STATUS_DECEASED,
			self::STATUS_COOWNED,
		);
	}

	/**
	 * Get allowed sexes.
	 *
	 * @return array<string> Allowed sex values.
	 */
	public static function get_allowed_sexes(): array {
		return array(
			self::SEX_MALE,
			self::SEX_FEMALE,
		);
	}
}
