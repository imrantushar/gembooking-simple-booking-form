import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';
import { ACCENT_SWATCHES } from '../../../utils/config';

export default function StylePanel( { attributes, setAttributes } ) {
	const accent = attributes.accent;
	const [ hex, setHex ] = useState( accent );

	useEffect( () => setHex( accent ), [ accent ] );

	const onHex = ( value ) => {
		setHex( value );
		let next = value.trim();
		if ( next[ 0 ] !== '#' ) {
			next = `#${ next }`;
		}
		if ( /^#[0-9a-fA-F]{6}$/.test( next ) ) {
			setAttributes( { accent: next.toUpperCase() } );
		}
	};

	return (
		<div className="gsbf-field">
			<span className="gsbf-field-label">
				{ __( 'Accent color', 'gembooking-simple-booking-form' ) }
			</span>
			<div className="gsbf-swatches">
				{ ACCENT_SWATCHES.map( ( [ name, color ] ) => (
					<button
						key={ color }
						type="button"
						aria-label={ name }
						aria-pressed={ accent.toUpperCase() === color }
						className="gsbf-swatch"
						style={ {
							background: color,
							boxShadow:
								accent.toUpperCase() === color
									? `0 0 0 2px #fff, 0 0 0 4px ${ color }`
									: '0 0 0 1px #e0e4e8',
						} }
						onClick={ () => setAttributes( { accent: color } ) }
					/>
				) ) }
				{ /* eslint-disable-next-line jsx-a11y/label-has-associated-control -- the colour input is nested in the label */ }
				<label
					className="gsbf-swatch is-custom"
					aria-label={ __(
						'Custom color',
						'gembooking-simple-booking-form'
					) }
				>
					<input
						type="color"
						value={ accent.toLowerCase() }
						onChange={ ( e ) =>
							setAttributes( {
								accent: e.target.value.toUpperCase(),
							} )
						}
					/>
					<span>+</span>
				</label>
			</div>
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Hex color', 'gembooking-simple-booking-form' ) }
				value={ hex }
				maxLength={ 7 }
				onChange={ onHex }
			/>
		</div>
	);
}
