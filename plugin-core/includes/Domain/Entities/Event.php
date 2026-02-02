<?php
/**
 * Event entity.
 *
 * Represents an event in the timeline.
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
 * Event class.
 */
class Event extends BaseEntity {

	/**
	 * Entity type constants.
	 */
	public const ENTITY_DOG    = 'dog';
	public const ENTITY_LITTER = 'litter';
	public const ENTITY_PUPPY  = 'puppy';

	/**
	 * Event type constants - Reproduction.
	 */
	public const TYPE_HEAT           = 'heat';
	public const TYPE_MATING         = 'mating';
	public const TYPE_PREGNANCY_TEST = 'pregnancy_test';
	public const TYPE_BIRTH          = 'birth';

	/**
	 * Event type constants - Health.
	 */
	public const TYPE_VACCINE    = 'vaccine';
	public const TYPE_DEWORMING  = 'deworming';
	public const TYPE_EXAM       = 'exam';
	public const TYPE_MEDICATION = 'medication';
	public const TYPE_SURGERY    = 'surgery';
	public const TYPE_VET_VISIT  = 'vet_visit';

	/**
	 * Event type constants - Other.
	 */
	public const TYPE_WEIGHING = 'weighing';
	public const TYPE_GROOMING = 'grooming';
	public const TYPE_TRAINING = 'training';
	public const TYPE_SHOW     = 'show';
	public const TYPE_NOTE     = 'note';

	/**
	 * Entity type (dog, litter, puppy).
	 *
	 * @var string
	 */
	private string $entity_type;

	/**
	 * Entity ID.
	 *
	 * @var int
	 */
	private int $entity_id;

	/**
	 * Event type.
	 *
	 * @var string
	 */
	private string $event_type;

	/**
	 * Event date.
	 *
	 * @var \DateTimeImmutable
	 */
	private \DateTimeImmutable $event_date;

	/**
	 * Event end date.
	 *
	 * @var \DateTimeImmutable|null
	 */
	private ?\DateTimeImmutable $event_end_date = null;

	/**
	 * Payload data.
	 *
	 * @var array<string, mixed>
	 */
	private array $payload = array();

	/**
	 * Reminder date.
	 *
	 * @var \DateTimeImmutable|null
	 */
	private ?\DateTimeImmutable $reminder_date = null;

	/**
	 * Reminder completed.
	 *
	 * @var bool
	 */
	private bool $reminder_completed = false;

	/**
	 * Notes.
	 *
	 * @var string|null
	 */
	private ?string $notes = null;

	/**
	 * Attachments.
	 *
	 * @var array<string>
	 */
	private array $attachments = array();

	/**
	 * Created by user ID.
	 *
	 * @var int|null
	 */
	private ?int $created_by = null;

	// Getters and setters.

	/**
	 * Get entity type.
	 *
	 * @return string Entity type.
	 */
	public function get_entity_type(): string {
		return $this->entity_type;
	}

	/**
	 * Set entity type.
	 *
	 * @param string $entity_type Entity type.
	 * @return self
	 */
	public function set_entity_type( string $entity_type ): self {
		$this->entity_type = $entity_type;
		return $this;
	}

	/**
	 * Get entity ID.
	 *
	 * @return int Entity ID.
	 */
	public function get_entity_id(): int {
		return $this->entity_id;
	}

	/**
	 * Set entity ID.
	 *
	 * @param int $entity_id Entity ID.
	 * @return self
	 */
	public function set_entity_id( int $entity_id ): self {
		$this->entity_id = $entity_id;
		return $this;
	}

	/**
	 * Get event type.
	 *
	 * @return string Event type.
	 */
	public function get_event_type(): string {
		return $this->event_type;
	}

	/**
	 * Set event type.
	 *
	 * @param string $event_type Event type.
	 * @return self
	 */
	public function set_event_type( string $event_type ): self {
		$this->event_type = $event_type;
		return $this;
	}

	/**
	 * Get event date.
	 *
	 * @return \DateTimeImmutable Event date.
	 */
	public function get_event_date(): \DateTimeImmutable {
		return $this->event_date;
	}

	/**
	 * Set event date.
	 *
	 * @param \DateTimeImmutable $event_date Event date.
	 * @return self
	 */
	public function set_event_date( \DateTimeImmutable $event_date ): self {
		$this->event_date = $event_date;
		return $this;
	}

	/**
	 * Get event end date.
	 *
	 * @return \DateTimeImmutable|null Event end date.
	 */
	public function get_event_end_date(): ?\DateTimeImmutable {
		return $this->event_end_date;
	}

	/**
	 * Set event end date.
	 *
	 * @param \DateTimeImmutable|null $event_end_date Event end date.
	 * @return self
	 */
	public function set_event_end_date( ?\DateTimeImmutable $event_end_date ): self {
		$this->event_end_date = $event_end_date;
		return $this;
	}

	/**
	 * Get payload.
	 *
	 * @return array<string, mixed> Payload.
	 */
	public function get_payload(): array {
		return $this->payload;
	}

	/**
	 * Set payload.
	 *
	 * @param array<string, mixed> $payload Payload.
	 * @return self
	 */
	public function set_payload( array $payload ): self {
		$this->payload = $payload;
		return $this;
	}

	/**
	 * Get payload value.
	 *
	 * @param string $key     Key.
	 * @param mixed  $default Default value.
	 * @return mixed Value.
	 */
	public function get_payload_value( string $key, mixed $default = null ): mixed {
		return $this->payload[ $key ] ?? $default;
	}

	/**
	 * Set payload value.
	 *
	 * @param string $key   Key.
	 * @param mixed  $value Value.
	 * @return self
	 */
	public function set_payload_value( string $key, mixed $value ): self {
		$this->payload[ $key ] = $value;
		return $this;
	}

	/**
	 * Get reminder date.
	 *
	 * @return \DateTimeImmutable|null Reminder date.
	 */
	public function get_reminder_date(): ?\DateTimeImmutable {
		return $this->reminder_date;
	}

	/**
	 * Set reminder date.
	 *
	 * @param \DateTimeImmutable|null $reminder_date Reminder date.
	 * @return self
	 */
	public function set_reminder_date( ?\DateTimeImmutable $reminder_date ): self {
		$this->reminder_date = $reminder_date;
		return $this;
	}

	/**
	 * Is reminder completed.
	 *
	 * @return bool Reminder completed.
	 */
	public function is_reminder_completed(): bool {
		return $this->reminder_completed;
	}

	/**
	 * Set reminder completed.
	 *
	 * @param bool $reminder_completed Reminder completed.
	 * @return self
	 */
	public function set_reminder_completed( bool $reminder_completed ): self {
		$this->reminder_completed = $reminder_completed;
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
	 * Get attachments.
	 *
	 * @return array<string> Attachments.
	 */
	public function get_attachments(): array {
		return $this->attachments;
	}

	/**
	 * Set attachments.
	 *
	 * @param array<string> $attachments Attachments.
	 * @return self
	 */
	public function set_attachments( array $attachments ): self {
		$this->attachments = $attachments;
		return $this;
	}

	/**
	 * Get created by.
	 *
	 * @return int|null Created by.
	 */
	public function get_created_by(): ?int {
		return $this->created_by;
	}

	/**
	 * Set created by.
	 *
	 * @param int|null $created_by Created by.
	 * @return self
	 */
	public function set_created_by( ?int $created_by ): self {
		$this->created_by = $created_by;
		return $this;
	}

	/**
	 * Check if event is a health event.
	 *
	 * @return bool True if health event.
	 */
	public function is_health_event(): bool {
		return in_array(
			$this->event_type,
			array(
				self::TYPE_VACCINE,
				self::TYPE_DEWORMING,
				self::TYPE_EXAM,
				self::TYPE_MEDICATION,
				self::TYPE_SURGERY,
				self::TYPE_VET_VISIT,
			),
			true
		);
	}

	/**
	 * Check if event is a reproduction event.
	 *
	 * @return bool True if reproduction event.
	 */
	public function is_reproduction_event(): bool {
		return in_array(
			$this->event_type,
			array(
				self::TYPE_HEAT,
				self::TYPE_MATING,
				self::TYPE_PREGNANCY_TEST,
				self::TYPE_BIRTH,
			),
			true
		);
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed> Entity data.
	 */
	public function to_array(): array {
		return array(
			'id'                 => $this->id,
			'tenant_id'          => $this->tenant_id,
			'entity_type'        => $this->entity_type,
			'entity_id'          => $this->entity_id,
			'event_type'         => $this->event_type,
			'event_date'         => $this->event_date->format( 'Y-m-d H:i:s' ),
			'event_end_date'     => $this->event_end_date?->format( 'Y-m-d H:i:s' ),
			'payload'            => $this->payload,
			'reminder_date'      => $this->reminder_date?->format( 'Y-m-d H:i:s' ),
			'reminder_completed' => $this->reminder_completed,
			'notes'              => $this->notes,
			'attachments'        => $this->attachments,
			'created_by'         => $this->created_by,
			'created_at'         => $this->created_at?->format( 'Y-m-d H:i:s' ),
			'updated_at'         => $this->updated_at?->format( 'Y-m-d H:i:s' ),
			'deleted_at'         => $this->deleted_at?->format( 'Y-m-d H:i:s' ),
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
		$entity->set_entity_type( $data['entity_type'] ?? self::ENTITY_DOG );
		$entity->set_entity_id( (int) ( $data['entity_id'] ?? 0 ) );
		$entity->set_event_type( $data['event_type'] ?? self::TYPE_NOTE );
		$entity->set_payload( is_array( $data['payload'] ?? null ) ? $data['payload'] : array() );
		$entity->set_reminder_completed( (bool) ( $data['reminder_completed'] ?? false ) );
		$entity->set_notes( $data['notes'] ?? null );
		$entity->set_attachments( is_array( $data['attachments'] ?? null ) ? $data['attachments'] : array() );
		$entity->set_created_by( isset( $data['created_by'] ) ? (int) $data['created_by'] : null );

		// Handle dates.
		$event_date = DateHelper::parse_iso( $data['event_date'] ?? null );
		if ( $event_date ) {
			$entity->set_event_date( $event_date );
		} else {
			$entity->set_event_date( new \DateTimeImmutable() );
		}

		$event_end = DateHelper::parse_iso( $data['event_end_date'] ?? null );
		$entity->set_event_end_date( $event_end );

		$reminder = DateHelper::parse_iso( $data['reminder_date'] ?? null );
		$entity->set_reminder_date( $reminder );

		$created_at = DateHelper::parse_iso( $data['created_at'] ?? null );
		$entity->set_created_at( $created_at );

		$updated_at = DateHelper::parse_iso( $data['updated_at'] ?? null );
		$entity->set_updated_at( $updated_at );

		$deleted_at = DateHelper::parse_iso( $data['deleted_at'] ?? null );
		$entity->set_deleted_at( $deleted_at );

		return $entity;
	}

	/**
	 * Get allowed entity types.
	 *
	 * @return array<string> Allowed entity type values.
	 */
	public static function get_allowed_entity_types(): array {
		return array(
			self::ENTITY_DOG,
			self::ENTITY_LITTER,
			self::ENTITY_PUPPY,
		);
	}

	/**
	 * Get allowed event types.
	 *
	 * @return array<string> Allowed event type values.
	 */
	public static function get_allowed_event_types(): array {
		return array(
			// Reproduction.
			self::TYPE_HEAT,
			self::TYPE_MATING,
			self::TYPE_PREGNANCY_TEST,
			self::TYPE_BIRTH,
			// Health.
			self::TYPE_VACCINE,
			self::TYPE_DEWORMING,
			self::TYPE_EXAM,
			self::TYPE_MEDICATION,
			self::TYPE_SURGERY,
			self::TYPE_VET_VISIT,
			// Other.
			self::TYPE_WEIGHING,
			self::TYPE_GROOMING,
			self::TYPE_TRAINING,
			self::TYPE_SHOW,
			self::TYPE_NOTE,
		);
	}
}
