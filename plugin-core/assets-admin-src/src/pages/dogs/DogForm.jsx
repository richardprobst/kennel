/**
 * Dog Form Page.
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
import { useNavigate, useParams } from 'react-router-dom';

/**
 * DogForm component for creating/editing dogs.
 *
 * @return {JSX.Element} The dog form component.
 */
function DogForm() {
	const navigate = useNavigate();
	const { id } = useParams();
	const isEditing = Boolean( id );

	const [ loading, setLoading ] = useState( false );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ success, setSuccess ] = useState( null );

	const [ formData, setFormData ] = useState( {
		name: '',
		call_name: '',
		registration_number: '',
		chip_number: '',
		tattoo: '',
		breed: '',
		variety: '',
		color: '',
		markings: '',
		birth_date: '',
		sex: 'male',
		status: 'active',
		sire_id: '',
		dam_id: '',
		notes: '',
	} );

	const statusOptions = [
		{ label: __( 'Ativo', 'canil-core' ), value: 'active' },
		{ label: __( 'Reprodutor(a)', 'canil-core' ), value: 'breeding' },
		{ label: __( 'Aposentado', 'canil-core' ), value: 'retired' },
		{ label: __( 'Vendido', 'canil-core' ), value: 'sold' },
		{ label: __( 'Falecido', 'canil-core' ), value: 'deceased' },
		{ label: __( 'Co-propriedade', 'canil-core' ), value: 'coowned' },
	];

	const sexOptions = [
		{ label: __( 'Macho', 'canil-core' ), value: 'male' },
		{ label: __( 'Fêmea', 'canil-core' ), value: 'female' },
	];

	const fetchDog = useCallback( async () => {
		setLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `/canil/v1/dogs/${ id }`,
			} );

			setFormData( {
				name: response.data?.name || '',
				call_name: response.data?.call_name || '',
				registration_number: response.data?.registration_number || '',
				chip_number: response.data?.chip_number || '',
				tattoo: response.data?.tattoo || '',
				breed: response.data?.breed || '',
				variety: response.data?.variety || '',
				color: response.data?.color || '',
				markings: response.data?.markings || '',
				birth_date: response.data?.birth_date || '',
				sex: response.data?.sex || 'male',
				status: response.data?.status || 'active',
				sire_id: response.data?.sire_id || '',
				dam_id: response.data?.dam_id || '',
				notes: response.data?.notes || '',
			} );
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao carregar cão.', 'canil-core' )
			);
		} finally {
			setLoading( false );
		}
	}, [ id ] );

	useEffect( () => {
		if ( isEditing ) {
			fetchDog();
		}
	}, [ isEditing, fetchDog ] );

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
			const data = { ...formData };

			// Remove empty optional fields.
			Object.keys( data ).forEach( ( key ) => {
				if ( data[ key ] === '' ) {
					data[ key ] = null;
				}
			} );

			if ( isEditing ) {
				await apiFetch( {
					path: `/canil/v1/dogs/${ id }`,
					method: 'PUT',
					data,
				} );
				setSuccess( __( 'Cão atualizado com sucesso!', 'canil-core' ) );
			} else {
				await apiFetch( {
					path: '/canil/v1/dogs',
					method: 'POST',
					data,
				} );
				setSuccess( __( 'Cão criado com sucesso!', 'canil-core' ) );
				setTimeout( () => navigate( '/dogs' ), 1500 );
			}
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao salvar cão.', 'canil-core' )
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

	return (
		<div className="canil-dog-form">
			<div className="canil-page-header">
				<h1>
					{ isEditing
						? __( 'Editar Cão', 'canil-core' )
						: __( 'Adicionar Cão', 'canil-core' ) }
				</h1>
				<Button
					variant="secondary"
					onClick={ () => navigate( '/dogs' ) }
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
						<h2>{ __( 'Identificação', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Nome *', 'canil-core' ) }
								value={ formData.name }
								onChange={ handleChange( 'name' ) }
								required
							/>
							<TextControl
								label={ __( 'Nome de Chamada', 'canil-core' ) }
								value={ formData.call_name }
								onChange={ handleChange( 'call_name' ) }
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={ __(
									'Número de Registro',
									'canil-core'
								) }
								value={ formData.registration_number }
								onChange={ handleChange(
									'registration_number'
								) }
								help={ __( 'Ex: CBKC 12345', 'canil-core' ) }
							/>
							<TextControl
								label={ __( 'Número do Chip', 'canil-core' ) }
								value={ formData.chip_number }
								onChange={ handleChange( 'chip_number' ) }
							/>
							<TextControl
								label={ __( 'Tatuagem', 'canil-core' ) }
								value={ formData.tattoo }
								onChange={ handleChange( 'tattoo' ) }
							/>
						</div>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{ __( 'Características', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Raça *', 'canil-core' ) }
								value={ formData.breed }
								onChange={ handleChange( 'breed' ) }
								required
							/>
							<TextControl
								label={ __( 'Variedade', 'canil-core' ) }
								value={ formData.variety }
								onChange={ handleChange( 'variety' ) }
								help={ __(
									'Ex: Pelo longo, Mini',
									'canil-core'
								) }
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Cor', 'canil-core' ) }
								value={ formData.color }
								onChange={ handleChange( 'color' ) }
							/>
							<TextControl
								label={ __( 'Marcações', 'canil-core' ) }
								value={ formData.markings }
								onChange={ handleChange( 'markings' ) }
							/>
						</div>
						<div className="canil-form-row">
							<SelectControl
								label={ __( 'Sexo *', 'canil-core' ) }
								value={ formData.sex }
								options={ sexOptions }
								onChange={ handleChange( 'sex' ) }
							/>
							<SelectControl
								label={ __( 'Status', 'canil-core' ) }
								value={ formData.status }
								options={ statusOptions }
								onChange={ handleChange( 'status' ) }
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={ __(
									'Data de Nascimento *',
									'canil-core'
								) }
								type="date"
								value={ formData.birth_date }
								onChange={ handleChange( 'birth_date' ) }
								required
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
						onClick={ () => navigate( '/dogs' ) }
					>
						{ __( 'Cancelar', 'canil-core' ) }
					</Button>
					<Button
						variant="primary"
						type="submit"
						isBusy={ saving }
						disabled={ saving }
					>
						{ saving && __( 'Salvando…', 'canil-core' ) }
						{ ! saving &&
							isEditing &&
							__( 'Atualizar', 'canil-core' ) }
						{ ! saving &&
							! isEditing &&
							__( 'Criar Cão', 'canil-core' ) }
					</Button>
				</div>
			</form>
		</div>
	);
}

export default DogForm;
