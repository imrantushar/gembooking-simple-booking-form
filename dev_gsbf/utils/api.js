/**
 * The widget talks to an "api" object with this shape:
 *
 *   getMonth( 'YYYY-MM' )            -> { days: [ { date, available } ] }
 *   getDay( 'YYYY-MM-DD' )           -> { slots: [ { start, minutes, available } ] }
 *   createBooking( input )           -> booking
 *   getBooking( id, token, action )  -> booking
 *   reschedule( id, token, start )   -> booking
 *
 * createRestApi() is the real thing (front end); createMockApi() backs the
 * editor preview with local data and saves nothing.
 */

import { openDates, slotsForDate } from './slots';

class ApiError extends Error {
	constructor( message, code, status ) {
		super( message );
		this.code = code;
		this.status = status;
	}
}

function routeUrl( path, params ) {
	const base = new URL( window.GsbfGlobal.restUrl, window.location.href );

	if ( base.searchParams.has( 'rest_route' ) ) {
		// Plain permalinks: the route lives in ?rest_route=/gsbf/v1/
		base.searchParams.set(
			'rest_route',
			base.searchParams.get( 'rest_route' ).replace( /\/$/, '' ) +
				'/' +
				path
		);
	} else {
		base.pathname = base.pathname.replace( /\/$/, '' ) + '/' + path;
	}
	Object.entries( params || {} ).forEach( ( [ key, value ] ) =>
		base.searchParams.set( key, value )
	);
	return base.toString();
}

async function request( url, options ) {
	let response;
	try {
		response = await window.fetch( url, {
			credentials: 'same-origin',
			...options,
		} );
	} catch {
		throw new ApiError( 'network', 'network', 0 );
	}
	const data = await response.json().catch( () => ( {} ) );
	if ( ! response.ok ) {
		throw new ApiError(
			data.message || 'error',
			data.code || 'error',
			response.status
		);
	}
	return data;
}

export function createRestApi( { payload, sig } ) {
	const signed = { config: payload, sig };
	const post = ( path, body ) =>
		request( routeUrl( path ), {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( { ...signed, ...body } ),
		} );

	return {
		getMonth: ( month ) =>
			request( routeUrl( 'availability', { ...signed, month } ) ),
		getDay: ( date ) =>
			request( routeUrl( 'availability', { ...signed, date } ) ),
		createBooking: ( input ) => post( 'bookings', input ),
		getBooking: ( id, token, action ) =>
			request( routeUrl( `bookings/${ id }`, { token, action } ) ),
		reschedule: ( id, token, start ) =>
			post( `bookings/${ id }/reschedule`, { token, start } ),
	};
}

const wait = ( ms ) => new Promise( ( resolve ) => setTimeout( resolve, ms ) );

export function createMockApi( config, approval = 'manual' ) {
	return {
		getMonth: async ( month ) => {
			await wait( 150 );
			return {
				days: openDates( config, month ).map( ( date ) => ( {
					date,
					available: true,
				} ) ),
			};
		},
		getDay: async ( date ) => {
			await wait( 150 );
			return { slots: slotsForDate( config, date ) };
		},
		createBooking: async ( input ) => {
			await wait( 700 );
			return {
				ref: 'GB-PREVIEW',
				status: approval === 'auto' ? 'confirmed' : 'pending',
				title: config.title,
				timezone: config.timezone,
				duration: config.duration,
				start: input.start,
				end: input.start + config.duration * 60,
				name: input.name,
				email: input.email,
				phone: input.phone,
			};
		},
		getBooking: async () => {
			throw new ApiError( 'preview', 'preview', 400 );
		},
		reschedule: async () => {
			throw new ApiError( 'preview', 'preview', 400 );
		},
	};
}

export { ApiError };
