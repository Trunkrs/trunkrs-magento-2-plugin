module.exports = {
  parser: '@typescript-eslint/parser',
  extends: [
    'airbnb-typescript-prettier'
  ],
  parserOptions: {
    ecmaVersion: 2018,
    sourceType: 'module',
    ecmaFeatures: {
      jsx: true,
    },
    project: 'tsconfig.eslint.json',
    tsconfigRootDir: __dirname,
  },
  ignorePatterns: ['**/*.d.ts', '**/*.config.js', '**/*.js'],
  env: {
    jest: true,
  },
  rules: {
    'react/jsx-props-no-spreading': ['off'],
    'class-methods-use-this': 'off',
    'react/prop-types': 'off',
    'import/no-extraneous-dependencies': ['off'],
    'import/no-named-as-default': ['off'],
    'no-useless-constructor': ['off'],
    'prettier/prettier': ['error', { endOfLine: 'auto' }],
    'jsx-a11y/anchor-is-valid': ['off'],
    'no-shadow': 'off',
    '@typescript-eslint/no-shadow': ['error'],
    'react/require-default-props': ['off'],
    // This rule doesn't work with TS
    'react/static-property-placement': ['off'],
  },
  overrides: [
    {
      files: ['*.tsx'],
      rules: {
        '@typescript-eslint/explicit-module-boundary-types': ['off'],
      },
    },
  ],
  settings: {
    react: {
      version: 'detect',
    },
  },
}
