/**
 * Browser port of includes/classes/slot-calendar.php, used only by the editor
 * preview (the front end always asks the server). Keep the rules in step.
 *
 * Start timestamps here are pseudo values (the date and time treated as UTC);
 * the preview never sends them anywhere.
 */

import { addDays, dateObj, diffDays, monthShape, todayInZone } from './time';

export function isDateOpen( config, date, today ) {
	const diff = diffDays( today, date );
	return (
		diff >= config.notice &&
		diff <= config.maxAdvance &&
		config.days.includes( dateObj( date ).getUTCDay() )
	);
}

export function openDates( config, month ) {
	const today = todayInZone( config.timezone );
	const { days } = monthShape( month );
	const out = [];
	for ( let d = 1; d <= days; d++ ) {
		const date = `${ month }-${ String( d ).padStart( 2, '0' ) }`;
		if ( isDateOpen( config, date, today ) ) {
			out.push( date );
		}
	}
	return out;
}

export function slotsForDate( config, date ) {
	const today = todayInZone( config.timezone );
	if ( ! isDateOpen( config, date, today ) ) {
		return [];
	}
	const step = config.duration + config.buffer;
	const base = Math.floor( dateObj( date ).getTime() / 1000 );
	const slots = [];
	for (
		let m = config.startHour * 60;
		m + config.duration <= config.endHour * 60;
		m += step
	) {
		slots.push( { start: base + m * 60, minutes: m, available: true } );
	}
	return slots;
}

export { addDays };
