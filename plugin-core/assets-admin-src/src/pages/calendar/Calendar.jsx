/**
 * Calendar Page.
 *
 * Monthly calendar view with events for the kennel system.
 *
 * @package
 */

import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	Spinner,
	Notice,
	CheckboxControl,
} from '@wordpress/components';
import { Link } from 'react-router-dom';

/**
 * Event type colors configuration.
 */
const EVENT_COLORS = {
	reproduction: '#e91e63',
	health: '#4caf50',
	weighing: '#2196f3',
	other: '#9e9e9e',
};

/**
 * Event type labels.
 */
const EVENT_TYPE_LABELS = {
	reproduction: __( 'Reprodução', 'canil-core' ),
	health: __( 'Saúde', 'canil-core' ),
	weighing: __( 'Pesagem', 'canil-core' ),
	other: __( 'Outros', 'canil-core' ),
};

/**
 * Map specific event types to categories.
 *
 * @param {string} eventType The specific event type.
 * @return {string} The event category.
 */
const getEventCategory = ( eventType ) => {
	const reproductionTypes = [
		'mating',
		'heat',
		'pregnancy_check',
		'birth',
		'expected_birth',
	];
	const healthTypes = [
		'vaccine',
		'deworming',
		'exam',
		'medication',
		'surgery',
		'vet_visit',
	];
	const weighingTypes = [ 'weighing' ];

	if ( reproductionTypes.includes( eventType ) ) {
		return 'reproduction';
	}
	if ( healthTypes.includes( eventType ) ) {
		return 'health';
	}
	if ( weighingTypes.includes( eventType ) ) {
		return 'weighing';
	}
	return 'other';
};

/**
 * Get the number of days in a month.
 *
 * @param {number} year  The year.
 * @param {number} month The month (0-11).
 * @return {number} Number of days.
 */
const getDaysInMonth = ( year, month ) => {
	return new Date( year, month + 1, 0 ).getDate();
};

/**
 * Get the first day of the week for a month (0 = Sunday, 6 = Saturday).
 *
 * @param {number} year  The year.
 * @param {number} month The month (0-11).
 * @return {number} Day of week.
 */
const getFirstDayOfMonth = ( year, month ) => {
	return new Date( year, month, 1 ).getDay();
};

/**
 * Format date to YYYY-MM-DD.
 *
 * @param {Date} date The date.
 * @return {string} Formatted date.
 */
const formatDate = ( date ) => {
	const year = date.getFullYear();
	const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
	const day = String( date.getDate() ).padStart( 2, '0' );
	return `${ year }-${ month }-${ day }`;
};

/**
 * Get localized month name.
 *
 * @param {number} month The month (0-11).
 * @return {string} Month name.
 */
const getMonthName = ( month ) => {
	const months = [
		__( 'Janeiro', 'canil-core' ),
		__( 'Fevereiro', 'canil-core' ),
		__( 'Março', 'canil-core' ),
		__( 'Abril', 'canil-core' ),
		__( 'Maio', 'canil-core' ),
		__( 'Junho', 'canil-core' ),
		__( 'Julho', 'canil-core' ),
		__( 'Agosto', 'canil-core' ),
		__( 'Setembro', 'canil-core' ),
		__( 'Outubro', 'canil-core' ),
		__( 'Novembro', 'canil-core' ),
		__( 'Dezembro', 'canil-core' ),
	];
	return months[ month ];
};

/**
 * Get localized weekday names.
 *
 * @return {string[]} Array of weekday names.
 */
const getWeekdayNames = () => {
	return [
		__( 'Dom', 'canil-core' ),
		__( 'Seg', 'canil-core' ),
		__( 'Ter', 'canil-core' ),
		__( 'Qua', 'canil-core' ),
		__( 'Qui', 'canil-core' ),
		__( 'Sex', 'canil-core' ),
		__( 'Sáb', 'canil-core' ),
	];
};

/**
 * Calendar component.
 *
 * @return {JSX.Element} The calendar component.
 */
function Calendar() {
	const today = new Date();
	const [ currentYear, setCurrentYear ] = useState( today.getFullYear() );
	const [ currentMonth, setCurrentMonth ] = useState( today.getMonth() );
	const [ selectedDate, setSelectedDate ] = useState( null );
	const [ events, setEvents ] = useState( [] );
	const [ dayEvents, setDayEvents ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ dayLoading, setDayLoading ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ summary, setSummary ] = useState( {
		upcoming: 0,
		overdue: 0,
		thisMonth: 0,
	} );
	const [ summaryLoading, setSummaryLoading ] = useState( true );

	// Event type filters.
	const [ filters, setFilters ] = useState( {
		reproduction: true,
		health: true,
		weighing: true,
		other: true,
	} );

	// Calculate month start/end dates.
	const monthStart = useMemo( () => {
		return new Date( currentYear, currentMonth, 1 );
	}, [ currentYear, currentMonth ] );

	const monthEnd = useMemo( () => {
		return new Date( currentYear, currentMonth + 1, 0 );
	}, [ currentYear, currentMonth ] );

	// Fetch events for the current month.
	const fetchEvents = useCallback( async () => {
		setLoading( true );
		setError( null );

		try {
			const params = new URLSearchParams( {
				start_date: formatDate( monthStart ),
				end_date: formatDate( monthEnd ),
			} );

			const response = await apiFetch( {
				path: `/canil/v1/calendar/events?${ params.toString() }`,
			} );

			setEvents( response.data || [] );
		} catch ( err ) {
			setError(
				err.message ||
					__(
						'Erro ao carregar eventos do calendário.',
						'canil-core'
					)
			);
		} finally {
			setLoading( false );
		}
	}, [ monthStart, monthEnd ] );

	// Fetch summary stats.
	const fetchSummary = useCallback( async () => {
		setSummaryLoading( true );

		try {
			const params = new URLSearchParams( {
				start_date: formatDate( monthStart ),
				end_date: formatDate( monthEnd ),
			} );

			const response = await apiFetch( {
				path: `/canil/v1/calendar/summary?${ params.toString() }`,
			} );

			setSummary( {
				upcoming: response.upcoming || 0,
				overdue: response.overdue || 0,
				thisMonth: response.this_month || 0,
			} );
		} catch ( err ) {
			// Stats are optional, don't block the page.
			// eslint-disable-next-line no-console
			console.warn( 'Failed to load calendar summary:', err );
		} finally {
			setSummaryLoading( false );
		}
	}, [ monthStart, monthEnd ] );

	// Fetch events for selected day.
	const fetchDayEvents = useCallback( async ( date ) => {
		setDayLoading( true );

		try {
			const response = await apiFetch( {
				path: `/canil/v1/calendar/date/${ date }`,
			} );

			setDayEvents( response.data || [] );
		} catch ( err ) {
			// eslint-disable-next-line no-console
			console.warn( 'Failed to load day events:', err );
			setDayEvents( [] );
		} finally {
			setDayLoading( false );
		}
	}, [] );

	useEffect( () => {
		fetchEvents();
		fetchSummary();
	}, [ fetchEvents, fetchSummary ] );

	useEffect( () => {
		if ( selectedDate ) {
			fetchDayEvents( selectedDate );
		}
	}, [ selectedDate, fetchDayEvents ] );

	// Group events by date.
	const eventsByDate = useMemo( () => {
		const grouped = {};
		events.forEach( ( event ) => {
			const date = event.event_date;
			if ( ! grouped[ date ] ) {
				grouped[ date ] = [];
			}
			grouped[ date ].push( event );
		} );
		return grouped;
	}, [ events ] );

	// Filter events by category.
	const getFilteredEventsForDate = useCallback(
		( date ) => {
			const dateEvents = eventsByDate[ date ] || [];
			return dateEvents.filter( ( event ) => {
				const category = getEventCategory( event.event_type );
				return filters[ category ];
			} );
		},
		[ eventsByDate, filters ]
	);

	// Navigation handlers.
	const goToPreviousMonth = () => {
		if ( currentMonth === 0 ) {
			setCurrentMonth( 11 );
			setCurrentYear( currentYear - 1 );
		} else {
			setCurrentMonth( currentMonth - 1 );
		}
		setSelectedDate( null );
	};

	const goToNextMonth = () => {
		if ( currentMonth === 11 ) {
			setCurrentMonth( 0 );
			setCurrentYear( currentYear + 1 );
		} else {
			setCurrentMonth( currentMonth + 1 );
		}
		setSelectedDate( null );
	};

	const goToToday = () => {
		setCurrentYear( today.getFullYear() );
		setCurrentMonth( today.getMonth() );
		setSelectedDate( formatDate( today ) );
	};

	// Handle day click.
	const handleDayClick = ( day ) => {
		const date = formatDate( new Date( currentYear, currentMonth, day ) );
		setSelectedDate( date );
	};

	// Toggle filter.
	const toggleFilter = ( category ) => {
		setFilters( ( prev ) => ( {
			...prev,
			[ category ]: ! prev[ category ],
		} ) );
	};

	// Generate calendar grid.
	const renderCalendarGrid = () => {
		const daysInMonth = getDaysInMonth( currentYear, currentMonth );
		const firstDayOfWeek = getFirstDayOfMonth( currentYear, currentMonth );
		const todayStr = formatDate( today );

		const cells = [];

		// Empty cells before first day.
		for ( let i = 0; i < firstDayOfWeek; i++ ) {
			cells.push(
				<div
					key={ `empty-${ i }` }
					className="canil-calendar-day canil-calendar-day-empty"
				/>
			);
		}

		// Days of the month.
		for ( let day = 1; day <= daysInMonth; day++ ) {
			const dateStr = formatDate(
				new Date( currentYear, currentMonth, day )
			);
			const filteredEvents = getFilteredEventsForDate( dateStr );
			const isToday = dateStr === todayStr;
			const isSelected = dateStr === selectedDate;

			// Get unique categories for this day.
			const categories = [
				...new Set(
					filteredEvents.map( ( e ) =>
						getEventCategory( e.event_type )
					)
				),
			];

			cells.push(
				<div
					key={ day }
					className={ `canil-calendar-day${
						isToday ? ' canil-calendar-day-today' : ''
					}${ isSelected ? ' canil-calendar-day-selected' : '' }` }
					onClick={ () => handleDayClick( day ) }
					onKeyDown={ ( e ) => {
						if ( e.key === 'Enter' || e.key === ' ' ) {
							handleDayClick( day );
						}
					} }
					role="button"
					tabIndex={ 0 }
				>
					<span className="canil-calendar-day-number">{ day }</span>
					{ categories.length > 0 && (
						<div className="canil-calendar-day-events">
							{ categories.map( ( cat ) => (
								<span
									key={ cat }
									className="canil-calendar-event-dot"
									style={ {
										backgroundColor: EVENT_COLORS[ cat ],
									} }
									title={ EVENT_TYPE_LABELS[ cat ] }
								/>
							) ) }
						</div>
					) }
				</div>
			);
		}

		return cells;
	};

	// Render event type badge.
	const renderEventBadge = ( eventType ) => {
		const category = getEventCategory( eventType );
		return (
			<span
				className="canil-calendar-event-badge"
				style={ { backgroundColor: EVENT_COLORS[ category ] } }
			>
				{ eventType }
			</span>
		);
	};

	// Get entity link.
	const getEntityLink = ( event ) => {
		if ( event.entity_type === 'dog' ) {
			return `/dogs/${ event.entity_id }`;
		}
		if ( event.entity_type === 'puppy' ) {
			return `/puppies/${ event.entity_id }`;
		}
		if ( event.entity_type === 'litter' ) {
			return `/litters/${ event.entity_id }`;
		}
		return null;
	};

	// Get entity name.
	const getEntityName = ( event ) => {
		return (
			event.entity_name ||
			event.dog_name ||
			event.puppy_name ||
			event.litter_name ||
			'-'
		);
	};

	// Get event edit link.
	const getEventEditLink = ( event ) => {
		const category = getEventCategory( event.event_type );
		if ( category === 'health' ) {
			return `/health/${ event.id }`;
		}
		if ( category === 'weighing' ) {
			return `/weighing/chart/${ event.entity_type }/${ event.entity_id }`;
		}
		return null;
	};

	// Render day detail content.
	const renderDayDetailContent = () => {
		if ( dayLoading ) {
			return (
				<div className="canil-loading">
					<Spinner />
				</div>
			);
		}

		if ( filteredDayEvents.length === 0 ) {
			return (
				<div className="canil-empty-state">
					<p>{ __( 'Nenhum evento neste dia.', 'canil-core' ) }</p>
				</div>
			);
		}

		return (
			<ul className="canil-calendar-event-list">
				{ filteredDayEvents.map( ( event ) => {
					const entityLink = getEntityLink( event );
					const editLink = getEventEditLink( event );

					return (
						<li
							key={ event.id }
							className="canil-calendar-event-item"
						>
							<div className="canil-calendar-event-header">
								{ renderEventBadge( event.event_type ) }
								<span className="canil-calendar-event-title">
									{ event.title || event.event_type }
								</span>
							</div>
							<div className="canil-calendar-event-details">
								{ event.event_time && (
									<span className="canil-calendar-event-time">
										{ event.event_time }
									</span>
								) }
								{ entityLink ? (
									<Link
										to={ entityLink }
										className="canil-calendar-event-entity"
									>
										{ getEntityName( event ) }
									</Link>
								) : (
									<span className="canil-calendar-event-entity">
										{ getEntityName( event ) }
									</span>
								) }
							</div>
							{ event.notes && (
								<p className="canil-calendar-event-notes">
									{ event.notes }
								</p>
							) }
							<div className="canil-calendar-event-actions">
								{ entityLink && (
									<Link
										to={ entityLink }
										className="components-button is-secondary is-small"
									>
										{ __( 'Ver', 'canil-core' ) }
									</Link>
								) }
								{ editLink && (
									<Link
										to={ editLink }
										className="components-button is-secondary is-small"
									>
										{ __( 'Editar', 'canil-core' ) }
									</Link>
								) }
							</div>
						</li>
					);
				} ) }
			</ul>
		);
	};

	// Filter day events by current filters.
	const filteredDayEvents = dayEvents.filter( ( event ) => {
		const category = getEventCategory( event.event_type );
		return filters[ category ];
	} );

	return (
		<div className="canil-calendar">
			<div className="canil-page-header">
				<h1>{ __( 'Calendário', 'canil-core' ) }</h1>
				<Button variant="secondary" onClick={ goToToday }>
					{ __( 'Hoje', 'canil-core' ) }
				</Button>
			</div>

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			{ /* Summary Stats */ }
			<div className="canil-calendar-summary">
				<Card className="canil-stat-card canil-stat-info">
					<CardBody>
						<div className="canil-stat-value">
							{ summaryLoading ? <Spinner /> : summary.upcoming }
						</div>
						<div className="canil-stat-label">
							{ __( 'Eventos Próximos', 'canil-core' ) }
						</div>
					</CardBody>
				</Card>
				<Card className="canil-stat-card canil-stat-danger">
					<CardBody>
						<div className="canil-stat-value">
							{ summaryLoading ? <Spinner /> : summary.overdue }
						</div>
						<div className="canil-stat-label">
							{ __( 'Lembretes Atrasados', 'canil-core' ) }
						</div>
					</CardBody>
				</Card>
				<Card className="canil-stat-card canil-stat-success">
					<CardBody>
						<div className="canil-stat-value">
							{ summaryLoading ? <Spinner /> : summary.thisMonth }
						</div>
						<div className="canil-stat-label">
							{ __( 'Eventos este Mês', 'canil-core' ) }
						</div>
					</CardBody>
				</Card>
			</div>

			<div className="canil-calendar-container">
				{ /* Filters and Legend */ }
				<Card className="canil-calendar-filters">
					<CardHeader>
						<h2>{ __( 'Filtros', 'canil-core' ) }</h2>
					</CardHeader>
					<CardBody>
						<div className="canil-calendar-filter-list">
							{ Object.keys( EVENT_COLORS ).map( ( category ) => (
								<CheckboxControl
									key={ category }
									label={
										<span className="canil-calendar-filter-label">
											<span
												className="canil-calendar-filter-color"
												style={ {
													backgroundColor:
														EVENT_COLORS[
															category
														],
												} }
											/>
											{ EVENT_TYPE_LABELS[ category ] }
										</span>
									}
									checked={ filters[ category ] }
									onChange={ () => toggleFilter( category ) }
								/>
							) ) }
						</div>

						<div className="canil-calendar-legend">
							<h3>{ __( 'Legenda', 'canil-core' ) }</h3>
							<ul>
								{ Object.keys( EVENT_COLORS ).map(
									( category ) => (
										<li key={ category }>
											<span
												className="canil-calendar-legend-dot"
												style={ {
													backgroundColor:
														EVENT_COLORS[
															category
														],
												} }
											/>
											{ EVENT_TYPE_LABELS[ category ] }
										</li>
									)
								) }
							</ul>
						</div>
					</CardBody>
				</Card>

				{ /* Calendar */ }
				<Card className="canil-calendar-main">
					<CardHeader>
						<div className="canil-calendar-nav">
							<Button
								variant="secondary"
								onClick={ goToPreviousMonth }
								aria-label={ __(
									'Mês anterior',
									'canil-core'
								) }
							>
								‹
							</Button>
							<h2>
								{ getMonthName( currentMonth ) } { currentYear }
							</h2>
							<Button
								variant="secondary"
								onClick={ goToNextMonth }
								aria-label={ __( 'Próximo mês', 'canil-core' ) }
							>
								›
							</Button>
						</div>
					</CardHeader>
					<CardBody>
						{ loading ? (
							<div className="canil-loading">
								<Spinner />
							</div>
						) : (
							<div className="canil-calendar-grid">
								{ /* Weekday headers */ }
								{ getWeekdayNames().map( ( name ) => (
									<div
										key={ name }
										className="canil-calendar-weekday"
									>
										{ name }
									</div>
								) ) }
								{ /* Calendar days */ }
								{ renderCalendarGrid() }
							</div>
						) }
					</CardBody>
				</Card>

				{ /* Day Detail Panel */ }
				{ selectedDate && (
					<Card className="canil-calendar-day-detail">
						<CardHeader>
							<h2>
								{ __( 'Eventos em', 'canil-core' ) }{ ' ' }
								{ new Date(
									selectedDate + 'T00:00:00'
								).toLocaleDateString( 'pt-BR' ) }
							</h2>
						</CardHeader>
						<CardBody>{ renderDayDetailContent() }</CardBody>
					</Card>
				) }
			</div>

			{ /* Inline styles for calendar */ }
			<style>{ `
				.canil-calendar-summary {
					display: grid;
					grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
					gap: 15px;
					margin-bottom: 20px;
				}

				.canil-stat-card {
					text-align: center;
				}

				.canil-stat-value {
					font-size: 32px;
					font-weight: 700;
					line-height: 1.2;
				}

				.canil-stat-label {
					font-size: 13px;
					color: #666;
					margin-top: 5px;
				}

				.canil-stat-info .canil-stat-value { color: #2196f3; }
				.canil-stat-danger .canil-stat-value { color: #f44336; }
				.canil-stat-success .canil-stat-value { color: #4caf50; }
				.canil-stat-warning .canil-stat-value { color: #ff9800; }

				.canil-calendar-container {
					display: grid;
					grid-template-columns: 220px 1fr 280px;
					gap: 20px;
					align-items: start;
				}

				@media (max-width: 1200px) {
					.canil-calendar-container {
						grid-template-columns: 1fr;
					}
					.canil-calendar-filters,
					.canil-calendar-day-detail {
						order: 2;
					}
					.canil-calendar-main {
						order: 1;
					}
				}

				.canil-calendar-filters .components-card__header h2,
				.canil-calendar-main .components-card__header h2,
				.canil-calendar-day-detail .components-card__header h2 {
					margin: 0;
					font-size: 14px;
					font-weight: 600;
				}

				.canil-calendar-filter-list {
					display: flex;
					flex-direction: column;
					gap: 8px;
				}

				.canil-calendar-filter-label {
					display: flex;
					align-items: center;
					gap: 8px;
				}

				.canil-calendar-filter-color,
				.canil-calendar-legend-dot {
					width: 12px;
					height: 12px;
					border-radius: 50%;
					display: inline-block;
				}

				.canil-calendar-legend {
					margin-top: 20px;
					padding-top: 15px;
					border-top: 1px solid #ddd;
				}

				.canil-calendar-legend h3 {
					margin: 0 0 10px;
					font-size: 13px;
					font-weight: 600;
				}

				.canil-calendar-legend ul {
					list-style: none;
					margin: 0;
					padding: 0;
				}

				.canil-calendar-legend li {
					display: flex;
					align-items: center;
					gap: 8px;
					font-size: 12px;
					color: #666;
					margin-bottom: 5px;
				}

				.canil-calendar-nav {
					display: flex;
					align-items: center;
					justify-content: space-between;
					width: 100%;
				}

				.canil-calendar-nav h2 {
					margin: 0;
					font-size: 16px;
					font-weight: 600;
				}

				.canil-calendar-nav .components-button {
					min-width: 36px;
					padding: 0;
					font-size: 20px;
				}

				.canil-calendar-grid {
					display: grid;
					grid-template-columns: repeat(7, 1fr);
					gap: 2px;
				}

				.canil-calendar-weekday {
					text-align: center;
					font-size: 12px;
					font-weight: 600;
					color: #666;
					padding: 8px 4px;
					background: #f5f5f5;
				}

				.canil-calendar-day {
					aspect-ratio: 1;
					min-height: 60px;
					padding: 4px;
					background: #fff;
					border: 1px solid #e0e0e0;
					cursor: pointer;
					display: flex;
					flex-direction: column;
					transition: background-color 0.15s, border-color 0.15s;
				}

				.canil-calendar-day:hover {
					background: #f5f5f5;
				}

				.canil-calendar-day-empty {
					background: #fafafa;
					cursor: default;
				}

				.canil-calendar-day-today {
					border-color: #2196f3;
					border-width: 2px;
				}

				.canil-calendar-day-today .canil-calendar-day-number {
					background: #2196f3;
					color: #fff;
					border-radius: 50%;
					width: 24px;
					height: 24px;
					display: flex;
					align-items: center;
					justify-content: center;
				}

				.canil-calendar-day-selected {
					background: #e3f2fd;
					border-color: #1976d2;
				}

				.canil-calendar-day-number {
					font-size: 13px;
					font-weight: 500;
				}

				.canil-calendar-day-events {
					display: flex;
					flex-wrap: wrap;
					gap: 3px;
					margin-top: auto;
				}

				.canil-calendar-event-dot {
					width: 8px;
					height: 8px;
					border-radius: 50%;
				}

				.canil-calendar-event-list {
					list-style: none;
					margin: 0;
					padding: 0;
				}

				.canil-calendar-event-item {
					padding: 12px;
					border: 1px solid #e0e0e0;
					border-radius: 4px;
					margin-bottom: 10px;
				}

				.canil-calendar-event-item:last-child {
					margin-bottom: 0;
				}

				.canil-calendar-event-header {
					display: flex;
					align-items: center;
					gap: 8px;
					margin-bottom: 8px;
				}

				.canil-calendar-event-badge {
					display: inline-block;
					padding: 2px 8px;
					border-radius: 3px;
					font-size: 11px;
					font-weight: 600;
					color: #fff;
					text-transform: uppercase;
				}

				.canil-calendar-event-title {
					font-weight: 500;
				}

				.canil-calendar-event-details {
					display: flex;
					flex-wrap: wrap;
					gap: 10px;
					font-size: 13px;
					color: #666;
					margin-bottom: 8px;
				}

				.canil-calendar-event-time {
					font-weight: 500;
				}

				.canil-calendar-event-entity a {
					color: #1976d2;
					text-decoration: none;
				}

				.canil-calendar-event-entity a:hover {
					text-decoration: underline;
				}

				.canil-calendar-event-notes {
					font-size: 12px;
					color: #666;
					margin: 0 0 8px;
					font-style: italic;
				}

				.canil-calendar-event-actions {
					display: flex;
					gap: 8px;
				}

				.canil-calendar-event-actions .components-button {
					font-size: 12px;
				}
			` }</style>
		</div>
	);
}

export default Calendar;
