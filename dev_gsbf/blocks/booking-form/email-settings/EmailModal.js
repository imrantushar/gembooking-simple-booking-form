import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Modal, Notice, TabPanel } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import SettingsTab from './SettingsTab';
import PreviewTab from './PreviewTab';

/**
 * "Configure email" modal. Site-wide settings: they are saved to the site, not
 * to the block. Brand colour edits this block's accent attribute.
 */
export default function EmailModal( { accent, setAccent, onClose, onSaved } ) {
	const [ settings, setSettings ] = useState( null );
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

	return (
		<Modal
			title={ __(
				'Email notifications',
				'gembooking-simple-booking-form'
			) }
			onRequestClose={ onClose }
			className="gsbf-email-modal"
		>
			{ notice && (
				<Notice status={ notice.status } isDismissible={ false }>
					{ notice.text }
				</Notice>
			) }

			{ ! settings ? (
				<p>{ __( 'Loading…', 'gembooking-simple-booking-form' ) }</p>
			) : (
				<TabPanel
					tabs={ [
						{
							name: 'settings',
							title: __(
								'Settings',
								'gembooking-simple-booking-form'
							),
						},
						{
							name: 'admin',
							title: __(
								'Admin email',
								'gembooking-simple-booking-form'
							),
						},
						{
							name: 'customer',
							title: __(
								'Customer email',
								'gembooking-simple-booking-form'
							),
						},
					] }
				>
					{ ( tab ) =>
						tab.name === 'settings' ? (
							<SettingsTab
								settings={ settings }
								update={ update }
								accent={ accent }
								setAccent={ setAccent }
							/>
						) : (
							<PreviewTab
								kind={ tab.name }
								settings={ settings }
								accent={ accent }
							/>
						)
					}
				</TabPanel>
			) }

			<div className="gsbf-modal-actions">
				<Button
					variant="secondary"
					onClick={ sendTest }
					disabled={ busy || ! settings }
				>
					{ __(
						'Send test email',
						'gembooking-simple-booking-form'
					) }
				</Button>
				<Button
					variant="primary"
					onClick={ done }
					disabled={ busy || ! settings }
				>
					{ __( 'Done', 'gembooking-simple-booking-form' ) }
				</Button>
			</div>
		</Modal>
	);
}
