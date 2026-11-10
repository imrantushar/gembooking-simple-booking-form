import { useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Chips, NumberInput, SelectInput } from '../../../components/ui';
import { ADVANCE_PRESETS, BUFFER_PRESETS, LIMITS } from '../../../utils/config';
import { formatMinutes, timezoneLabel } from '../../../utils/time';

// Monday first, like the design.
const DAY_ORDER = [ 1, 2, 3, 4, 5, 6, 0 ];

const dayName = ( day ) =>
	new Date( Date.UTC( 2023, 0, 1 + day ) ).toLocaleDateString( undefined, {
		timeZone: 'UTC',
		weekday: 'short',
	} );

const hourOptions = ( from, to ) => {
	const options = [];
	for ( let h = from; h <= to; h++ ) {
		options.push( { value: h, label: formatMinutes( h * 60, false ) } );
	}
	return options;
};

const days = ( n ) =>
	sprintf(
		/* translators: %d: number of days */
		_n( '%d day', '%d days', n, 'gembooking-simple-booking-form' ),
		n
	);

export default function AvailabilityPanel( { attributes, setAttributes } ) {
	const zones = ( window.GsbfGlobal && window.GsbfGlobal.timezones ) || [
		attributes.timezone,
	];

	// "Custom" is shown when chosen, or when the saved value is not one of the presets.
	const [ bufferCustom, setBufferCustom ] = useState( false );
	const [ advanceCustom, setAdvanceCustom ] = useState( false );
	const bufferIsCustom =
		bufferCustom || ! BUFFER_PRESETS.includes( attributes.buffer );
	const advanceIsCustom =
		advanceCustom || ! ADVANCE_PRESETS.includes( attributes.maxAdvance );

	const toggleDay = ( day ) => {
		const next = attributes.days.includes( day )
			? attributes.days.filter( ( d ) => d !== day )
			: [ ...attributes.days, day ];
		setAttributes( { days: next.sort() } );
	};

	return (
		<>
			<Chips
				label={ __(
					'Available days',
					'gembooking-simple-booking-form'
				) }
				options={ DAY_ORDER.map( ( day ) => ( {
					value: day,
					label: dayName( day ),
				} ) ) }
				isOn={ ( day ) => attributes.days.includes( day ) }
				onToggle={ toggleDay }
			/>

			<div className="gsbf-two">
				<SelectInput
					label={ __( 'From', 'gembooking-simple-booking-form' ) }
					value={ attributes.startHour }
					options={ hourOptions(
						0,
						Math.max( 0, attributes.endHour - 1 )
					) }
					onChange={ ( value ) =>
						setAttributes( { startHour: Number( value ) } )
					}
				/>
				<SelectInput
					label={ __( 'To', 'gembooking-simple-booking-form' ) }
					value={ attributes.endHour }
					options={ hourOptions( attributes.startHour + 1, 24 ) }
					onChange={ ( value ) =>
						setAttributes( { endHour: Number( value ) } )
					}
				/>
			</div>

			<SelectInput
				label={ __(
					'Minimum notice',
					'gembooking-simple-booking-form'
				) }
				value={ attributes.notice }
				options={ [
					{
						value: 0,
						label: __( 'None', 'gembooking-simple-booking-form' ),
					},
					{ value: 1, label: days( 1 ) },
					{ value: 2, label: days( 2 ) },
				] }
				onChange={ ( value ) =>
					setAttributes( { notice: Number( value ) } )
				}
			/>
			<SelectInput
				label={ __(
					'Buffer between bookings',
					'gembooking-simple-booking-form'
				) }
				value={ bufferIsCustom ? 'custom' : attributes.buffer }
				options={ [
					...BUFFER_PRESETS.map( ( v ) => ( {
						value: v,
						label: v
							? sprintf(
									/* translators: %d: minutes */
									__(
										'%d min',
										'gembooking-simple-booking-form'
									),
									v
								)
							: __( 'None', 'gembooking-simple-booking-form' ),
					} ) ),
					{
						value: 'custom',
						label: __( 'Custom', 'gembooking-simple-booking-form' ),
					},
				] }
				onChange={ ( value ) => {
					if ( value === 'custom' ) {
						setBufferCustom( true );
						return;
					}
					setBufferCustom( false );
					setAttributes( { buffer: Number( value ) } );
				} }
			/>
			{ bufferIsCustom && (
				<NumberInput
					label={ __(
						'Custom buffer (minutes)',
						'gembooking-simple-booking-form'
					) }
					hint={ __(
						'Whole minutes, from 1 to 240.',
						'gembooking-simple-booking-form'
					) }
					min={ LIMITS.buffer[ 0 ] }
					max={ LIMITS.buffer[ 1 ] }
					value={ attributes.buffer }
					onChange={ ( buffer ) => setAttributes( { buffer } ) }
				/>
			) }
			<SelectInput
				label={ __( 'Book up to', 'gembooking-simple-booking-form' ) }
				value={ advanceIsCustom ? 'custom' : attributes.maxAdvance }
				options={ [
					{
						value: 14,
						label: __(
							'2 weeks',
							'gembooking-simple-booking-form'
						),
					},
					{ value: 30, label: days( 30 ) },
					{ value: 60, label: days( 60 ) },
					{
						value: 'custom',
						label: __( 'Custom', 'gembooking-simple-booking-form' ),
					},
				] }
				onChange={ ( value ) => {
					if ( value === 'custom' ) {
						setAdvanceCustom( true );
						return;
					}
					setAdvanceCustom( false );
					setAttributes( { maxAdvance: Number( value ) } );
				} }
			/>
			{ advanceIsCustom && (
				<NumberInput
					label={ __(
						'Custom days ahead',
						'gembooking-simple-booking-form'
					) }
					hint={ __(
						'Whole days, from 1 to 365.',
						'gembooking-simple-booking-form'
					) }
					min={ LIMITS.maxAdvance[ 0 ] }
					max={ LIMITS.maxAdvance[ 1 ] }
					value={ attributes.maxAdvance }
					onChange={ ( maxAdvance ) =>
						setAttributes( { maxAdvance } )
					}
				/>
			) }
			<SelectInput
				label={ __( 'Timezone', 'gembooking-simple-booking-form' ) }
				value={ attributes.timezone }
				options={ zones.map( ( zone ) => ( {
					value: zone,
					label: timezoneLabel( zone ),
				} ) ) }
				onChange={ ( timezone ) => setAttributes( { timezone } ) }
			/>
		</>
	);
}
