import { __ } from '@wordpress/i18n';
import { TextareaControl, ToggleControl } from '@wordpress/components';

export default function FormPanel( { attributes, setAttributes } ) {
	return (
		<>
			<p className="gsbf-help">
				{ __(
					'Name and Email are always required.',
					'gembooking-simple-booking-form'
				) }
			</p>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __(
					'Require phone number',
					'gembooking-simple-booking-form'
				) }
				checked={ attributes.phoneRequired }
				onChange={ ( phoneRequired ) =>
					setAttributes( { phoneRequired } )
				}
			/>
			<TextareaControl
				__nextHasNoMarginBottom
				label={ __(
					'Confirmation message',
					'gembooking-simple-booking-form'
				) }
				value={ attributes.message }
				onChange={ ( message ) => setAttributes( { message } ) }
				rows={ 3 }
			/>
		</>
	);
}
