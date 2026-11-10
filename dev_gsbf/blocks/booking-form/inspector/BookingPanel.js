import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { DURATION_PRESETS, LIMITS, clamp } from '../../../utils/config';
import { NumberInput, Segmented, TextInput } from '../../../components/ui';

export default function BookingPanel( { attributes, setAttributes } ) {
	const [ customMode, setCustomMode ] = useState( false );
	const isCustom =
		customMode || ! DURATION_PRESETS.includes( attributes.duration );

	const setDuration = ( value ) => {
		setAttributes( {
			duration: clamp( Math.round( Number( value ) / 5 ) * 5, 5, 480 ),
		} );
	};

	return (
		<>
			<TextInput
				label={ __( 'Event title', 'gembooking-simple-booking-form' ) }
				value={ attributes.title }
				onChange={ ( e ) => setAttributes( { title: e.target.value } ) }
			/>

			<Segmented
				label={ __( 'Form layout', 'gembooking-simple-booking-form' ) }
				value={ attributes.layout }
				options={ [
					{
						value: 'step',
						label: __(
							'Step by step',
							'gembooking-simple-booking-form'
						),
					},
					{
						value: 'single',
						label: __(
							'One at a time',
							'gembooking-simple-booking-form'
						),
					},
				] }
				onChange={ ( layout ) => setAttributes( { layout } ) }
			/>

			<Segmented
				className="is-duration"
				label={ __( 'Duration', 'gembooking-simple-booking-form' ) }
				value={ isCustom ? 'custom' : attributes.duration }
				options={ [
					...DURATION_PRESETS.map( ( minutes ) => ( {
						value: minutes,
						label: `${ minutes }m`,
					} ) ),
					{
						value: 'custom',
						label: __( 'Custom', 'gembooking-simple-booking-form' ),
					},
				] }
				onChange={ ( value ) => {
					if ( value === 'custom' ) {
						setCustomMode( true );
						return;
					}
					setCustomMode( false );
					setDuration( value );
				} }
			/>

			{ isCustom && (
				<NumberInput
					label={ __(
						'Custom duration (minutes)',
						'gembooking-simple-booking-form'
					) }
					hint={ __(
						'Whole minutes in steps of 5, from 5 to 480.',
						'gembooking-simple-booking-form'
					) }
					min={ LIMITS.duration[ 0 ] }
					max={ LIMITS.duration[ 1 ] }
					normalize={ ( n ) =>
						clamp(
							Math.round( n / 5 ) * 5,
							LIMITS.duration[ 0 ],
							LIMITS.duration[ 1 ]
						)
					}
					value={ attributes.duration }
					onChange={ ( duration ) => setAttributes( { duration } ) }
				/>
			) }
		</>
	);
}
