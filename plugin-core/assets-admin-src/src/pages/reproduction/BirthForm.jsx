/**
 * Birth Form Page.
 *
 * Form to register a birth and create puppies.
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
	Icon,
} from '@wordpress/components';
import { useNavigate, useParams } from 'react-router-dom';
import { plus, trash } from '@wordpress/icons';

/**
 * BirthForm component for registering a birth.
 *
 * @return {JSX.Element} The birth form component.
 */
function BirthForm() {
	const navigate = useNavigate();
	const { litterId } = useParams();

	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ success, setSuccess ] = useState( null );
	const [ litter, setLitter ] = useState( null );

	const [ formData, setFormData ] = useState( {
		birth_date: '',
		birth_type: 'natural',
		notes: '',
	} );

	const [ puppies, setPuppies ] = useState( [
		{ sex: 'male', color: '', birth_weight: '', identifier: '', notes: '' },
	] );

	const birthTypeOptions = [
		{ label: __( 'Natural', 'canil-core' ), value: 'natural' },
		{ label: __( 'Cesárea', 'canil-core' ), value: 'cesarean' },
		{ label: __( 'Assistido', 'canil-core' ), value: 'assisted' },
	];

	const sexOptions = [
		{ label: __( 'Macho', 'canil-core' ), value: 'male' },
		{ label: __( 'Fêmea', 'canil-core' ), value: 'female' },
	];

	const fetchLitter = useCallback( async () => {
		setLoading( true );
		try {
			const response = await apiFetch( {
				path: `/canil/v1/litters/${ litterId }`,
			} );
			setLitter( response.data );
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao carregar ninhada.', 'canil-core' )
			);
		} finally {
			setLoading( false );
		}
	}, [ litterId ] );

	useEffect( () => {
		if ( litterId ) {
			fetchLitter();
		}
	}, [ litterId, fetchLitter ] );

	const handleChange = ( field ) => ( value ) => {
		setFormData( ( prev ) => ( {
			...prev,
			[ field ]: value,
		} ) );
	};

	const handlePuppyChange = ( index, field ) => ( value ) => {
		setPuppies( ( prev ) => {
			const updated = [ ...prev ];
			updated[ index ] = { ...updated[ index ], [ field ]: value };
			return updated;
		} );
	};

	const addPuppy = () => {
		setPuppies( ( prev ) => [
			...prev,
			{
				sex: 'male',
				color: '',
				birth_weight: '',
				identifier: '',
				notes: '',
			},
		] );
	};

	const removePuppy = ( index ) => {
		if ( puppies.length > 1 ) {
			setPuppies( ( prev ) => prev.filter( ( _, i ) => i !== index ) );
		}
	};

	const handleSubmit = async ( e ) => {
		e.preventDefault();
		setSaving( true );
		setError( null );
		setSuccess( null );

		try {
			const data = {
				litter_id: parseInt( litterId, 10 ),
				birth_date: formData.birth_date,
				birth_type: formData.birth_type,
				notes: formData.notes || '',
				puppies: puppies.map( ( puppy, index ) => ( {
					...puppy,
					identifier:
						puppy.identifier ||
						`${ puppy.sex === 'male' ? 'M' : 'F' }-${ index + 1 }`,
					birth_weight: puppy.birth_weight
						? parseInt( puppy.birth_weight, 10 )
						: null,
				} ) ),
			};

			const response = await apiFetch( {
				path: '/canil/v1/reproduction/birth',
				method: 'POST',
				data,
			} );

			setSuccess( response.data.message );
			setTimeout( () => navigate( `/litters/${ litterId }` ), 1500 );
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao registrar parto.', 'canil-core' )
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

	if ( ! litter ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ __( 'Ninhada não encontrada.', 'canil-core' ) }
			</Notice>
		);
	}

	const malesCount = puppies.filter( ( p ) => p.sex === 'male' ).length;
	const femalesCount = puppies.filter( ( p ) => p.sex === 'female' ).length;

	return (
		<div className="canil-birth-form">
			<div className="canil-page-header">
				<h1>{ __( 'Registrar Parto', 'canil-core' ) }</h1>
				<Button
					variant="secondary"
					onClick={ () => navigate( `/litters/${ litterId }` ) }
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

			<Card>
				<CardHeader>
					<h2>{ __( 'Informações da Ninhada', 'canil-core' ) }</h2>
				</CardHeader>
				<CardBody>
					<p>
						<strong>{ __( 'Ninhada:', 'canil-core' ) }</strong>{ ' ' }
						{ litter.name || `#${ litter.id }` }
					</p>
					{ litter.expected_birth_date && (
						<p>
							<strong>
								{ __( 'Previsão de Parto:', 'canil-core' ) }
							</strong>{ ' ' }
							{ litter.expected_birth_date }
						</p>
					) }
				</CardBody>
			</Card>

			<form onSubmit={ handleSubmit }>
				<Card>
					<CardHeader>
						<h2>{ __( 'Dados do Parto', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<div className="canil-form-row">
							<TextControl
								label={ __( 'Data do Parto *', 'canil-core' ) }
								type="date"
								value={ formData.birth_date }
								onChange={ handleChange( 'birth_date' ) }
								required
							/>
							<SelectControl
								label={ __( 'Tipo de Parto', 'canil-core' ) }
								value={ formData.birth_type }
								options={ birthTypeOptions }
								onChange={ handleChange( 'birth_type' ) }
							/>
						</div>
						<TextareaControl
							label={ __( 'Observações do Parto', 'canil-core' ) }
							value={ formData.notes }
							onChange={ handleChange( 'notes' ) }
							rows={ 3 }
						/>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<div
							style={ {
								display: 'flex',
								justifyContent: 'space-between',
								alignItems: 'center',
								width: '100%',
							} }
						>
							<h2>
								{ __( 'Filhotes', 'canil-core' ) } (
								{ puppies.length })
							</h2>
							<div>
								<span style={ { marginRight: '16px' } }>
									{ __( 'Machos:', 'canil-core' ) }{ ' ' }
									{ malesCount } |{ ' ' }
									{ __( 'Fêmeas:', 'canil-core' ) }{ ' ' }
									{ femalesCount }
								</span>
								<Button
									variant="secondary"
									onClick={ addPuppy }
									icon={ <Icon icon={ plus } /> }
								>
									{ __( 'Adicionar Filhote', 'canil-core' ) }
								</Button>
							</div>
						</div>
					</CardHeader>
					<CardBody>
						{ puppies.map( ( puppy, index ) => (
							<div
								key={ index }
								className="canil-puppy-row"
								style={ {
									borderBottom: '1px solid #ddd',
									paddingBottom: '16px',
									marginBottom: '16px',
								} }
							>
								<div
									style={ {
										display: 'flex',
										justifyContent: 'space-between',
										alignItems: 'center',
										marginBottom: '8px',
									} }
								>
									<strong>
										{ __( 'Filhote', 'canil-core' ) } #
										{ index + 1 }
									</strong>
									{ puppies.length > 1 && (
										<Button
											variant="tertiary"
											isDestructive
											onClick={ () =>
												removePuppy( index )
											}
											icon={ <Icon icon={ trash } /> }
										>
											{ __( 'Remover', 'canil-core' ) }
										</Button>
									) }
								</div>
								<div className="canil-form-row">
									<TextControl
										label={ __(
											'Identificador',
											'canil-core'
										) }
										value={ puppy.identifier }
										onChange={ handlePuppyChange(
											index,
											'identifier'
										) }
										placeholder={
											puppy.sex === 'male'
												? `M-${ index + 1 }`
												: `F-${ index + 1 }`
										}
									/>
									<SelectControl
										label={ __( 'Sexo *', 'canil-core' ) }
										value={ puppy.sex }
										options={ sexOptions }
										onChange={ handlePuppyChange(
											index,
											'sex'
										) }
									/>
									<TextControl
										label={ __( 'Cor', 'canil-core' ) }
										value={ puppy.color }
										onChange={ handlePuppyChange(
											index,
											'color'
										) }
									/>
									<TextControl
										label={ __(
											'Peso ao Nascer (g)',
											'canil-core'
										) }
										type="number"
										min="0"
										value={ puppy.birth_weight }
										onChange={ handlePuppyChange(
											index,
											'birth_weight'
										) }
									/>
								</div>
								<TextareaControl
									label={ __( 'Observações', 'canil-core' ) }
									value={ puppy.notes }
									onChange={ handlePuppyChange(
										index,
										'notes'
									) }
									rows={ 2 }
								/>
							</div>
						) ) }
					</CardBody>
				</Card>

				<div className="canil-form-actions">
					<Button
						variant="secondary"
						onClick={ () => navigate( `/litters/${ litterId }` ) }
					>
						{ __( 'Cancelar', 'canil-core' ) }
					</Button>
					<Button
						variant="primary"
						type="submit"
						isBusy={ saving }
						disabled={ saving || ! formData.birth_date }
					>
						{ saving
							? __( 'Salvando…', 'canil-core' )
							: __( 'Registrar Parto', 'canil-core' ) }
					</Button>
				</div>
			</form>
		</div>
	);
}

export default BirthForm;
