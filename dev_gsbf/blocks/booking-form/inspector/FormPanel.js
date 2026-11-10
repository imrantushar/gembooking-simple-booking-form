import { __ } from '@wordpress/i18n';
import { Switch } from '../../../components/ui';

export default function FormPanel( { attributes, setAttributes } ) {
	return (
		<Switch
			label={ __(
				'Require phone number',
				'gembooking-simple-booking-form'
			) }
			checked={ attributes.phoneRequired }
			onChange={ ( phoneRequired ) => setAttributes( { phoneRequired } ) }
		/>
	);
}
