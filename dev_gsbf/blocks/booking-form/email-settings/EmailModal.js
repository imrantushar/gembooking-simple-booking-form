import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Modal } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import SettingsTab from './SettingsTab';
import PreviewTab from './PreviewTab';
import { OutlineButton, PrimaryButton } from '../../../components/ui';

/**
 * "Configure email" modal. Site-wide settings: they are saved to the site, not
 * to the block. Brand colour edits this block's accent attribute.
 */
export default function EmailModal( { accent, setAccent, onClose, onSaved } ) {
	const [ settings, setSettings ] = useState( null );
	const [ tab, setTab ] = useState( 'settings' );
	const [ busy, setBusy ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		apiFetch( { path: '/gsbf/v1/email-settings' } )
			.then( setSettings )
			.catch( ( e ) =>
				setNotice( { status: 'error', text: e.message } )
			);
	}, [] );

	const update = ( patch ) => setSettings( ( s ) => ( { ...s, ...patch } ) );

	const save = async () => {
		const { logoUrl, ...payload } = settings;
		const saved = await apiFetch( {
			path: '/gsbf/v1/email-settings',
			method: 'POST',
			data: { settings: payload },
		} );
		setSettings( saved );
		onSaved( saved );
		return saved;
	};

	const run = async ( task, success ) => {
		setBusy( true );
		setNotice( null );
		try {
			await task();
			if ( success ) {
				setNotice( { status: 'success', text: success } );
			}
		} catch ( e ) {
			setNotice( { status: 'error', text: e.message } );
			setBusy( false );
			return false;
		}
		setBusy( false );
		return true;
	};

	const sendTest = () =>
		run(
			async () => {
				await save();
				await apiFetch( {
					path: '/gsbf/v1/email-settings/test',
					method: 'POST',
					data: { accent },
				} );
			},
			__(
				'Test email sent to the admin address.',
				'gembooking-simple-booking-form'
			)
		);

	const done = async () => {
		if ( await run( save ) ) {
			onClose();
		}
	};

	const tabs = [
		[ 'settings', __( 'Settings', 'gembooking-simple-booking-form' ) ],
		[ 'admin', __( 'Admin email', 'gembooking-simple-booking-form' ) ],
		[
			'customer',
			__( 'Customer email', 'gembooking-simple-booking-form' ),
		],
	];

	let body = (
		<p className="gsbf-modal-loading">
			{ __( 'Loading…', 'gembooking-simple-booking-form' ) }
		</p>
	);
	if ( settings ) {
		body =
			tab === 'settings' ? (
				<SettingsTab
					settings={ settings }
					update={ update }
					accent={ accent }
					setAccent={ setAccent }
				/>
			) : (
				<PreviewTab
					kind={ tab }
					settings={ settings }
					accent={ accent }
				/>
			);
	}

	return (
		<Modal
			title={ __(
				'Email notifications',
				'gembooking-simple-booking-form'
			) }
			onRequestClose={ onClose }
			className="gsbf-email-modal"
		>
			<div className="gsbf-modal-tabs" role="tablist">
				{ tabs.map( ( [ name, title ] ) => (
					<button
						key={ name }
						type="button"
						role="tab"
						aria-selected={ tab === name }
						className={ tab === name ? 'is-on' : '' }
						onClick={ () => setTab( name ) }
					>
						{ title }
					</button>
				) ) }
			</div>

			{ notice && (
				<div
					className={ `gsbf-notice is-${ notice.status }` }
					role="status"
				>
					{ notice.text }
				</div>
			) }

			<div className="gsbf-modal-body" role="tabpanel">
				{ body }
			</div>

			<div className="gsbf-modal-actions">
				<OutlineButton
					className="is-inline is-small"
					onClick={ sendTest }
					disabled={ busy || ! settings }
				>
					{ __(
						'Send test email',
						'gembooking-simple-booking-form'
					) }
				</OutlineButton>
				<PrimaryButton onClick={ done } disabled={ busy || ! settings }>
					{ __( 'Done', 'gembooking-simple-booking-form' ) }
				</PrimaryButton>
			</div>
		</Modal>
	);
}
