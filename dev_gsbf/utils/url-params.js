/**
 * Entry mode from the email links: ?gsbf_action=view|reschedule&gsbf_booking=ID&gsbf_token=...
 */

export function readEntry() {
	const params = new URLSearchParams( window.location.search );
	const action = params.get( 'gsbf_action' );
	const id = parseInt( params.get( 'gsbf_booking' ) || '0', 10 );
	const token = params.get( 'gsbf_token' ) || '';

	if ( ( action === 'view' || action === 'reschedule' ) && id && token ) {
		return { action, id, token };
	}
	return null;
}
