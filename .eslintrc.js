module.exports = {
	extends: [
		"@10up/eslint-config"
	],
	overrides: [
		{
			files: ["*.js"],
			rules: {
				"vars-on-top": "off",
				"one-var": "off",
				"prefer-const": "off",
				"object-shorthand": "off",
				"jsdoc/require-param-type": "off",
				"jsdoc/require-param-description": "off",
				"jsdoc/newline-after-description": "off",
				"jsdoc/check-types": "off",
				"jsdoc/require-returns-check": "off",
				"jsdoc/no-undefined-types": "off",
				"jsdoc/require-description": "off",
				"jsdoc/require-param": "off",
				"jsdoc/require-returns": "off",
				"jsdoc/check-tag-names": "off",
				"jsdoc/check-param-names": "off",
				"jsdoc/check-indentation": "off",
				"prefer-template": "off",
				"prefer-destructuring": "off",
				"prefer-rest-params": "off",
				"no-undef": "off",
				"no-var": "off",
				"no-param-reassign": "off",
				"no-unused-vars": "off",
				"no-restricted-globals": "off",
				"no-alert": "off",
				"no-multi-assign": "off",
				"no-redeclare": "off",
				"no-lonely-if": "off",
				"no-prototype-builtins": "off",
				"no-undef-init": "off",
				"no-cond-assign": "off",
				"no-empty": "off",
				"no-restricted-properties": "off",
				"block-scoped-var": "off",
				"default-case": "off",
				"eqeqeq": "off",
				"consistent-return": "off",
				"valid-typeof": "off"
			}
		}
	]
};