/**
 * Small form controls styled exactly like the design's block sidebar and
 * email modal (see styles/editor.scss). They replace the default WordPress
 * controls so borders, radii and the black primary color match the design.
 */

import { useEffect, useRef, useState } from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';
import { isDigits } from '../../utils/validate';

export function Field( { label, hint, htmlFor, children } ) {
	return (
		<div className="gsbf-sb-field">
			{ label && (
				<label className="gsbf-sb-label" htmlFor={ htmlFor }>
					{ label }
				</label>
			) }
			{ children }
			{ hint && <div className="gsbf-sb-hint">{ hint }</div> }
		</div>
	);
}

/** A caption for a group of buttons (not tied to one input). */
export function GroupLabel( { id, children } ) {
	return (
		<span className="gsbf-sb-label" id={ id }>
			{ children }
		</span>
	);
}

export function TextInput( { label, hint, size, className = '', ...props } ) {
	const id = useInstanceId( TextInput, 'gsbf-text' );
	return (
		<Field label={ label } hint={ hint } htmlFor={ id }>
			<input
				id={ id }
				className={ `gsbf-sb-input${ size === 'lg' ? ' is-lg' : '' } ${ className }` }
				type="text"
				{ ...props }
			/>
		</Field>
	);
}

export function SelectInput( { label, hint, value, options, onChange, size } ) {
	const id = useInstanceId( SelectInput, 'gsbf-select' );
	return (
		<Field label={ label } hint={ hint } htmlFor={ id }>
			<select
				id={ id }
				className={ `gsbf-sb-input is-select${ size === 'lg' ? ' is-lg' : '' }` }
				value={ String( value ) }
				onChange={ ( e ) => onChange( e.target.value ) }
			>
				{ options.map( ( option ) => (
					<option
						key={ option.value }
						value={ String( option.value ) }
					>
						{ option.label }
					</option>
				) ) }
			</select>
		</Field>
	);
}

export function TextAreaInput( { label, hint, ...props } ) {
	const id = useInstanceId( TextAreaInput, 'gsbf-area' );
	return (
		<Field label={ label } hint={ hint } htmlFor={ id }>
			<textarea
				id={ id }
				className="gsbf-sb-input is-textarea"
				{ ...props }
			/>
		</Field>
	);
}

/**
 * A number field that only ever holds a whole number: no decimals, no
 * exponent, no sign. Typing, pasting and dropping text with anything but
 * digits is ignored. A valid value inside [min, max] is applied while typing;
 * on blur an out-of-range value is brought back inside the range.
 *
 * `normalize` (optional) maps a typed whole number to the value to store,
 * for example rounding a duration to 5 minutes.
 */
export function NumberInput( {
	label,
	hint,
	value,
	min,
	max,
	normalize,
	onChange,
	size,
	...props
} ) {
	const id = useInstanceId( NumberInput, 'gsbf-number' );
	const [ text, setText ] = useState( String( value ) );
	const typing = useRef( false );

	// Follow outside changes (e.g. a preset was chosen), but never while typing.
	useEffect( () => {
		if ( ! typing.current ) {
			setText( String( value ) );
		}
	}, [ value ] );

	const store = ( n ) => {
		const next = normalize
			? normalize( n )
			: Math.min( max, Math.max( min, n ) );
		onChange( next );
		return next;
	};

	const change = ( raw ) => {
		if ( ! isDigits( raw ) ) {
			return; // Decimal point, exponent, sign or letters: not a whole number.
		}
		setText( raw );
		if ( raw === '' ) {
			return;
		}
		const n = parseInt( raw, 10 );
		if ( n >= min && n <= max ) {
			store( n );
		}
	};

	const blur = () => {
		typing.current = false;
		const n = text === '' ? NaN : parseInt( text, 10 );
		// Only store when the person changed it; opening the field must not alter the value.
		const next = Number.isNaN( n ) || n === value ? value : store( n );
		setText( String( next ) );
	};

	return (
		<Field label={ label } hint={ hint } htmlFor={ id }>
			<input
				id={ id }
				className={ `gsbf-sb-input${ size === 'lg' ? ' is-lg' : '' }` }
				type="number"
				inputMode="numeric"
				pattern="[0-9]*"
				min={ min }
				max={ max }
				step={ 1 }
				value={ text }
				onFocus={ () => {
					typing.current = true;
				} }
				onBlur={ blur }
				onKeyDown={ ( e ) => {
					if ( [ '.', ',', 'e', 'E', '+', '-' ].includes( e.key ) ) {
						e.preventDefault();
					}
				} }
				onPaste={ ( e ) => {
					e.preventDefault();
					change( e.clipboardData.getData( 'text' ).trim() );
				} }
				onChange={ ( e ) => change( e.target.value ) }
				{ ...props }
			/>
		</Field>
	);
}

/** Segmented control: one option on, in a light-grey tray (layout, duration). */
export function Segmented( {
	label,
	value,
	options,
	onChange,
	className = '',
} ) {
	const id = useInstanceId( Segmented, 'gsbf-seg' );
	return (
		<div className="gsbf-sb-field">
			<GroupLabel id={ id }>{ label }</GroupLabel>
			<div
				className={ `gsbf-seg-control ${ className }` }
				role="group"
				aria-labelledby={ id }
			>
				{ options.map( ( option ) => (
					<button
						key={ option.value }
						type="button"
						className={ option.value === value ? 'is-on' : '' }
						aria-pressed={ option.value === value }
						onClick={ () => onChange( option.value ) }
					>
						{ option.label }
					</button>
				) ) }
			</div>
		</div>
	);
}

/** Toggle chips (available days). */
export function Chips( { label, options, isOn, onToggle } ) {
	const id = useInstanceId( Chips, 'gsbf-chips' );
	return (
		<div className="gsbf-sb-field">
			<GroupLabel id={ id }>{ label }</GroupLabel>
			<div className="gsbf-chips-row" role="group" aria-labelledby={ id }>
				{ options.map( ( option ) => (
					<button
						key={ option.value }
						type="button"
						className={ `gsbf-chip${ isOn( option.value ) ? ' is-on' : '' }` }
						aria-pressed={ isOn( option.value ) }
						onClick={ () => onToggle( option.value ) }
					>
						{ option.label }
					</button>
				) ) }
			</div>
		</div>
	);
}

export function Switch( { label, checked, onChange } ) {
	return (
		<div className="gsbf-switch-row">
			<span>{ label }</span>
			<button
				type="button"
				role="switch"
				aria-checked={ checked }
				aria-label={ label }
				className="gsbf-switch"
				onClick={ () => onChange( ! checked ) }
			/>
		</div>
	);
}

export function OutlineButton( { className = '', ...props } ) {
	return (
		<button
			type="button"
			className={ `gsbf-outline-btn ${ className }` }
			{ ...props }
		/>
	);
}

export function PrimaryButton( { className = '', ...props } ) {
	return (
		<button
			type="button"
			className={ `gsbf-primary-btn ${ className }` }
			{ ...props }
		/>
	);
}
