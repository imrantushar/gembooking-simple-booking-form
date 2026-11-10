/**
 * Small form controls styled exactly like the design's block sidebar and
 * email modal (see styles/editor.scss). They replace the default WordPress
 * controls so borders, radii and the black primary color match the design.
 */

import { useInstanceId } from '@wordpress/compose';

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
