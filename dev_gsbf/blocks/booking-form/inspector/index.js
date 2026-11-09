import { __ } from '@wordpress/i18n';
import { PanelBody } from '@wordpress/components';
import BookingPanel from './BookingPanel';
import AvailabilityPanel from './AvailabilityPanel';
import FormPanel from './FormPanel';
import StylePanel from './StylePanel';
import EmailPanel from './EmailPanel';

/**
 * The block settings sidebar. Panel order follows the design:
 * Booking, Availability, Form and messages, Style, Email notifications.
 */
export default function Inspector( { attributes, setAttributes } ) {
	const props = { attributes, setAttributes };

	return (
		<>
			<PanelBody
				title={ __( 'Booking', 'gembooking-simple-booking-form' ) }
				initialOpen
			>
				<BookingPanel { ...props } />
			</PanelBody>
			<PanelBody
				title={ __( 'Availability', 'gembooking-simple-booking-form' ) }
				initialOpen={ false }
			>
				<AvailabilityPanel { ...props } />
			</PanelBody>
			<PanelBody
				title={ __(
					'Form and messages',
					'gembooking-simple-booking-form'
				) }
				initialOpen={ false }
			>
				<FormPanel { ...props } />
			</PanelBody>
			<PanelBody
				title={ __( 'Style', 'gembooking-simple-booking-form' ) }
				initialOpen={ false }
			>
				<StylePanel { ...props } />
			</PanelBody>
			<PanelBody
				title={ __(
					'Email notifications',
					'gembooking-simple-booking-form'
				) }
				initialOpen={ false }
			>
				<EmailPanel { ...props } />
			</PanelBody>
		</>
	);
}
