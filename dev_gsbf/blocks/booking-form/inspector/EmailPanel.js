import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import EmailModal from '../email-settings/EmailModal';

export default function EmailPanel( { attributes, setAttributes } ) {
	const [ settings, setSettings ] = useState( null );
	const [ open, setOpen ] = useState( false );

	useEffect( () => {
		apiFetch( { path: '/gsbf/v1/email-settings' } )
			.then( setSettings )
			.catch( () => {} );
	}, [] );

	let summary = __( 'Loading…', 'gembooking-simple-booking-form' );
	if ( settings ) {
		summary = sprintf(
			/* translators: %s: admin email address */
			__( 'Requests go to %s.', 'gembooking-simple-booking-form' ),
			settings.adminTo
		);
		summary +=
			' ' +
			( settings.approval === 'manual'
				? __(
						'You confirm each booking from the email.',
						'gembooking-simple-booking-form'
					)
				: __(
						'Bookings are confirmed automatically.',
						'gembooking-simple-booking-form'
					) );
	}

	return (
		<>
			<p className="gsbf-help">{ summary }</p>
			<Button variant="secondary" onClick={ () => setOpen( true ) }>
				{ __( 'Configure email', 'gembooking-simple-booking-form' ) }
			</Button>
			{ open && (
				<EmailModal
					accent={ attributes.accent }
					setAccent={ ( accent ) => setAttributes( { accent } ) }
					onClose={ () => setOpen( false ) }
					onSaved={ setSettings }
				/>
			) }
		</>
	);
}
