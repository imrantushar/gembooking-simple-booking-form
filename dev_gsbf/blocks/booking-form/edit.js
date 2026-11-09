import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	BlockControls,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import {
	Button,
	Modal,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import BookingWidget from '../../components/BookingWidget';
import { createMockApi } from '../../utils/api';
import { normalizeConfig, uuid } from '../../utils/config';
import Inspector from './inspector';

const flatten = ( blocks ) =>
	blocks.flatMap( ( block ) => [ block, ...flatten( block.innerBlocks ) ] );

function PreviewModal( { config, api, onClose } ) {
	const [ device, setDevice ] = useState( 'desktop' );

	return (
		<Modal
			title={ __( 'Preview form', 'gembooking-simple-booking-form' ) }
			onRequestClose={ onClose }
			className="gsbf-preview-modal"
			isFullScreen
		>
			<div className="gsbf-preview-bar">
				<Button
					variant={ device === 'desktop' ? 'primary' : 'secondary' }
					onClick={ () => setDevice( 'desktop' ) }
				>
					{ __( 'Desktop', 'gembooking-simple-booking-form' ) }
				</Button>
				<Button
					variant={ device === 'mobile' ? 'primary' : 'secondary' }
					onClick={ () => setDevice( 'mobile' ) }
				>
					{ __( 'Mobile', 'gembooking-simple-booking-form' ) }
				</Button>
				<span className="gsbf-preview-note">
					{ __(
						'Settings update the form live. Bookings made here are not saved.',
						'gembooking-simple-booking-form'
					) }
				</span>
			</div>
			<div className="gsbf-preview-stage">
				<div
					className="gsbf-form"
					style={ {
						'--gsbf-accent': config.accent,
						width: '100%',
						maxWidth: device === 'mobile' ? 390 : 860,
					} }
				>
					<BookingWidget config={ config } api={ api } />
				</div>
			</div>
		</Modal>
	);
}

export default function Edit( { attributes, setAttributes, clientId } ) {
	const [ resetKey, setResetKey ] = useState( 0 );
	const [ previewing, setPreviewing ] = useState( false );
	const config = normalizeConfig( attributes );
	const approval =
		( window.GsbfGlobal && window.GsbfGlobal.approval ) || 'manual';

	// Every block needs its own formId (bookings are scoped by it) and a timezone.
	useEffect( () => {
		const patch = {};
		if ( ! attributes.formId ) {
			patch.formId = uuid();
		}
		if ( ! attributes.timezone ) {
			patch.timezone = config.timezone;
		}
		if ( Object.keys( patch ).length ) {
			setAttributes( patch );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// A copied block carries the same formId: the later one gets a new id.
	const blocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks(),
		[]
	);
	useEffect( () => {
		if ( ! attributes.formId ) {
			return;
		}
		const same = flatten( blocks ).filter(
			( block ) =>
				block.name === 'gsbf/booking-form' &&
				block.attributes.formId === attributes.formId
		);
		if ( same.length > 1 && same[ 0 ].clientId !== clientId ) {
			setAttributes( { formId: uuid() } );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ blocks, attributes.formId ] );

	const configKey = JSON.stringify( config );
	const api = useMemo(
		() => createMockApi( config, approval ),
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ configKey, approval ]
	);

	const blockProps = useBlockProps( {
		className: 'gsbf-form',
		style: { '--gsbf-accent': config.accent },
	} );

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton onClick={ () => setPreviewing( true ) }>
						{ __(
							'Preview form',
							'gembooking-simple-booking-form'
						) }
					</ToolbarButton>
					<ToolbarButton
						onClick={ () => setResetKey( resetKey + 1 ) }
					>
						{ __( 'Reset', 'gembooking-simple-booking-form' ) }
					</ToolbarButton>
				</ToolbarGroup>
			</BlockControls>

			<InspectorControls>
				<Inspector
					attributes={ config }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				<BookingWidget
					key={ `${ resetKey }:${ configKey }` }
					config={ config }
					api={ api }
				/>
			</div>

			{ previewing && (
				<PreviewModal
					config={ config }
					api={ api }
					onClose={ () => setPreviewing( false ) }
				/>
			) }
		</>
	);
}
