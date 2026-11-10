import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ACCENT_SWATCHES } from '../../../utils/config';
import { GroupLabel } from '../../../components/ui';

export default function StylePanel( { attributes, setAttributes } ) {
	const accent = attributes.accent.toUpperCase();
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

	const preset = ACCENT_SWATCHES.some( ( [ , color ] ) => color === accent );

	return (
		<div className="gsbf-sb-field">
			<GroupLabel id="gsbf-accent-label">
				{ __( 'Accent color', 'gembooking-simple-booking-form' ) }
			</GroupLabel>
			<div
				className="gsbf-swatches"
				role="group"
				aria-labelledby="gsbf-accent-label"
			>
				{ ACCENT_SWATCHES.map( ( [ name, color ] ) => (
					<button
						key={ color }
						type="button"
						aria-label={ name }
						aria-pressed={ accent === color }
						className={ `gsbf-swatch${ accent === color ? ' is-on' : '' }` }
						style={ { '--swatch': color } }
						onClick={ () => setAttributes( { accent: color } ) }
					/>
				) ) }
				{ /* eslint-disable-next-line jsx-a11y/label-has-associated-control -- the colour input is nested in the label */ }
				<label
					className={ `gsbf-swatch is-custom${
						! preset ? ' is-on' : ''
					}` }
					style={ { '--swatch': preset ? '#f6f7f8' : accent } }
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
					<span aria-hidden="true">+</span>
				</label>
			</div>
			<div className="gsbf-hex-row">
				<span className="gsbf-dot" style={ { background: accent } } />
				<input
					className="gsbf-sb-input is-hex"
					value={ hex }
					maxLength={ 7 }
					aria-label={ __(
						'Hex color',
						'gembooking-simple-booking-form'
					) }
					onChange={ ( e ) => onHex( e.target.value ) }
				/>
				<span className="gsbf-sb-hint is-inline">
					{ __( 'Custom color', 'gembooking-simple-booking-form' ) }
				</span>
			</div>
		</div>
	);
}
