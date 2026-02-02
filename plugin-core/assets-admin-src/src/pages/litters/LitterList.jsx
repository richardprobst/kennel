/**
 * Litter List Page.
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
 * LitterList component.
 *
 * @return {JSX.Element} The litter list component.
 */
function LitterList() {
	const navigate = useNavigate();
	const [ litters, setLitters ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ search, setSearch ] = useState( '' );
	const [ statusFilter, setStatusFilter ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );

	const statusOptions = [
		{ label: __( 'Todos', 'canil-core' ), value: '' },
		{ label: __( 'Planejada', 'canil-core' ), value: 'planned' },
		{ label: __( 'Confirmada', 'canil-core' ), value: 'confirmed' },
		{ label: __( 'Prenhe', 'canil-core' ), value: 'pregnant' },
		{ label: __( 'Nascida', 'canil-core' ), value: 'born' },
		{ label: __( 'Desmamada', 'canil-core' ), value: 'weaned' },
		{ label: __( 'Encerrada', 'canil-core' ), value: 'closed' },
		{ label: __( 'Cancelada', 'canil-core' ), value: 'cancelled' },
	];

	const fetchLitters = useCallback( async () => {
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

			const response = await apiFetch( {
				path: `/canil/v1/litters?${ params.toString() }`,
			} );

			setLitters( response.data || [] );
			setTotalPages( response.meta?.total_pages || 1 );
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao carregar ninhadas.', 'canil-core' )
			);
		} finally {
			setLoading( false );
		}
	}, [ search, statusFilter, page ] );

	useEffect( () => {
		fetchLitters();
	}, [ fetchLitters ] );

	const handleDelete = async ( id ) => {
		if (
			// eslint-disable-next-line no-alert
			! window.confirm(
				__(
					'Tem certeza que deseja excluir esta ninhada?',
					'canil-core'
				)
			)
		) {
			return;
		}

		try {
			await apiFetch( {
				path: `/canil/v1/litters/${ id }`,
				method: 'DELETE',
			} );
			fetchLitters();
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao excluir ninhada.', 'canil-core' )
			);
		}
	};

	const getStatusLabel = ( status ) => {
		const option = statusOptions.find( ( opt ) => opt.value === status );
		return option ? option.label : status;
	};

	return (
		<div className="canil-litter-list">
			<div className="canil-page-header">
				<h1>{ __( 'Ninhadas', 'canil-core' ) }</h1>
				<Button
					variant="primary"
					onClick={ () => navigate( '/litters/new' ) }
				>
					{ __( 'Adicionar Ninhada', 'canil-core' ) }
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

					{ ! loading && litters.length === 0 && (
						<div className="canil-empty-state">
							<p>
								{ __(
									'Nenhuma ninhada encontrada.',
									'canil-core'
								) }
							</p>
							<Button
								variant="primary"
								onClick={ () => navigate( '/litters/new' ) }
							>
								{ __(
									'Adicionar Primeira Ninhada',
									'canil-core'
								) }
							</Button>
						</div>
					) }

					{ ! loading && litters.length > 0 && (
						<>
							<table className="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th>
											{ __( 'Nome/Letra', 'canil-core' ) }
										</th>
										<th>
											{ __( 'Status', 'canil-core' ) }
										</th>
										<th>
											{ __(
												'Data Cobertura',
												'canil-core'
											) }
										</th>
										<th>
											{ __(
												'Data Prevista',
												'canil-core'
											) }
										</th>
										<th>
											{ __( 'Nascidos', 'canil-core' ) }
										</th>
										<th>{ __( 'Ações', 'canil-core' ) }</th>
									</tr>
								</thead>
								<tbody>
									{ litters.map( ( litter ) => (
										<tr key={ litter.id }>
											<td>
												<Link
													to={ `/litters/${ litter.id }` }
												>
													<strong>
														{ litter.name ||
															litter.litter_letter ||
															`#${ litter.id }` }
													</strong>
												</Link>
											</td>
											<td>
												{ getStatusLabel(
													litter.status
												) }
											</td>
											<td>
												{ litter.mating_date || '-' }
											</td>
											<td>
												{ litter.expected_birth_date ||
													'-' }
											</td>
											<td>
												{ litter.puppies_born_count > 0
													? `${ litter.puppies_alive_count }/${ litter.puppies_born_count }`
													: '-' }
											</td>
											<td>
												<Button
													variant="secondary"
													size="small"
													onClick={ () =>
														navigate(
															`/litters/${ litter.id }`
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
														handleDelete(
															litter.id
														)
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

export default LitterList;
