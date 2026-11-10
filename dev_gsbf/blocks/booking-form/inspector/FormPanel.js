import { __ } from '@wordpress/i18n';
import { Switch, TextAreaInput } from '../../../components/ui';

export default function FormPanel( { attributes, setAttributes } ) {
	return (
		<>
			<p className="gsbf-sb-hint is-first">
				{ __(
					'Name and Email are always required.',
					'gembooking-simple-booking-form'
				) }
			</p>
			<Switch
				label={ __(
					'Require phone number',
					'gembooking-simple-booking-form'
				) }
				checked={ attributes.phoneRequired }
				onChange={ ( phoneRequired ) =>
					setAttributes( { phoneRequired } )
				}
			/>
			<TextAreaInput
				label={ __(
					'Confirmation message',
					'gembooking-simple-booking-form'
				) }
				rows={ 3 }
				value={ attributes.message }
				onChange={ ( e ) =>
					setAttributes( { message: e.target.value } )
				}
			/>
		</>
	);
}
