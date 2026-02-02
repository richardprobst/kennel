/**
 * Weight Chart Page.
 *
 * Displays weight evolution chart for a specific entity.
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
	CardHeader,
	Spinner,
	Notice,
	SelectControl,
} from '@wordpress/components';
import { useNavigate, useParams } from 'react-router-dom';

/**
 * WeightChart component for displaying weight evolution.
 *
 * @return {JSX.Element} The weight chart component.
 */
function WeightChart() {
	const navigate = useNavigate();
	const { entityType: paramEntityType, entityId: paramEntityId } =
		useParams();

	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState( null );

	// Entity selection.
	const [ entityType, setEntityType ] = useState(
		paramEntityType || 'puppy'
	);
	const [ entityId, setEntityId ] = useState( paramEntityId || '' );
	const [ dogs, setDogs ] = useState( [] );
	const [ puppies, setPuppies ] = useState( [] );
	const [ loadingEntities, setLoadingEntities ] = useState( true );

	// Weight data.
	const [ weightData, setWeightData ] = useState( [] );
	const [ entityName, setEntityName ] = useState( '' );

	const entityTypeOptions = [
		{ label: __( 'Filhote', 'canil-core' ), value: 'puppy' },
		{ label: __( 'Cão', 'canil-core' ), value: 'dog' },
	];

	// Fetch entities for selection.
	const fetchEntities = useCallback( async () => {
		setLoadingEntities( true );
		try {
			const [ dogsRes, puppiesRes ] = await Promise.all( [
				apiFetch( { path: '/canil/v1/dogs?per_page=100' } ),
				apiFetch( { path: '/canil/v1/puppies?per_page=100' } ),
			] );

			const dogOptions = ( dogsRes.data || [] ).map( ( dog ) => ( {
				label:
					dog.name + ( dog.call_name ? ` (${ dog.call_name })` : '' ),
				value: dog.id.toString(),
			} ) );
			dogOptions.unshift( {
				label: __( 'Selecione um cão', 'canil-core' ),
				value: '',
			} );
			setDogs( dogOptions );

			const puppyOptions = ( puppiesRes.data || [] ).map( ( puppy ) => ( {
				label:
					puppy.name ||
					puppy.identifier ||
					`${ __( 'Filhote', 'canil-core' ) } #${ puppy.id }`,
				value: puppy.id.toString(),
			} ) );
			puppyOptions.unshift( {
				label: __( 'Selecione um filhote', 'canil-core' ),
				value: '',
			} );
			setPuppies( puppyOptions );
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao carregar animais.', 'canil-core' )
			);
		} finally {
			setLoadingEntities( false );
		}
	}, [] );

	// Helper function for weight diff class name.
	const getWeightDiffClass = ( diff ) => {
		if ( diff > 0 ) {
			return 'canil-weight-gain';
		}
		if ( diff < 0 ) {
			return 'canil-weight-loss';
		}
		return '';
	};

	// Fetch weight evolution data.
	const fetchWeightData = useCallback( async () => {
		if ( ! entityId ) {
			setWeightData( [] );
			setEntityName( '' );
			return;
		}

		setLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `/canil/v1/weighing/evolution/${ entityType }/${ entityId }`,
			} );

			setWeightData( response.data || [] );
			setEntityName( response.entity_name || '' );
		} catch ( err ) {
			setError(
				err.message ||
					__( 'Erro ao carregar dados de peso.', 'canil-core' )
			);
		} finally {
			setLoading( false );
		}
	}, [ entityType, entityId ] );

	useEffect( () => {
		fetchEntities();
	}, [ fetchEntities ] );

	useEffect( () => {
		fetchWeightData();
	}, [ fetchWeightData ] );

	// SVG Chart rendering.
	const renderChart = () => {
		if ( weightData.length === 0 ) {
			return null;
		}

		const chartWidth = 600;
		const chartHeight = 300;
		const padding = { top: 20, right: 30, bottom: 40, left: 60 };
		const innerWidth = chartWidth - padding.left - padding.right;
		const innerHeight = chartHeight - padding.top - padding.bottom;

		// Normalize weights to same unit (grams).
		const normalizedData = weightData.map( ( record ) => {
			let weightInGrams = record.weight;
			const recordUnit = record.unit || 'g';

			if ( recordUnit === 'kg' ) {
				weightInGrams = record.weight * 1000;
			} else if ( recordUnit === 'lb' ) {
				weightInGrams = record.weight * 453.592;
			}

			return {
				...record,
				normalizedWeight: weightInGrams,
			};
		} );

		// Calculate scales.
		const weights = normalizedData.map( ( d ) => d.normalizedWeight );
		const minWeight = Math.min( ...weights ) * 0.9;
		const maxWeight = Math.max( ...weights ) * 1.1;

		const xScale = ( index ) =>
			padding.left +
			( index / ( normalizedData.length - 1 || 1 ) ) * innerWidth;

		const yScale = ( weight ) =>
			padding.top +
			innerHeight -
			( ( weight - minWeight ) / ( maxWeight - minWeight || 1 ) ) *
				innerHeight;

		// Generate path.
		const pathData = normalizedData
			.map( ( d, i ) => {
				const x = xScale( i );
				const y = yScale( d.normalizedWeight );
				return `${ i === 0 ? 'M' : 'L' } ${ x } ${ y }`;
			} )
			.join( ' ' );

		// Y-axis ticks.
		const yTicks = 5;
		const yTickValues = Array.from( { length: yTicks }, ( _, i ) => {
			return (
				minWeight + ( ( maxWeight - minWeight ) * i ) / ( yTicks - 1 )
			);
		} );

		// Determine display unit.
		const maxWeightGrams = Math.max( ...weights );
		const displayUnit = maxWeightGrams >= 1000 ? 'kg' : 'g';
		const formatWeight = ( w ) => {
			if ( displayUnit === 'kg' ) {
				return ( w / 1000 ).toFixed( 2 );
			}
			return Math.round( w );
		};

		return (
			<svg
				viewBox={ `0 0 ${ chartWidth } ${ chartHeight }` }
				className="canil-weight-chart-svg"
				style={ {
					width: '100%',
					maxWidth: chartWidth,
					height: 'auto',
				} }
			>
				{ /* Grid lines */ }
				{ yTickValues.map( ( tick, i ) => (
					<line
						key={ `grid-${ i }` }
						x1={ padding.left }
						y1={ yScale( tick ) }
						x2={ chartWidth - padding.right }
						y2={ yScale( tick ) }
						stroke="#e0e0e0"
						strokeWidth="1"
					/>
				) ) }

				{ /* Y-axis */ }
				<line
					x1={ padding.left }
					y1={ padding.top }
					x2={ padding.left }
					y2={ chartHeight - padding.bottom }
					stroke="#333"
					strokeWidth="1"
				/>

				{ /* Y-axis ticks */ }
				{ yTickValues.map( ( tick, i ) => (
					<g key={ `y-tick-${ i }` }>
						<line
							x1={ padding.left - 5 }
							y1={ yScale( tick ) }
							x2={ padding.left }
							y2={ yScale( tick ) }
							stroke="#333"
							strokeWidth="1"
						/>
						<text
							x={ padding.left - 10 }
							y={ yScale( tick ) }
							textAnchor="end"
							alignmentBaseline="middle"
							fontSize="12"
							fill="#666"
						>
							{ formatWeight( tick ) }
						</text>
					</g>
				) ) }

				{ /* Y-axis label */ }
				<text
					x={ 15 }
					y={ chartHeight / 2 }
					transform={ `rotate(-90, 15, ${ chartHeight / 2 })` }
					textAnchor="middle"
					fontSize="12"
					fill="#666"
				>
					{ __( 'Peso', 'canil-core' ) } ({ displayUnit })
				</text>

				{ /* X-axis */ }
				<line
					x1={ padding.left }
					y1={ chartHeight - padding.bottom }
					x2={ chartWidth - padding.right }
					y2={ chartHeight - padding.bottom }
					stroke="#333"
					strokeWidth="1"
				/>

				{ /* X-axis labels (dates) */ }
				{ normalizedData.map( ( d, i ) => {
					// Only show some labels to avoid overlap.
					if (
						normalizedData.length > 10 &&
						i % Math.ceil( normalizedData.length / 10 ) !== 0
					) {
						return null;
					}
					return (
						<text
							key={ `x-label-${ i }` }
							x={ xScale( i ) }
							y={ chartHeight - padding.bottom + 20 }
							textAnchor="middle"
							fontSize="10"
							fill="#666"
						>
							{ d.date }
						</text>
					);
				} ) }

				{ /* Line */ }
				<path
					d={ pathData }
					fill="none"
					stroke="#0073aa"
					strokeWidth="2"
				/>

				{ /* Data points */ }
				{ normalizedData.map( ( d, i ) => (
					<circle
						key={ `point-${ i }` }
						cx={ xScale( i ) }
						cy={ yScale( d.normalizedWeight ) }
						r="4"
						fill="#0073aa"
						stroke="#fff"
						strokeWidth="2"
					>
						<title>
							{ `${ d.date }: ${ d.weight } ${ d.unit || 'g' }` }
						</title>
					</circle>
				) ) }
			</svg>
		);
	};

	const entityOptions = entityType === 'dog' ? dogs : puppies;

	return (
		<div className="canil-weight-chart">
			<div className="canil-page-header">
				<h1>
					{ __( 'Evolução de Peso', 'canil-core' ) }
					{ entityName && ` - ${ entityName }` }
				</h1>
				<Button
					variant="secondary"
					onClick={ () => navigate( '/weighing' ) }
				>
					{ __( 'Voltar', 'canil-core' ) }
				</Button>
			</div>

			{ error && (
				<Notice
					status="error"
					isDismissible
					onDismiss={ () => setError( null ) }
				>
					{ error }
				</Notice>
			) }

			<Card>
				<CardHeader>
					<h2>{ __( 'Selecionar Animal', 'canil-core' ) }</h2>
				</CardHeader>
				<CardBody>
					<div className="canil-form-row">
						<SelectControl
							label={ __( 'Tipo de Animal', 'canil-core' ) }
							value={ entityType }
							options={ entityTypeOptions }
							onChange={ ( value ) => {
								setEntityType( value );
								setEntityId( '' );
							} }
						/>
						<SelectControl
							label={ __( 'Animal', 'canil-core' ) }
							value={ entityId }
							options={ entityOptions }
							onChange={ setEntityId }
							disabled={ loadingEntities }
						/>
					</div>
				</CardBody>
			</Card>

			{ loading && (
				<div className="canil-loading">
					<Spinner />
				</div>
			) }

			{ ! loading && entityId && weightData.length === 0 && (
				<Card>
					<CardBody>
						<div className="canil-empty-state">
							<p>
								{ __(
									'Nenhum registro de peso encontrado para este animal.',
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
					</CardBody>
				</Card>
			) }

			{ ! loading && weightData.length > 0 && (
				<>
					<Card>
						<CardHeader>
							<h2>
								{ __( 'Gráfico de Evolução', 'canil-core' ) }
							</h2>
						</CardHeader>
						<CardBody>
							<div className="canil-chart-container">
								{ renderChart() }
							</div>
						</CardBody>
					</Card>

					<Card>
						<CardHeader>
							<h2>
								{ __( 'Histórico de Pesagens', 'canil-core' ) }
							</h2>
						</CardHeader>
						<CardBody>
							<table className="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th>{ __( 'Data', 'canil-core' ) }</th>
										<th>{ __( 'Peso', 'canil-core' ) }</th>
										<th>{ __( 'Tipo', 'canil-core' ) }</th>
										<th>
											{ __( 'Variação', 'canil-core' ) }
										</th>
									</tr>
								</thead>
								<tbody>
									{ /* Reverse for newest first display */ }
									{ [ ...weightData ]
										.reverse()
										.map(
											( record, index, reversedData ) => {
												// Get previous record (older one in original order, which is next in reversed array)
												const prevRecord =
													reversedData[ index + 1 ];
												let diff = null;

												if ( prevRecord ) {
													// Convert to same unit for comparison.
													let currentWeight =
														record.weight;
													let prevWeight =
														prevRecord.weight;
													const currentUnit =
														record.unit || 'g';
													const prevUnit =
														prevRecord.unit || 'g';

													// Convert both to grams for comparison.
													if (
														currentUnit === 'kg'
													) {
														currentWeight *= 1000;
													} else if (
														currentUnit === 'lb'
													) {
														currentWeight *= 453.592;
													}

													if ( prevUnit === 'kg' ) {
														prevWeight *= 1000;
													} else if (
														prevUnit === 'lb'
													) {
														prevWeight *= 453.592;
													}

													diff =
														currentWeight -
														prevWeight;
												}

												const typeLabels = {
													birth_weight: __(
														'Peso ao Nascer',
														'canil-core'
													),
													weekly: __(
														'Semanal',
														'canil-core'
													),
													monthly: __(
														'Mensal',
														'canil-core'
													),
													general: __(
														'Geral',
														'canil-core'
													),
												};

												return (
													<tr
														key={
															record.id || index
														}
													>
														<td>{ record.date }</td>
														<td>
															<strong>
																{
																	record.weight
																}{ ' ' }
																{ record.unit ||
																	'g' }
															</strong>
														</td>
														<td>
															<span className="canil-badge">
																{ typeLabels[
																	record
																		.weight_type
																] ||
																	record.weight_type ||
																	'-' }
															</span>
														</td>
														<td>
															{ diff !== null ? (
																<span
																	className={ getWeightDiffClass(
																		diff
																	) }
																>
																	{ diff > 0
																		? '+'
																		: '' }
																	{ diff >=
																		1000 ||
																	diff <=
																		-1000
																		? (
																				diff /
																				1000
																		  ).toFixed(
																				2
																		  ) +
																		  ' kg'
																		: Math.round(
																				diff
																		  ) +
																		  ' g' }
																</span>
															) : (
																'-'
															) }
														</td>
													</tr>
												);
											}
										) }
								</tbody>
							</table>
						</CardBody>
					</Card>
				</>
			) }
		</div>
	);
}

export default WeightChart;
