<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    |
    | This configuration option allows you to change the default theme for the
    | application. By default, we use the Tailwind theme.
    |
    */
    'theme' => 'tailwind',

    /*
    |--------------------------------------------------------------------------
    | Per-Page Settings
    |--------------------------------------------------------------------------
    |
    | Configure the default behavior for the per-page selector in data grids.
    |
    */
    'per_page' => [
        // Default number of records per page
        'default' => 15,

        // Available per-page options in the dropdown
        'values' => [15, 30, 50, 100],

        // Whether to show the "All" option
        'show_all' => true,

        // Maximum records to allow "All" option (prevents performance issues)
        'show_all_threshold' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Styles
    |--------------------------------------------------------------------------
    |
    | Define default CSS classes for table elements across all grids. These
    | can be overridden per-grid by setting the $tableStyles property on
    | individual components. Empty values fall back to the theme template.
    |
    */
    'table_styles' => [
        'table' => 'w-full text-sm text-left',
        'thead' => 'border-b border-gray-200 text-gray-700',
        'tbody' => 'divide-y divide-gray-200',
        'tfoot' => 'border-t border-gray-200',
        'tfoot-tr' => 'bg-gray-50',
        'tfoot-td' => 'px-3 py-2 font-semibold text-gray-700',
        'th' => 'px-3 py-3 font-medium tracking-wide',
        'td' => 'px-3 py-2 text-gray-600',
        'tr' => 'hover:bg-gray-50',
    ],
];
