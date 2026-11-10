import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

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

	const tool = ( command, label, title, style, action ) => (
		<button
			key={ command }
			type="button"
			className="gsbf-tool"
			style={ style }
			aria-label={ title }
			onMouseDown={ ( e ) => e.preventDefault() }
			onClick={ action || ( () => run( command ) ) }
		>
			{ label }
		</button>
	);

	return (
		<div className="gsbf-footer-editor">
			<div className="gsbf-footer-tools">
				{ tool(
					'bold',
					'B',
					__( 'Bold', 'gembooking-simple-booking-form' ),
					{
						fontWeight: 700,
					}
				) }
				{ tool(
					'italic',
					'I',
					__( 'Italic', 'gembooking-simple-booking-form' ),
					{ fontStyle: 'italic' }
				) }
				{ tool(
					'underline',
					'U',
					__( 'Underline', 'gembooking-simple-booking-form' ),
					{ textDecoration: 'underline' }
				) }
				<span className="gsbf-tool-sep" />
				{ tool(
					'insertUnorderedList',
					'•',
					__( 'List', 'gembooking-simple-booking-form' )
				) }
				{ tool(
					'createLink',
					'↗',
					__( 'Link', 'gembooking-simple-booking-form' ),
					undefined,
					() => {
						// eslint-disable-next-line no-alert
						const url = window.prompt(
							__( 'Link URL', 'gembooking-simple-booking-form' ),
							'https://'
						);
						if ( url ) {
							run( 'createLink', url );
						}
					}
				) }
				<span className="gsbf-tool-sep" />
				{ tool(
					'removeFormat',
					'Tx',
					__( 'Clear formatting', 'gembooking-simple-booking-form' )
				) }
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
