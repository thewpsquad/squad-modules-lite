/**
 * ESLint flat configuration.
 *
 * Migrated from the legacy `.eslintrc.js` to the flat config format required by
 * ESLint 9/10 and shipped by @wordpress/scripts / @wordpress/eslint-plugin v25+.
 * The WordPress `recommended` config already bundles the react, react-hooks,
 * jsx-a11y, import, jsdoc, prettier and @typescript-eslint plugins, so those are
 * not re-registered here.
 */

import wordpress from '@wordpress/eslint-plugin';
import tseslint from 'typescript-eslint';
import wc from 'eslint-plugin-wc';
import globals from 'globals';

export default [
	// Replaces .eslintignore.
	{
		ignores: [
			'bin/**',
			'build/**',
			'@*/**',
			'temp/**',
			'tools/**',
			'node_modules/**',
			'src/divi-builder/divi-4/helpers/frontend/dot-lottie-helpers.ts',
			'src/divi-builder/divi-5/**',
		],
	},

	// WordPress shareable configs (flat). `recommended` pulls in react,
	// react-hooks, jsx-a11y, import, jsdoc, prettier and @typescript-eslint.
	...wordpress.configs.recommended,
	...wordpress.configs.esnext,
	...wordpress.configs.custom,
	...wordpress.configs.i18n,

	// Web Components rules.
	wc.configs[ 'flat/recommended' ],

	// Project-wide language options, settings and rule overrides.
	{
		files: [ 'src/**/*.{js,jsx,ts,tsx}' ],
		languageOptions: {
			parser: tseslint.parser,
			ecmaVersion: 'latest',
			sourceType: 'module',
			parserOptions: {
				projectService: true,
				tsconfigRootDir: import.meta.dirname,
				ecmaFeatures: { jsx: true },
			},
			globals: {
				...globals.browser,
				...globals.node,
				wp: 'readonly',
				_: 'readonly',
				NodeList: 'readonly',
				Element: 'readonly',
				ET_Builder: 'readonly',
				ETBuilderBackendDynamic: 'readonly',
				React: 'readonly',
			},
		},
		settings: {
			react: { version: 'detect' },
			'import/resolver': {
				typescript: {
					alwaysTryTypes: true,
					project: './tsconfig.json',
				},
				alias: {
					map: [ [ '@src', './src' ] ],
					extensions: [ '.ts', '.tsx', '.js', '.jsx', '.json', '.css', '.scss' ],
				},
			},
		},
		rules: {
			// WordPress style rules.
			indent: 'off',
			quotes: [ 'error', 'single', { allowTemplateLiterals: true, avoidEscape: true } ],
			semi: [ 'error', 'always' ],
			'comma-dangle': [ 'error', {
				arrays: 'never',
				objects: 'never',
				imports: 'never',
				exports: 'never',
				functions: 'never',
			} ],

			// Turn off jsdoc rules.
			'jsdoc/require-param': 'off',
			'jsdoc/newline-after-description': 'off',

			// TypeScript rules.
			'@typescript-eslint/ban-ts-comment': 'off',
			'@typescript-eslint/no-explicit-any': 'off',
			'@typescript-eslint/explicit-member-accessibility': [ 'error', {
				accessibility: 'explicit',
				overrides: {
					accessors: 'explicit',
					constructors: 'no-public',
					methods: 'explicit',
					properties: 'explicit',
					parameterProperties: 'explicit',
				},
			} ],
			'@typescript-eslint/explicit-function-return-type': [ 'error', {
				allowExpressions: true,
				allowTypedFunctionExpressions: true,
				allowHigherOrderFunctions: true,
				allowDirectConstAssertionInArrowFunctions: true,
				allowConciseArrowFunctionExpressionsStartingWithVoid: true,
			} ],
			'@typescript-eslint/member-ordering': [ 'error', {
				default: [
					'static-field',
					'instance-field',
					'constructor',
					'public-instance-method',
					'protected-instance-method',
					'private-instance-method',
				],
			} ],
			'@typescript-eslint/no-inferrable-types': 'off',
			'@typescript-eslint/no-unsafe-function-type': 'off',
			'@typescript-eslint/no-unsafe-call': 'off',
			'@typescript-eslint/no-unsafe-assignment': 'off',
			'@typescript-eslint/no-unsafe-member-access': 'off',

			// React/JSX rules.
			'react/jsx-boolean-value': [ 'error', 'never' ],
			'react/jsx-curly-spacing': [ 'error', 'always' ],
			'react/jsx-equals-spacing': [ 'error', 'never' ],
			'react/jsx-key': 'error',
			'react/jsx-no-bind': 'off',
			'react/jsx-no-useless-fragment': 'error',
			'react/self-closing-comp': 'error',
			'react/jsx-wrap-multilines': 'error',

			// Import organization.
			'import/order': [ 'error', {
				groups: [
					'builtin',
					'external',
					'internal',
					'parent',
					'sibling',
					'index',
					'object',
					'type',
				],
				pathGroups: [
					{ pattern: '@wordpress/**', group: 'external', position: 'before' },
					{ pattern: '@divi/**', group: 'external', position: 'before' },
					{ pattern: '@src/**', group: 'internal', position: 'after' },
				],
				pathGroupsExcludedImportTypes: [ 'type' ],
				'newlines-between': 'always',
				alphabetize: { order: 'asc', caseInsensitive: true },
				distinctGroup: true,
				warnOnUnassignedImports: true,
			} ],

			// Code quality rules.
			'no-console': [ 'warn', { allow: [ 'warn', 'error' ] } ],
			'no-debugger': 'error',
			'no-duplicate-imports': 'error',
			'no-unused-vars': [ 'warn', {
				varsIgnorePattern: 'React|_',
				argsIgnorePattern: '^_',
			} ],
			'prefer-const': 'error',
			'no-var': 'error',

			// Disabled / relaxed rules.
			camelcase: 'off',
			'import/no-unresolved': 'off',
			'prettier/prettier': 'off',

			// WordPress-specific rules.
			'@wordpress/no-unused-vars-before-return': 'error',
			'@wordpress/valid-sprintf': 'error',
			'@wordpress/i18n-text-domain': [ 'error', { allowedTextDomain: 'squad-modules-for-divi' } ],
			'@wordpress/i18n-translator-comments': 'error',
			'@wordpress/i18n-no-variables': 'error',
			'@wordpress/i18n-no-placeholders-only': 'error',
			'@wordpress/i18n-ellipsis': 'error',
		},
	},

	// Test files: opt out of type-aware linting — they are not part of the build
	// tsconfig, so the typed project service can't resolve them and reports a
	// parsing error for every one.
	{
		files: [
			'src/**/*.{test,spec}.{js,jsx,ts,tsx}',
			'src/**/__tests__/**',
			'src/**/test/**',
		],
		...tseslint.configs.disableTypeChecked,
	},

	// Test files: register the Jest globals (describe/it/expect/jest/beforeEach…)
	// so they don't surface as spurious `no-undef` errors — the project lints all
	// of ./src, including *.test.* files, and the Jest suite itself runs green.
	{
		files: [
			'src/**/*.{test,spec}.{js,jsx,ts,tsx}',
			'src/**/__tests__/**',
			'src/**/test/**',
		],
		languageOptions: {
			globals: {
				...globals.jest,
			},
		},
		// Test code doesn't need production-grade annotations.
		rules: {
			'@typescript-eslint/explicit-function-return-type': 'off',
			'@typescript-eslint/explicit-member-accessibility': 'off',
			'@typescript-eslint/no-unused-vars': 'off',
			'@typescript-eslint/no-shadow': 'off',
			'jsdoc/require-param-type': 'off',
			'jsdoc/require-returns-type': 'off',
		},
	},

	// JSX files opt out of type-aware linting (no TS program backing them).
	{
		files: [ 'src/**/*.jsx' ],
		...tseslint.configs.disableTypeChecked,
	},

	// Disable Prettier everywhere — formatting is not enforced through ESLint.
	{
		rules: {
			'prettier/prettier': 'off',
		},
	},
];
