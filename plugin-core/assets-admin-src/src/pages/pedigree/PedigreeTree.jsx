/**
 * Pedigree Tree Page.
 *
 * @package
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	Card,
	CardBody,
	CardHeader,
	Spinner,
	Notice,
	SelectControl,
} from '@wordpress/components';
import { useParams, useNavigate, Link } from 'react-router-dom';

/**
 * Pedigree Tree View component.
 *
 * @return {JSX.Element} The pedigree tree component.
 */
function PedigreeTree() {
	const { id } = useParams();
	const navigate = useNavigate();
	const [ dogId, setDogId ] = useState( id || '' );
	const [ dogs, setDogs ] = useState( [] );
	const [ pedigree, setPedigree ] = useState( null );
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ generations, setGenerations ] = useState( '3' );

	// Fetch dogs for selection
	useEffect( () => {
		const fetchDogs = async () => {
			try {
				const response = await apiFetch( {
					path: '/canil/v1/dogs?per_page=100',
				} );
				setDogs( response.data || [] );
			} catch ( err ) {
				// Silent fail
			}
		};
		fetchDogs();
	}, [] );

	// Fetch pedigree when dog or generations change
	const fetchPedigree = useCallback( async () => {
		if ( ! dogId ) {
			setPedigree( null );
			return;
		}

		setLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `/canil/v1/pedigree/${ dogId }?generations=${ generations }`,
			} );

			setPedigree( response.data || null );
		} catch ( err ) {
			setError(
				err.message || __( 'Erro ao carregar pedigree.', 'canil-core' )
			);
		} finally {
			setLoading( false );
		}
	}, [ dogId, generations ] );

	useEffect( () => {
		if ( dogId ) {
			fetchPedigree();
		}
	}, [ fetchPedigree, dogId ] );

	// Dog options for select
	const dogOptions = [
		{ label: __( 'Selecione um cão…', 'canil-core' ), value: '' },
		...dogs.map( ( dog ) => ( {
			label: `${ dog.name } (${ dog.breed })`,
			value: dog.id.toString(),
		} ) ),
	];

	const generationOptions = [
		{ label: __( '1 geração (Pais)', 'canil-core' ), value: '1' },
		{ label: __( '2 gerações (Avós)', 'canil-core' ), value: '2' },
		{ label: __( '3 gerações (Bisavós)', 'canil-core' ), value: '3' },
		{ label: __( '4 gerações (Trisavós)', 'canil-core' ), value: '4' },
		{ label: __( '5 gerações (Tetravós)', 'canil-core' ), value: '5' },
	];

	/**
	 * Render ancestor node.
	 *
	 * @param {Object|null} node     - Ancestor node data.
	 * @param {string}      role     - Role label.
	 * @param {number}      depth    - Current depth.
	 * @param {number}      maxDepth - Maximum depth.
	 * @return {JSX.Element} The ancestor node element.
	 */
	const renderAncestor = ( node, role, depth, maxDepth ) => {
		if ( ! node ) {
			return (
				<div className="pedigree-node pedigree-node--unknown">
					<div className="pedigree-node__role">{ role }</div>
					<div className="pedigree-node__name">
						{ __( 'Desconhecido', 'canil-core' ) }
					</div>
				</div>
			);
		}

		const dog = node.dog || {};
		const hasChildren = depth < maxDepth && node.pedigree;

		return (
			<div className="pedigree-branch">
				<div
					className={ `pedigree-node pedigree-node--${
						dog.sex || 'unknown'
					}` }
				>
					<div className="pedigree-node__role">{ role }</div>
					{ dog.photo_main_url && (
						<img
							src={ dog.photo_main_url }
							alt={ dog.name }
							className="pedigree-node__photo"
						/>
					) }
					<div className="pedigree-node__name">
						{ dog.id ? (
							<Link to={ `/dogs/${ dog.id }` }>{ dog.name }</Link>
						) : (
							dog.name
						) }
					</div>
					{ dog.registration_number && (
						<div className="pedigree-node__reg">
							{ dog.registration_number }
						</div>
					) }
					{ dog.titles && dog.titles.length > 0 && (
						<div className="pedigree-node__titles">
							{ dog.titles.map( ( t ) => t.title ).join( ', ' ) }
						</div>
					) }
				</div>
				{ hasChildren && (
					<div className="pedigree-children">
						{ renderAncestor(
							node.pedigree?.sire,
							__( 'Pai', 'canil-core' ),
							depth + 1,
							maxDepth
						) }
						{ renderAncestor(
							node.pedigree?.dam,
							__( 'Mãe', 'canil-core' ),
							depth + 1,
							maxDepth
						) }
					</div>
				) }
			</div>
		);
	};

	return (
		<div className="canil-page">
			<div className="canil-page__header">
				<h1>{ __( 'Pedigree', 'canil-core' ) }</h1>
			</div>

			<Card className="canil-filters">
				<CardBody>
					<div className="canil-filters__row">
						<SelectControl
							label={ __( 'Cão', 'canil-core' ) }
							value={ dogId }
							options={ dogOptions }
							onChange={ ( value ) => {
								setDogId( value );
								if ( value ) {
									navigate( `/pedigree/${ value }` );
								}
							} }
						/>
						<SelectControl
							label={ __( 'Gerações', 'canil-core' ) }
							value={ generations }
							options={ generationOptions }
							onChange={ setGenerations }
						/>
					</div>
				</CardBody>
			</Card>

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			{ loading && (
				<div className="canil-loading">
					<Spinner />
					<p>{ __( 'Carregando pedigree…', 'canil-core' ) }</p>
				</div>
			) }

			{ ! loading && pedigree && (
				<Card className="canil-pedigree-card">
					<CardHeader>
						<h2>
							{ __( 'Pedigree de', 'canil-core' ) }{ ' ' }
							{ pedigree.dog?.name }
						</h2>
					</CardHeader>
					<CardBody>
						<div className="pedigree-tree">
							<div className="pedigree-root">
								<div className="pedigree-node pedigree-node--root">
									{ pedigree.dog?.photo_main_url && (
										<img
											src={ pedigree.dog.photo_main_url }
											alt={ pedigree.dog.name }
											className="pedigree-node__photo pedigree-node__photo--large"
										/>
									) }
									<div className="pedigree-node__name">
										<strong>{ pedigree.dog?.name }</strong>
									</div>
									{ pedigree.dog?.registration_number && (
										<div className="pedigree-node__reg">
											{ pedigree.dog.registration_number }
										</div>
									) }
									{ pedigree.dog?.breed && (
										<div className="pedigree-node__breed">
											{ pedigree.dog.breed }
										</div>
									) }
								</div>
								<div className="pedigree-children">
									{ renderAncestor(
										pedigree.pedigree?.sire,
										__( 'Pai', 'canil-core' ),
										1,
										parseInt( generations, 10 )
									) }
									{ renderAncestor(
										pedigree.pedigree?.dam,
										__( 'Mãe', 'canil-core' ),
										1,
										parseInt( generations, 10 )
									) }
								</div>
							</div>
						</div>
					</CardBody>
				</Card>
			) }

			{ ! loading && ! pedigree && dogId && (
				<Notice status="warning" isDismissible={ false }>
					{ __( 'Pedigree não encontrado.', 'canil-core' ) }
				</Notice>
			) }

			{ ! dogId && (
				<Card>
					<CardBody>
						<p>
							{ __(
								'Selecione um cão acima para visualizar seu pedigree.',
								'canil-core'
							) }
						</p>
					</CardBody>
				</Card>
			) }
		</div>
	);
}

export default PedigreeTree;
