/**
 * Canil Admin Entry Point.
 *
 * @package
 */

import { createRoot } from '@wordpress/element';
import App from './App';
import './style.css';

/**
 * Map WordPress page slugs to React Router hash routes.
 */
const pageRoutes = {
	'canil-dashboard': '/',
	'canil-dogs': '/dogs',
	'canil-litters': '/litters',
	'canil-puppies': '/puppies',
	'canil-people': '/people',
	'canil-health': '/health',
	'canil-weighing': '/weighing',
	'canil-calendar': '/calendar',
	'canil-pedigree': '/pedigree',
	'canil-reports': '/reports',
};

/**
 * Initialize the correct hash route based on WordPress page slug.
 */
function initializeRoute() {
	const currentPage = window.canilAdmin?.currentPage || 'canil-dashboard';
	const targetRoute = pageRoutes[ currentPage ] || '/';

	// Only set the hash if it's empty or points to root and we need a different route.
	// Hash could be "", "#", "#/", so we normalize by removing '#' prefix.
	const currentHash = window.location.hash.replace( /^#\/?/, '' );
	if ( ! currentHash && targetRoute !== '/' ) {
		window.location.hash = targetRoute;
	}
}

const root = document.getElementById( 'canil-admin-root' );

if ( root ) {
	initializeRoute();
	createRoot( root ).render( <App /> );
}
