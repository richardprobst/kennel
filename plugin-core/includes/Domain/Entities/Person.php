<?php
/**
 * Person entity.
 *
 * Represents a person related to the kennel.
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
 * Person class.
 */
class Person extends BaseEntity {

	/**
	 * Person type constants.
	 */
	public const TYPE_INTERESTED   = 'interested';
	public const TYPE_BUYER        = 'buyer';
	public const TYPE_VETERINARIAN = 'veterinarian';
	public const TYPE_HANDLER      = 'handler';
	public const TYPE_PARTNER      = 'partner';
	public const TYPE_OTHER        = 'other';

	/**
	 * Name.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * Email.
	 *
	 * @var string|null
	 */
	private ?string $email = null;

	/**
	 * Phone.
	 *
	 * @var string|null
	 */
	private ?string $phone = null;

	/**
	 * Secondary phone.
	 *
	 * @var string|null
	 */
	private ?string $phone_secondary = null;

	/**
	 * Type.
	 *
	 * @var string
	 */
	private string $type = self::TYPE_INTERESTED;

	/**
	 * Address street.
	 *
	 * @var string|null
	 */
	private ?string $address_street = null;

	/**
	 * Address number.
	 *
	 * @var string|null
	 */
	private ?string $address_number = null;

	/**
	 * Address complement.
	 *
	 * @var string|null
	 */
	private ?string $address_complement = null;

	/**
	 * Address neighborhood.
	 *
	 * @var string|null
	 */
	private ?string $address_neighborhood = null;

	/**
	 * Address city.
	 *
	 * @var string|null
	 */
	private ?string $address_city = null;

	/**
	 * Address state.
	 *
	 * @var string|null
	 */
	private ?string $address_state = null;

	/**
	 * Address ZIP.
	 *
	 * @var string|null
	 */
	private ?string $address_zip = null;

	/**
	 * Address country.
	 *
	 * @var string
	 */
	private string $address_country = 'Brasil';

	/**
	 * CPF document.
	 *
	 * @var string|null
	 */
	private ?string $document_cpf = null;

	/**
	 * RG document.
	 *
	 * @var string|null
	 */
	private ?string $document_rg = null;

	/**
	 * Preferences (for interested parties).
	 *
	 * @var array<string, mixed>
	 */
	private array $preferences = array();

	/**
	 * Referred by ID.
	 *
	 * @var int|null
	 */
	private ?int $referred_by_id = null;

	/**
	 * Notes.
	 *
	 * @var string|null
	 */
	private ?string $notes = null;

	/**
	 * Tags.
	 *
	 * @var array<string>
	 */
	private array $tags = array();

	// Getters and setters.

	/**
	 * Get name.
	 *
	 * @return string Name.
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Set name.
	 *
	 * @param string $name Name.
	 * @return self
	 */
	public function set_name( string $name ): self {
		$this->name = $name;
		return $this;
	}

	/**
	 * Get email.
	 *
	 * @return string|null Email.
	 */
	public function get_email(): ?string {
		return $this->email;
	}

	/**
	 * Set email.
	 *
	 * @param string|null $email Email.
	 * @return self
	 */
	public function set_email( ?string $email ): self {
		$this->email = $email;
		return $this;
	}

	/**
	 * Get phone.
	 *
	 * @return string|null Phone.
	 */
	public function get_phone(): ?string {
		return $this->phone;
	}

	/**
	 * Set phone.
	 *
	 * @param string|null $phone Phone.
	 * @return self
	 */
	public function set_phone( ?string $phone ): self {
		$this->phone = $phone;
		return $this;
	}

	/**
	 * Get secondary phone.
	 *
	 * @return string|null Secondary phone.
	 */
	public function get_phone_secondary(): ?string {
		return $this->phone_secondary;
	}

	/**
	 * Set secondary phone.
	 *
	 * @param string|null $phone_secondary Secondary phone.
	 * @return self
	 */
	public function set_phone_secondary( ?string $phone_secondary ): self {
		$this->phone_secondary = $phone_secondary;
		return $this;
	}

	/**
	 * Get type.
	 *
	 * @return string Type.
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Set type.
	 *
	 * @param string $type Type.
	 * @return self
	 */
	public function set_type( string $type ): self {
		$this->type = $type;
		return $this;
	}

	/**
	 * Get address street.
	 *
	 * @return string|null Address street.
	 */
	public function get_address_street(): ?string {
		return $this->address_street;
	}

	/**
	 * Set address street.
	 *
	 * @param string|null $address_street Address street.
	 * @return self
	 */
	public function set_address_street( ?string $address_street ): self {
		$this->address_street = $address_street;
		return $this;
	}

	/**
	 * Get address number.
	 *
	 * @return string|null Address number.
	 */
	public function get_address_number(): ?string {
		return $this->address_number;
	}

	/**
	 * Set address number.
	 *
	 * @param string|null $address_number Address number.
	 * @return self
	 */
	public function set_address_number( ?string $address_number ): self {
		$this->address_number = $address_number;
		return $this;
	}

	/**
	 * Get address complement.
	 *
	 * @return string|null Address complement.
	 */
	public function get_address_complement(): ?string {
		return $this->address_complement;
	}

	/**
	 * Set address complement.
	 *
	 * @param string|null $address_complement Address complement.
	 * @return self
	 */
	public function set_address_complement( ?string $address_complement ): self {
		$this->address_complement = $address_complement;
		return $this;
	}

	/**
	 * Get address neighborhood.
	 *
	 * @return string|null Address neighborhood.
	 */
	public function get_address_neighborhood(): ?string {
		return $this->address_neighborhood;
	}

	/**
	 * Set address neighborhood.
	 *
	 * @param string|null $address_neighborhood Address neighborhood.
	 * @return self
	 */
	public function set_address_neighborhood( ?string $address_neighborhood ): self {
		$this->address_neighborhood = $address_neighborhood;
		return $this;
	}

	/**
	 * Get address city.
	 *
	 * @return string|null Address city.
	 */
	public function get_address_city(): ?string {
		return $this->address_city;
	}

	/**
	 * Set address city.
	 *
	 * @param string|null $address_city Address city.
	 * @return self
	 */
	public function set_address_city( ?string $address_city ): self {
		$this->address_city = $address_city;
		return $this;
	}

	/**
	 * Get address state.
	 *
	 * @return string|null Address state.
	 */
	public function get_address_state(): ?string {
		return $this->address_state;
	}

	/**
	 * Set address state.
	 *
	 * @param string|null $address_state Address state.
	 * @return self
	 */
	public function set_address_state( ?string $address_state ): self {
		$this->address_state = $address_state;
		return $this;
	}

	/**
	 * Get address ZIP.
	 *
	 * @return string|null Address ZIP.
	 */
	public function get_address_zip(): ?string {
		return $this->address_zip;
	}

	/**
	 * Set address ZIP.
	 *
	 * @param string|null $address_zip Address ZIP.
	 * @return self
	 */
	public function set_address_zip( ?string $address_zip ): self {
		$this->address_zip = $address_zip;
		return $this;
	}

	/**
	 * Get address country.
	 *
	 * @return string Address country.
	 */
	public function get_address_country(): string {
		return $this->address_country;
	}

	/**
	 * Set address country.
	 *
	 * @param string $address_country Address country.
	 * @return self
	 */
	public function set_address_country( string $address_country ): self {
		$this->address_country = $address_country;
		return $this;
	}

	/**
	 * Get CPF document.
	 *
	 * @return string|null CPF document.
	 */
	public function get_document_cpf(): ?string {
		return $this->document_cpf;
	}

	/**
	 * Set CPF document.
	 *
	 * @param string|null $document_cpf CPF document.
	 * @return self
	 */
	public function set_document_cpf( ?string $document_cpf ): self {
		$this->document_cpf = $document_cpf;
		return $this;
	}

	/**
	 * Get RG document.
	 *
	 * @return string|null RG document.
	 */
	public function get_document_rg(): ?string {
		return $this->document_rg;
	}

	/**
	 * Set RG document.
	 *
	 * @param string|null $document_rg RG document.
	 * @return self
	 */
	public function set_document_rg( ?string $document_rg ): self {
		$this->document_rg = $document_rg;
		return $this;
	}

	/**
	 * Get preferences.
	 *
	 * @return array<string, mixed> Preferences.
	 */
	public function get_preferences(): array {
		return $this->preferences;
	}

	/**
	 * Set preferences.
	 *
	 * @param array<string, mixed> $preferences Preferences.
	 * @return self
	 */
	public function set_preferences( array $preferences ): self {
		$this->preferences = $preferences;
		return $this;
	}

	/**
	 * Get referred by ID.
	 *
	 * @return int|null Referred by ID.
	 */
	public function get_referred_by_id(): ?int {
		return $this->referred_by_id;
	}

	/**
	 * Set referred by ID.
	 *
	 * @param int|null $referred_by_id Referred by ID.
	 * @return self
	 */
	public function set_referred_by_id( ?int $referred_by_id ): self {
		$this->referred_by_id = $referred_by_id;
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
	 * Get tags.
	 *
	 * @return array<string> Tags.
	 */
	public function get_tags(): array {
		return $this->tags;
	}

	/**
	 * Set tags.
	 *
	 * @param array<string> $tags Tags.
	 * @return self
	 */
	public function set_tags( array $tags ): self {
		$this->tags = $tags;
		return $this;
	}

	/**
	 * Get full address as string.
	 *
	 * @return string Full address.
	 */
	public function get_full_address(): string {
		$parts = array_filter(
			array(
				$this->address_street,
				$this->address_number,
				$this->address_complement,
				$this->address_neighborhood,
				$this->address_city,
				$this->address_state,
				$this->address_zip,
				$this->address_country,
			)
		);

		return implode( ', ', $parts );
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed> Entity data.
	 */
	public function to_array(): array {
		return array(
			'id'                   => $this->id,
			'tenant_id'            => $this->tenant_id,
			'name'                 => $this->name,
			'email'                => $this->email,
			'phone'                => $this->phone,
			'phone_secondary'      => $this->phone_secondary,
			'type'                 => $this->type,
			'address_street'       => $this->address_street,
			'address_number'       => $this->address_number,
			'address_complement'   => $this->address_complement,
			'address_neighborhood' => $this->address_neighborhood,
			'address_city'         => $this->address_city,
			'address_state'        => $this->address_state,
			'address_zip'          => $this->address_zip,
			'address_country'      => $this->address_country,
			'document_cpf'         => $this->document_cpf,
			'document_rg'          => $this->document_rg,
			'preferences'          => $this->preferences,
			'referred_by_id'       => $this->referred_by_id,
			'notes'                => $this->notes,
			'tags'                 => $this->tags,
			'created_at'           => $this->created_at?->format( 'Y-m-d H:i:s' ),
			'updated_at'           => $this->updated_at?->format( 'Y-m-d H:i:s' ),
			'deleted_at'           => $this->deleted_at?->format( 'Y-m-d H:i:s' ),
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
		$entity->set_email( $data['email'] ?? null );
		$entity->set_phone( $data['phone'] ?? null );
		$entity->set_phone_secondary( $data['phone_secondary'] ?? null );
		$entity->set_type( $data['type'] ?? self::TYPE_INTERESTED );
		$entity->set_address_street( $data['address_street'] ?? null );
		$entity->set_address_number( $data['address_number'] ?? null );
		$entity->set_address_complement( $data['address_complement'] ?? null );
		$entity->set_address_neighborhood( $data['address_neighborhood'] ?? null );
		$entity->set_address_city( $data['address_city'] ?? null );
		$entity->set_address_state( $data['address_state'] ?? null );
		$entity->set_address_zip( $data['address_zip'] ?? null );
		$entity->set_address_country( $data['address_country'] ?? 'Brasil' );
		$entity->set_document_cpf( $data['document_cpf'] ?? null );
		$entity->set_document_rg( $data['document_rg'] ?? null );
		$entity->set_preferences( is_array( $data['preferences'] ?? null ) ? $data['preferences'] : array() );
		$entity->set_referred_by_id( isset( $data['referred_by_id'] ) ? (int) $data['referred_by_id'] : null );
		$entity->set_notes( $data['notes'] ?? null );
		$entity->set_tags( is_array( $data['tags'] ?? null ) ? $data['tags'] : array() );

		$created_at = DateHelper::parse_iso( $data['created_at'] ?? null );
		$entity->set_created_at( $created_at );

		$updated_at = DateHelper::parse_iso( $data['updated_at'] ?? null );
		$entity->set_updated_at( $updated_at );

		$deleted_at = DateHelper::parse_iso( $data['deleted_at'] ?? null );
		$entity->set_deleted_at( $deleted_at );

		return $entity;
	}

	/**
	 * Get allowed types.
	 *
	 * @return array<string> Allowed type values.
	 */
	public static function get_allowed_types(): array {
		return array(
			self::TYPE_INTERESTED,
			self::TYPE_BUYER,
			self::TYPE_VETERINARIAN,
			self::TYPE_HANDLER,
			self::TYPE_PARTNER,
			self::TYPE_OTHER,
		);
	}
}
