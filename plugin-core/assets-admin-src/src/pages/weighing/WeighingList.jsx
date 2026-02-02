/**
 * Weighing List Page.
 *
 * Lists weight records with filters and quick stats.
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
 * WeighingList component.
 *
 * @return {JSX.Element} The weighing list component.
 */
function WeighingList() {
	const navigate = useNavigate();
	const [ records, setRecords ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ entityTypeFilter, setEntityTypeFilter ] = useState( '' );
	const [ litterFilter, setLitterFilter ] = useState( '' );
	const [ dateFrom, setDateFrom ] = useState( '' );
	const [ dateTo, setDateTo ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );

	// Quick stats.
	const [ recentWeights, setRecentWeights ] = useState( [] );
	const [ statsLoading, setStatsLoading ] = useState( true );

	// Litters for filter.
	const [ litters, setLitters ] = useState( [] );

	const entityTypeOptions = [
		{ label: __( 'Todos', 'canil-core' ), value: '' },
		{ label: __( 'Cães', 'canil-core' ), value: 'dog' },
		{ label: __( 'Filhotes', 'canil-core' ), value: 'puppy' },
	];

	const typeLabels = {
		birth_weight: __( 'Peso ao Nascer', 'canil-core' ),
		weekly: __( 'Semanal', 'canil-core' ),
		monthly: __( 'Mensal', 'canil-core' ),
		general: __( 'Geral', 'canil-core' ),
	};

	const fetchLitters = useCallback( async () => {
		try {
			const response = await apiFetch( {
				path: '/canil/v1/litters?per_page=100&status=born,weaned',
			} );
			const litterOptions = ( response.data || [] ).map( ( litter ) => ( {
				label: litter.name || litter.litter_letter || `#${ litter.id }`,
				value: litter.id.toString(),
			} ) );
			litterOptions.unshift( {
				label: __( 'Todas as Ninhadas', 'canil-core' ),
				value: '',
			} );
			setLitters( litterOptions );
		} catch ( err ) {
			// Litters are optional for filter, don't block.
			// eslint-disable-next-line no-console
			console.warn( 'Failed to load litters:', err );
		}
	}, [] );

	const fetchRecentWeights = useCallback( async () => {
		setStatsLoading( true );
		try {
			const params = new URLSearchParams( {
				per_page: '5',
				page: '1',
			} );

			const response = await apiFetch( {
				path: `/canil/v1/events?event_type=weighing&${ params.toString() }`,
			} );

			setRecentWeights( response.data || [] );
		} catch ( err ) {
			// Stats are optional, don't block the page.
			// eslint-disable-next-line no-console
			console.warn( 'Failed to load recent weights:', err );
		} finally {
			setStatsLoading( false );
		}
	}, [] );

	const fetchRecords = useCallback( async () => {
		setLoading( true );
		setError( null );

		try {
			const params = new URLSearchParams( {
				page: page.toString(),
				per_page: '20',
				event_type: 'weighing',
			} );

			if ( entityTypeFilter ) {
				params.append( 'entity_type', entityTypeFilter );
			}
			if ( litterFilter ) {
				params.append( 'litter_id', litterFilter );
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

			setRecords( response.data || [] );
			setTotalPages( response.meta?.total_pages || 1 );
		} catch ( err ) {
			setError(
				err.message ||
					__( 'Erro ao carregar registros de peso.', 'canil-core' )
			);
		} finally {
			setLoading( false );
		}
	}, [ entityTypeFilter, litterFilter, dateFrom, dateTo, page ] );

	useEffect( () => {
		fetchLitters();
		fetchRecentWeights();
	}, [ fetchLitters, fetchRecentWeights ] );

	useEffect( () => {
		fetchRecords();
	}, [ fetchRecords ] );

	const handleDelete = async ( id ) => {
		if (
			// eslint-disable-next-line no-alert
			! window.confirm(
				__(
					'Tem certeza que deseja excluir este registro?',
					'canil-core'
				)
			)
		) {
			return;
		}

		try {
			await apiFetch( {
				path: `/canil/v1/events/${ id }`,
				method: 'DELETE',
			} );
			fetchRecords();
			fetchRecentWeights();
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao excluir registro.', 'canil-core' )
			);
		}
	};

	const formatWeight = ( weight, unit ) => {
		if ( ! weight ) {
			return '-';
		}
		return `${ weight } ${ unit || 'g' }`;
	};

	const renderGainLoss = ( record ) => {
		const diff = record.payload?.weight_diff;
		if ( diff === undefined || diff === null ) {
			return '-';
		}

		const unit = record.payload?.unit || 'g';
		const sign = diff > 0 ? '+' : '';
		let className = '';
		if ( diff > 0 ) {
			className = 'canil-weight-gain';
		} else if ( diff < 0 ) {
			className = 'canil-weight-loss';
		}

		return (
			<span className={ className }>
				{ sign }
				{ diff } { unit }
			</span>
		);
	};

	return (
		<div className="canil-weighing-list">
			<div className="canil-page-header">
				<h1>{ __( 'Pesagens', 'canil-core' ) }</h1>
				<div className="canil-page-header-actions">
					<Button
						variant="secondary"
						onClick={ () => navigate( '/weighing/batch' ) }
					>
						{ __( 'Pesagem em Lote', 'canil-core' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ () => navigate( '/weighing/new' ) }
					>
						{ __( 'Registrar Peso', 'canil-core' ) }
					</Button>
				</div>
			</div>

			{ /* Quick Stats - Recent Weights */ }
			<div className="canil-weighing-stats">
				<Card>
					<CardBody>
						<h3>{ __( 'Últimas Pesagens', 'canil-core' ) }</h3>
						{ statsLoading && <Spinner /> }
						{ ! statsLoading && recentWeights.length === 0 && (
							<p className="canil-empty-message">
								{ __(
									'Nenhuma pesagem recente.',
									'canil-core'
								) }
							</p>
						) }
						{ ! statsLoading && recentWeights.length > 0 && (
							<div className="canil-recent-weights">
								{ recentWeights.map( ( record ) => (
									<div
										key={ record.id }
										className="canil-recent-weight-item"
									>
										<span className="canil-animal-name">
											{ record.entity_type === 'dog'
												? record.dog_name
												: record.puppy_name ||
												  `${ __(
														'Filhote',
														'canil-core'
												  ) } #${ record.entity_id }` }
										</span>
										<span className="canil-weight-value">
											{ formatWeight(
												record.payload?.weight,
												record.payload?.unit
											) }
										</span>
										<span className="canil-weight-date">
											{ record.event_date }
										</span>
									</div>
								) ) }
							</div>
						) }
					</CardBody>
				</Card>
			</div>

			<Card>
				<CardBody>
					<div className="canil-filters">
						<SelectControl
							label={ __( 'Tipo de Animal', 'canil-core' ) }
							value={ entityTypeFilter }
							options={ entityTypeOptions }
							onChange={ ( value ) => {
								setEntityTypeFilter( value );
								if ( value !== 'puppy' ) {
									setLitterFilter( '' );
								}
							} }
						/>
						{ entityTypeFilter === 'puppy' && (
							<SelectControl
								label={ __( 'Ninhada', 'canil-core' ) }
								value={ litterFilter }
								options={ litters }
								onChange={ setLitterFilter }
							/>
						) }
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

					{ ! loading && records.length === 0 && (
						<div className="canil-empty-state">
							<p>
								{ __(
									'Nenhum registro de peso encontrado.',
									'canil-core'
								) }
							</p>
							<Button
								variant="primary"
								onClick={ () => navigate( '/weighing/new' ) }
							>
								{ __(
									'Registrar Primeiro Peso',
									'canil-core'
								) }
							</Button>
						</div>
					) }

					{ ! loading && records.length > 0 && (
						<>
							<table className="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th>{ __( 'Data', 'canil-core' ) }</th>
										<th>
											{ __( 'Animal', 'canil-core' ) }
										</th>
										<th>{ __( 'Peso', 'canil-core' ) }</th>
										<th>{ __( 'Tipo', 'canil-core' ) }</th>
										<th>
											{ __( 'Variação', 'canil-core' ) }
										</th>
										<th>{ __( 'Ações', 'canil-core' ) }</th>
									</tr>
								</thead>
								<tbody>
									{ records.map( ( record ) => (
										<tr key={ record.id }>
											<td>{ record.event_date }</td>
											<td>
												{ record.entity_type ===
													'dog' &&
													record.dog_name && (
														<Link
															to={ `/dogs/${ record.entity_id }` }
														>
															{ record.dog_name }
														</Link>
													) }
												{ record.entity_type ===
													'puppy' && (
													<Link
														to={ `/puppies/${ record.entity_id }` }
													>
														{ record.puppy_name ||
															`${ __(
																'Filhote',
																'canil-core'
															) } #${
																record.entity_id
															}` }
													</Link>
												) }
												{ ! record.dog_name &&
													! record.puppy_name &&
													record.entity_type ===
														'dog' && (
														<span>
															{ __(
																'Cão',
																'canil-core'
															) }{ ' ' }
															#
															{ record.entity_id }
														</span>
													) }
											</td>
											<td>
												<strong>
													{ formatWeight(
														record.payload?.weight,
														record.payload?.unit
													) }
												</strong>
											</td>
											<td>
												<span className="canil-badge">
													{ typeLabels[
														record.payload
															?.weight_type
													] ||
														record.payload
															?.weight_type ||
														'-' }
												</span>
											</td>
											<td>
												{ renderGainLoss( record ) }
											</td>
											<td>
												<Link
													to={ `/weighing/chart/${ record.entity_type }/${ record.entity_id }` }
													className="button button-secondary button-small"
												>
													{ __(
														'Gráfico',
														'canil-core'
													) }
												</Link>
												<Button
													variant="tertiary"
													size="small"
													isDestructive
													onClick={ () =>
														handleDelete(
															record.id
														)
													}
												>
													{ __(
														'Excluir',
														'canil-core'
													) }
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

export default WeighingList;
