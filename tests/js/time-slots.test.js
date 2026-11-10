import { describe, expect, it } from 'vitest';
import {
	formatMinutes,
	monthShape,
	addDays,
	diffDays,
} from '../../dev_gsbf/utils/time';
import { isDateOpen, slotsForDate } from '../../dev_gsbf/utils/slots';
import { DEFAULTS } from '../../dev_gsbf/utils/config';

describe( 'time helpers', () => {
	it( 'formats minutes in 12h and 24h', () => {
		expect( formatMinutes( 540, false ) ).toBe( '9:00 AM' );
		expect( formatMinutes( 780, false ) ).toBe( '1:00 PM' );
		expect( formatMinutes( 0, false ) ).toBe( '12:00 AM' );
		expect( formatMinutes( 540, true ) ).toBe( '09:00' );
	} );

	it( 'knows the shape of a month', () => {
		expect( monthShape( '2026-11' ) ).toEqual( { lead: 0, days: 30 } );
		expect( monthShape( '2026-02' ).days ).toBe( 28 );
	} );

	it( 'does calendar day maths', () => {
		expect( addDays( '2026-11-30', 1 ) ).toBe( '2026-12-01' );
		expect( diffDays( '2026-11-08', '2027-01-07' ) ).toBe( 60 );
	} );
} );

describe( 'slot rules (port of SlotCalendar)', () => {
	const config = { ...DEFAULTS, timezone: 'Asia/Dhaka' };

	it( 'respects weekdays, notice and max advance', () => {
		const today = '2026-11-08'; // Sunday
		expect( isDateOpen( config, '2026-11-09', today ) ).toBe( true );
		expect( isDateOpen( config, '2026-11-14', today ) ).toBe( false ); // Saturday
		expect( isDateOpen( config, '2026-11-08', today ) ).toBe( false ); // < 1 day notice
		expect( isDateOpen( config, '2027-01-07', today ) ).toBe( true );
		expect( isDateOpen( config, '2027-01-08', today ) ).toBe( false );
	} );

	it( 'builds 16 half-hour slots for 9-17', () => {
		const date = new Date( Date.now() + 3 * 864e5 );
		const day = new Date( date );
		// Next weekday so the date is open whatever day the test runs on.
		while ( [ 0, 6 ].includes( day.getUTCDay() ) ) {
			day.setUTCDate( day.getUTCDate() + 1 );
		}
		const slots = slotsForDate(
			{ ...config, timezone: 'UTC' },
			day.toISOString().slice( 0, 10 )
		);
		expect( slots ).toHaveLength( 16 );
		expect( slots[ 0 ].minutes ).toBe( 540 );
		expect( slots[ 15 ].minutes ).toBe( 990 );
	} );
} );

describe( 'whole-number input', () => {
	it( 'accepts only digits, so decimals, exponents and signs are rejected', async () => {
		const { isDigits } = await import( '../../dev_gsbf/utils/validate' );
		expect( isDigits( '' ) ).toBe( true );
		expect( isDigits( '20' ) ).toBe( true );
		[ '1.5', '1,5', '1e3', '-5', '+5', '2.0', 'abc', ' 5' ].forEach(
			( bad ) => expect( isDigits( bad ) ).toBe( false )
		);
	} );
} );
