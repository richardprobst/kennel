/**
 * Person List Page.
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
 * PersonList component.
 *
 * @return {JSX.Element} The person list component.
 */
function PersonList() {
	const navigate = useNavigate();
	const [people, setPeople] = useState([]);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState(null);
	const [search, setSearch] = useState('');
	const [typeFilter, setTypeFilter] = useState('');
	const [page, setPage] = useState(1);
	const [totalPages, setTotalPages] = useState(1);

	const typeOptions = [
		{ label: __('Todos', 'canil-core'), value: '' },
		{ label: __('Interessado', 'canil-core'), value: 'interested' },
		{ label: __('Comprador', 'canil-core'), value: 'buyer' },
		{ label: __('Veterinário', 'canil-core'), value: 'veterinarian' },
		{ label: __('Handler', 'canil-core'), value: 'handler' },
		{ label: __('Parceiro', 'canil-core'), value: 'partner' },
		{ label: __('Outro', 'canil-core'), value: 'other' },
	];

	useEffect(() => {
		fetchPeople();
	}, [search, typeFilter, page]);

	const fetchPeople = async () => {
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
			if (typeFilter) {
				params.append('type', typeFilter);
			}

			const response = await apiFetch({
				path: `/canil/v1/people?${params.toString()}`,
			});

			setPeople(response.data || []);
			setTotalPages(response.meta?.total_pages || 1);
		} catch (err) {
			setError(err.message || __('Erro ao carregar pessoas.', 'canil-core'));
		} finally {
			setLoading(false);
		}
	};

	const handleDelete = async (id) => {
		if (!window.confirm(__('Tem certeza que deseja excluir esta pessoa?', 'canil-core'))) {
			return;
		}

		try {
			await apiFetch({
				path: `/canil/v1/people/${id}`,
				method: 'DELETE',
			});
			fetchPeople();
		} catch (err) {
			setError(err.message || __('Erro ao excluir pessoa.', 'canil-core'));
		}
	};

	const getTypeLabel = (type) => {
		const option = typeOptions.find((opt) => opt.value === type);
		return option ? option.label : type;
	};

	return (
		<div className="canil-person-list">
			<div className="canil-page-header">
				<h1>{__('Pessoas', 'canil-core')}</h1>
				<Button variant="primary" onClick={() => navigate('/people/new')}>
					{__('Adicionar Pessoa', 'canil-core')}
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
							label={__('Tipo', 'canil-core')}
							value={typeFilter}
							options={typeOptions}
							onChange={setTypeFilter}
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
					) : people.length === 0 ? (
						<div className="canil-empty-state">
							<p>{__('Nenhuma pessoa encontrada.', 'canil-core')}</p>
							<Button variant="primary" onClick={() => navigate('/people/new')}>
								{__('Adicionar Primeira Pessoa', 'canil-core')}
							</Button>
						</div>
					) : (
						<>
							<table className="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th>{__('Nome', 'canil-core')}</th>
										<th>{__('Tipo', 'canil-core')}</th>
										<th>{__('E-mail', 'canil-core')}</th>
										<th>{__('Telefone', 'canil-core')}</th>
										<th>{__('Cidade', 'canil-core')}</th>
										<th>{__('Ações', 'canil-core')}</th>
									</tr>
								</thead>
								<tbody>
									{people.map((person) => (
										<tr key={person.id}>
											<td>
												<Link to={`/people/${person.id}`}>
													<strong>{person.name}</strong>
												</Link>
											</td>
											<td>{getTypeLabel(person.type)}</td>
											<td>{person.email || '-'}</td>
											<td>{person.phone || '-'}</td>
											<td>{person.address_city || '-'}</td>
											<td>
												<Button
													variant="secondary"
													size="small"
													onClick={() => navigate(`/people/${person.id}`)}
												>
													{__('Editar', 'canil-core')}
												</Button>
												<Button
													variant="tertiary"
													size="small"
													isDestructive
													onClick={() => handleDelete(person.id)}
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

export default PersonList;
