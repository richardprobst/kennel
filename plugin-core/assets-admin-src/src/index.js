/**
 * Canil Admin Entry Point.
 *
 * @package CanilCore
 */

import { createRoot } from '@wordpress/element';
import App from './App';
import './style.css';

const root = document.getElementById('canil-admin-root');

if (root) {
	createRoot(root).render(<App />);
}
