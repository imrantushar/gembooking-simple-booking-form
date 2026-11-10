import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { DURATION_PRESETS, clamp } from '../../../utils/config';
import { Segmented, TextInput } from '../../../components/ui';

export default function BookingPanel( { attributes, setAttributes } ) {
	const [ customMode, setCustomMode ] = useState( false );
	const isCustom =
		customMode || ! DURATION_PRESETS.includes( attributes.duration );

	const setDuration = ( value ) => {
		const rounded = Math.round( Number( value ) / 5 ) * 5;
		if ( ! rounded ) {
			return;
		}
		setAttributes( { duration: clamp( rounded, 5, 480 ) } );
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
				<TextInput
					type="number"
					min={ 5 }
					max={ 480 }
					step={ 5 }
					label={ __(
						'Custom duration',
						'gembooking-simple-booking-form'
					) }
					aria-label={ __(
						'Custom duration in minutes',
						'gembooking-simple-booking-form'
					) }
					value={ attributes.duration }
					onChange={ ( e ) => setDuration( e.target.value ) }
				/>
			) }
		</>
	);
}
