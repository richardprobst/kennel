/**
 * Litter Form Page.
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
 * LitterForm component for creating/editing litters.
 *
 * @return {JSX.Element} The litter form component.
 */
function LitterForm() {
	const navigate = useNavigate();
	const { id } = useParams();
	const isEditing = Boolean(id);

	const [loading, setLoading] = useState(false);
	const [saving, setSaving] = useState(false);
	const [error, setError] = useState(null);
	const [success, setSuccess] = useState(null);
	const [dogs, setDogs] = useState({ males: [], females: [] });

	const [formData, setFormData] = useState({
		name: '',
		litter_letter: '',
		dam_id: '',
		sire_id: '',
		status: 'planned',
		heat_start_date: '',
		mating_date: '',
		mating_type: '',
		pregnancy_confirmed_date: '',
		expected_birth_date: '',
		actual_birth_date: '',
		birth_type: '',
		puppies_born_count: '',
		puppies_alive_count: '',
		males_count: '',
		females_count: '',
		notes: '',
	});

	const statusOptions = [
		{ label: __('Planejada', 'canil-core'), value: 'planned' },
		{ label: __('Confirmada', 'canil-core'), value: 'confirmed' },
		{ label: __('Prenhe', 'canil-core'), value: 'pregnant' },
		{ label: __('Nascida', 'canil-core'), value: 'born' },
		{ label: __('Desmamada', 'canil-core'), value: 'weaned' },
		{ label: __('Encerrada', 'canil-core'), value: 'closed' },
		{ label: __('Cancelada', 'canil-core'), value: 'cancelled' },
	];

	const matingTypeOptions = [
		{ label: __('Selecione...', 'canil-core'), value: '' },
		{ label: __('Natural', 'canil-core'), value: 'natural' },
		{ label: __('Inseminação (fresco)', 'canil-core'), value: 'artificial_fresh' },
		{ label: __('Inseminação (congelado)', 'canil-core'), value: 'artificial_frozen' },
	];

	const birthTypeOptions = [
		{ label: __('Selecione...', 'canil-core'), value: '' },
		{ label: __('Natural', 'canil-core'), value: 'natural' },
		{ label: __('Cesárea', 'canil-core'), value: 'cesarean' },
		{ label: __('Assistido', 'canil-core'), value: 'assisted' },
	];

	useEffect(() => {
		fetchDogs();
		if (isEditing) {
			fetchLitter();
		}
	}, [id]);

	const fetchDogs = async () => {
		try {
			const [malesResponse, femalesResponse] = await Promise.all([
				apiFetch({ path: '/canil/v1/dogs/breeding?sex=male' }),
				apiFetch({ path: '/canil/v1/dogs/breeding?sex=female' }),
			]);

			setDogs({
				males: malesResponse.data || [],
				females: femalesResponse.data || [],
			});
		} catch (err) {
			console.error('Error fetching dogs:', err);
		}
	};

	const fetchLitter = async () => {
		setLoading(true);
		setError(null);

		try {
			const response = await apiFetch({
				path: `/canil/v1/litters/${id}`,
			});

			const data = response.data || {};
			setFormData({
				name: data.name || '',
				litter_letter: data.litter_letter || '',
				dam_id: data.dam_id || '',
				sire_id: data.sire_id || '',
				status: data.status || 'planned',
				heat_start_date: data.heat_start_date || '',
				mating_date: data.mating_date || '',
				mating_type: data.mating_type || '',
				pregnancy_confirmed_date: data.pregnancy_confirmed_date || '',
				expected_birth_date: data.expected_birth_date || '',
				actual_birth_date: data.actual_birth_date || '',
				birth_type: data.birth_type || '',
				puppies_born_count: data.puppies_born_count || '',
				puppies_alive_count: data.puppies_alive_count || '',
				males_count: data.males_count || '',
				females_count: data.females_count || '',
				notes: data.notes || '',
			});
		} catch (err) {
			setError(err.message || __('Erro ao carregar ninhada.', 'canil-core'));
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

			// Convert IDs to integers.
			if (data.dam_id) data.dam_id = parseInt(data.dam_id, 10);
			if (data.sire_id) data.sire_id = parseInt(data.sire_id, 10);

			// Remove empty optional fields.
			Object.keys(data).forEach((key) => {
				if (data[key] === '') {
					data[key] = null;
				}
			});

			if (isEditing) {
				await apiFetch({
					path: `/canil/v1/litters/${id}`,
					method: 'PUT',
					data,
				});
				setSuccess(__('Ninhada atualizada com sucesso!', 'canil-core'));
			} else {
				await apiFetch({
					path: '/canil/v1/litters',
					method: 'POST',
					data,
				});
				setSuccess(__('Ninhada criada com sucesso!', 'canil-core'));
				setTimeout(() => navigate('/litters'), 1500);
			}
		} catch (err) {
			setError(err.message || __('Erro ao salvar ninhada.', 'canil-core'));
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

	const damOptions = [
		{ label: __('Selecione a matriz...', 'canil-core'), value: '' },
		...dogs.females.map((dog) => ({
			label: dog.name,
			value: dog.id.toString(),
		})),
	];

	const sireOptions = [
		{ label: __('Selecione o reprodutor...', 'canil-core'), value: '' },
		...dogs.males.map((dog) => ({
			label: dog.name,
			value: dog.id.toString(),
		})),
	];

	return (
		<div className="canil-litter-form">
			<div className="canil-page-header">
				<h1>
					{isEditing
						? __('Editar Ninhada', 'canil-core')
						: __('Adicionar Ninhada', 'canil-core')}
				</h1>
				<Button variant="secondary" onClick={() => navigate('/litters')}>
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
						<h2>{__('Identificação', 'canil-core')}</h2>
					</CardHeader>
					<CardBody>
						<div className="canil-form-row">
							<TextControl
								label={__('Nome da Ninhada', 'canil-core')}
								value={formData.name}
								onChange={handleChange('name')}
								help={__('Ex: Ninhada A, Ninhada do Verão', 'canil-core')}
							/>
							<TextControl
								label={__('Letra', 'canil-core')}
								value={formData.litter_letter}
								onChange={handleChange('litter_letter')}
								maxLength={1}
								help={__('Letra da ninhada (A, B, C...)', 'canil-core')}
							/>
						</div>
						<div className="canil-form-row">
							<SelectControl
								label={__('Matriz (Mãe) *', 'canil-core')}
								value={formData.dam_id}
								options={damOptions}
								onChange={handleChange('dam_id')}
							/>
							<SelectControl
								label={__('Reprodutor (Pai) *', 'canil-core')}
								value={formData.sire_id}
								options={sireOptions}
								onChange={handleChange('sire_id')}
							/>
						</div>
						<div className="canil-form-row">
							<SelectControl
								label={__('Status', 'canil-core')}
								value={formData.status}
								options={statusOptions}
								onChange={handleChange('status')}
							/>
						</div>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{__('Datas', 'canil-core')}</h2>
					</CardHeader>
					<CardBody>
						<div className="canil-form-row">
							<TextControl
								label={__('Início do Cio', 'canil-core')}
								type="date"
								value={formData.heat_start_date}
								onChange={handleChange('heat_start_date')}
							/>
							<TextControl
								label={__('Data da Cobertura', 'canil-core')}
								type="date"
								value={formData.mating_date}
								onChange={handleChange('mating_date')}
							/>
							<SelectControl
								label={__('Tipo de Cobertura', 'canil-core')}
								value={formData.mating_type}
								options={matingTypeOptions}
								onChange={handleChange('mating_type')}
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={__('Confirmação de Gestação', 'canil-core')}
								type="date"
								value={formData.pregnancy_confirmed_date}
								onChange={handleChange('pregnancy_confirmed_date')}
							/>
							<TextControl
								label={__('Previsão de Parto', 'canil-core')}
								type="date"
								value={formData.expected_birth_date}
								onChange={handleChange('expected_birth_date')}
								help={__('Calculado automaticamente: cobertura + 63 dias', 'canil-core')}
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={__('Data do Parto', 'canil-core')}
								type="date"
								value={formData.actual_birth_date}
								onChange={handleChange('actual_birth_date')}
							/>
							<SelectControl
								label={__('Tipo de Parto', 'canil-core')}
								value={formData.birth_type}
								options={birthTypeOptions}
								onChange={handleChange('birth_type')}
							/>
						</div>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{__('Filhotes', 'canil-core')}</h2>
					</CardHeader>
					<CardBody>
						<div className="canil-form-row">
							<TextControl
								label={__('Nascidos', 'canil-core')}
								type="number"
								min="0"
								value={formData.puppies_born_count}
								onChange={handleChange('puppies_born_count')}
							/>
							<TextControl
								label={__('Vivos', 'canil-core')}
								type="number"
								min="0"
								value={formData.puppies_alive_count}
								onChange={handleChange('puppies_alive_count')}
							/>
						</div>
						<div className="canil-form-row">
							<TextControl
								label={__('Machos', 'canil-core')}
								type="number"
								min="0"
								value={formData.males_count}
								onChange={handleChange('males_count')}
							/>
							<TextControl
								label={__('Fêmeas', 'canil-core')}
								type="number"
								min="0"
								value={formData.females_count}
								onChange={handleChange('females_count')}
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
					<Button variant="secondary" onClick={() => navigate('/litters')}>
						{__('Cancelar', 'canil-core')}
					</Button>
					<Button variant="primary" type="submit" isBusy={saving} disabled={saving}>
						{saving
							? __('Salvando...', 'canil-core')
							: isEditing
							? __('Atualizar', 'canil-core')
							: __('Criar Ninhada', 'canil-core')}
					</Button>
				</div>
			</form>
		</div>
	);
}

export default LitterForm;
