import { __ } from '@wordpress/i18n';

/**
 * Pending / confirmed screen with the booking summary.
 */
export default function DoneStep( { pending, title, text, rows, onAnother } ) {
	return (
		<div className="gsbf-done">
			<div
				className={ `gsbf-done-icon ${ pending ? 'is-pending' : 'is-ok' }` }
			>
				{ pending ? (
					<svg
						width="28"
						height="28"
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						strokeWidth="2"
						strokeLinecap="round"
						strokeLinejoin="round"
						aria-hidden="true"
					>
						<circle cx="12" cy="12" r="9" />
						<path d="M12 7v5l3 2" />
					</svg>
				) : (
					<svg
						width="28"
						height="28"
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						strokeWidth="2.5"
						strokeLinecap="round"
						strokeLinejoin="round"
						aria-hidden="true"
					>
						<path d="M5 12.5l4.5 4.5L19 7.5" />
					</svg>
				) }
			</div>
			<div className="gsbf-done-title" role="status">
				{ title }
			</div>
			<div className="gsbf-done-text">{ text }</div>
			<div className="gsbf-rows">
				{ rows.map( ( [ label, value, mono ] ) => (
					<div className="gsbf-row" key={ label }>
						<span>{ label }</span>
						<span className={ mono ? 'is-mono' : '' }>
							{ value }
						</span>
					</div>
				) ) }
			</div>
			<button
				type="button"
				className="gsbf-btn-secondary is-block"
				onClick={ onAnother }
			>
				{ __( 'Book another time', 'gembooking-simple-booking-form' ) }
			</button>
		</div>
	);
}
