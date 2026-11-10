import { __ } from '@wordpress/i18n';
import { formatMinutes } from '../utils/time';

/**
 * Time slots of the selected date with the 12h / 24h switch.
 */
export default function SlotList( {
	title,
	date,
	slots,
	selectedStart,
	h24,
	onToggle24,
	onPick,
	emptyText,
} ) {
	let body;

	if ( ! date ) {
		body = <div className="gsbf-empty">{ emptyText }</div>;
	} else if ( slots === null ) {
		body = (
			<div className="gsbf-empty">
				{ __( 'Loading times…', 'gembooking-simple-booking-form' ) }
			</div>
		);
	} else if ( slots.length === 0 ) {
		body = (
			<div className="gsbf-empty">
				{ __(
					'No times left on this day.',
					'gembooking-simple-booking-form'
				) }
			</div>
		);
	} else {
		body = (
			<div className="gsbf-slot-list">
				{ slots.map( ( slot ) => {
					const selected = selectedStart === slot.start;
					return (
						<button
							key={ slot.start }
							type="button"
							className={ `gsbf-slot${
								selected ? ' is-selected' : ''
							}` }
							disabled={ ! slot.available }
							aria-pressed={ selected }
							onClick={ () => onPick( slot ) }
						>
							{ formatMinutes( slot.minutes, h24 ) }
						</button>
					);
				} ) }
			</div>
		);
	}

	return (
		<div className="gsbf-slots">
			<div className="gsbf-slots-head">
				<div className="gsbf-cal-title">{ title }</div>
				<div className="gsbf-seg" role="group">
					<button
						type="button"
						className={ ! h24 ? 'is-on' : '' }
						aria-label={ __(
							'12-hour format',
							'gembooking-simple-booking-form'
						) }
						onClick={ () => onToggle24( false ) }
					>
						12h
					</button>
					<button
						type="button"
						className={ h24 ? 'is-on' : '' }
						aria-label={ __(
							'24-hour format',
							'gembooking-simple-booking-form'
						) }
						onClick={ () => onToggle24( true ) }
					>
						24h
					</button>
				</div>
			</div>
			{ body }
		</div>
	);
}
