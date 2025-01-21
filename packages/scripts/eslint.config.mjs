import globals from 'globals';
import babelParser from '@babel/eslint-parser';
import _import from 'eslint-plugin-import';
import simpleImportSort from 'eslint-plugin-simple-import-sort';
import { fixupPluginRules } from '@eslint/compat';
import tsParser from '@typescript-eslint/parser';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import js from '@eslint/js';
import { FlatCompat } from '@eslint/eslintrc';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const compat = new FlatCompat({
  baseDirectory: __dirname,
  recommendedConfig: js.configs.recommended,
  allConfig: js.configs.all,
});

export default [
  ...compat.extends('eslint:recommended', 'plugin:react/recommended', 'plugin:prettier/recommended'),
  {
    plugins: {},

    languageOptions: {
      globals: {
        ...globals.node,
        ...globals.browser,
      },

      parser: babelParser,
      ecmaVersion: 2020,
      sourceType: 'module',

      parserOptions: {
        ecmaFeatures: {
          jsx: true,
          legacyDecorators: true,
        },
      },
    },

    settings: {
      react: {
        version: '17.0',
      },
    },

    rules: {
      'no-console': 'off',
      'no-prototype-builtins': 'off',
      'no-undef': 'off',
      'react/no-find-dom-node': 'off',
      'no-case-declarations': 'off',
    },
  },
  ...compat.extends('plugin:@typescript-eslint/recommended', 'plugin:@typescript-eslint/recommended').map((config) => ({
    ...config,
    files: ['**/*.ts', '**/*.tsx'],
  })),
  {
    files: ['**/*.ts', '**/*.tsx'],

    plugins: {
      import: fixupPluginRules(_import),
      'simple-import-sort': simpleImportSort,
    },

    languageOptions: {
      parser: tsParser,
    },

    rules: {
      '@typescript-eslint/ban-ts-comment': 'off',
      '@typescript-eslint/consistent-type-imports': ['error', { prefer: 'type-imports' }],
      '@typescript-eslint/explicit-function-return-type': 'off',
      '@typescript-eslint/no-explicit-any': 'error',
      '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
      'import/no-cycle': ['error', { ignoreExternal: true }],
      'prettier/prettier': 'error',
      'simple-import-sort/exports': 'error',
      'simple-import-sort/imports': 'error',
      'sort-imports': 'off',
      'sort-keys': 'off',
    },
  },
];
