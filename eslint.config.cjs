/**
 * ESLint flat config: the @wordpress/scripts default plus a few project rules.
 */
const base = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...base,
	{
		files: [ 'dev_gsbf/**/*.{js,jsx}' ],
		rules: {
			// @wordpress/* packages are provided by WordPress at runtime (webpack externals).
			'import/no-extraneous-dependencies': 'off',
			'import/no-unresolved': [ 'error', { ignore: [ '^@wordpress/' ] } ],
			// Components are documented by their prop names; JSDoc for destructured props is noise.
			'jsdoc/require-param': 'off',
			'jsdoc/require-param-type': 'off',
			'jsdoc/check-param-names': 'off',
		},
	},
];
