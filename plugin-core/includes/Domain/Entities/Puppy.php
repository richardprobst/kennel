<?php
/**
 * Puppy entity.
 *
 * Represents a puppy from a litter.
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
 * Puppy class.
 */
class Puppy extends BaseEntity {

	/**
	 * Puppy status constants.
	 */
	public const STATUS_AVAILABLE = 'available';
	public const STATUS_RESERVED  = 'reserved';
	public const STATUS_SOLD      = 'sold';
	public const STATUS_RETAINED  = 'retained';
	public const STATUS_DECEASED  = 'deceased';
	public const STATUS_RETURNED  = 'returned';

	/**
	 * Sex constants.
	 */
	public const SEX_MALE   = 'male';
	public const SEX_FEMALE = 'female';

	/**
	 * Litter ID.
	 *
	 * @var int
	 */
	private int $litter_id;

	/**
	 * Identifier.
	 *
	 * @var string
	 */
	private string $identifier;

	/**
	 * Registered name.
	 *
	 * @var string|null
	 */
	private ?string $name = null;

	/**
	 * Call name.
	 *
	 * @var string|null
	 */
	private ?string $call_name = null;

	/**
	 * Registration number.
	 *
	 * @var string|null
	 */
	private ?string $registration_number = null;

	/**
	 * Chip number.
	 *
	 * @var string|null
	 */
	private ?string $chip_number = null;

	/**
	 * Sex.
	 *
	 * @var string
	 */
	private string $sex;

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
	 * Birth weight in grams.
	 *
	 * @var float|null
	 */
	private ?float $birth_weight = null;

	/**
	 * Birth order.
	 *
	 * @var int|null
	 */
	private ?int $birth_order = null;

	/**
	 * Birth notes.
	 *
	 * @var string|null
	 */
	private ?string $birth_notes = null;

	/**
	 * Status.
	 *
	 * @var string
	 */
	private string $status = self::STATUS_AVAILABLE;

	/**
	 * Buyer ID.
	 *
	 * @var int|null
	 */
	private ?int $buyer_id = null;

	/**
	 * Reservation date.
	 *
	 * @var \DateTimeImmutable|null
	 */
	private ?\DateTimeImmutable $reservation_date = null;

	/**
	 * Sale date.
	 *
	 * @var \DateTimeImmutable|null
	 */
	private ?\DateTimeImmutable $sale_date = null;

	/**
	 * Delivery date.
	 *
	 * @var \DateTimeImmutable|null
	 */
	private ?\DateTimeImmutable $delivery_date = null;

	/**
	 * Price.
	 *
	 * @var float|null
	 */
	private ?float $price = null;

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
	 * Notes.
	 *
	 * @var string|null
	 */
	private ?string $notes = null;

	// Getters and setters.

	/**
	 * Get litter ID.
	 *
	 * @return int Litter ID.
	 */
	public function get_litter_id(): int {
		return $this->litter_id;
	}

	/**
	 * Set litter ID.
	 *
	 * @param int $litter_id Litter ID.
	 * @return self
	 */
	public function set_litter_id( int $litter_id ): self {
		$this->litter_id = $litter_id;
		return $this;
	}

	/**
	 * Get identifier.
	 *
	 * @return string Identifier.
	 */
	public function get_identifier(): string {
		return $this->identifier;
	}

	/**
	 * Set identifier.
	 *
	 * @param string $identifier Identifier.
	 * @return self
	 */
	public function set_identifier( string $identifier ): self {
		$this->identifier = $identifier;
		return $this;
	}

	/**
	 * Get name.
	 *
	 * @return string|null Name.
	 */
	public function get_name(): ?string {
		return $this->name;
	}

	/**
	 * Set name.
	 *
	 * @param string|null $name Name.
	 * @return self
	 */
	public function set_name( ?string $name ): self {
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
	 * Get birth weight.
	 *
	 * @return float|null Birth weight in grams.
	 */
	public function get_birth_weight(): ?float {
		return $this->birth_weight;
	}

	/**
	 * Set birth weight.
	 *
	 * @param float|null $birth_weight Birth weight in grams.
	 * @return self
	 */
	public function set_birth_weight( ?float $birth_weight ): self {
		$this->birth_weight = $birth_weight;
		return $this;
	}

	/**
	 * Get birth order.
	 *
	 * @return int|null Birth order.
	 */
	public function get_birth_order(): ?int {
		return $this->birth_order;
	}

	/**
	 * Set birth order.
	 *
	 * @param int|null $birth_order Birth order.
	 * @return self
	 */
	public function set_birth_order( ?int $birth_order ): self {
		$this->birth_order = $birth_order;
		return $this;
	}

	/**
	 * Get birth notes.
	 *
	 * @return string|null Birth notes.
	 */
	public function get_birth_notes(): ?string {
		return $this->birth_notes;
	}

	/**
	 * Set birth notes.
	 *
	 * @param string|null $birth_notes Birth notes.
	 * @return self
	 */
	public function set_birth_notes( ?string $birth_notes ): self {
		$this->birth_notes = $birth_notes;
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
	 * Get buyer ID.
	 *
	 * @return int|null Buyer ID.
	 */
	public function get_buyer_id(): ?int {
		return $this->buyer_id;
	}

	/**
	 * Set buyer ID.
	 *
	 * @param int|null $buyer_id Buyer ID.
	 * @return self
	 */
	public function set_buyer_id( ?int $buyer_id ): self {
		$this->buyer_id = $buyer_id;
		return $this;
	}

	/**
	 * Get reservation date.
	 *
	 * @return \DateTimeImmutable|null Reservation date.
	 */
	public function get_reservation_date(): ?\DateTimeImmutable {
		return $this->reservation_date;
	}

	/**
	 * Set reservation date.
	 *
	 * @param \DateTimeImmutable|null $reservation_date Reservation date.
	 * @return self
	 */
	public function set_reservation_date( ?\DateTimeImmutable $reservation_date ): self {
		$this->reservation_date = $reservation_date;
		return $this;
	}

	/**
	 * Get sale date.
	 *
	 * @return \DateTimeImmutable|null Sale date.
	 */
	public function get_sale_date(): ?\DateTimeImmutable {
		return $this->sale_date;
	}

	/**
	 * Set sale date.
	 *
	 * @param \DateTimeImmutable|null $sale_date Sale date.
	 * @return self
	 */
	public function set_sale_date( ?\DateTimeImmutable $sale_date ): self {
		$this->sale_date = $sale_date;
		return $this;
	}

	/**
	 * Get delivery date.
	 *
	 * @return \DateTimeImmutable|null Delivery date.
	 */
	public function get_delivery_date(): ?\DateTimeImmutable {
		return $this->delivery_date;
	}

	/**
	 * Set delivery date.
	 *
	 * @param \DateTimeImmutable|null $delivery_date Delivery date.
	 * @return self
	 */
	public function set_delivery_date( ?\DateTimeImmutable $delivery_date ): self {
		$this->delivery_date = $delivery_date;
		return $this;
	}

	/**
	 * Get price.
	 *
	 * @return float|null Price.
	 */
	public function get_price(): ?float {
		return $this->price;
	}

	/**
	 * Set price.
	 *
	 * @param float|null $price Price.
	 * @return self
	 */
	public function set_price( ?float $price ): self {
		$this->price = $price;
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
			'id'                  => $this->id,
			'tenant_id'           => $this->tenant_id,
			'litter_id'           => $this->litter_id,
			'identifier'          => $this->identifier,
			'name'                => $this->name,
			'call_name'           => $this->call_name,
			'registration_number' => $this->registration_number,
			'chip_number'         => $this->chip_number,
			'sex'                 => $this->sex,
			'color'               => $this->color,
			'markings'            => $this->markings,
			'birth_weight'        => $this->birth_weight,
			'birth_order'         => $this->birth_order,
			'birth_notes'         => $this->birth_notes,
			'status'              => $this->status,
			'buyer_id'            => $this->buyer_id,
			'reservation_date'    => $this->reservation_date?->format( 'Y-m-d' ),
			'sale_date'           => $this->sale_date?->format( 'Y-m-d' ),
			'delivery_date'       => $this->delivery_date?->format( 'Y-m-d' ),
			'price'               => $this->price,
			'photo_main_url'      => $this->photo_main_url,
			'photos'              => $this->photos,
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
		$entity->set_litter_id( (int) ( $data['litter_id'] ?? 0 ) );
		$entity->set_identifier( $data['identifier'] ?? '' );
		$entity->set_name( $data['name'] ?? null );
		$entity->set_call_name( $data['call_name'] ?? null );
		$entity->set_registration_number( $data['registration_number'] ?? null );
		$entity->set_chip_number( $data['chip_number'] ?? null );
		$entity->set_sex( $data['sex'] ?? self::SEX_MALE );
		$entity->set_color( $data['color'] ?? null );
		$entity->set_markings( $data['markings'] ?? null );
		$entity->set_birth_weight( isset( $data['birth_weight'] ) ? (float) $data['birth_weight'] : null );
		$entity->set_birth_order( isset( $data['birth_order'] ) ? (int) $data['birth_order'] : null );
		$entity->set_birth_notes( $data['birth_notes'] ?? null );
		$entity->set_status( $data['status'] ?? self::STATUS_AVAILABLE );
		$entity->set_buyer_id( isset( $data['buyer_id'] ) ? (int) $data['buyer_id'] : null );
		$entity->set_price( isset( $data['price'] ) ? (float) $data['price'] : null );
		$entity->set_photo_main_url( $data['photo_main_url'] ?? null );
		$entity->set_photos( is_array( $data['photos'] ?? null ) ? $data['photos'] : array() );
		$entity->set_notes( $data['notes'] ?? null );

		// Handle dates.
		$reservation = DateHelper::parse_iso( $data['reservation_date'] ?? null );
		$entity->set_reservation_date( $reservation );

		$sale = DateHelper::parse_iso( $data['sale_date'] ?? null );
		$entity->set_sale_date( $sale );

		$delivery = DateHelper::parse_iso( $data['delivery_date'] ?? null );
		$entity->set_delivery_date( $delivery );

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
			self::STATUS_AVAILABLE,
			self::STATUS_RESERVED,
			self::STATUS_SOLD,
			self::STATUS_RETAINED,
			self::STATUS_DECEASED,
			self::STATUS_RETURNED,
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
