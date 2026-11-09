/**
 * Front-end entry: turns every server-rendered mount into a live booking form.
 */

import { createRoot } from '@wordpress/element';
import BookingWidget from './components/BookingWidget';
import { createRestApi } from './utils/api';
import { normalizeConfig } from './utils/config';
import { readEntry } from './utils/url-params';
import './styles/widget.scss';

function mount( el ) {
	if ( el.dataset.gsbfMounted ) {
		return;
	}

	let signed;
	try {
		signed = JSON.parse( el.dataset.gsbfConfig );
	} catch {
		return;
	}

	el.dataset.gsbfMounted = '1';

	const config = normalizeConfig( signed.c );
	const api = createRestApi( {
		payload: el.dataset.gsbfConfig,
		sig: el.dataset.gsbfSig,
	} );

	createRoot( el ).render(
		<BookingWidget config={ config } api={ api } entry={ readEntry() } />
	);
}

function init() {
	document
		.querySelectorAll( '.gsbf-form[data-gsbf-config]' )
		.forEach( mount );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
