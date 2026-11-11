/* eslint-disable jsx-a11y/label-has-associated-control -- labels are tied to their inputs by id (htmlFor), which the rule cannot follow through the helper */
import { __ } from '@wordpress/i18n';

/**
 * Name / email / phone. In reschedule mode the fields are read-only and the
 * approval warning (manual mode) must be acknowledged first.
 */
export default function DetailsStep( {
	summary,
	fields,
	errors,
	phoneRequired,
	readOnly,
	warning,
	acknowledged,
	onAcknowledge,
	pickError,
	submitError,
	saving,
	submitLabel,
	honeypotRef,
	onChange,
	onBack,
	onSubmit,
} ) {
	const field = ( key, label, props ) => (
		<>
			{  }
			<label className="gsbf-label" htmlFor={ `gsbf-${ key }` }>
				{ label }
			</label>
			<input
				id={ `gsbf-${ key }` }
				className={ `gsbf-input${ errors[ key ] ? ' has-error' : '' }` }
				value={ fields[ key ] }
				readOnly={ readOnly }
				aria-invalid={ !! errors[ key ] }
				onChange={ ( e ) => onChange( key, e.target.value ) }
				{ ...props }
			/>
			{ errors[ key ] && (
				<div className="gsbf-error" role="alert">
					{ errors[ key ] }
				</div>
			) }
		</>
	);

	return (
		<div className="gsbf-details">
			<div className="gsbf-summary">
				<div className="gsbf-summary-text">{ summary }</div>
				<button
					type="button"
					className="gsbf-linkbtn"
					onClick={ onBack }
				>
					{ __( 'Change', 'gembooking-simple-booking-form' ) }
				</button>
			</div>

			{ field( 'name', __( 'Name', 'gembooking-simple-booking-form' ), {
				placeholder: __(
					'Your full name',
					'gembooking-simple-booking-form'
				),
				autoComplete: 'name',
			} ) }
			<div className="gsbf-gap" />
			{ field( 'email', __( 'Email', 'gembooking-simple-booking-form' ), {
				type: 'email',
				placeholder: 'you@example.com',
				autoComplete: 'email',
			} ) }
			<div className="gsbf-gap" />
			{ field(
				'phone',
				<>
					{ __( 'Phone', 'gembooking-simple-booking-form' ) }{ ' ' }
					{ ! phoneRequired && (
						<span className="gsbf-optional">
							{ __(
								'(optional)',
								'gembooking-simple-booking-form'
							) }
						</span>
					) }
				</>,
				{
					type: 'tel',
					placeholder: '+880 1XXX XXXXXX',
					autoComplete: 'tel',
				}
			) }

			{ /* Honeypot: real visitors never see or fill this. */ }
			<div className="gsbf-hp" aria-hidden="true">
				<label htmlFor="gsbf-website">
					{ __( 'Website', 'gembooking-simple-booking-form' ) }
				</label>
				<input
					id="gsbf-website"
					ref={ honeypotRef }
					type="text"
					tabIndex={ -1 }
					autoComplete="off"
				/>
			</div>

			{ warning && (
				<div className="gsbf-warning" role="note">
					<div>{ warning }</div>
					<label className="gsbf-check">
						<input
							type="checkbox"
							checked={ acknowledged }
							onChange={ ( e ) =>
								onAcknowledge( e.target.checked )
							}
						/>
						<span>
							{ __(
								'I understand',
								'gembooking-simple-booking-form'
							) }
						</span>
					</label>
				</div>
			) }

			{ pickError && (
				<div className="gsbf-error" role="alert">
					{ pickError }
				</div>
			) }
			{ submitError && (
				<div className="gsbf-alert" role="alert">
					{ submitError }
				</div>
			) }

			<div className="gsbf-actions">
				<button
					type="button"
					className="gsbf-btn-secondary"
					onClick={ onBack }
				>
					{ __( 'Back', 'gembooking-simple-booking-form' ) }
				</button>
				<button
					type="button"
					className="gsbf-btn-primary"
					disabled={ saving || ( warning && ! acknowledged ) }
					onClick={ onSubmit }
				>
					{ saving
						? __( 'Saving…', 'gembooking-simple-booking-form' )
						: submitLabel }
				</button>
			</div>
		</div>
	);
}
