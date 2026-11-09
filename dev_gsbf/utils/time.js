/**
 * Date/time helpers. All "local" values are in the form's IANA timezone.
 */

const pad = ( n ) => String( n ).padStart( 2, '0' );

/** Y-m-d / minutes-after-midnight of a Unix timestamp (seconds) in a timezone. */
export function partsInZone( ts, timeZone ) {
	const fmt = new Intl.DateTimeFormat( 'en-CA', {
		timeZone,
		year: 'numeric',
		month: '2-digit',
		day: '2-digit',
		hour: '2-digit',
		minute: '2-digit',
		hourCycle: 'h23',
	} );
	const p = {};
	fmt.formatToParts( new Date( ts * 1000 ) ).forEach( ( part ) => {
		p[ part.type ] = part.value;
	} );
	return {
		date: `${ p.year }-${ p.month }-${ p.day }`,
		minutes: parseInt( p.hour, 10 ) * 60 + parseInt( p.minute, 10 ),
	};
}

/** Today's calendar date (Y-m-d) in a timezone. */
export function todayInZone( timeZone ) {
	return partsInZone( Math.floor( Date.now() / 1000 ), timeZone ).date;
}

export function parseDate( date ) {
	const [ y, m, d ] = date.split( '-' ).map( Number );
	return { y, m, d };
}

/** A UTC midnight Date for a Y-m-d string (only used for calendar maths/labels). */
export function dateObj( date ) {
	const { y, m, d } = parseDate( date );
	return new Date( Date.UTC( y, m - 1, d ) );
}

export function addDays( date, days ) {
	const o = dateObj( date );
	o.setUTCDate( o.getUTCDate() + days );
	return o.toISOString().slice( 0, 10 );
}

export function diffDays( from, to ) {
	return Math.round( ( dateObj( to ) - dateObj( from ) ) / 864e5 );
}

/** Y-m of today plus an offset in months. */
export function monthWithOffset( today, offset ) {
	const { y, m } = parseDate( today );
	const o = new Date( Date.UTC( y, m - 1 + offset, 1 ) );
	return `${ o.getUTCFullYear() }-${ pad( o.getUTCMonth() + 1 ) }`;
}

/** Whole months between today's month and the month of a date. */
export function monthOffset( today, date ) {
	const a = parseDate( today );
	const b = parseDate( date );
	return ( b.y - a.y ) * 12 + ( b.m - a.m );
}

export function monthLabel( month ) {
	return new Date( `${ month }-01T00:00:00Z` ).toLocaleDateString(
		undefined,
		{
			timeZone: 'UTC',
			month: 'long',
			year: 'numeric',
		}
	);
}

/** Weekday of the 1st (0 = Sunday) and number of days of a Y-m month. */
export function monthShape( month ) {
	const { y, m } = parseDate( `${ month }-01` );
	return {
		lead: new Date( Date.UTC( y, m - 1, 1 ) ).getUTCDay(),
		days: new Date( Date.UTC( y, m, 0 ) ).getUTCDate(),
	};
}

export function weekdayShort( index ) {
	// 2023-01-01 was a Sunday.
	return new Date( Date.UTC( 2023, 0, 1 + index ) )
		.toLocaleDateString( undefined, { timeZone: 'UTC', weekday: 'short' } )
		.slice( 0, 2 );
}

/** 540 => "9:00 AM" (or "09:00" in 24 h). */
export function formatMinutes( minutes, h24 ) {
	const h = Math.floor( minutes / 60 ) % 24;
	const m = minutes % 60;
	if ( h24 ) {
		return `${ pad( h ) }:${ pad( m ) }`;
	}
	return `${ h % 12 || 12 }:${ pad( m ) } ${ h < 12 ? 'AM' : 'PM' }`;
}

export function formatRange( startMinutes, duration, h24 ) {
	return `${ formatMinutes( startMinutes, h24 ) } – ${ formatMinutes(
		startMinutes + duration,
		h24
	) }`;
}

/** "Wed, Nov 11" */
export function formatDateShort( date ) {
	return dateObj( date ).toLocaleDateString( undefined, {
		timeZone: 'UTC',
		weekday: 'short',
		month: 'short',
		day: 'numeric',
	} );
}

/** "Wednesday, November 11, 2026" */
export function formatDateLong( date ) {
	return dateObj( date ).toLocaleDateString( undefined, {
		timeZone: 'UTC',
		weekday: 'long',
		year: 'numeric',
		month: 'long',
		day: 'numeric',
	} );
}

/** "Asia/Dhaka (GMT+6)" */
export function timezoneLabel( timeZone ) {
	try {
		const part = new Intl.DateTimeFormat( 'en', {
			timeZone,
			timeZoneName: 'shortOffset',
		} )
			.formatToParts( new Date() )
			.find( ( p ) => p.type === 'timeZoneName' );
		return part ? `${ timeZone } (${ part.value })` : timeZone;
	} catch {
		return timeZone;
	}
}
