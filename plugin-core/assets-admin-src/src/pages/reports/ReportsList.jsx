/**
 * Reports List Page.
 *
 * @package
 */

import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	Spinner,
	Notice,
	SelectControl,
	TextControl,
} from '@wordpress/components';

/**
 * ReportsList component.
 *
 * @return {JSX.Element} The reports list component.
 */
function ReportsList() {
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ report, setReport ] = useState( null );
	const [ reportType, setReportType ] = useState( '' );
	const [ startDate, setStartDate ] = useState( '' );
	const [ endDate, setEndDate ] = useState( '' );
	const [ statusFilter, setStatusFilter ] = useState( '' );

	const reportTypes = [
		{ label: __( 'Selecione um relatório…', 'canil-core' ), value: '' },
		{ label: __( 'Plantel Atual', 'canil-core' ), value: 'plantel' },
		{ label: __( 'Ninhadas', 'canil-core' ), value: 'litters' },
		{ label: __( 'Filhotes', 'canil-core' ), value: 'puppies' },
		{ label: __( 'Eventos de Saúde', 'canil-core' ), value: 'health' },
	];

	const statusOptions = {
		plantel: [
			{ label: __( 'Todos', 'canil-core' ), value: '' },
			{ label: __( 'Ativo', 'canil-core' ), value: 'active' },
			{ label: __( 'Reprodutor(a)', 'canil-core' ), value: 'breeding' },
			{ label: __( 'Aposentado', 'canil-core' ), value: 'retired' },
		],
		litters: [
			{ label: __( 'Todos', 'canil-core' ), value: '' },
			{ label: __( 'Planejada', 'canil-core' ), value: 'planned' },
			{ label: __( 'Confirmada', 'canil-core' ), value: 'confirmed' },
			{ label: __( 'Em Gestação', 'canil-core' ), value: 'pregnant' },
			{ label: __( 'Nascida', 'canil-core' ), value: 'born' },
			{ label: __( 'Encerrada', 'canil-core' ), value: 'closed' },
		],
		puppies: [
			{ label: __( 'Todos', 'canil-core' ), value: '' },
			{ label: __( 'Disponível', 'canil-core' ), value: 'available' },
			{ label: __( 'Reservado', 'canil-core' ), value: 'reserved' },
			{ label: __( 'Vendido', 'canil-core' ), value: 'sold' },
			{ label: __( 'Retido', 'canil-core' ), value: 'retained' },
		],
		health: [ { label: __( 'Todos', 'canil-core' ), value: '' } ],
	};

	const fetchReport = useCallback(
		async ( format = 'json' ) => {
			if ( ! reportType ) {
				setError(
					__( 'Selecione um tipo de relatório.', 'canil-core' )
				);
				return;
			}

			setLoading( true );
			setError( null );

			try {
				const params = new URLSearchParams();

				if ( startDate ) {
					params.append( 'start_date', startDate );
				}
				if ( endDate ) {
					params.append( 'end_date', endDate );
				}
				if ( statusFilter ) {
					params.append( 'status', statusFilter );
				}
				if ( format === 'csv' ) {
					params.append( 'format', 'csv' );
				}

				const path = `/canil/v1/reports/${ reportType }?${ params.toString() }`;

				if ( format === 'csv' ) {
					// For CSV, we need to handle differently
					const response = await fetch(
						`${
							window.canilAdmin.apiUrl
						}/reports/${ reportType }?${ params.toString() }`,
						{
							headers: {
								'X-WP-Nonce': window.canilAdmin.nonce,
							},
						}
					);

					if ( ! response.ok ) {
						throw new Error(
							__( 'Erro ao gerar CSV.', 'canil-core' )
						);
					}

					const blob = await response.blob();
					const url = window.URL.createObjectURL( blob );
					const a = document.createElement( 'a' );
					a.href = url;
					a.download = `relatorio-${ reportType }-${
						new Date().toISOString().split( 'T' )[ 0 ]
					}.csv`;
					document.body.appendChild( a );
					a.click();
					window.URL.revokeObjectURL( url );
					document.body.removeChild( a );

					setLoading( false );
					return;
				}

				const response = await apiFetch( { path } );

				setReport( response.data || null );
			} catch ( err ) {
				setError(
					err.message ||
						__( 'Erro ao carregar relatório.', 'canil-core' )
				);
			} finally {
				setLoading( false );
			}
		},
		[ reportType, startDate, endDate, statusFilter ]
	);

	/**
	 * Render summary cards.
	 *
	 * @param {Object} summary - Summary data.
	 * @return {JSX.Element} Summary cards.
	 */
	const renderSummary = ( summary ) => {
		if ( ! summary ) {
			return null;
		}

		return (
			<div className="canil-summary">
				{ Object.entries( summary ).map( ( [ key, value ] ) => {
					// Skip nested objects for simple display
					if ( typeof value === 'object' ) {
						return null;
					}
					return (
						<div key={ key } className="canil-summary__item">
							<span className="canil-summary__label">
								{ key.replace( /_/g, ' ' ).toUpperCase() }
							</span>
							<span className="canil-summary__value">
								{ value }
							</span>
						</div>
					);
				} ) }
			</div>
		);
	};

	/**
	 * Render data table.
	 *
	 * @param {Array} data    - Data array.
	 * @param {Array} columns - Column definitions.
	 * @return {JSX.Element} Data table.
	 */
	const renderTable = ( data, columns ) => {
		if ( ! data || data.length === 0 ) {
			return <p>{ __( 'Nenhum registro encontrado.', 'canil-core' ) }</p>;
		}

		return (
			<table className="canil-table">
				<thead>
					<tr>
						{ columns.map( ( col ) => (
							<th key={ col.key }>{ col.label }</th>
						) ) }
					</tr>
				</thead>
				<tbody>
					{ data.map( ( row, index ) => (
						<tr key={ row.id || index }>
							{ columns.map( ( col ) => (
								<td key={ col.key }>
									{ col.render
										? col.render( row[ col.key ], row )
										: row[ col.key ] || '-' }
								</td>
							) ) }
						</tr>
					) ) }
				</tbody>
			</table>
		);
	};

	/**
	 * Get columns for report type.
	 *
	 * @param {string} type - Report type.
	 * @return {Array} Column definitions.
	 */
	const getColumns = ( type ) => {
		switch ( type ) {
			case 'plantel':
				return [
					{ key: 'name', label: __( 'Nome', 'canil-core' ) },
					{ key: 'breed', label: __( 'Raça', 'canil-core' ) },
					{
						key: 'sex',
						label: __( 'Sexo', 'canil-core' ),
						render: ( val ) =>
							val === 'male'
								? __( 'Macho', 'canil-core' )
								: __( 'Fêmea', 'canil-core' ),
					},
					{
						key: 'birth_date',
						label: __( 'Nascimento', 'canil-core' ),
					},
					{ key: 'status', label: __( 'Status', 'canil-core' ) },
				];
			case 'litters':
				return [
					{ key: 'name', label: __( 'Ninhada', 'canil-core' ) },
					{ key: 'dam_name', label: __( 'Matriz', 'canil-core' ) },
					{
						key: 'sire_name',
						label: __( 'Reprodutor', 'canil-core' ),
					},
					{
						key: 'mating_date',
						label: __( 'Cobertura', 'canil-core' ),
					},
					{ key: 'status', label: __( 'Status', 'canil-core' ) },
					{
						key: 'puppies_alive_count',
						label: __( 'Filhotes', 'canil-core' ),
					},
				];
			case 'puppies':
				return [
					{
						key: 'identifier',
						label: __( 'Identificador', 'canil-core' ),
					},
					{ key: 'name', label: __( 'Nome', 'canil-core' ) },
					{
						key: 'sex',
						label: __( 'Sexo', 'canil-core' ),
						render: ( val ) =>
							val === 'male'
								? __( 'Macho', 'canil-core' )
								: __( 'Fêmea', 'canil-core' ),
					},
					{ key: 'color', label: __( 'Cor', 'canil-core' ) },
					{ key: 'status', label: __( 'Status', 'canil-core' ) },
				];
			case 'health':
				return [
					{ key: 'event_date', label: __( 'Data', 'canil-core' ) },
					{
						key: 'event_type',
						label: __( 'Tipo', 'canil-core' ),
					},
					{
						key: 'entity_type',
						label: __( 'Entidade', 'canil-core' ),
					},
					{ key: 'notes', label: __( 'Observações', 'canil-core' ) },
				];
			default:
				return [];
		}
	};

	return (
		<div className="canil-page">
			<div className="canil-page__header">
				<h1>{ __( 'Relatórios', 'canil-core' ) }</h1>
			</div>

			<Card className="canil-filters">
				<CardBody>
					<div className="canil-filters__row">
						<SelectControl
							label={ __( 'Tipo de Relatório', 'canil-core' ) }
							value={ reportType }
							options={ reportTypes }
							onChange={ ( value ) => {
								setReportType( value );
								setReport( null );
								setStatusFilter( '' );
							} }
						/>

						{ reportType &&
							statusOptions[ reportType ] &&
							statusOptions[ reportType ].length > 1 && (
								<SelectControl
									label={ __( 'Status', 'canil-core' ) }
									value={ statusFilter }
									options={ statusOptions[ reportType ] }
									onChange={ setStatusFilter }
								/>
							) }

						{ ( reportType === 'litters' ||
							reportType === 'health' ) && (
							<>
								<TextControl
									label={ __( 'Data Inicial', 'canil-core' ) }
									type="date"
									value={ startDate }
									onChange={ setStartDate }
								/>
								<TextControl
									label={ __( 'Data Final', 'canil-core' ) }
									type="date"
									value={ endDate }
									onChange={ setEndDate }
								/>
							</>
						) }
					</div>

					<div className="canil-filters__actions">
						<Button
							variant="primary"
							onClick={ () => fetchReport( 'json' ) }
							disabled={ loading || ! reportType }
						>
							{ __( 'Gerar Relatório', 'canil-core' ) }
						</Button>
						<Button
							variant="secondary"
							onClick={ () => fetchReport( 'csv' ) }
							disabled={ loading || ! reportType }
						>
							{ __( 'Exportar CSV', 'canil-core' ) }
						</Button>
					</div>
				</CardBody>
			</Card>

			{ error && (
				<Notice
					status="error"
					isDismissible
					onDismiss={ () => setError( null ) }
				>
					{ error }
				</Notice>
			) }

			{ loading && (
				<div className="canil-loading">
					<Spinner />
					<p>{ __( 'Gerando relatório…', 'canil-core' ) }</p>
				</div>
			) }

			{ ! loading && report && (
				<Card className="canil-report">
					<CardHeader>
						<h2>
							{
								reportTypes.find(
									( r ) => r.value === reportType
								)?.label
							}
						</h2>
						<small>
							{ __( 'Gerado em:', 'canil-core' ) }{ ' ' }
							{ report.generated_at }
						</small>
					</CardHeader>
					<CardBody>
						{ renderSummary( report.summary ) }

						<div className="canil-report__data">
							{ renderTable(
								report.data,
								getColumns( reportType )
							) }
						</div>
					</CardBody>
				</Card>
			) }
		</div>
	);
}

export default ReportsList;
