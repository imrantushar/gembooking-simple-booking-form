/**
 * Block attribute defaults and limits. Mirrors includes/classes/config.php.
 */

export const DEFAULTS = {
	formId: '',
	title: '30-minute consultation',
	description: 'A short call to talk through your project and next steps.',
	layout: 'step',
	duration: 30,
	days: [ 1, 2, 3, 4, 5 ],
	startHour: 9,
	endHour: 17,
	notice: 1,
	buffer: 0,
	maxAdvance: 60,
	timezone: 'UTC',
	accent: '#141A24',
	phoneRequired: false,
	message:
		'A confirmation email is on its way. We look forward to speaking with you.',
};

export const ACCENT_SWATCHES = [
	[ 'Black', '#141A24' ],
	[ 'Blue', '#006BFF' ],
	[ 'Violet', '#5B21B6' ],
	[ 'Green', '#166534' ],
];

export const DURATION_PRESETS = [ 15, 30, 45, 60 ];

export function uuid() {
	if ( window.crypto && window.crypto.randomUUID ) {
		return window.crypto.randomUUID();
	}
	// Fallback for non-secure contexts: random hex groups in a v4-looking shape.
	const hex = ( n ) =>
		Array.from( { length: n }, () =>
			Math.floor( Math.random() * 16 ).toString( 16 )
		).join( '' );
	return `${ hex( 8 ) }-${ hex( 4 ) }-4${ hex( 3 ) }-a${ hex( 3 ) }-${ hex(
		12
	) }`;
}

/** Block attributes merged over the defaults (empty strings fall back too). */
export function normalizeConfig( attributes ) {
	const config = { ...DEFAULTS, ...attributes };
	if ( ! config.timezone ) {
		config.timezone =
			( window.GsbfGlobal && window.GsbfGlobal.siteTimezone ) ||
			DEFAULTS.timezone;
	}
	return config;
}

export const clamp = ( value, min, max ) =>
	Math.min( max, Math.max( min, value ) );
