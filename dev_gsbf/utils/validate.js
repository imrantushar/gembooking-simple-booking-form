export const isEmail = ( value ) => /^\S+@\S+\.\S+$/.test( value );

/** Only digits (or nothing yet): no ".", ",", "e", "+" or "-", so no decimals. */
export const isDigits = ( value ) => /^\d*$/.test( value );
