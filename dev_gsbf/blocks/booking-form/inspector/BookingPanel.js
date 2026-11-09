import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, TextControl, TextareaControl } from '@wordpress/components';
import { DURATION_PRESETS, clamp } from '../../../utils/config';

export default function BookingPanel( { attributes, setAttributes } ) {
	const isCustomValue = ! DURATION_PRESETS.includes( attributes.duration );
	const [ customMode, setCustomMode ] = useState( false );
	const custom = customMode || isCustomValue;

	const setDuration = ( value ) => {
		const rounded = Math.round( Number( value ) / 5 ) * 5;
		if ( ! rounded ) {
			return;
		}
		setAttributes( { duration: clamp( rounded, 5, 480 ) } );
	};

	return (
		<>
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Event title', 'gembooking-simple-booking-form' ) }
				value={ attributes.title }
				onChange={ ( title ) => setAttributes( { title } ) }
			/>
			<TextareaControl
				__nextHasNoMarginBottom
				label={ __( 'Description', 'gembooking-simple-booking-form' ) }
				value={ attributes.description }
				onChange={ ( description ) => setAttributes( { description } ) }
				rows={ 3 }
			/>

			<div className="gsbf-field">
				<span className="gsbf-field-label">
					{ __( 'Form layout', 'gembooking-simple-booking-form' ) }
				</span>
				<div className="gsbf-chips">
					{ [
						[
							'step',
							__(
								'Step by step',
								'gembooking-simple-booking-form'
							),
						],
						[
							'single',
							__(
								'One at a time',
								'gembooking-simple-booking-form'
							),
						],
					].map( ( [ value, label ] ) => (
						<Button
							key={ value }
							variant={
								attributes.layout === value
									? 'primary'
									: 'secondary'
							}
							onClick={ () => setAttributes( { layout: value } ) }
						>
							{ label }
						</Button>
					) ) }
				</div>
			</div>

			<div className="gsbf-field">
				<span className="gsbf-field-label">
					{ __( 'Duration', 'gembooking-simple-booking-form' ) }
				</span>
				<div className="gsbf-chips">
					{ DURATION_PRESETS.map( ( minutes ) => (
						<Button
							key={ minutes }
							variant={
								! custom && attributes.duration === minutes
									? 'primary'
									: 'secondary'
							}
							onClick={ () => {
								setCustomMode( false );
								setDuration( minutes );
							} }
						>
							{ minutes }m
						</Button>
					) ) }
					<Button
						variant={ custom ? 'primary' : 'secondary' }
						onClick={ () => setCustomMode( true ) }
					>
						{ __( 'Custom', 'gembooking-simple-booking-form' ) }
					</Button>
				</div>
			</div>
			{ custom && (
				<TextControl
					__nextHasNoMarginBottom
					type="number"
					min={ 5 }
					max={ 480 }
					step={ 5 }
					label={ __(
						'Custom duration (minutes)',
						'gembooking-simple-booking-form'
					) }
					value={ attributes.duration }
					onChange={ setDuration }
				/>
			) }
		</>
	);
}
