import { __ } from '@wordpress/i18n';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import FooterEditor from './FooterEditor';

/**
 * One settings row: title and description on the left, the control on the right.
 */
function Row( { title, desc, children } ) {
	return (
		<div className="gsbf-setting">
			<div className="gsbf-setting-text">
				<div className="gsbf-setting-title">{ title }</div>
				{ desc && <div className="gsbf-setting-desc">{ desc }</div> }
			</div>
			<div className="gsbf-setting-control">{ children }</div>
		</div>
	);
}

/**
 * Settings tab of the email modal: sender, admin address, approval mode,
 * content type, logo, brand colour and footer.
 */
export default function SettingsTab( { settings, update, accent, setAccent } ) {
	const onHex = ( value ) => {
		let next = value.trim();
		if ( next[ 0 ] !== '#' ) {
			next = `#${ next }`;
		}
		if ( /^#[0-9a-fA-F]{6}$/.test( next ) ) {
			setAccent( next.toUpperCase() );
		}
	};

	return (
		<div className="gsbf-mail-settings">
			<section className="gsbf-card">
				<h3>{ __( 'Email', 'gembooking-simple-booking-form' ) }</h3>
				<Row
					title={ __( 'Name', 'gembooking-simple-booking-form' ) }
					desc={ __(
						'The common name for all the outgoing emails.',
						'gembooking-simple-booking-form'
					) }
				>
					<input
						className="gsbf-sb-input is-lg"
						aria-label={ __(
							'Name',
							'gembooking-simple-booking-form'
						) }
						value={ settings.fromName }
						onChange={ ( e ) =>
							update( { fromName: e.target.value } )
						}
					/>
				</Row>
				<Row
					title={ __(
						'Email Address',
						'gembooking-simple-booking-form'
					) }
					desc={ __(
						'All emails will be sent from this email address.',
						'gembooking-simple-booking-form'
					) }
				>
					<input
						className="gsbf-sb-input is-lg"
						type="email"
						aria-label={ __(
							'Email Address',
							'gembooking-simple-booking-form'
						) }
						value={ settings.fromEmail }
						onChange={ ( e ) =>
							update( { fromEmail: e.target.value } )
						}
					/>
				</Row>
				<Row
					title={ __(
						'Send admin emails to',
						'gembooking-simple-booking-form'
					) }
					desc={ __(
						'The address that receives new booking requests.',
						'gembooking-simple-booking-form'
					) }
				>
					<input
						className="gsbf-sb-input is-lg"
						type="email"
						aria-label={ __(
							'Send admin emails to',
							'gembooking-simple-booking-form'
						) }
						value={ settings.adminTo }
						onChange={ ( e ) =>
							update( { adminTo: e.target.value } )
						}
					/>
				</Row>
				<Row
					title={ __( 'Approval', 'gembooking-simple-booking-form' ) }
					desc={
						settings.approval === 'manual'
							? __(
									'Each booking is saved as Pending. The customer is told it is pending right away, and gets the confirmation after you click Confirm in the admin email.',
									'gembooking-simple-booking-form'
								)
							: __(
									'Bookings are confirmed instantly. The customer gets the confirmation email right away.',
									'gembooking-simple-booking-form'
								)
					}
				>
					<select
						className="gsbf-sb-input is-lg is-select"
						aria-label={ __(
							'Approval',
							'gembooking-simple-booking-form'
						) }
						value={ settings.approval }
						onChange={ ( e ) =>
							update( { approval: e.target.value } )
						}
					>
						<option value="manual">
							{ __(
								'Admin confirms by email',
								'gembooking-simple-booking-form'
							) }
						</option>
						<option value="auto">
							{ __(
								'Confirm automatically',
								'gembooking-simple-booking-form'
							) }
						</option>
					</select>
				</Row>
			</section>

			<section className="gsbf-card">
				<h3>
					{ __( 'Email Template', 'gembooking-simple-booking-form' ) }
				</h3>
				<Row
					title={ __(
						'Content Type',
						'gembooking-simple-booking-form'
					) }
					desc={ __(
						'HTML sends the designed layout. Plain text sends simple text.',
						'gembooking-simple-booking-form'
					) }
				>
					<select
						className="gsbf-sb-input is-lg is-select"
						aria-label={ __(
							'Content Type',
							'gembooking-simple-booking-form'
						) }
						value={ settings.type }
						onChange={ ( e ) => update( { type: e.target.value } ) }
					>
						<option value="html">HTML</option>
						<option value="plain">
							{ __(
								'Plain text',
								'gembooking-simple-booking-form'
							) }
						</option>
					</select>
				</Row>
				<Row
					title={ __( 'Logo', 'gembooking-simple-booking-form' ) }
					desc={ __(
						'Shown in the branded email header.',
						'gembooking-simple-booking-form'
					) }
				>
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
									<button
										type="button"
										className="gsbf-outline-btn is-inline"
										onClick={ open }
									>
										{ __(
											'Upload',
											'gembooking-simple-booking-form'
										) }
									</button>
								) }
							/>
						</MediaUploadCheck>
						{ settings.logo ? (
							<button
								type="button"
								className="gsbf-danger-btn"
								onClick={ () =>
									update( { logo: 0, logoUrl: '' } )
								}
							>
								{ __(
									'Remove',
									'gembooking-simple-booking-form'
								) }
							</button>
						) : null }
					</div>
				</Row>
				<Row
					title={ __(
						'Brand Color',
						'gembooking-simple-booking-form'
					) }
					desc={ __(
						"Used for the email header band and buttons. It is this form's accent color.",
						'gembooking-simple-booking-form'
					) }
				>
					<div className="gsbf-brand">
						{ /* eslint-disable-next-line jsx-a11y/label-has-associated-control -- the colour input is nested in the label */ }
						<label className="gsbf-brand-swatch">
							<span style={ { background: accent } } />
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
						</label>
						<input
							key={ accent }
							className="gsbf-sb-input is-lg is-hex-lg"
							defaultValue={ accent }
							maxLength={ 7 }
							aria-label={ __(
								'Brand color hex',
								'gembooking-simple-booking-form'
							) }
							onChange={ ( e ) => onHex( e.target.value ) }
						/>
					</div>
				</Row>
				<div className="gsbf-setting is-block">
					<div className="gsbf-setting-title">
						{ __(
							'Footer Text',
							'gembooking-simple-booking-form'
						) }
					</div>
					<FooterEditor
						value={ settings.footerHtml }
						onChange={ ( footerHtml ) => update( { footerHtml } ) }
					/>
				</div>
			</section>
		</div>
	);
}
