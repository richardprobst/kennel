/**
 * Person Form Page.
 *
 * @package CanilCore
 */

import { useState, useEffect } from '@wordpress/element';
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
 * PersonForm component for creating/editing people.
 *
 * @return {JSX.Element} The person form component.
 */
function PersonForm() {
	const navigate = useNavigate();
	const { id } = useParams();
	const isEditing = Boolean(id);

	const [loading, setLoading] = useState(false);
	const [saving, setSaving] = useState(false);
	const [error, setError] = useState(null);
	const [success, setSuccess] = useState(null);

	const [formData, setFormData] = useState({
		name: '',
		email: '',
		phone: '',
		phone_secondary: '',
		type: 'interested',
		address_street: '',
		address_number: '',
		address_complement: '',
		address_neighborhood: '',
		address_city: '',
		address_state: '',
		address_zip: '',
		address_country: 'Brasil',
		document_cpf: '',
		document_rg: '',
		notes: '',
	});

	const typeOptions = [
		{ label: __('Interessado', 'canil-core'), value: 'interested' },
		{ label: __('Comprador', 'canil-core'), value: 'buyer' },
		{ label: __('Veterinário', 'canil-core'), value: 'veterinarian' },
		{ label: __('Handler', 'canil-core'), value: 'handler' },
		{ label: __('Parceiro', 'canil-core'), value: 'partner' },
		{ label: __('Outro', 'canil-core'), value: 'other' },
	];

	useEffect(() => {
		if (isEditing) {
			fetchPerson();
		}
	}, [id]);

	const fetchPerson = async () => {
		setLoading(true);
		setError(null);

		try {
			const response = await apiFetch({
				path: `/canil/v1/people/${id}`,
			});

			const data = response.data || {};
			setFormData({
				name: data.name || '',
				email: data.email || '',
				phone: data.phone || '',
				phone_secondary: data.phone_secondary || '',
				type: data.type || 'interested',
				address_street: data.address_street || '',
				address_number: data.address_number || '',
				address_complement: data.address_complement || '',
				address_neighborhood: data.address_neighborhood || '',
				address_city: data.address_city || '',
				address_state: data.address_state || '',
				address_zip: data.address_zip || '',
				address_country: data.address_country || 'Brasil',
				document_cpf: data.document_cpf || '',
				document_rg: data.document_rg || '',
				notes: data.notes || '',
			});
		} catch (err) {
			setError(err.message || __('Erro ao carregar pessoa.', 'canil-core'));
		} finally {
			setLoading(false);
		}
	};

	const handleChange = (field) => (value) => {
		setFormData((prev) => ({
			...prev,
			[field]: value,
		}));
	};

	const handleSubmit = async (e) => {
		e.preventDefault();
		setSaving(true);
		setError(null);
		setSuccess(null);

		try {
			const data = { ...formData };

			// Remove empty optional fields.
			Object.keys(data).forEach((key) => {
				if (data[key] === '') {
					data[key] = null;
				}
			});

			if (isEditing) {
				await apiFetch({
					path: `/canil/v1/people/${id}`,
					method: 'PUT',
					data,
				});
				setSuccess(__('Pessoa atualizada com sucesso!', 'canil-core'));
			} else {
				await apiFetch({
					path: '/canil/v1/people',
					method: 'POST',
					data,
				});
				setSuccess(__('Pessoa criada com sucesso!', 'canil-core'));
				setTimeout(() => navigate('/people'), 1500);
			}
		} catch (err) {
			setError(err.message || __('Erro ao salvar pessoa.', 'canil-core'));
		} finally {
			setSaving(false);
		}
	};

	if (loading) {
		return (
			<div className="canil-loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="canil-person-form">
			<div className="canil-page-header">
				<h1>
					{isEditing
						? __('Editar Pessoa', 'canil-core')
						: __('Adicionar Pessoa', 'canil-core')}
				</h1>
				<Button variant="secondary" onClick={() => navigate('/people')}>
					{__('Voltar', 'canil-core')}
				</Button>
			</div>

			{error && (
				<Notice status="error" isDismissible onDismiss={() => setError(null)}>
					{error}
				</Notice>
			)}

			{success && (
				<Notice status="success" isDismissible onDismiss={() => setSuccess(null)}>
					{success}
				</Notice>
			)}

			<form onSubmit={handleSubmit}>
				<Card>
					<CardHeader>
						<h2>{__('Dados Pessoais', 'canil-core')}</h2>
					</CardHeader>
					<CardBody>
						<div className="canil-form-row">
							<TextControl
								label={__('Nome *', 'canil-core')}
								value={formData.name}
								onChange={handleChange('name')}
								required
							/>
							<SelectControl
								label={__('Tipo', 'canil-core')}
								value={formData.type}
								options={typeOptions}
								onChange={handleChange('type')}
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={__('E-mail', 'canil-core')}
								type="email"
								value={formData.email}
								onChange={handleChange('email')}
							/>
							<TextControl
								label={__('Telefone', 'canil-core')}
								value={formData.phone}
								onChange={handleChange('phone')}
							/>
							<TextControl
								label={__('Telefone Secundário', 'canil-core')}
								value={formData.phone_secondary}
								onChange={handleChange('phone_secondary')}
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={__('CPF', 'canil-core')}
								value={formData.document_cpf}
								onChange={handleChange('document_cpf')}
							/>
							<TextControl
								label={__('RG', 'canil-core')}
								value={formData.document_rg}
								onChange={handleChange('document_rg')}
							/>
						</div>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{__('Endereço', 'canil-core')}</h2>
					</CardHeader>
					<CardBody>
						<div className="canil-form-row">
							<TextControl
								label={__('CEP', 'canil-core')}
								value={formData.address_zip}
								onChange={handleChange('address_zip')}
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={__('Logradouro', 'canil-core')}
								value={formData.address_street}
								onChange={handleChange('address_street')}
							/>
							<TextControl
								label={__('Número', 'canil-core')}
								value={formData.address_number}
								onChange={handleChange('address_number')}
								style={{ maxWidth: '100px' }}
							/>
							<TextControl
								label={__('Complemento', 'canil-core')}
								value={formData.address_complement}
								onChange={handleChange('address_complement')}
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={__('Bairro', 'canil-core')}
								value={formData.address_neighborhood}
								onChange={handleChange('address_neighborhood')}
							/>
							<TextControl
								label={__('Cidade', 'canil-core')}
								value={formData.address_city}
								onChange={handleChange('address_city')}
							/>
							<TextControl
								label={__('Estado', 'canil-core')}
								value={formData.address_state}
								onChange={handleChange('address_state')}
								style={{ maxWidth: '80px' }}
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={__('País', 'canil-core')}
								value={formData.address_country}
								onChange={handleChange('address_country')}
							/>
						</div>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{__('Observações', 'canil-core')}</h2>
					</CardHeader>
					<CardBody>
						<TextareaControl
							label={__('Notas', 'canil-core')}
							value={formData.notes}
							onChange={handleChange('notes')}
							rows={4}
						/>
					</CardBody>
				</Card>

				<div className="canil-form-actions">
					<Button variant="secondary" onClick={() => navigate('/people')}>
						{__('Cancelar', 'canil-core')}
					</Button>
					<Button variant="primary" type="submit" isBusy={saving} disabled={saving}>
						{saving
							? __('Salvando...', 'canil-core')
							: isEditing
							? __('Atualizar', 'canil-core')
							: __('Criar Pessoa', 'canil-core')}
					</Button>
				</div>
			</form>
		</div>
	);
}

export default PersonForm;
