<?php
/**
 * Plugin capabilities.
 *
 * Defines all capabilities for the plugin.
 *
 * @package CanilCore
 */

namespace CanilCore\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Capabilities class.
 */
class Capabilities {

	/**
	 * Get all plugin capabilities.
	 *
	 * @return array<string, string> Capabilities with descriptions.
	 */
	public static function get_capabilities(): array {
		return array(
			'manage_kennel'   => __( 'Gerenciar configurações do canil', 'canil-core' ),
			'manage_dogs'     => __( 'Gerenciar cães (CRUD)', 'canil-core' ),
			'manage_litters'  => __( 'Gerenciar ninhadas (CRUD)', 'canil-core' ),
			'manage_puppies'  => __( 'Gerenciar filhotes (CRUD)', 'canil-core' ),
			'manage_people'   => __( 'Gerenciar pessoas (CRUD)', 'canil-core' ),
			'view_reports'    => __( 'Visualizar relatórios', 'canil-core' ),
			'manage_settings' => __( 'Alterar configurações', 'canil-core' ),
		);
	}

	/**
	 * Get capability keys.
	 *
	 * @return array<string> List of capability names.
	 */
	public static function get_capability_keys(): array {
		return array_keys( self::get_capabilities() );
	}

	/**
	 * Check if user has any kennel capability.
	 *
	 * @param int|null $user_id User ID. Defaults to current user.
	 * @return bool True if user has any capability.
	 */
	public static function user_has_any_capability( ?int $user_id = null ): bool {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( 0 === $user_id ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		foreach ( self::get_capability_keys() as $cap ) {
			if ( $user->has_cap( $cap ) ) {
				return true;
			}
		}

		return false;
	}
}
