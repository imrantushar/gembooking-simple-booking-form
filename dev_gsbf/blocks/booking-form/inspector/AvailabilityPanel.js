import { __, _n, sprintf } from '@wordpress/i18n';
import { Chips, SelectInput } from '../../../components/ui';
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
				value={ attributes.buffer }
				options={ [ 0, 5, 10, 15 ].map( ( v ) => ( {
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
				} ) ) }
				onChange={ ( value ) =>
					setAttributes( { buffer: Number( value ) } )
				}
			/>
			<SelectInput
				label={ __( 'Book up to', 'gembooking-simple-booking-form' ) }
				value={ attributes.maxAdvance }
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
				] }
				onChange={ ( value ) =>
					setAttributes( { maxAdvance: Number( value ) } )
				}
			/>
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
