<?php
/**
 * Litter entity.
 *
 * Represents a litter in the kennel.
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
 * Litter class.
 */
class Litter extends BaseEntity {

	/**
	 * Litter status constants.
	 */
	public const STATUS_PLANNED   = 'planned';
	public const STATUS_CONFIRMED = 'confirmed';
	public const STATUS_PREGNANT  = 'pregnant';
	public const STATUS_BORN      = 'born';
	public const STATUS_WEANED    = 'weaned';
	public const STATUS_CLOSED    = 'closed';
	public const STATUS_CANCELLED = 'cancelled';

	/**
	 * Mating type constants.
	 */
	public const MATING_NATURAL           = 'natural';
	public const MATING_ARTIFICIAL_FRESH  = 'artificial_fresh';
	public const MATING_ARTIFICIAL_FROZEN = 'artificial_frozen';

	/**
	 * Birth type constants.
	 */
	public const BIRTH_NATURAL  = 'natural';
	public const BIRTH_CESAREAN = 'cesarean';
	public const BIRTH_ASSISTED = 'assisted';

	/**
	 * Litter name.
	 *
	 * @var string|null
	 */
	private ?string $name = null;

	/**
	 * Litter letter.
	 *
	 * @var string|null
	 */
	private ?string $litter_letter = null;

	/**
	 * Dam (mother) ID.
	 *
	 * @var int
	 */
	private int $dam_id;

	/**
	 * Sire (father) ID.
	 *
	 * @var int
	 */
	private int $sire_id;

	/**
	 * Status.
	 *
	 * @var string
	 */
	private string $status = self::STATUS_PLANNED;

	/**
	 * Heat start date.
	 *
	 * @var \DateTimeImmutable|null
	 */
	private ?\DateTimeImmutable $heat_start_date = null;

	/**
	 * Mating date.
	 *
	 * @var \DateTimeImmutable|null
	 */
	private ?\DateTimeImmutable $mating_date = null;

	/**
	 * Mating type.
	 *
	 * @var string|null
	 */
	private ?string $mating_type = null;

	/**
	 * Pregnancy confirmed date.
	 *
	 * @var \DateTimeImmutable|null
	 */
	private ?\DateTimeImmutable $pregnancy_confirmed_date = null;

	/**
	 * Expected birth date.
	 *
	 * @var \DateTimeImmutable|null
	 */
	private ?\DateTimeImmutable $expected_birth_date = null;

	/**
	 * Actual birth date.
	 *
	 * @var \DateTimeImmutable|null
	 */
	private ?\DateTimeImmutable $actual_birth_date = null;

	/**
	 * Birth type.
	 *
	 * @var string|null
	 */
	private ?string $birth_type = null;

	/**
	 * Puppies born count.
	 *
	 * @var int
	 */
	private int $puppies_born_count = 0;

	/**
	 * Puppies alive count.
	 *
	 * @var int
	 */
	private int $puppies_alive_count = 0;

	/**
	 * Males count.
	 *
	 * @var int
	 */
	private int $males_count = 0;

	/**
	 * Females count.
	 *
	 * @var int
	 */
	private int $females_count = 0;

	/**
	 * Veterinarian ID.
	 *
	 * @var int|null
	 */
	private ?int $veterinarian_id = null;

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
	 * @return string|null Litter name.
	 */
	public function get_name(): ?string {
		return $this->name;
	}

	/**
	 * Set name.
	 *
	 * @param string|null $name Litter name.
	 * @return self
	 */
	public function set_name( ?string $name ): self {
		$this->name = $name;
		return $this;
	}

	/**
	 * Get litter letter.
	 *
	 * @return string|null Litter letter.
	 */
	public function get_litter_letter(): ?string {
		return $this->litter_letter;
	}

	/**
	 * Set litter letter.
	 *
	 * @param string|null $litter_letter Litter letter.
	 * @return self
	 */
	public function set_litter_letter( ?string $litter_letter ): self {
		$this->litter_letter = $litter_letter;
		return $this;
	}

	/**
	 * Get dam ID.
	 *
	 * @return int Dam ID.
	 */
	public function get_dam_id(): int {
		return $this->dam_id;
	}

	/**
	 * Set dam ID.
	 *
	 * @param int $dam_id Dam ID.
	 * @return self
	 */
	public function set_dam_id( int $dam_id ): self {
		$this->dam_id = $dam_id;
		return $this;
	}

	/**
	 * Get sire ID.
	 *
	 * @return int Sire ID.
	 */
	public function get_sire_id(): int {
		return $this->sire_id;
	}

	/**
	 * Set sire ID.
	 *
	 * @param int $sire_id Sire ID.
	 * @return self
	 */
	public function set_sire_id( int $sire_id ): self {
		$this->sire_id = $sire_id;
		return $this;
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
	 * Get heat start date.
	 *
	 * @return \DateTimeImmutable|null Heat start date.
	 */
	public function get_heat_start_date(): ?\DateTimeImmutable {
		return $this->heat_start_date;
	}

	/**
	 * Set heat start date.
	 *
	 * @param \DateTimeImmutable|null $heat_start_date Heat start date.
	 * @return self
	 */
	public function set_heat_start_date( ?\DateTimeImmutable $heat_start_date ): self {
		$this->heat_start_date = $heat_start_date;
		return $this;
	}

	/**
	 * Get mating date.
	 *
	 * @return \DateTimeImmutable|null Mating date.
	 */
	public function get_mating_date(): ?\DateTimeImmutable {
		return $this->mating_date;
	}

	/**
	 * Set mating date.
	 *
	 * @param \DateTimeImmutable|null $mating_date Mating date.
	 * @return self
	 */
	public function set_mating_date( ?\DateTimeImmutable $mating_date ): self {
		$this->mating_date = $mating_date;

		// Auto-calculate expected birth date.
		if ( null !== $mating_date ) {
			$this->expected_birth_date = DateHelper::calculate_expected_birth( $mating_date );
		}

		return $this;
	}

	/**
	 * Get mating type.
	 *
	 * @return string|null Mating type.
	 */
	public function get_mating_type(): ?string {
		return $this->mating_type;
	}

	/**
	 * Set mating type.
	 *
	 * @param string|null $mating_type Mating type.
	 * @return self
	 */
	public function set_mating_type( ?string $mating_type ): self {
		$this->mating_type = $mating_type;
		return $this;
	}

	/**
	 * Get pregnancy confirmed date.
	 *
	 * @return \DateTimeImmutable|null Pregnancy confirmed date.
	 */
	public function get_pregnancy_confirmed_date(): ?\DateTimeImmutable {
		return $this->pregnancy_confirmed_date;
	}

	/**
	 * Set pregnancy confirmed date.
	 *
	 * @param \DateTimeImmutable|null $pregnancy_confirmed_date Pregnancy confirmed date.
	 * @return self
	 */
	public function set_pregnancy_confirmed_date( ?\DateTimeImmutable $pregnancy_confirmed_date ): self {
		$this->pregnancy_confirmed_date = $pregnancy_confirmed_date;
		return $this;
	}

	/**
	 * Get expected birth date.
	 *
	 * @return \DateTimeImmutable|null Expected birth date.
	 */
	public function get_expected_birth_date(): ?\DateTimeImmutable {
		return $this->expected_birth_date;
	}

	/**
	 * Set expected birth date.
	 *
	 * @param \DateTimeImmutable|null $expected_birth_date Expected birth date.
	 * @return self
	 */
	public function set_expected_birth_date( ?\DateTimeImmutable $expected_birth_date ): self {
		$this->expected_birth_date = $expected_birth_date;
		return $this;
	}

	/**
	 * Get actual birth date.
	 *
	 * @return \DateTimeImmutable|null Actual birth date.
	 */
	public function get_actual_birth_date(): ?\DateTimeImmutable {
		return $this->actual_birth_date;
	}

	/**
	 * Set actual birth date.
	 *
	 * @param \DateTimeImmutable|null $actual_birth_date Actual birth date.
	 * @return self
	 */
	public function set_actual_birth_date( ?\DateTimeImmutable $actual_birth_date ): self {
		$this->actual_birth_date = $actual_birth_date;
		return $this;
	}

	/**
	 * Get birth type.
	 *
	 * @return string|null Birth type.
	 */
	public function get_birth_type(): ?string {
		return $this->birth_type;
	}

	/**
	 * Set birth type.
	 *
	 * @param string|null $birth_type Birth type.
	 * @return self
	 */
	public function set_birth_type( ?string $birth_type ): self {
		$this->birth_type = $birth_type;
		return $this;
	}

	/**
	 * Get puppies born count.
	 *
	 * @return int Puppies born count.
	 */
	public function get_puppies_born_count(): int {
		return $this->puppies_born_count;
	}

	/**
	 * Set puppies born count.
	 *
	 * @param int $puppies_born_count Puppies born count.
	 * @return self
	 */
	public function set_puppies_born_count( int $puppies_born_count ): self {
		$this->puppies_born_count = $puppies_born_count;
		return $this;
	}

	/**
	 * Get puppies alive count.
	 *
	 * @return int Puppies alive count.
	 */
	public function get_puppies_alive_count(): int {
		return $this->puppies_alive_count;
	}

	/**
	 * Set puppies alive count.
	 *
	 * @param int $puppies_alive_count Puppies alive count.
	 * @return self
	 */
	public function set_puppies_alive_count( int $puppies_alive_count ): self {
		$this->puppies_alive_count = $puppies_alive_count;
		return $this;
	}

	/**
	 * Get males count.
	 *
	 * @return int Males count.
	 */
	public function get_males_count(): int {
		return $this->males_count;
	}

	/**
	 * Set males count.
	 *
	 * @param int $males_count Males count.
	 * @return self
	 */
	public function set_males_count( int $males_count ): self {
		$this->males_count = $males_count;
		return $this;
	}

	/**
	 * Get females count.
	 *
	 * @return int Females count.
	 */
	public function get_females_count(): int {
		return $this->females_count;
	}

	/**
	 * Set females count.
	 *
	 * @param int $females_count Females count.
	 * @return self
	 */
	public function set_females_count( int $females_count ): self {
		$this->females_count = $females_count;
		return $this;
	}

	/**
	 * Get veterinarian ID.
	 *
	 * @return int|null Veterinarian ID.
	 */
	public function get_veterinarian_id(): ?int {
		return $this->veterinarian_id;
	}

	/**
	 * Set veterinarian ID.
	 *
	 * @param int|null $veterinarian_id Veterinarian ID.
	 * @return self
	 */
	public function set_veterinarian_id( ?int $veterinarian_id ): self {
		$this->veterinarian_id = $veterinarian_id;
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
	 * Convert to array.
	 *
	 * @return array<string, mixed> Entity data.
	 */
	public function to_array(): array {
		return array(
			'id'                       => $this->id,
			'tenant_id'                => $this->tenant_id,
			'name'                     => $this->name,
			'litter_letter'            => $this->litter_letter,
			'dam_id'                   => $this->dam_id,
			'sire_id'                  => $this->sire_id,
			'status'                   => $this->status,
			'heat_start_date'          => $this->heat_start_date?->format( 'Y-m-d' ),
			'mating_date'              => $this->mating_date?->format( 'Y-m-d' ),
			'mating_type'              => $this->mating_type,
			'pregnancy_confirmed_date' => $this->pregnancy_confirmed_date?->format( 'Y-m-d' ),
			'expected_birth_date'      => $this->expected_birth_date?->format( 'Y-m-d' ),
			'actual_birth_date'        => $this->actual_birth_date?->format( 'Y-m-d' ),
			'birth_type'               => $this->birth_type,
			'puppies_born_count'       => $this->puppies_born_count,
			'puppies_alive_count'      => $this->puppies_alive_count,
			'males_count'              => $this->males_count,
			'females_count'            => $this->females_count,
			'veterinarian_id'          => $this->veterinarian_id,
			'notes'                    => $this->notes,
			'created_at'               => $this->created_at?->format( 'Y-m-d H:i:s' ),
			'updated_at'               => $this->updated_at?->format( 'Y-m-d H:i:s' ),
			'deleted_at'               => $this->deleted_at?->format( 'Y-m-d H:i:s' ),
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
		$entity->set_name( $data['name'] ?? null );
		$entity->set_litter_letter( $data['litter_letter'] ?? null );
		$entity->set_dam_id( (int) ( $data['dam_id'] ?? 0 ) );
		$entity->set_sire_id( (int) ( $data['sire_id'] ?? 0 ) );
		$entity->set_status( $data['status'] ?? self::STATUS_PLANNED );
		$entity->set_mating_type( $data['mating_type'] ?? null );
		$entity->set_birth_type( $data['birth_type'] ?? null );
		$entity->set_puppies_born_count( (int) ( $data['puppies_born_count'] ?? 0 ) );
		$entity->set_puppies_alive_count( (int) ( $data['puppies_alive_count'] ?? 0 ) );
		$entity->set_males_count( (int) ( $data['males_count'] ?? 0 ) );
		$entity->set_females_count( (int) ( $data['females_count'] ?? 0 ) );
		$entity->set_veterinarian_id( isset( $data['veterinarian_id'] ) ? (int) $data['veterinarian_id'] : null );
		$entity->set_notes( $data['notes'] ?? null );

		// Handle dates.
		$heat_start = DateHelper::parse_iso( $data['heat_start_date'] ?? null );
		$entity->set_heat_start_date( $heat_start );

		$mating = DateHelper::parse_iso( $data['mating_date'] ?? null );
		$entity->set_mating_date( $mating );

		$pregnancy = DateHelper::parse_iso( $data['pregnancy_confirmed_date'] ?? null );
		$entity->set_pregnancy_confirmed_date( $pregnancy );

		$expected = DateHelper::parse_iso( $data['expected_birth_date'] ?? null );
		$entity->set_expected_birth_date( $expected );

		$actual = DateHelper::parse_iso( $data['actual_birth_date'] ?? null );
		$entity->set_actual_birth_date( $actual );

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
			self::STATUS_PLANNED,
			self::STATUS_CONFIRMED,
			self::STATUS_PREGNANT,
			self::STATUS_BORN,
			self::STATUS_WEANED,
			self::STATUS_CLOSED,
			self::STATUS_CANCELLED,
		);
	}

	/**
	 * Get allowed mating types.
	 *
	 * @return array<string> Allowed mating type values.
	 */
	public static function get_allowed_mating_types(): array {
		return array(
			self::MATING_NATURAL,
			self::MATING_ARTIFICIAL_FRESH,
			self::MATING_ARTIFICIAL_FROZEN,
		);
	}

	/**
	 * Get allowed birth types.
	 *
	 * @return array<string> Allowed birth type values.
	 */
	public static function get_allowed_birth_types(): array {
		return array(
			self::BIRTH_NATURAL,
			self::BIRTH_CESAREAN,
			self::BIRTH_ASSISTED,
		);
	}
}
