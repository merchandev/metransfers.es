import js from '@eslint/js';
import globals from 'globals';

const projectGlobals = {
  ClipboardJS: 'readonly',
  Swal: 'readonly',
  flatpickr: 'readonly',
  google: 'readonly',
  hqp_ajax: 'readonly',
  hqp_vars: 'readonly',
  jQuery: 'readonly',
  meTransfers: 'readonly',
  meTransfersPublic: 'readonly',
  ptsData: 'readonly',
  wptb_ajax: 'readonly',
  wptbData: 'readonly',
  wptb_vars: 'readonly'
};

export default [
  {
    ignores: [
      'node_modules/**',
      'vendor/**',
      'playwright-report/**',
      'test-results/**'
    ]
  },
  {
    files: ['assets/js/**/*.js', 'app/Legacy/**/*.js'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'script',
      globals: {
        ...globals.browser,
        ...projectGlobals
      }
    },
    rules: {
      ...js.configs.recommended.rules,
      'no-unused-vars': ['error', { argsIgnorePattern: '^_', caughtErrors: 'none' }]
    }
  },
  {
    files: ['tests/e2e/**/*.js', '*.config.mjs'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.browser,
        ...globals.node
      }
    },
    rules: js.configs.recommended.rules
  }
];
