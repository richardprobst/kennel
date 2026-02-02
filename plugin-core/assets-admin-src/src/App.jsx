/**
 * Main App Component.
 *
 * @package CanilCore
 */

import { HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import Dashboard from './pages/Dashboard';
import DogList from './pages/dogs/DogList';
import DogForm from './pages/dogs/DogForm';

/**
 * App component with routing.
 *
 * @return {JSX.Element} The app component.
 */
function App() {
	return (
		<HashRouter>
			<Routes>
				<Route path="/" element={<Dashboard />} />
				<Route path="/dogs" element={<DogList />} />
				<Route path="/dogs/new" element={<DogForm />} />
				<Route path="/dogs/:id" element={<DogForm />} />
				<Route path="*" element={<Navigate to="/" replace />} />
			</Routes>
		</HashRouter>
	);
}

export default App;
