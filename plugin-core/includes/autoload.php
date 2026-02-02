<?php
/**
 * Autoloader for Canil Core classes.
 *
 * @package CanilCore
 */

namespace CanilCore;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	function ( string $class ): void {
		// Only autoload classes from our namespace.
		$prefix = 'CanilCore\\';
		$len    = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		// Get the relative class name.
		$relative_class = substr( $class, $len );

		// Replace namespace separators with directory separators.
		$file = CANIL_CORE_PATH . 'includes/' . str_replace( '\\', '/', $relative_class ) . '.php';

		// If the file exists, require it.
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);
