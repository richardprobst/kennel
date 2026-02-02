/**
 * Dashboard Page.
 *
 * @package
 */

import { __ } from '@wordpress/i18n';
import { Card, CardBody, CardHeader } from '@wordpress/components';
import { Link } from 'react-router-dom';

/**
 * Dashboard component.
 *
 * @return {JSX.Element} The dashboard component.
 */
function Dashboard() {
	return (
		<div className="canil-dashboard">
			<h1>{ __( 'Dashboard', 'canil-core' ) }</h1>

			<div className="canil-dashboard-cards">
				<Card>
					<CardHeader>
						<h2>{ __( 'Cães', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<p>
							{ __(
								'Gerencie o plantel de cães do seu canil.',
								'canil-core'
							) }
						</p>
						<Link
							to="/dogs"
							className="components-button is-primary"
						>
							{ __( 'Ver Cães', 'canil-core' ) }
						</Link>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{ __( 'Ninhadas', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<p>
							{ __(
								'Acompanhe as ninhadas e gestações.',
								'canil-core'
							) }
						</p>
						<Link
							to="/litters"
							className="components-button is-primary"
						>
							{ __( 'Ver Ninhadas', 'canil-core' ) }
						</Link>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{ __( 'Filhotes', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<p>
							{ __(
								'Gerencie os filhotes de cada ninhada.',
								'canil-core'
							) }
						</p>
						<Link
							to="/puppies"
							className="components-button is-primary"
						>
							{ __( 'Ver Filhotes', 'canil-core' ) }
						</Link>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{ __( 'Pessoas', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<p>
							{ __(
								'Gerencie interessados, compradores e veterinários.',
								'canil-core'
							) }
						</p>
						<Link
							to="/people"
							className="components-button is-primary"
						>
							{ __( 'Ver Pessoas', 'canil-core' ) }
						</Link>
					</CardBody>
				</Card>
			</div>
		</div>
	);
}

export default Dashboard;
