/**
 * Weighing Form Page.
 *
 * Form for recording individual weight measurements.
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
	Notice,
	TextControl,
	TextareaControl,
	SelectControl,
} from '@wordpress/components';
import { useNavigate } from 'react-router-dom';

/**
 * WeighingForm component for recording weight measurements.
 *
 * @return {JSX.Element} The weighing form component.
 */
function WeighingForm() {
	const navigate = useNavigate();

	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ success, setSuccess ] = useState( null );
	const [ recordAnother, setRecordAnother ] = useState( false );

	// Entity selection data.
	const [ dogs, setDogs ] = useState( [] );
	const [ puppies, setPuppies ] = useState( [] );
	const [ loadingEntities, setLoadingEntities ] = useState( true );

	const [ formData, setFormData ] = useState( {
		entity_type: 'puppy',
		entity_id: '',
		event_date: new Date().toISOString().split( 'T' )[ 0 ],
		weight: '',
		unit: 'g',
		weight_type: 'weekly',
		notes: '',
	} );

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

	const entityTypeOptions = [
		{ label: __( 'Filhote', 'canil-core' ), value: 'puppy' },
		{ label: __( 'Cão', 'canil-core' ), value: 'dog' },
	];

	// Fetch dogs and puppies for selection.
	const fetchEntities = useCallback( async () => {
		setLoadingEntities( true );
		try {
			const [ dogsRes, puppiesRes ] = await Promise.all( [
				apiFetch( { path: '/canil/v1/dogs?per_page=100' } ),
				apiFetch( { path: '/canil/v1/puppies?per_page=100' } ),
			] );

			// Group dogs.
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

			// Group puppies by litter.
			const puppyOptions = [];
			const puppiesByLitter = {};

			( puppiesRes.data || [] ).forEach( ( puppy ) => {
				const litterKey = puppy.litter_id || 'no_litter';
				if ( ! puppiesByLitter[ litterKey ] ) {
					puppiesByLitter[ litterKey ] = {
						litterName:
							puppy.litter_name ||
							__( 'Sem Ninhada', 'canil-core' ),
						puppies: [],
					};
				}
				puppiesByLitter[ litterKey ].puppies.push( puppy );
			} );

			// Add placeholder.
			puppyOptions.push( {
				label: __( 'Selecione um filhote', 'canil-core' ),
				value: '',
			} );

			// Add grouped puppies.
			Object.values( puppiesByLitter ).forEach( ( group ) => {
				group.puppies.forEach( ( puppy ) => {
					puppyOptions.push( {
						label: `${ group.litterName } - ${
							puppy.name || puppy.identifier || `#${ puppy.id }`
						}`,
						value: puppy.id.toString(),
					} );
				} );
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

	useEffect( () => {
		fetchEntities();
	}, [ fetchEntities ] );

	const handleChange = ( field ) => ( value ) => {
		setFormData( ( prev ) => ( {
			...prev,
			[ field ]: value,
		} ) );
	};

	const resetForm = () => {
		setFormData( ( prev ) => ( {
			...prev,
			entity_id: '',
			weight: '',
			notes: '',
		} ) );
		setSuccess( null );
		setError( null );
	};

	const handleSubmit = async ( e ) => {
		e.preventDefault();
		setSaving( true );
		setError( null );
		setSuccess( null );

		try {
			const data = {
				entity_type: formData.entity_type,
				entity_id: parseInt( formData.entity_id, 10 ),
				event_date: formData.event_date,
				weight: parseFloat( formData.weight ),
				weight_unit: formData.unit,
				type: formData.weight_type,
				notes: formData.notes || null,
			};

			await apiFetch( {
				path: '/canil/v1/weighing',
				method: 'POST',
				data,
			} );

			setSuccess( __( 'Peso registrado com sucesso!', 'canil-core' ) );

			if ( recordAnother ) {
				resetForm();
			} else {
				setTimeout( () => navigate( '/weighing' ), 1500 );
			}
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao registrar peso.', 'canil-core' )
			);
		} finally {
			setSaving( false );
		}
	};

	const entityOptions = formData.entity_type === 'dog' ? dogs : puppies;

	return (
		<div className="canil-weighing-form">
			<div className="canil-page-header">
				<h1>{ __( 'Registrar Peso', 'canil-core' ) }</h1>
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
						<h2>{ __( 'Animal', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<div className="canil-form-row">
							<SelectControl
								label={ __( 'Tipo de Animal *', 'canil-core' ) }
								value={ formData.entity_type }
								options={ entityTypeOptions }
								onChange={ ( value ) => {
									handleChange( 'entity_type' )( value );
									handleChange( 'entity_id' )( '' );
								} }
							/>
							<SelectControl
								label={ __( 'Animal *', 'canil-core' ) }
								value={ formData.entity_id }
								options={ entityOptions }
								onChange={ handleChange( 'entity_id' ) }
								disabled={ loadingEntities }
							/>
						</div>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{ __( 'Dados da Pesagem', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Data *', 'canil-core' ) }
								type="date"
								value={ formData.event_date }
								onChange={ handleChange( 'event_date' ) }
								required
							/>
							<SelectControl
								label={ __(
									'Tipo de Pesagem *',
									'canil-core'
								) }
								value={ formData.weight_type }
								options={ weightTypeOptions }
								onChange={ handleChange( 'weight_type' ) }
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Peso *', 'canil-core' ) }
								type="number"
								step="0.01"
								min="0"
								value={ formData.weight }
								onChange={ handleChange( 'weight' ) }
								required
							/>
							<SelectControl
								label={ __( 'Unidade *', 'canil-core' ) }
								value={ formData.unit }
								options={ unitOptions }
								onChange={ handleChange( 'unit' ) }
							/>
						</div>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{ __( 'Observações', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<TextareaControl
							label={ __( 'Notas Adicionais', 'canil-core' ) }
							value={ formData.notes }
							onChange={ handleChange( 'notes' ) }
							rows={ 3 }
						/>
					</CardBody>
				</Card>

				<div className="canil-form-actions">
					<Button
						variant="secondary"
						onClick={ () => navigate( '/weighing' ) }
					>
						{ __( 'Cancelar', 'canil-core' ) }
					</Button>
					<Button
						variant="secondary"
						type="submit"
						isBusy={ saving }
						disabled={
							saving || ! formData.entity_id || ! formData.weight
						}
						onClick={ () => setRecordAnother( true ) }
					>
						{ __( 'Salvar e Registrar Outro', 'canil-core' ) }
					</Button>
					<Button
						variant="primary"
						type="submit"
						isBusy={ saving }
						disabled={
							saving || ! formData.entity_id || ! formData.weight
						}
						onClick={ () => setRecordAnother( false ) }
					>
						{ saving
							? __( 'Salvando…', 'canil-core' )
							: __( 'Salvar', 'canil-core' ) }
					</Button>
				</div>
			</form>
		</div>
	);
}

export default WeighingForm;
