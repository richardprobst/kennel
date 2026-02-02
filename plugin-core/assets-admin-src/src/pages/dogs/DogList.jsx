/**
 * Dog List Page.
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
	Spinner,
	Notice,
	SearchControl,
	SelectControl,
} from '@wordpress/components';
import { Link, useNavigate } from 'react-router-dom';

/**
 * DogList component.
 *
 * @return {JSX.Element} The dog list component.
 */
function DogList() {
	const navigate = useNavigate();
	const [ dogs, setDogs ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ search, setSearch ] = useState( '' );
	const [ statusFilter, setStatusFilter ] = useState( '' );
	const [ sexFilter, setSexFilter ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );

	const statusOptions = [
		{ label: __( 'Todos', 'canil-core' ), value: '' },
		{ label: __( 'Ativo', 'canil-core' ), value: 'active' },
		{ label: __( 'Reprodutor(a)', 'canil-core' ), value: 'breeding' },
		{ label: __( 'Aposentado', 'canil-core' ), value: 'retired' },
		{ label: __( 'Vendido', 'canil-core' ), value: 'sold' },
		{ label: __( 'Falecido', 'canil-core' ), value: 'deceased' },
	];

	const sexOptions = [
		{ label: __( 'Todos', 'canil-core' ), value: '' },
		{ label: __( 'Macho', 'canil-core' ), value: 'male' },
		{ label: __( 'Fêmea', 'canil-core' ), value: 'female' },
	];

	const fetchDogs = useCallback( async () => {
		setLoading( true );
		setError( null );

		try {
			const params = new URLSearchParams( {
				page: page.toString(),
				per_page: '20',
			} );

			if ( search ) {
				params.append( 'search', search );
			}
			if ( statusFilter ) {
				params.append( 'status', statusFilter );
			}
			if ( sexFilter ) {
				params.append( 'sex', sexFilter );
			}

			const response = await apiFetch( {
				path: `/canil/v1/dogs?${ params.toString() }`,
			} );

			setDogs( response.data || [] );
			setTotalPages( response.meta?.total_pages || 1 );
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao carregar cães.', 'canil-core' )
			);
		} finally {
			setLoading( false );
		}
	}, [ search, statusFilter, sexFilter, page ] );

	useEffect( () => {
		fetchDogs();
	}, [ fetchDogs ] );

	const handleDelete = async ( id ) => {
		if (
			// eslint-disable-next-line no-alert
			! window.confirm(
				__( 'Tem certeza que deseja excluir este cão?', 'canil-core' )
			)
		) {
			return;
		}

		try {
			await apiFetch( {
				path: `/canil/v1/dogs/${ id }`,
				method: 'DELETE',
			} );
			fetchDogs();
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao excluir cão.', 'canil-core' )
			);
		}
	};

	return (
		<div className="canil-dog-list">
			<div className="canil-page-header">
				<h1>{ __( 'Cães', 'canil-core' ) }</h1>
				<Button
					variant="primary"
					onClick={ () => navigate( '/dogs/new' ) }
				>
					{ __( 'Adicionar Cão', 'canil-core' ) }
				</Button>
			</div>

			<Card>
				<CardBody>
					<div className="canil-filters">
						<SearchControl
							label={ __( 'Buscar', 'canil-core' ) }
							value={ search }
							onChange={ setSearch }
						/>
						<SelectControl
							label={ __( 'Status', 'canil-core' ) }
							value={ statusFilter }
							options={ statusOptions }
							onChange={ setStatusFilter }
						/>
						<SelectControl
							label={ __( 'Sexo', 'canil-core' ) }
							value={ sexFilter }
							options={ sexOptions }
							onChange={ setSexFilter }
						/>
					</div>

					{ error && (
						<Notice status="error" isDismissible={ false }>
							{ error }
						</Notice>
					) }

					{ loading && (
						<div className="canil-loading">
							<Spinner />
						</div>
					) }
					{ ! loading && dogs.length === 0 && (
						<div className="canil-empty-state">
							<p>
								{ __( 'Nenhum cão encontrado.', 'canil-core' ) }
							</p>
							<Button
								variant="primary"
								onClick={ () => navigate( '/dogs/new' ) }
							>
								{ __( 'Adicionar Primeiro Cão', 'canil-core' ) }
							</Button>
						</div>
					) }
					{ ! loading && dogs.length > 0 && (
						<>
							<table className="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th>{ __( 'Nome', 'canil-core' ) }</th>
										<th>{ __( 'Raça', 'canil-core' ) }</th>
										<th>{ __( 'Sexo', 'canil-core' ) }</th>
										<th>
											{ __( 'Status', 'canil-core' ) }
										</th>
										<th>
											{ __( 'Nascimento', 'canil-core' ) }
										</th>
										<th>{ __( 'Ações', 'canil-core' ) }</th>
									</tr>
								</thead>
								<tbody>
									{ dogs.map( ( dog ) => (
										<tr key={ dog.id }>
											<td>
												<Link
													to={ `/dogs/${ dog.id }` }
												>
													<strong>
														{ dog.name }
													</strong>
												</Link>
												{ dog.call_name && (
													<span className="canil-call-name">
														({ dog.call_name })
													</span>
												) }
											</td>
											<td>{ dog.breed }</td>
											<td>
												{ dog.sex === 'male'
													? __(
															'Macho',
															'canil-core'
													  )
													: __(
															'Fêmea',
															'canil-core'
													  ) }
											</td>
											<td>{ dog.status }</td>
											<td>{ dog.birth_date }</td>
											<td>
												<Button
													variant="secondary"
													size="small"
													onClick={ () =>
														navigate(
															`/dogs/${ dog.id }`
														)
													}
												>
													{ __(
														'Editar',
														'canil-core'
													) }
												</Button>
												<Button
													variant="tertiary"
													size="small"
													isDestructive
													onClick={ () =>
														handleDelete( dog.id )
													}
												>
													{ __(
														'Excluir',
														'canil-core'
													) }
												</Button>
											</td>
										</tr>
									) ) }
								</tbody>
							</table>

							{ totalPages > 1 && (
								<div className="canil-pagination">
									<Button
										variant="secondary"
										disabled={ page <= 1 }
										onClick={ () => setPage( page - 1 ) }
									>
										{ __( 'Anterior', 'canil-core' ) }
									</Button>
									<span>
										{ __( 'Página', 'canil-core' ) }{ ' ' }
										{ page } { __( 'de', 'canil-core' ) }{ ' ' }
										{ totalPages }
									</span>
									<Button
										variant="secondary"
										disabled={ page >= totalPages }
										onClick={ () => setPage( page + 1 ) }
									>
										{ __( 'Próxima', 'canil-core' ) }
									</Button>
								</div>
							) }
						</>
					) }
				</CardBody>
			</Card>
		</div>
	);
}

export default DogList;
