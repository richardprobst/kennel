/**
 * Health Form Page.
 *
 * Form for creating/editing health events (vaccines, dewormings, exams, etc.).
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
 * HealthForm component for creating/editing health events.
 *
 * @return {JSX.Element} The health form component.
 */
function HealthForm() {
	const navigate = useNavigate();
	const { id } = useParams();
	const isEditing = Boolean( id );

	const [ loading, setLoading ] = useState( false );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ success, setSuccess ] = useState( null );

	// Entity selection data.
	const [ dogs, setDogs ] = useState( [] );
	const [ puppies, setPuppies ] = useState( [] );
	const [ loadingEntities, setLoadingEntities ] = useState( true );

	const [ formData, setFormData ] = useState( {
		entity_type: 'dog',
		entity_id: '',
		type: 'vaccine',
		event_date: new Date().toISOString().split( 'T' )[ 0 ],
		notes: '',
		// Dynamic payload fields.
		payload: {},
	} );

	const typeOptions = [
		{ label: __( 'Vacina', 'canil-core' ), value: 'vaccine' },
		{ label: __( 'Vermífugo', 'canil-core' ), value: 'deworming' },
		{ label: __( 'Exame', 'canil-core' ), value: 'exam' },
		{ label: __( 'Medicamento', 'canil-core' ), value: 'medication' },
		{ label: __( 'Cirurgia', 'canil-core' ), value: 'surgery' },
		{ label: __( 'Consulta Veterinária', 'canil-core' ), value: 'vet_visit' },
	];

	const entityTypeOptions = [
		{ label: __( 'Cão', 'canil-core' ), value: 'dog' },
		{ label: __( 'Filhote', 'canil-core' ), value: 'puppy' },
	];

	// Fetch dogs and puppies for selection.
	const fetchEntities = useCallback( async () => {
		setLoadingEntities( true );
		try {
			const [ dogsRes, puppiesRes ] = await Promise.all( [
				apiFetch( { path: '/canil/v1/dogs?per_page=100' } ),
				apiFetch( { path: '/canil/v1/puppies?per_page=100' } ),
			] );

			const dogOptions = ( dogsRes.data || [] ).map( ( dog ) => ( {
				label: dog.name + ( dog.call_name ? ` (${ dog.call_name })` : '' ),
				value: dog.id.toString(),
			} ) );
			dogOptions.unshift( { label: __( 'Selecione um cão', 'canil-core' ), value: '' } );
			setDogs( dogOptions );

			const puppyOptions = ( puppiesRes.data || [] ).map( ( puppy ) => ( {
				label: puppy.name || `${ __( 'Filhote', 'canil-core' ) } #${ puppy.id }`,
				value: puppy.id.toString(),
			} ) );
			puppyOptions.unshift( { label: __( 'Selecione um filhote', 'canil-core' ), value: '' } );
			setPuppies( puppyOptions );
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao carregar animais.', 'canil-core' )
			);
		} finally {
			setLoadingEntities( false );
		}
	}, [] );

	// Fetch existing event for editing.
	const fetchEvent = useCallback( async () => {
		setLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `/canil/v1/events/${ id }`,
			} );

			const data = response.data || response;
			setFormData( {
				entity_type: data.entity_type || 'dog',
				entity_id: data.entity_id?.toString() || '',
				type: data.event_type || 'vaccine',
				event_date: data.event_date || '',
				notes: data.notes || '',
				payload: data.payload || {},
			} );
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao carregar evento.', 'canil-core' )
			);
		} finally {
			setLoading( false );
		}
	}, [ id ] );

	useEffect( () => {
		fetchEntities();
	}, [ fetchEntities ] );

	useEffect( () => {
		if ( isEditing ) {
			fetchEvent();
		}
	}, [ isEditing, fetchEvent ] );

	const handleChange = ( field ) => ( value ) => {
		setFormData( ( prev ) => ( {
			...prev,
			[ field ]: value,
		} ) );
	};

	const handlePayloadChange = ( field ) => ( value ) => {
		setFormData( ( prev ) => ( {
			...prev,
			payload: {
				...prev.payload,
				[ field ]: value,
			},
		} ) );
	};

	const handleTypeChange = ( value ) => {
		setFormData( ( prev ) => ( {
			...prev,
			type: value,
			// Reset payload when type changes.
			payload: {},
		} ) );
	};

	const handleSubmit = async ( e ) => {
		e.preventDefault();
		setSaving( true );
		setError( null );
		setSuccess( null );

		try {
			// Build the data object with flat structure (API expects fields at root level).
			const data = {
				entity_type: formData.entity_type,
				entity_id: parseInt( formData.entity_id, 10 ),
				event_date: formData.event_date,
				notes: formData.notes || null,
				// Spread payload fields at root level.
				...formData.payload,
			};

			// Clean empty fields.
			Object.keys( data ).forEach( ( key ) => {
				if ( data[ key ] === '' || data[ key ] === undefined ) {
					delete data[ key ];
				}
			} );

			// Determine endpoint based on type for specific event types.
			const typeEndpoints = {
				vaccine: '/canil/v1/health/vaccine',
				deworming: '/canil/v1/health/deworming',
				exam: '/canil/v1/health/exam',
				medication: '/canil/v1/health/medication',
				surgery: '/canil/v1/health/surgery',
				vet_visit: '/canil/v1/health/vet-visit',
			};

			if ( isEditing ) {
				// For editing, use events endpoint.
				await apiFetch( {
					path: `/canil/v1/events/${ id }`,
					method: 'PUT',
					data: {
						...data,
						event_type: formData.type,
						payload: formData.payload,
					},
				} );
				setSuccess( __( 'Evento atualizado com sucesso!', 'canil-core' ) );
			} else {
				const endpoint = typeEndpoints[ formData.type ];
				await apiFetch( {
					path: endpoint,
					method: 'POST',
					data,
				} );
				setSuccess( __( 'Evento criado com sucesso!', 'canil-core' ) );
				setTimeout( () => navigate( '/health' ), 1500 );
			}
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao salvar evento.', 'canil-core' )
			);
		} finally {
			setSaving( false );
		}
	};

	/**
	 * Render dynamic form fields based on event type.
	 *
	 * @return {JSX.Element|null} The dynamic fields.
	 */
	const renderTypeSpecificFields = () => {
		switch ( formData.type ) {
			case 'vaccine':
				return (
					<>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Nome da Vacina *', 'canil-core' ) }
								value={ formData.payload.name || '' }
								onChange={ handlePayloadChange( 'name' ) }
								required
							/>
							<TextControl
								label={ __( 'Fabricante', 'canil-core' ) }
								value={ formData.payload.manufacturer || '' }
								onChange={ handlePayloadChange( 'manufacturer' ) }
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Lote', 'canil-core' ) }
								value={ formData.payload.batch || '' }
								onChange={ handlePayloadChange( 'batch' ) }
							/>
							<TextControl
								label={ __( 'Próxima Dose', 'canil-core' ) }
								type="date"
								value={ formData.payload.next_dose_date || '' }
								onChange={ handlePayloadChange( 'next_dose_date' ) }
							/>
						</div>
					</>
				);

			case 'deworming':
				return (
					<>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Produto *', 'canil-core' ) }
								value={ formData.payload.product || '' }
								onChange={ handlePayloadChange( 'product' ) }
								required
							/>
							<TextControl
								label={ __( 'Dosagem', 'canil-core' ) }
								value={ formData.payload.dosage || '' }
								onChange={ handlePayloadChange( 'dosage' ) }
								help={ __( 'Ex: 1 comprimido, 5ml', 'canil-core' ) }
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Próxima Dose', 'canil-core' ) }
								type="date"
								value={ formData.payload.next_dose_date || '' }
								onChange={ handlePayloadChange( 'next_dose_date' ) }
							/>
						</div>
					</>
				);

			case 'exam':
				return (
					<>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Tipo de Exame *', 'canil-core' ) }
								value={ formData.payload.exam_type || '' }
								onChange={ handlePayloadChange( 'exam_type' ) }
								required
								help={ __( 'Ex: Hemograma, Raio-X, Ultrassom', 'canil-core' ) }
							/>
							<TextControl
								label={ __( 'Veterinário', 'canil-core' ) }
								value={ formData.payload.veterinarian || '' }
								onChange={ handlePayloadChange( 'veterinarian' ) }
							/>
						</div>
						<div className="canil-form-row">
							<TextareaControl
								label={ __( 'Resultado', 'canil-core' ) }
								value={ formData.payload.result || '' }
								onChange={ handlePayloadChange( 'result' ) }
								rows={ 3 }
							/>
						</div>
					</>
				);

			case 'medication':
				return (
					<>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Nome do Medicamento *', 'canil-core' ) }
								value={ formData.payload.name || '' }
								onChange={ handlePayloadChange( 'name' ) }
								required
							/>
							<TextControl
								label={ __( 'Dosagem', 'canil-core' ) }
								value={ formData.payload.dosage || '' }
								onChange={ handlePayloadChange( 'dosage' ) }
								help={ __( 'Ex: 500mg, 2 comprimidos', 'canil-core' ) }
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Frequência', 'canil-core' ) }
								value={ formData.payload.frequency || '' }
								onChange={ handlePayloadChange( 'frequency' ) }
								help={ __( 'Ex: 2x ao dia, a cada 8 horas', 'canil-core' ) }
							/>
							<TextControl
								label={ __( 'Data Término', 'canil-core' ) }
								type="date"
								value={ formData.payload.end_date || '' }
								onChange={ handlePayloadChange( 'end_date' ) }
							/>
						</div>
					</>
				);

			case 'surgery':
				return (
					<>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Tipo de Cirurgia *', 'canil-core' ) }
								value={ formData.payload.surgery_type || '' }
								onChange={ handlePayloadChange( 'surgery_type' ) }
								required
								help={ __( 'Ex: Castração, Cesariana', 'canil-core' ) }
							/>
							<TextControl
								label={ __( 'Veterinário', 'canil-core' ) }
								value={ formData.payload.veterinarian || '' }
								onChange={ handlePayloadChange( 'veterinarian' ) }
							/>
						</div>
					</>
				);

			case 'vet_visit':
				return (
					<>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Motivo da Consulta *', 'canil-core' ) }
								value={ formData.payload.reason || '' }
								onChange={ handlePayloadChange( 'reason' ) }
								required
							/>
							<TextControl
								label={ __( 'Veterinário', 'canil-core' ) }
								value={ formData.payload.veterinarian || '' }
								onChange={ handlePayloadChange( 'veterinarian' ) }
							/>
						</div>
						<div className="canil-form-row">
							<TextareaControl
								label={ __( 'Diagnóstico', 'canil-core' ) }
								value={ formData.payload.diagnosis || '' }
								onChange={ handlePayloadChange( 'diagnosis' ) }
								rows={ 2 }
							/>
						</div>
						<div className="canil-form-row">
							<TextareaControl
								label={ __( 'Tratamento', 'canil-core' ) }
								value={ formData.payload.treatment || '' }
								onChange={ handlePayloadChange( 'treatment' ) }
								rows={ 2 }
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Próxima Consulta', 'canil-core' ) }
								type="date"
								value={ formData.payload.next_visit_date || '' }
								onChange={ handlePayloadChange( 'next_visit_date' ) }
							/>
						</div>
					</>
				);

			default:
				return null;
		}
	};

	if ( loading ) {
		return (
			<div className="canil-loading">
				<Spinner />
			</div>
		);
	}

	const entityOptions = formData.entity_type === 'dog' ? dogs : puppies;

	return (
		<div className="canil-health-form">
			<div className="canil-page-header">
				<h1>
					{ isEditing
						? __( 'Editar Evento de Saúde', 'canil-core' )
						: __( 'Adicionar Evento de Saúde', 'canil-core' ) }
				</h1>
				<Button
					variant="secondary"
					onClick={ () => navigate( '/health' ) }
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
						<h2>{ __( 'Informações Básicas', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<div className="canil-form-row">
							<SelectControl
								label={ __( 'Tipo de Animal *', 'canil-core' ) }
								value={ formData.entity_type }
								options={ entityTypeOptions }
								onChange={ handleChange( 'entity_type' ) }
							/>
							<SelectControl
								label={ __( 'Animal *', 'canil-core' ) }
								value={ formData.entity_id }
								options={ entityOptions }
								onChange={ handleChange( 'entity_id' ) }
								disabled={ loadingEntities }
							/>
						</div>
						<div className="canil-form-row">
							<SelectControl
								label={ __( 'Tipo de Evento *', 'canil-core' ) }
								value={ formData.type }
								options={ typeOptions }
								onChange={ handleTypeChange }
							/>
							<TextControl
								label={ __( 'Data do Evento *', 'canil-core' ) }
								type="date"
								value={ formData.event_date }
								onChange={ handleChange( 'event_date' ) }
								required
							/>
						</div>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{ __( 'Detalhes do Evento', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						{ renderTypeSpecificFields() }
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
							rows={ 4 }
						/>
					</CardBody>
				</Card>

				<div className="canil-form-actions">
					<Button
						variant="secondary"
						onClick={ () => navigate( '/health' ) }
					>
						{ __( 'Cancelar', 'canil-core' ) }
					</Button>
					<Button
						variant="primary"
						type="submit"
						isBusy={ saving }
						disabled={ saving || ! formData.entity_id }
					>
						{ saving && __( 'Salvando…', 'canil-core' ) }
						{ ! saving &&
							isEditing &&
							__( 'Atualizar', 'canil-core' ) }
						{ ! saving &&
							! isEditing &&
							__( 'Criar Evento', 'canil-core' ) }
					</Button>
				</div>
			</form>
		</div>
	);
}

export default HealthForm;
