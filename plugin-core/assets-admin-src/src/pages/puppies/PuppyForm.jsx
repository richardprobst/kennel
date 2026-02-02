/**
 * Puppy Form Page.
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
 * PuppyForm component for creating/editing puppies.
 *
 * @return {JSX.Element} The puppy form component.
 */
function PuppyForm() {
	const navigate = useNavigate();
	const { id } = useParams();
	const isEditing = Boolean( id );

	const [ loading, setLoading ] = useState( false );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ success, setSuccess ] = useState( null );
	const [ litters, setLitters ] = useState( [] );

	const [ formData, setFormData ] = useState( {
		litter_id: '',
		identifier: '',
		name: '',
		call_name: '',
		registration_number: '',
		chip_number: '',
		sex: 'male',
		color: '',
		markings: '',
		birth_weight: '',
		birth_order: '',
		birth_notes: '',
		status: 'available',
		price: '',
		notes: '',
	} );

	const statusOptions = [
		{ label: __( 'Disponível', 'canil-core' ), value: 'available' },
		{ label: __( 'Reservado', 'canil-core' ), value: 'reserved' },
		{ label: __( 'Vendido', 'canil-core' ), value: 'sold' },
		{ label: __( 'Retido', 'canil-core' ), value: 'retained' },
		{ label: __( 'Falecido', 'canil-core' ), value: 'deceased' },
		{ label: __( 'Devolvido', 'canil-core' ), value: 'returned' },
	];

	const sexOptions = [
		{ label: __( 'Macho', 'canil-core' ), value: 'male' },
		{ label: __( 'Fêmea', 'canil-core' ), value: 'female' },
	];

	const fetchLitters = useCallback( async () => {
		try {
			const response = await apiFetch( {
				path: '/canil/v1/litters/dropdown',
			} );
			setLitters( response.data || [] );
		} catch ( err ) {
			// eslint-disable-next-line no-console
			console.error( 'Error fetching litters:', err );
		}
	}, [] );

	const fetchPuppy = useCallback( async () => {
		setLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `/canil/v1/puppies/${ id }`,
			} );

			const data = response.data || {};
			setFormData( {
				litter_id: data.litter_id || '',
				identifier: data.identifier || '',
				name: data.name || '',
				call_name: data.call_name || '',
				registration_number: data.registration_number || '',
				chip_number: data.chip_number || '',
				sex: data.sex || 'male',
				color: data.color || '',
				markings: data.markings || '',
				birth_weight: data.birth_weight || '',
				birth_order: data.birth_order || '',
				birth_notes: data.birth_notes || '',
				status: data.status || 'available',
				price: data.price || '',
				notes: data.notes || '',
			} );
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao carregar filhote.', 'canil-core' )
			);
		} finally {
			setLoading( false );
		}
	}, [ id ] );

	useEffect( () => {
		fetchLitters();
		if ( isEditing ) {
			fetchPuppy();
		}
	}, [ isEditing, fetchLitters, fetchPuppy ] );

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

			// Convert IDs to integers.
			if ( data.litter_id ) {
				data.litter_id = parseInt( data.litter_id, 10 );
			}

			// Remove empty optional fields.
			Object.keys( data ).forEach( ( key ) => {
				if ( data[ key ] === '' ) {
					data[ key ] = null;
				}
			} );

			if ( isEditing ) {
				await apiFetch( {
					path: `/canil/v1/puppies/${ id }`,
					method: 'PUT',
					data,
				} );
				setSuccess(
					__( 'Filhote atualizado com sucesso!', 'canil-core' )
				);
			} else {
				await apiFetch( {
					path: '/canil/v1/puppies',
					method: 'POST',
					data,
				} );
				setSuccess( __( 'Filhote criado com sucesso!', 'canil-core' ) );
				setTimeout( () => navigate( '/puppies' ), 1500 );
			}
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao salvar filhote.', 'canil-core' )
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

	const litterOptions = [
		{ label: __( 'Selecione a ninhada…', 'canil-core' ), value: '' },
		...litters.map( ( litter ) => ( {
			label: litter.name || litter.litter_letter || `#${ litter.id }`,
			value: litter.id.toString(),
		} ) ),
	];

	return (
		<div className="canil-puppy-form">
			<div className="canil-page-header">
				<h1>
					{ isEditing
						? __( 'Editar Filhote', 'canil-core' )
						: __( 'Adicionar Filhote', 'canil-core' ) }
				</h1>
				<Button
					variant="secondary"
					onClick={ () => navigate( '/puppies' ) }
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
							<SelectControl
								label={ __( 'Ninhada *', 'canil-core' ) }
								value={ formData.litter_id }
								options={ litterOptions }
								onChange={ handleChange( 'litter_id' ) }
							/>
							<TextControl
								label={ __( 'Identificador *', 'canil-core' ) }
								value={ formData.identifier }
								onChange={ handleChange( 'identifier' ) }
								required
								help={ __(
									'Ex: Macho 1, Fêmea 2',
									'canil-core'
								) }
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Nome Registrado', 'canil-core' ) }
								value={ formData.name }
								onChange={ handleChange( 'name' ) }
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
							/>
							<TextControl
								label={ __( 'Número do Chip', 'canil-core' ) }
								value={ formData.chip_number }
								onChange={ handleChange( 'chip_number' ) }
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
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{ __( 'Nascimento', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<div className="canil-form-row">
							<TextControl
								label={ __(
									'Peso ao Nascer (gramas)',
									'canil-core'
								) }
								type="number"
								min="0"
								step="0.1"
								value={ formData.birth_weight }
								onChange={ handleChange( 'birth_weight' ) }
							/>
							<TextControl
								label={ __(
									'Ordem de Nascimento',
									'canil-core'
								) }
								type="number"
								min="1"
								value={ formData.birth_order }
								onChange={ handleChange( 'birth_order' ) }
							/>
						</div>
						<TextareaControl
							label={ __(
								'Observações do Nascimento',
								'canil-core'
							) }
							value={ formData.birth_notes }
							onChange={ handleChange( 'birth_notes' ) }
							rows={ 2 }
						/>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{ __( 'Venda', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Preço', 'canil-core' ) }
								type="number"
								min="0"
								step="0.01"
								value={ formData.price }
								onChange={ handleChange( 'price' ) }
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
						onClick={ () => navigate( '/puppies' ) }
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
							__( 'Criar Filhote', 'canil-core' ) }
					</Button>
				</div>
			</form>
		</div>
	);
}

export default PuppyForm;
