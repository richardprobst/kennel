<?php
/**
 * Base entity.
 *
 * Abstract base class for all domain entities.
 *
 * @package CanilCore
 */

namespace CanilCore\Domain\Entities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BaseEntity class.
 */
abstract class BaseEntity {

	/**
	 * Entity ID.
	 *
	 * @var int|null
	 */
	protected ?int $id = null;

	/**
	 * Tenant ID (user ID).
	 *
	 * @var int
	 */
	protected int $tenant_id;

	/**
	 * Created at timestamp.
	 *
	 * @var \DateTimeImmutable|null
	 */
	protected ?\DateTimeImmutable $created_at = null;

	/**
	 * Updated at timestamp.
	 *
	 * @var \DateTimeImmutable|null
	 */
	protected ?\DateTimeImmutable $updated_at = null;

	/**
	 * Deleted at timestamp (soft delete).
	 *
	 * @var \DateTimeImmutable|null
	 */
	protected ?\DateTimeImmutable $deleted_at = null;

	/**
	 * Get entity ID.
	 *
	 * @return int|null Entity ID.
	 */
	public function get_id(): ?int {
		return $this->id;
	}

	/**
	 * Set entity ID.
	 *
	 * @param int $id Entity ID.
	 * @return self
	 */
	public function set_id( int $id ): self {
		$this->id = $id;
		return $this;
	}

	/**
	 * Get tenant ID.
	 *
	 * @return int Tenant ID.
	 */
	public function get_tenant_id(): int {
		return $this->tenant_id;
	}

	/**
	 * Set tenant ID.
	 *
	 * @param int $tenant_id Tenant ID.
	 * @return self
	 */
	public function set_tenant_id( int $tenant_id ): self {
		$this->tenant_id = $tenant_id;
		return $this;
	}

	/**
	 * Get created at timestamp.
	 *
	 * @return \DateTimeImmutable|null Created at timestamp.
	 */
	public function get_created_at(): ?\DateTimeImmutable {
		return $this->created_at;
	}

	/**
	 * Set created at timestamp.
	 *
	 * @param \DateTimeImmutable|null $created_at Timestamp.
	 * @return self
	 */
	public function set_created_at( ?\DateTimeImmutable $created_at ): self {
		$this->created_at = $created_at;
		return $this;
	}

	/**
	 * Get updated at timestamp.
	 *
	 * @return \DateTimeImmutable|null Updated at timestamp.
	 */
	public function get_updated_at(): ?\DateTimeImmutable {
		return $this->updated_at;
	}

	/**
	 * Set updated at timestamp.
	 *
	 * @param \DateTimeImmutable|null $updated_at Timestamp.
	 * @return self
	 */
	public function set_updated_at( ?\DateTimeImmutable $updated_at ): self {
		$this->updated_at = $updated_at;
		return $this;
	}

	/**
	 * Get deleted at timestamp.
	 *
	 * @return \DateTimeImmutable|null Deleted at timestamp.
	 */
	public function get_deleted_at(): ?\DateTimeImmutable {
		return $this->deleted_at;
	}

	/**
	 * Set deleted at timestamp.
	 *
	 * @param \DateTimeImmutable|null $deleted_at Timestamp.
	 * @return self
	 */
	public function set_deleted_at( ?\DateTimeImmutable $deleted_at ): self {
		$this->deleted_at = $deleted_at;
		return $this;
	}

	/**
	 * Check if entity is new (not persisted).
	 *
	 * @return bool True if new.
	 */
	public function is_new(): bool {
		return null === $this->id;
	}

	/**
	 * Check if entity is deleted (soft delete).
	 *
	 * @return bool True if deleted.
	 */
	public function is_deleted(): bool {
		return null !== $this->deleted_at;
	}

	/**
	 * Convert entity to array.
	 *
	 * @return array<string, mixed> Entity data.
	 */
	abstract public function to_array(): array;

	/**
	 * Create entity from array.
	 *
	 * @param array<string, mixed> $data Entity data.
	 * @return static New entity instance.
	 */
	abstract public static function from_array( array $data ): static;
}
