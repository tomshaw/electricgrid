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
];
