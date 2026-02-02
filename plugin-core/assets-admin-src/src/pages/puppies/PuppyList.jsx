/**
 * Puppy List Page.
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
	Spinner,
	Notice,
	SearchControl,
	SelectControl,
} from '@wordpress/components';
import { Link, useNavigate } from 'react-router-dom';

/**
 * PuppyList component.
 *
 * @return {JSX.Element} The puppy list component.
 */
function PuppyList() {
	const navigate = useNavigate();
	const [puppies, setPuppies] = useState([]);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState(null);
	const [search, setSearch] = useState('');
	const [statusFilter, setStatusFilter] = useState('');
	const [sexFilter, setSexFilter] = useState('');
	const [page, setPage] = useState(1);
	const [totalPages, setTotalPages] = useState(1);

	const statusOptions = [
		{ label: __('Todos', 'canil-core'), value: '' },
		{ label: __('Disponível', 'canil-core'), value: 'available' },
		{ label: __('Reservado', 'canil-core'), value: 'reserved' },
		{ label: __('Vendido', 'canil-core'), value: 'sold' },
		{ label: __('Retido', 'canil-core'), value: 'retained' },
		{ label: __('Falecido', 'canil-core'), value: 'deceased' },
		{ label: __('Devolvido', 'canil-core'), value: 'returned' },
	];

	const sexOptions = [
		{ label: __('Todos', 'canil-core'), value: '' },
		{ label: __('Macho', 'canil-core'), value: 'male' },
		{ label: __('Fêmea', 'canil-core'), value: 'female' },
	];

	useEffect(() => {
		fetchPuppies();
	}, [search, statusFilter, sexFilter, page]);

	const fetchPuppies = async () => {
		setLoading(true);
		setError(null);

		try {
			const params = new URLSearchParams({
				page: page.toString(),
				per_page: '20',
			});

			if (search) {
				params.append('search', search);
			}
			if (statusFilter) {
				params.append('status', statusFilter);
			}
			if (sexFilter) {
				params.append('sex', sexFilter);
			}

			const response = await apiFetch({
				path: `/canil/v1/puppies?${params.toString()}`,
			});

			setPuppies(response.data || []);
			setTotalPages(response.meta?.total_pages || 1);
		} catch (err) {
			setError(err.message || __('Erro ao carregar filhotes.', 'canil-core'));
		} finally {
			setLoading(false);
		}
	};

	const handleDelete = async (id) => {
		if (!window.confirm(__('Tem certeza que deseja excluir este filhote?', 'canil-core'))) {
			return;
		}

		try {
			await apiFetch({
				path: `/canil/v1/puppies/${id}`,
				method: 'DELETE',
			});
			fetchPuppies();
		} catch (err) {
			setError(err.message || __('Erro ao excluir filhote.', 'canil-core'));
		}
	};

	const getStatusLabel = (status) => {
		const option = statusOptions.find((opt) => opt.value === status);
		return option ? option.label : status;
	};

	return (
		<div className="canil-puppy-list">
			<div className="canil-page-header">
				<h1>{__('Filhotes', 'canil-core')}</h1>
				<Button variant="primary" onClick={() => navigate('/puppies/new')}>
					{__('Adicionar Filhote', 'canil-core')}
				</Button>
			</div>

			<Card>
				<CardBody>
					<div className="canil-filters">
						<SearchControl
							label={__('Buscar', 'canil-core')}
							value={search}
							onChange={setSearch}
						/>
						<SelectControl
							label={__('Status', 'canil-core')}
							value={statusFilter}
							options={statusOptions}
							onChange={setStatusFilter}
						/>
						<SelectControl
							label={__('Sexo', 'canil-core')}
							value={sexFilter}
							options={sexOptions}
							onChange={setSexFilter}
						/>
					</div>

					{error && (
						<Notice status="error" isDismissible={false}>
							{error}
						</Notice>
					)}

					{loading ? (
						<div className="canil-loading">
							<Spinner />
						</div>
					) : puppies.length === 0 ? (
						<div className="canil-empty-state">
							<p>{__('Nenhum filhote encontrado.', 'canil-core')}</p>
							<Button variant="primary" onClick={() => navigate('/puppies/new')}>
								{__('Adicionar Primeiro Filhote', 'canil-core')}
							</Button>
						</div>
					) : (
						<>
							<table className="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th>{__('Identificador', 'canil-core')}</th>
										<th>{__('Nome', 'canil-core')}</th>
										<th>{__('Sexo', 'canil-core')}</th>
										<th>{__('Cor', 'canil-core')}</th>
										<th>{__('Status', 'canil-core')}</th>
										<th>{__('Ações', 'canil-core')}</th>
									</tr>
								</thead>
								<tbody>
									{puppies.map((puppy) => (
										<tr key={puppy.id}>
											<td>
												<Link to={`/puppies/${puppy.id}`}>
													<strong>{puppy.identifier}</strong>
												</Link>
											</td>
											<td>{puppy.name || puppy.call_name || '-'}</td>
											<td>
												{puppy.sex === 'male'
													? __('Macho', 'canil-core')
													: __('Fêmea', 'canil-core')}
											</td>
											<td>{puppy.color || '-'}</td>
											<td>{getStatusLabel(puppy.status)}</td>
											<td>
												<Button
													variant="secondary"
													size="small"
													onClick={() => navigate(`/puppies/${puppy.id}`)}
												>
													{__('Editar', 'canil-core')}
												</Button>
												<Button
													variant="tertiary"
													size="small"
													isDestructive
													onClick={() => handleDelete(puppy.id)}
												>
													{__('Excluir', 'canil-core')}
												</Button>
											</td>
										</tr>
									))}
								</tbody>
							</table>

							{totalPages > 1 && (
								<div className="canil-pagination">
									<Button
										variant="secondary"
										disabled={page <= 1}
										onClick={() => setPage(page - 1)}
									>
										{__('Anterior', 'canil-core')}
									</Button>
									<span>
										{__('Página', 'canil-core')} {page} {__('de', 'canil-core')}{' '}
										{totalPages}
									</span>
									<Button
										variant="secondary"
										disabled={page >= totalPages}
										onClick={() => setPage(page + 1)}
									>
										{__('Próxima', 'canil-core')}
									</Button>
								</div>
							)}
						</>
					)}
				</CardBody>
			</Card>
		</div>
	);
}

export default PuppyList;
