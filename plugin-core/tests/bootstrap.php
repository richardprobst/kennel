<?php
/**
 * PHPUnit bootstrap file for Canil Core.
 *
 * @package CanilCore
 */

// Define test constants.
define( 'CANIL_CORE_TESTING', true );

// Define ABSPATH to allow loading plugin files outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

// Load Composer autoloader.
$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( file_exists( $autoloader ) ) {
	require_once $autoloader;
}

// Load WordPress test library if available.
$wordpress_tests = getenv( 'WP_TESTS_DIR' );
if ( $wordpress_tests && file_exists( $wordpress_tests . '/includes/functions.php' ) ) {
	require_once $wordpress_tests . '/includes/functions.php';

	// Load plugin.
	tests_add_filter(
		'muplugins_loaded',
		function () {
			require dirname( __DIR__ ) . '/canil-core.php';
		}
	);

	require_once $wordpress_tests . '/includes/bootstrap.php';
}
