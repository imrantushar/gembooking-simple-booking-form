import { __, sprintf } from '@wordpress/i18n';
import { monthLabel, monthShape, weekdayShort } from '../utils/time';

/**
 * Month grid. `days` is a Map of open dates => still has a free slot, or null while loading.
 */
export default function Calendar( {
	month,
	days,
	selectedDate,
	timezone,
	canPrev,
	canNext,
	onPrev,
	onNext,
	onPick,
} ) {
	const { lead, days: count } = monthShape( month );
	const cells = [];

	for ( let i = 0; i < lead; i++ ) {
		cells.push( <div key={ `b${ i }` } /> );
	}
	for ( let n = 1; n <= count; n++ ) {
		const date = `${ month }-${ String( n ).padStart( 2, '0' ) }`;
		const open = days && days.get( date ) === true;
		const selected = selectedDate === date;

		cells.push(
			open ? (
				<button
					key={ date }
					type="button"
					className={ `gsbf-day${ selected ? ' is-selected' : '' }` }
					aria-pressed={ selected }
					onClick={ () => onPick( date ) }
				>
					{ n }
				</button>
			) : (
				<div key={ date } className="gsbf-day is-off">
					{ n }
				</div>
			)
		);
	}

	return (
		<div className="gsbf-cal">
			<div className="gsbf-cal-head">
				<div className="gsbf-cal-title" aria-live="polite">
					{ monthLabel( month ) }
				</div>
				<div className="gsbf-cal-nav">
					<button
						type="button"
						className="gsbf-nav"
						aria-label={ __(
							'Previous month',
							'gembooking-simple-booking-form'
						) }
						disabled={ ! canPrev }
						onClick={ onPrev }
					>
						‹
					</button>
					<button
						type="button"
						className="gsbf-nav"
						aria-label={ __(
							'Next month',
							'gembooking-simple-booking-form'
						) }
						disabled={ ! canNext }
						onClick={ onNext }
					>
						›
					</button>
				</div>
			</div>
			<div className="gsbf-weekdays" aria-hidden="true">
				{ [ 0, 1, 2, 3, 4, 5, 6 ].map( ( i ) => (
					<div key={ i }>{ weekdayShort( i ) }</div>
				) ) }
			</div>
			<div className={ `gsbf-days${ days ? '' : ' is-loading' }` }>
				{ cells }
			</div>
			<div className="gsbf-note">
				{ sprintf(
					/* translators: %s: timezone */
					__( 'Times shown in %s', 'gembooking-simple-booking-form' ),
					timezone
				) }
			</div>
		</div>
	);
}
