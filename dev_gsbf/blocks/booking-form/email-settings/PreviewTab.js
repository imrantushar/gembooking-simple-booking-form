import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Read-only preview of the admin or customer email, rendered by the server
 * from the (unsaved) settings so it matches what is really sent.
 */
export default function PreviewTab( { kind, settings, accent } ) {
	const [ html, setHtml ] = useState( '' );
	const [ failed, setFailed ] = useState( false );

	const { logo, logoUrl, ...rest } = settings;
	const key = JSON.stringify( [ kind, rest, logo, accent ] );

	useEffect( () => {
		let cancelled = false;
		const timer = setTimeout( () => {
			apiFetch( {
				path: '/gsbf/v1/email-settings/preview',
				method: 'POST',
				data: { kind, settings: { ...rest, logo }, accent },
			} )
				.then( ( data ) => {
					if ( ! cancelled ) {
						setFailed( false );
						setHtml( data.html );
					}
				} )
				.catch( () => ! cancelled && setFailed( true ) );
		}, 250 );

		return () => {
			cancelled = true;
			clearTimeout( timer );
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ key ] );

	return (
		<div className="gsbf-mail-preview">
			<p className="gsbf-help">
				{ __(
					'Preview with sample data. The real email fills in the booking details.',
					'gembooking-simple-booking-form'
				) }
			</p>
			{ failed ? (
				<p className="gsbf-help">
					{ __(
						'The preview could not be loaded.',
						'gembooking-simple-booking-form'
					) }
				</p>
			) : (
				<iframe
					className="gsbf-mail-frame"
					title={ __(
						'Email preview',
						'gembooking-simple-booking-form'
					) }
					sandbox=""
					srcDoc={ html }
				/>
			) }
		</div>
	);
}
