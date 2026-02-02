/**
 * Main App Component.
 *
 * @package CanilCore
 */

import { HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import Dashboard from './pages/Dashboard';
import DogList from './pages/dogs/DogList';
import DogForm from './pages/dogs/DogForm';
import LitterList from './pages/litters/LitterList';
import LitterForm from './pages/litters/LitterForm';
import PuppyList from './pages/puppies/PuppyList';
import PuppyForm from './pages/puppies/PuppyForm';
import PersonList from './pages/people/PersonList';
import PersonForm from './pages/people/PersonForm';

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
				<Route path="/litters" element={<LitterList />} />
				<Route path="/litters/new" element={<LitterForm />} />
				<Route path="/litters/:id" element={<LitterForm />} />
				<Route path="/puppies" element={<PuppyList />} />
				<Route path="/puppies/new" element={<PuppyForm />} />
				<Route path="/puppies/:id" element={<PuppyForm />} />
				<Route path="/people" element={<PersonList />} />
				<Route path="/people/new" element={<PersonForm />} />
				<Route path="/people/:id" element={<PersonForm />} />
				<Route path="*" element={<Navigate to="/" replace />} />
			</Routes>
		</HashRouter>
	);
}

export default App;
