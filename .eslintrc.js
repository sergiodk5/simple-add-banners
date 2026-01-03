module.exports = {
	root: true,
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	env: {
		browser: true,
		jquery: true,
	},
	globals: {
		wp: 'readonly',
		ajaxurl: 'readonly',
		simpleAddBanners: 'readonly',
	},
	rules: {
		// Enforce strict equality
		eqeqeq: [ 'error', 'always' ],

		// Enforce single quotes
		quotes: [ 'error', 'single', { avoidEscape: true } ],

		// Require semicolons
		semi: [ 'error', 'always' ],

		// Enforce camelCase
		camelcase: [ 'error', { properties: 'never' } ],

		// No console in production
		'no-console': [ 'warn', { allow: [ 'warn', 'error' ] } ],

		// Spacing
		'space-in-parens': [ 'error', 'always' ],
		'array-bracket-spacing': [ 'error', 'always' ],
		'object-curly-spacing': [ 'error', 'always' ],
		'computed-property-spacing': [ 'error', 'always' ],

		// Yoda conditions not enforced in JS (different from PHP)
		yoda: 'off',
	},
	ignorePatterns: [
		'node_modules/',
		'vendor/',
		'build/',
		'dist/',
		'*.min.js',
	],
};
