/**
 * Mating Form Page.
 *
 * Form to register a mating and create a litter.
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
	TextareaControl,
	SelectControl,
} from '@wordpress/components';
import { useNavigate } from 'react-router-dom';

/**
 * MatingForm component for registering a mating.
 *
 * @return {JSX.Element} The mating form component.
 */
function MatingForm() {
	const navigate = useNavigate();

	const [ loading, setLoading ] = useState( false );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ success, setSuccess ] = useState( null );

	const [ dams, setDams ] = useState( [] );
	const [ sires, setSires ] = useState( [] );

	const [ formData, setFormData ] = useState( {
		dam_id: '',
		sire_id: '',
		mating_date: '',
		mating_type: 'natural',
		heat_start_date: '',
		notes: '',
	} );

	const matingTypeOptions = [
		{ label: __( 'Natural', 'canil-core' ), value: 'natural' },
		{
			label: __( 'Inseminação (Sêmen Fresco)', 'canil-core' ),
			value: 'artificial_fresh',
		},
		{
			label: __( 'Inseminação (Sêmen Congelado)', 'canil-core' ),
			value: 'artificial_frozen',
		},
	];

	const fetchDogs = useCallback( async () => {
		setLoading( true );
		try {
			// Fetch females.
			const femaleResponse = await apiFetch( {
				path: '/canil/v1/dogs/breeding?sex=female',
			} );
			setDams( femaleResponse.data || [] );

			// Fetch males.
			const maleResponse = await apiFetch( {
				path: '/canil/v1/dogs/breeding?sex=male',
			} );
			setSires( maleResponse.data || [] );
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao carregar cães.', 'canil-core' )
			);
		} finally {
			setLoading( false );
		}
	}, [] );

	useEffect( () => {
		fetchDogs();
	}, [ fetchDogs ] );

	const handleChange = ( field ) => ( value ) => {
		setFormData( ( prev ) => ( {
			...prev,
			[ field ]: value,
		} ) );
	};

	const handleSubmit = async ( e ) => {
		e.preventDefault();
		setSaving( true );
		setError( null );
		setSuccess( null );

		try {
			const data = {
				dam_id: parseInt( formData.dam_id, 10 ),
				sire_id: parseInt( formData.sire_id, 10 ),
				mating_date: formData.mating_date,
				mating_type: formData.mating_type,
				notes: formData.notes || '',
			};

			if ( formData.heat_start_date ) {
				data.heat_start_date = formData.heat_start_date;
			}

			const response = await apiFetch( {
				path: '/canil/v1/reproduction/mating',
				method: 'POST',
				data,
			} );

			setSuccess( response.data.message );
			setTimeout( () => navigate( '/litters' ), 1500 );
		} catch ( err ) {
			setError(
				err.message ||
					__( 'Erro ao registrar cobertura.', 'canil-core' )
			);
		} finally {
			setSaving( false );
		}
	};

	if ( loading ) {
		return (
			<div className="canil-loading">
				<Spinner />
			</div>
		);
	}

	const damOptions = [
		{ label: __( 'Selecione a matriz', 'canil-core' ), value: '' },
		...dams.map( ( dog ) => ( {
			label: `${ dog.name } (${ dog.breed })`,
			value: dog.id.toString(),
		} ) ),
	];

	const sireOptions = [
		{ label: __( 'Selecione o reprodutor', 'canil-core' ), value: '' },
		...sires.map( ( dog ) => ( {
			label: `${ dog.name } (${ dog.breed })`,
			value: dog.id.toString(),
		} ) ),
	];

	return (
		<div className="canil-mating-form">
			<div className="canil-page-header">
				<h1>{ __( 'Registrar Cobertura', 'canil-core' ) }</h1>
				<Button
					variant="secondary"
					onClick={ () => navigate( '/litters' ) }
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
						<h2>{ __( 'Pais', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<div className="canil-form-row">
							<SelectControl
								label={ __( 'Matriz (Fêmea) *', 'canil-core' ) }
								value={ formData.dam_id }
								options={ damOptions }
								onChange={ handleChange( 'dam_id' ) }
								help={ __(
									'Selecione a fêmea que será coberta.',
									'canil-core'
								) }
							/>
							<SelectControl
								label={ __(
									'Reprodutor (Macho) *',
									'canil-core'
								) }
								value={ formData.sire_id }
								options={ sireOptions }
								onChange={ handleChange( 'sire_id' ) }
								help={ __(
									'Selecione o macho reprodutor.',
									'canil-core'
								) }
							/>
						</div>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{ __( 'Datas', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<div className="canil-form-row">
							<TextControl
								label={ __(
									'Data da Cobertura *',
									'canil-core'
								) }
								type="date"
								value={ formData.mating_date }
								onChange={ handleChange( 'mating_date' ) }
								required
								help={ __(
									'A previsão de parto será calculada automaticamente (63 dias).',
									'canil-core'
								) }
							/>
							<TextControl
								label={ __( 'Início do Cio', 'canil-core' ) }
								type="date"
								value={ formData.heat_start_date }
								onChange={ handleChange( 'heat_start_date' ) }
								help={ __( 'Opcional.', 'canil-core' ) }
							/>
						</div>
						<div className="canil-form-row">
							<SelectControl
								label={ __(
									'Tipo de Cobertura',
									'canil-core'
								) }
								value={ formData.mating_type }
								options={ matingTypeOptions }
								onChange={ handleChange( 'mating_type' ) }
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
							label={ __( 'Notas', 'canil-core' ) }
							value={ formData.notes }
							onChange={ handleChange( 'notes' ) }
							rows={ 4 }
						/>
					</CardBody>
				</Card>

				<div className="canil-form-actions">
					<Button
						variant="secondary"
						onClick={ () => navigate( '/litters' ) }
					>
						{ __( 'Cancelar', 'canil-core' ) }
					</Button>
					<Button
						variant="primary"
						type="submit"
						isBusy={ saving }
						disabled={
							saving ||
							! formData.dam_id ||
							! formData.sire_id ||
							! formData.mating_date
						}
					>
						{ saving
							? __( 'Salvando…', 'canil-core' )
							: __( 'Registrar Cobertura', 'canil-core' ) }
					</Button>
				</div>
			</form>
		</div>
	);
}

export default MatingForm;
