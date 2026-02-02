/**
 * Health List Page.
 *
 * Lists health events (vaccines, dewormings, exams, medications, surgeries, vet visits).
 *
 * @package
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Card,
	CardBody,
	Spinner,
	Notice,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { Link, useNavigate } from 'react-router-dom';

/**
 * HealthList component.
 *
 * @return {JSX.Element} The health list component.
 */
function HealthList() {
	const navigate = useNavigate();
	const [ events, setEvents ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ typeFilter, setTypeFilter ] = useState( '' );
	const [ entityTypeFilter, setEntityTypeFilter ] = useState( '' );
	const [ dateFrom, setDateFrom ] = useState( '' );
	const [ dateTo, setDateTo ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );

	// Dashboard stats.
	const [ stats, setStats ] = useState( {
		upcomingVaccines: 0,
		upcomingDewormings: 0,
		overdue: 0,
	} );
	const [ statsLoading, setStatsLoading ] = useState( true );

	const typeOptions = [
		{ label: __( 'Todos', 'canil-core' ), value: '' },
		{ label: __( 'Vacinas', 'canil-core' ), value: 'vaccine' },
		{ label: __( 'Vermífugos', 'canil-core' ), value: 'deworming' },
		{ label: __( 'Exames', 'canil-core' ), value: 'exam' },
		{ label: __( 'Medicamentos', 'canil-core' ), value: 'medication' },
		{ label: __( 'Cirurgias', 'canil-core' ), value: 'surgery' },
		{ label: __( 'Consultas', 'canil-core' ), value: 'vet_visit' },
	];

	const entityTypeOptions = [
		{ label: __( 'Todos', 'canil-core' ), value: '' },
		{ label: __( 'Cães', 'canil-core' ), value: 'dog' },
		{ label: __( 'Filhotes', 'canil-core' ), value: 'puppy' },
	];

	const typeLabels = {
		vaccine: __( 'Vacina', 'canil-core' ),
		deworming: __( 'Vermífugo', 'canil-core' ),
		exam: __( 'Exame', 'canil-core' ),
		medication: __( 'Medicamento', 'canil-core' ),
		surgery: __( 'Cirurgia', 'canil-core' ),
		vet_visit: __( 'Consulta', 'canil-core' ),
	};

	const fetchStats = useCallback( async () => {
		setStatsLoading( true );
		try {
			const [ vaccinesRes, dewormingsRes, overdueRes ] = await Promise.all( [
				apiFetch( { path: '/canil/v1/health/upcoming-vaccines' } ),
				apiFetch( { path: '/canil/v1/health/upcoming-dewormings' } ),
				apiFetch( { path: '/canil/v1/health/overdue' } ),
			] );

			setStats( {
				upcomingVaccines: vaccinesRes.meta?.total || 0,
				upcomingDewormings: dewormingsRes.meta?.total || 0,
				overdue: overdueRes.meta?.total || 0,
			} );
		} catch ( err ) {
			// Stats are optional, don't block the page.
			// eslint-disable-next-line no-console
			console.warn( 'Failed to load health stats:', err );
		} finally {
			setStatsLoading( false );
		}
	}, [] );

	const fetchEvents = useCallback( async () => {
		setLoading( true );
		setError( null );

		try {
			const params = new URLSearchParams( {
				page: page.toString(),
				per_page: '20',
			} );

			if ( typeFilter ) {
				params.append( 'event_type', typeFilter );
			}
			if ( entityTypeFilter ) {
				params.append( 'entity_type', entityTypeFilter );
			}
			if ( dateFrom ) {
				params.append( 'date_from', dateFrom );
			}
			if ( dateTo ) {
				params.append( 'date_to', dateTo );
			}

			const response = await apiFetch( {
				path: `/canil/v1/events?${ params.toString() }`,
			} );

			// Filter to only health-related events.
			const healthTypes = [ 'vaccine', 'deworming', 'exam', 'medication', 'surgery', 'vet_visit' ];
			const healthEvents = ( response.data || [] ).filter(
				( event ) => healthTypes.includes( event.event_type )
			);

			setEvents( healthEvents );
			setTotalPages( response.meta?.total_pages || 1 );
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao carregar eventos de saúde.', 'canil-core' )
			);
		} finally {
			setLoading( false );
		}
	}, [ typeFilter, entityTypeFilter, dateFrom, dateTo, page ] );

	useEffect( () => {
		fetchStats();
	}, [ fetchStats ] );

	useEffect( () => {
		fetchEvents();
	}, [ fetchEvents ] );

	const handleDelete = async ( id ) => {
		if (
			// eslint-disable-next-line no-alert
			! window.confirm(
				__( 'Tem certeza que deseja excluir este evento?', 'canil-core' )
			)
		) {
			return;
		}

		try {
			await apiFetch( {
				path: `/canil/v1/events/${ id }`,
				method: 'DELETE',
			} );
			fetchEvents();
			fetchStats();
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao excluir evento.', 'canil-core' )
			);
		}
	};

	const handleTypeFilterClick = ( type ) => {
		setTypeFilter( type );
		setPage( 1 );
	};

	const formatEventDetails = ( event ) => {
		const details = [];

		if ( event.payload ) {
			if ( event.payload.name ) {
				details.push( event.payload.name );
			}
			if ( event.payload.product ) {
				details.push( event.payload.product );
			}
			if ( event.payload.exam_type ) {
				details.push( event.payload.exam_type );
			}
			if ( event.payload.surgery_type ) {
				details.push( event.payload.surgery_type );
			}
			if ( event.payload.reason ) {
				details.push( event.payload.reason );
			}
			if ( event.payload.veterinarian ) {
				details.push( `Vet: ${ event.payload.veterinarian }` );
			}
		}

		return details.join( ' - ' ) || '-';
	};

	return (
		<div className="canil-health-list">
			<div className="canil-page-header">
				<h1>{ __( 'Saúde', 'canil-core' ) }</h1>
				<Button
					variant="primary"
					onClick={ () => navigate( '/health/new' ) }
				>
					{ __( 'Adicionar Evento', 'canil-core' ) }
				</Button>
			</div>

			{ /* Dashboard Widgets */ }
			<div className="canil-health-dashboard">
				<Card className="canil-stat-card canil-stat-warning">
					<CardBody>
						<div className="canil-stat-value">
							{ statsLoading ? <Spinner /> : stats.upcomingVaccines }
						</div>
						<div className="canil-stat-label">
							{ __( 'Vacinas Próximas', 'canil-core' ) }
						</div>
					</CardBody>
				</Card>
				<Card className="canil-stat-card canil-stat-info">
					<CardBody>
						<div className="canil-stat-value">
							{ statsLoading ? <Spinner /> : stats.upcomingDewormings }
						</div>
						<div className="canil-stat-label">
							{ __( 'Vermífugos Próximos', 'canil-core' ) }
						</div>
					</CardBody>
				</Card>
				<Card className="canil-stat-card canil-stat-danger">
					<CardBody>
						<div className="canil-stat-value">
							{ statsLoading ? <Spinner /> : stats.overdue }
						</div>
						<div className="canil-stat-label">
							{ __( 'Atrasados', 'canil-core' ) }
						</div>
					</CardBody>
				</Card>
			</div>

			{ /* Type Filter Tabs */ }
			<div className="canil-health-tabs">
				<Button
					variant={ typeFilter === '' ? 'primary' : 'secondary' }
					onClick={ () => handleTypeFilterClick( '' ) }
				>
					{ __( 'Todos', 'canil-core' ) }
				</Button>
				<Button
					variant={ typeFilter === 'vaccine' ? 'primary' : 'secondary' }
					onClick={ () => handleTypeFilterClick( 'vaccine' ) }
				>
					{ __( 'Vacinas', 'canil-core' ) }
				</Button>
				<Button
					variant={ typeFilter === 'deworming' ? 'primary' : 'secondary' }
					onClick={ () => handleTypeFilterClick( 'deworming' ) }
				>
					{ __( 'Vermífugos', 'canil-core' ) }
				</Button>
				<Button
					variant={ typeFilter === 'exam' ? 'primary' : 'secondary' }
					onClick={ () => handleTypeFilterClick( 'exam' ) }
				>
					{ __( 'Exames', 'canil-core' ) }
				</Button>
				<Button
					variant={ typeFilter === 'medication' ? 'primary' : 'secondary' }
					onClick={ () => handleTypeFilterClick( 'medication' ) }
				>
					{ __( 'Medicamentos', 'canil-core' ) }
				</Button>
				<Button
					variant={ typeFilter === 'surgery' ? 'primary' : 'secondary' }
					onClick={ () => handleTypeFilterClick( 'surgery' ) }
				>
					{ __( 'Cirurgias', 'canil-core' ) }
				</Button>
			</div>

			<Card>
				<CardBody>
					<div className="canil-filters">
						<SelectControl
							label={ __( 'Tipo de Entidade', 'canil-core' ) }
							value={ entityTypeFilter }
							options={ entityTypeOptions }
							onChange={ setEntityTypeFilter }
						/>
						<TextControl
							label={ __( 'Data Início', 'canil-core' ) }
							type="date"
							value={ dateFrom }
							onChange={ setDateFrom }
						/>
						<TextControl
							label={ __( 'Data Fim', 'canil-core' ) }
							type="date"
							value={ dateTo }
							onChange={ setDateTo }
						/>
					</div>

					{ error && (
						<Notice status="error" isDismissible={ false }>
							{ error }
						</Notice>
					) }

					{ loading && (
						<div className="canil-loading">
							<Spinner />
						</div>
					) }

					{ ! loading && events.length === 0 && (
						<div className="canil-empty-state">
							<p>
								{ __( 'Nenhum evento de saúde encontrado.', 'canil-core' ) }
							</p>
							<Button
								variant="primary"
								onClick={ () => navigate( '/health/new' ) }
							>
								{ __( 'Adicionar Primeiro Evento', 'canil-core' ) }
							</Button>
						</div>
					) }

					{ ! loading && events.length > 0 && (
						<>
							<table className="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th>{ __( 'Data', 'canil-core' ) }</th>
										<th>{ __( 'Tipo', 'canil-core' ) }</th>
										<th>{ __( 'Animal', 'canil-core' ) }</th>
										<th>{ __( 'Detalhes', 'canil-core' ) }</th>
										<th>{ __( 'Ações', 'canil-core' ) }</th>
									</tr>
								</thead>
								<tbody>
									{ events.map( ( event ) => (
										<tr key={ event.id }>
											<td>{ event.event_date }</td>
											<td>
												<span className={ `canil-badge canil-badge-${ event.event_type }` }>
													{ typeLabels[ event.event_type ] || event.event_type }
												</span>
											</td>
											<td>
												{ event.entity_type === 'dog' && event.dog_name && (
													<Link to={ `/dogs/${ event.entity_id }` }>
														{ event.dog_name }
													</Link>
												) }
												{ event.entity_type === 'puppy' && event.puppy_name && (
													<Link to={ `/puppies/${ event.entity_id }` }>
														{ event.puppy_name }
													</Link>
												) }
												{ ! event.dog_name && ! event.puppy_name && (
													<span>
														{ event.entity_type === 'dog'
															? __( 'Cão', 'canil-core' )
															: __( 'Filhote', 'canil-core' ) }{ ' ' }
														#{ event.entity_id }
													</span>
												) }
											</td>
											<td>{ formatEventDetails( event ) }</td>
											<td>
												<Button
													variant="secondary"
													size="small"
													onClick={ () =>
														navigate( `/health/${ event.id }` )
													}
												>
													{ __( 'Editar', 'canil-core' ) }
												</Button>
												<Button
													variant="tertiary"
													size="small"
													isDestructive
													onClick={ () => handleDelete( event.id ) }
												>
													{ __( 'Excluir', 'canil-core' ) }
												</Button>
											</td>
										</tr>
									) ) }
								</tbody>
							</table>

							{ totalPages > 1 && (
								<div className="canil-pagination">
									<Button
										variant="secondary"
										disabled={ page <= 1 }
										onClick={ () => setPage( page - 1 ) }
									>
										{ __( 'Anterior', 'canil-core' ) }
									</Button>
									<span>
										{ __( 'Página', 'canil-core' ) }{ ' ' }
										{ page } { __( 'de', 'canil-core' ) }{ ' ' }
										{ totalPages }
									</span>
									<Button
										variant="secondary"
										disabled={ page >= totalPages }
										onClick={ () => setPage( page + 1 ) }
									>
										{ __( 'Próxima', 'canil-core' ) }
									</Button>
								</div>
							) }
						</>
					) }
				</CardBody>
			</Card>
		</div>
	);
}

export default HealthList;
