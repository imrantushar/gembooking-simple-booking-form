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
 * The `gsbf-inspector` wrapper scopes the design styles and the black
 * primary color (styles/editor.scss).
 */
export default function Inspector( { attributes, setAttributes } ) {
	const props = { attributes, setAttributes };

	return (
		<div className="gsbf-inspector">
			<PanelBody
				title={ __( 'Booking', 'gembooking-simple-booking-form' ) }
				initialOpen
			>
				<div className="gsbf-panel-body">
					<BookingPanel { ...props } />
				</div>
			</PanelBody>
			<PanelBody
				title={ __( 'Availability', 'gembooking-simple-booking-form' ) }
				initialOpen={ false }
			>
				<div className="gsbf-panel-body">
					<AvailabilityPanel { ...props } />
				</div>
			</PanelBody>
			<PanelBody
				title={ __(
					'Form and messages',
					'gembooking-simple-booking-form'
				) }
				initialOpen={ false }
			>
				<div className="gsbf-panel-body">
					<FormPanel { ...props } />
				</div>
			</PanelBody>
			<PanelBody
				title={ __( 'Style', 'gembooking-simple-booking-form' ) }
				initialOpen={ false }
			>
				<div className="gsbf-panel-body">
					<StylePanel { ...props } />
				</div>
			</PanelBody>
			<PanelBody
				title={ __(
					'Email notifications',
					'gembooking-simple-booking-form'
				) }
				initialOpen={ false }
			>
				<div className="gsbf-panel-body">
					<EmailPanel { ...props } />
				</div>
			</PanelBody>
		</div>
	);
}
