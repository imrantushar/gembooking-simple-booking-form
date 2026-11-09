import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Small rich-text editor for the email footer (bold, italic, underline, list,
 * link, clear). The server runs the HTML through wp_kses() when saving.
 */
export default function FooterEditor( { value, onChange } ) {
	const ref = useRef( null );

	// Seed the editable area once; afterwards the browser owns its content.
	useEffect( () => {
		if ( ref.current ) {
			ref.current.innerHTML = value;
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	const run = ( command, arg ) => {
		if ( ref.current ) {
			ref.current.focus();
		}
		document.execCommand( command, false, arg || null );
		if ( ref.current ) {
			onChange( ref.current.innerHTML );
		}
	};

	const tools = [
		[ 'bold', 'B', __( 'Bold', 'gembooking-simple-booking-form' ) ],
		[ 'italic', 'I', __( 'Italic', 'gembooking-simple-booking-form' ) ],
		[
			'underline',
			'U',
			__( 'Underline', 'gembooking-simple-booking-form' ),
		],
		[
			'insertUnorderedList',
			'•',
			__( 'List', 'gembooking-simple-booking-form' ),
		],
	];

	return (
		<div className="gsbf-footer-editor">
			<div className="gsbf-footer-tools">
				{ tools.map( ( [ command, label, title ] ) => (
					<Button
						key={ command }
						size="small"
						label={ title }
						onMouseDown={ ( e ) => e.preventDefault() }
						onClick={ () => run( command ) }
					>
						{ label }
					</Button>
				) ) }
				<Button
					size="small"
					label={ __( 'Link', 'gembooking-simple-booking-form' ) }
					onMouseDown={ ( e ) => e.preventDefault() }
					onClick={ () => {
						// eslint-disable-next-line no-alert
						const url = window.prompt(
							__( 'Link URL', 'gembooking-simple-booking-form' ),
							'https://'
						);
						if ( url ) {
							run( 'createLink', url );
						}
					} }
				>
					↗
				</Button>
				<Button
					size="small"
					label={ __(
						'Clear formatting',
						'gembooking-simple-booking-form'
					) }
					onMouseDown={ ( e ) => e.preventDefault() }
					onClick={ () => run( 'removeFormat' ) }
				>
					Tx
				</Button>
			</div>
			<div
				ref={ ref }
				className="gsbf-footer-area"
				contentEditable
				suppressContentEditableWarning
				role="textbox"
				aria-multiline="true"
				aria-label={ __(
					'Footer text',
					'gembooking-simple-booking-form'
				) }
				onInput={ ( e ) => onChange( e.currentTarget.innerHTML ) }
			/>
		</div>
	);
}
