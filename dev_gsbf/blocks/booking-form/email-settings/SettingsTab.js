/* eslint-disable jsx-a11y/label-has-associated-control -- the colour input is nested inside the label */
import { __ } from '@wordpress/i18n';
import { Button, SelectControl, TextControl } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import FooterEditor from './FooterEditor';

/**
 * Settings tab of the email modal: sender, admin address, approval mode,
 * content type, logo, brand colour and footer.
 */
export default function SettingsTab( { settings, update, accent, setAccent } ) {
	return (
		<div className="gsbf-settings">
			<h3>{ __( 'Email', 'gembooking-simple-booking-form' ) }</h3>
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Name', 'gembooking-simple-booking-form' ) }
				help={ __(
					'The common name for all the outgoing emails.',
					'gembooking-simple-booking-form'
				) }
				value={ settings.fromName }
				onChange={ ( fromName ) => update( { fromName } ) }
			/>
			<TextControl
				__nextHasNoMarginBottom
				type="email"
				label={ __(
					'Email Address',
					'gembooking-simple-booking-form'
				) }
				help={ __(
					'All emails will be sent from this email address.',
					'gembooking-simple-booking-form'
				) }
				value={ settings.fromEmail }
				onChange={ ( fromEmail ) => update( { fromEmail } ) }
			/>
			<TextControl
				__nextHasNoMarginBottom
				type="email"
				label={ __(
					'Send admin emails to',
					'gembooking-simple-booking-form'
				) }
				help={ __(
					'The address that receives new booking requests.',
					'gembooking-simple-booking-form'
				) }
				value={ settings.adminTo }
				onChange={ ( adminTo ) => update( { adminTo } ) }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Approval', 'gembooking-simple-booking-form' ) }
				help={
					settings.approval === 'manual'
						? __(
								'Each booking is saved as Pending. The customer is emailed only after you click Confirm in the admin email.',
								'gembooking-simple-booking-form'
							)
						: __(
								'Bookings are confirmed instantly. The customer gets the confirmation email right away.',
								'gembooking-simple-booking-form'
							)
				}
				value={ settings.approval }
				options={ [
					{
						value: 'manual',
						label: __(
							'Admin confirms by email',
							'gembooking-simple-booking-form'
						),
					},
					{
						value: 'auto',
						label: __(
							'Confirm automatically',
							'gembooking-simple-booking-form'
						),
					},
				] }
				onChange={ ( approval ) => update( { approval } ) }
			/>

			<h3>
				{ __( 'Email Template', 'gembooking-simple-booking-form' ) }
			</h3>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Content Type', 'gembooking-simple-booking-form' ) }
				help={ __(
					'HTML sends the designed layout. Plain text sends simple text.',
					'gembooking-simple-booking-form'
				) }
				value={ settings.type }
				options={ [
					{ value: 'html', label: 'HTML' },
					{
						value: 'plain',
						label: __(
							'Plain text',
							'gembooking-simple-booking-form'
						),
					},
				] }
				onChange={ ( type ) => update( { type } ) }
			/>

			<div className="gsbf-field">
				<span className="gsbf-field-label">
					{ __( 'Logo', 'gembooking-simple-booking-form' ) }
				</span>
				<p className="gsbf-help">
					{ __(
						'Shown in the branded email header.',
						'gembooking-simple-booking-form'
					) }
				</p>
				<div className="gsbf-logo">
					<div className="gsbf-logo-box">
						{ settings.logoUrl ? (
							<img src={ settings.logoUrl } alt="" />
						) : (
							<span>
								{ __(
									'Logo',
									'gembooking-simple-booking-form'
								) }
							</span>
						) }
					</div>
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ 'image' ] }
							value={ settings.logo }
							onSelect={ ( media ) =>
								update( {
									logo: media.id,
									logoUrl:
										( media.sizes &&
											media.sizes.thumbnail &&
											media.sizes.thumbnail.url ) ||
										media.url,
								} )
							}
							render={ ( { open } ) => (
								<Button variant="secondary" onClick={ open }>
									{ __(
										'Upload',
										'gembooking-simple-booking-form'
									) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
					{ settings.logo ? (
						<Button
							variant="tertiary"
							isDestructive
							onClick={ () => update( { logo: 0, logoUrl: '' } ) }
						>
							{ __( 'Remove', 'gembooking-simple-booking-form' ) }
						</Button>
					) : null }
				</div>
			</div>

			<div className="gsbf-field">
				<span className="gsbf-field-label">
					{ __( 'Brand Color', 'gembooking-simple-booking-form' ) }
				</span>
				<p className="gsbf-help">
					{ __(
						"Used for the email header band and buttons. It is this form's accent color.",
						'gembooking-simple-booking-form'
					) }
				</p>
				<label className="gsbf-brand">
					<input
						type="color"
						value={ accent.toLowerCase() }
						onChange={ ( e ) =>
							setAccent( e.target.value.toUpperCase() )
						}
						aria-label={ __(
							'Brand color',
							'gembooking-simple-booking-form'
						) }
					/>
					<code>{ accent }</code>
				</label>
			</div>

			<div className="gsbf-field">
				<span className="gsbf-field-label">
					{ __( 'Footer Text', 'gembooking-simple-booking-form' ) }
				</span>
				<FooterEditor
					value={ settings.footerHtml }
					onChange={ ( footerHtml ) => update( { footerHtml } ) }
				/>
			</div>
		</div>
	);
}
