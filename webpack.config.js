const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

// Single source of truth. Must match GSBF_VERSION in the main plugin file:
// PHP enqueues `assets/build/{name}.{VERSION}.js`, so the file webpack emits
// has to carry the same version.
const VERSION = require( './package.json' ).version;

module.exports = {
	...defaultConfig,
	entry: {
		// Block editor only: block, settings sidebar, email modal.
		blocks: path.resolve( __dirname, 'dev_gsbf/blocks.js' ),
		// Front end: hydrates the booking form.
		frontend: path.resolve( __dirname, 'dev_gsbf/frontend.js' ),
	},
	output: {
		...defaultConfig.output,
		filename: `[name].${ VERSION }.js`,
		path: path.resolve( __dirname, 'assets/build' ),
		clean: true,
	},
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...( defaultConfig.resolve && defaultConfig.resolve.alias ),
			'@Components': path.resolve( __dirname, 'dev_gsbf/components/' ),
			'@Utils': path.resolve( __dirname, 'dev_gsbf/utils/' ),
			'@Hooks': path.resolve( __dirname, 'dev_gsbf/hooks/' ),
		},
	},
};
