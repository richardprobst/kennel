/**
 * Batch Weighing Page.
 *
 * Form for batch weighing puppies in a litter.
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
	TextControl,
	SelectControl,
} from '@wordpress/components';
import { useNavigate } from 'react-router-dom';

/**
 * BatchWeighing component for batch weighing puppies in a litter.
 *
 * @return {JSX.Element} The batch weighing form component.
 */
function BatchWeighing() {
	const navigate = useNavigate();

	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ success, setSuccess ] = useState( null );

	// Litter selection.
	const [ litters, setLitters ] = useState( [] );
	const [ loadingLitters, setLoadingLitters ] = useState( true );
	const [ selectedLitter, setSelectedLitter ] = useState( '' );

	// Puppies data.
	const [ puppies, setPuppies ] = useState( [] );
	const [ loadingPuppies, setLoadingPuppies ] = useState( false );
	const [ puppyWeights, setPuppyWeights ] = useState( {} );
	const [ previousWeights, setPreviousWeights ] = useState( {} );

	// Shared form data.
	const [ eventDate, setEventDate ] = useState(
		new Date().toISOString().split( 'T' )[ 0 ]
	);
	const [ unit, setUnit ] = useState( 'g' );
	const [ weightType, setWeightType ] = useState( 'weekly' );

	const unitOptions = [
		{ label: __( 'Gramas (g)', 'canil-core' ), value: 'g' },
		{ label: __( 'Quilogramas (kg)', 'canil-core' ), value: 'kg' },
		{ label: __( 'Libras (lb)', 'canil-core' ), value: 'lb' },
	];

	const weightTypeOptions = [
		{ label: __( 'Peso ao Nascer', 'canil-core' ), value: 'birth_weight' },
		{ label: __( 'Semanal', 'canil-core' ), value: 'weekly' },
		{ label: __( 'Mensal', 'canil-core' ), value: 'monthly' },
		{ label: __( 'Geral', 'canil-core' ), value: 'general' },
	];

	// Fetch litters.
	const fetchLitters = useCallback( async () => {
		setLoadingLitters( true );
		try {
			const response = await apiFetch( {
				path: '/canil/v1/litters?per_page=100&status=born,weaned',
			} );

			const litterOptions = ( response.data || [] ).map( ( litter ) => ( {
				label: `${
					litter.name || litter.litter_letter || `#${ litter.id }`
				} - ${ litter.puppies_alive_count || 0 } ${ __(
					'filhotes',
					'canil-core'
				) }`,
				value: litter.id.toString(),
			} ) );

			litterOptions.unshift( {
				label: __( 'Selecione uma ninhada', 'canil-core' ),
				value: '',
			} );

			setLitters( litterOptions );
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao carregar ninhadas.', 'canil-core' )
			);
		} finally {
			setLoadingLitters( false );
		}
	}, [] );

	// Fetch puppies for selected litter.
	const fetchPuppies = useCallback( async ( litterId ) => {
		if ( ! litterId ) {
			setPuppies( [] );
			setPuppyWeights( {} );
			setPreviousWeights( {} );
			return;
		}

		setLoadingPuppies( true );
		setError( null );

		try {
			// Fetch puppies in the litter.
			const puppiesRes = await apiFetch( {
				path: `/canil/v1/puppies?litter_id=${ litterId }&per_page=100`,
			} );

			const puppiesData = puppiesRes.data || [];
			setPuppies( puppiesData );

			// Initialize weights.
			const initialWeights = {};
			puppiesData.forEach( ( puppy ) => {
				initialWeights[ puppy.id ] = '';
			} );
			setPuppyWeights( initialWeights );

			// Fetch previous weights for all puppies.
			const prevWeights = {};
			await Promise.all(
				puppiesData.map( async ( puppy ) => {
					try {
						const historyRes = await apiFetch( {
							path: `/canil/v1/weighing/history/puppy/${ puppy.id }?per_page=1`,
						} );

						if ( historyRes.data && historyRes.data.length > 0 ) {
							const lastWeight = historyRes.data[ 0 ];
							prevWeights[ puppy.id ] = {
								weight: lastWeight.payload?.weight,
								unit: lastWeight.payload?.unit || 'g',
								date: lastWeight.event_date,
							};
						}
					} catch {
						// Ignore errors for individual puppy history.
					}
				} )
			);
			setPreviousWeights( prevWeights );
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao carregar filhotes.', 'canil-core' )
			);
		} finally {
			setLoadingPuppies( false );
		}
	}, [] );

	useEffect( () => {
		fetchLitters();
	}, [ fetchLitters ] );

	useEffect( () => {
		fetchPuppies( selectedLitter );
	}, [ selectedLitter, fetchPuppies ] );

	const handleWeightChange = ( puppyId ) => ( value ) => {
		setPuppyWeights( ( prev ) => ( {
			...prev,
			[ puppyId ]: value,
		} ) );
	};

	const getWeightDiffClass = ( diff ) => {
		if ( diff > 0 ) {
			return 'canil-weight-gain';
		}
		if ( diff < 0 ) {
			return 'canil-weight-loss';
		}
		return '';
	};

	const calculateDifference = ( puppyId, currentWeight ) => {
		const prev = previousWeights[ puppyId ];
		if ( ! prev || ! prev.weight || ! currentWeight ) {
			return null;
		}

		// Convert to same unit for comparison.
		let prevWeight = prev.weight;
		if ( prev.unit !== unit ) {
			// Simple conversion.
			if ( prev.unit === 'g' && unit === 'kg' ) {
				prevWeight = prev.weight / 1000;
			} else if ( prev.unit === 'kg' && unit === 'g' ) {
				prevWeight = prev.weight * 1000;
			} else if ( prev.unit === 'lb' && unit === 'kg' ) {
				prevWeight = prev.weight * 0.453592;
			} else if ( prev.unit === 'kg' && unit === 'lb' ) {
				prevWeight = prev.weight * 2.20462;
			} else if ( prev.unit === 'g' && unit === 'lb' ) {
				prevWeight = prev.weight * 0.00220462;
			} else if ( prev.unit === 'lb' && unit === 'g' ) {
				prevWeight = prev.weight * 453.592;
			}
		}

		return parseFloat( currentWeight ) - prevWeight;
	};

	const handleSubmit = async ( e ) => {
		e.preventDefault();
		setSaving( true );
		setError( null );
		setSuccess( null );

		// Filter puppies with weights entered.
		const weightsToSave = Object.entries( puppyWeights )
			.filter( ( [ , weight ] ) => weight && parseFloat( weight ) > 0 )
			.map( ( [ puppyId, weight ] ) => ( {
				puppy_id: parseInt( puppyId, 10 ),
				weight: parseFloat( weight ),
			} ) );

		if ( weightsToSave.length === 0 ) {
			setError(
				__( 'Por favor, insira pelo menos um peso.', 'canil-core' )
			);
			setSaving( false );
			return;
		}

		try {
			await apiFetch( {
				path: `/canil/v1/weighing/batch/${ selectedLitter }`,
				method: 'POST',
				data: {
					date: eventDate,
					weight_unit: unit,
					type: weightType,
					weights: weightsToSave,
				},
			} );

			setSuccess(
				__( 'Pesos registrados com sucesso!', 'canil-core' ) +
					` (${ weightsToSave.length } ${ __(
						'filhotes',
						'canil-core'
					) })`
			);

			setTimeout( () => navigate( '/weighing' ), 2000 );
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao registrar pesos.', 'canil-core' )
			);
		} finally {
			setSaving( false );
		}
	};

	const filledCount = Object.values( puppyWeights ).filter(
		( w ) => w && parseFloat( w ) > 0
	).length;

	return (
		<div className="canil-batch-weighing">
			<div className="canil-page-header">
				<h1>{ __( 'Pesagem em Lote', 'canil-core' ) }</h1>
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

			{ success && (
				<Notice
					status="success"
					isDismissible
					onDismiss={ () => setSuccess( null ) }
				>
					{ success }
				</Notice>
			) }

			<form onSubmit={ handleSubmit }>
				<Card>
					<CardHeader>
						<h2>{ __( 'Selecionar Ninhada', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<SelectControl
							label={ __( 'Ninhada *', 'canil-core' ) }
							value={ selectedLitter }
							options={ litters }
							onChange={ setSelectedLitter }
							disabled={ loadingLitters }
						/>
					</CardBody>
				</Card>

				{ selectedLitter && (
					<>
						<Card>
							<CardHeader>
								<h2>
									{ __(
										'Configurações Gerais',
										'canil-core'
									) }
								</h2>
							</CardHeader>
							<CardBody>
								<div className="canil-form-row">
									<TextControl
										label={ __( 'Data *', 'canil-core' ) }
										type="date"
										value={ eventDate }
										onChange={ setEventDate }
										required
									/>
									<SelectControl
										label={ __(
											'Tipo de Pesagem *',
											'canil-core'
										) }
										value={ weightType }
										options={ weightTypeOptions }
										onChange={ setWeightType }
									/>
									<SelectControl
										label={ __(
											'Unidade *',
											'canil-core'
										) }
										value={ unit }
										options={ unitOptions }
										onChange={ setUnit }
									/>
								</div>
							</CardBody>
						</Card>

						<Card>
							<CardHeader>
								<h2>
									{ __( 'Filhotes', 'canil-core' ) }
									{ puppies.length > 0 && (
										<span className="canil-count">
											{ ' ' }
											({ filledCount }/{ puppies.length }{ ' ' }
											{ __(
												'preenchidos',
												'canil-core'
											) }
											)
										</span>
									) }
								</h2>
							</CardHeader>
							<CardBody>
								{ loadingPuppies && (
									<div className="canil-loading">
										<Spinner />
									</div>
								) }

								{ ! loadingPuppies && puppies.length === 0 && (
									<div className="canil-empty-state">
										<p>
											{ __(
												'Nenhum filhote encontrado nesta ninhada.',
												'canil-core'
											) }
										</p>
									</div>
								) }

								{ ! loadingPuppies && puppies.length > 0 && (
									<table className="wp-list-table widefat fixed striped canil-batch-table">
										<thead>
											<tr>
												<th>
													{ __(
														'Identificação',
														'canil-core'
													) }
												</th>
												<th>
													{ __(
														'Peso Anterior',
														'canil-core'
													) }
												</th>
												<th>
													{ __(
														'Novo Peso',
														'canil-core'
													) }
												</th>
												<th>
													{ __(
														'Diferença',
														'canil-core'
													) }
												</th>
											</tr>
										</thead>
										<tbody>
											{ puppies.map( ( puppy ) => {
												const prev =
													previousWeights[ puppy.id ];
												const currentWeight =
													puppyWeights[ puppy.id ] ||
													'';
												const diff =
													calculateDifference(
														puppy.id,
														currentWeight
													);

												return (
													<tr key={ puppy.id }>
														<td>
															<strong>
																{ puppy.name ||
																	puppy.identifier ||
																	`#${ puppy.id }` }
															</strong>
															{ puppy.color && (
																<span className="canil-puppy-color">
																	{ ' ' }
																	(
																	{
																		puppy.color
																	}
																	)
																</span>
															) }
														</td>
														<td>
															{ prev ? (
																<span>
																	{
																		prev.weight
																	}{ ' ' }
																	{
																		prev.unit
																	}
																	<small className="canil-date">
																		{ ' ' }
																		(
																		{
																			prev.date
																		}
																		)
																	</small>
																</span>
															) : (
																<span className="canil-no-data">
																	{ __(
																		'Sem registro',
																		'canil-core'
																	) }
																</span>
															) }
														</td>
														<td>
															<TextControl
																type="number"
																step="0.01"
																min="0"
																value={
																	currentWeight
																}
																onChange={ handleWeightChange(
																	puppy.id
																) }
																placeholder={
																	unit
																}
															/>
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
																	{ diff.toFixed(
																		2
																	) }{ ' ' }
																	{ unit }
																</span>
															) : (
																'-'
															) }
														</td>
													</tr>
												);
											} ) }
										</tbody>
									</table>
								) }
							</CardBody>
						</Card>
					</>
				) }

				<div className="canil-form-actions">
					<Button
						variant="secondary"
						onClick={ () => navigate( '/weighing' ) }
					>
						{ __( 'Cancelar', 'canil-core' ) }
					</Button>
					<Button
						variant="primary"
						type="submit"
						isBusy={ saving }
						disabled={
							saving || ! selectedLitter || filledCount === 0
						}
					>
						{ saving
							? __( 'Salvando…', 'canil-core' )
							: __( 'Salvar Todos os Pesos', 'canil-core' ) }
					</Button>
				</div>
			</form>
		</div>
	);
}

export default BatchWeighing;
