<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Provider Registration
    |--------------------------------------------------------------------------
    |
    | Daftar provider tambahan untuk Filament
    |
    */
    'providers' => [
        App\Providers\Filament\ThemeServiceProvider::class,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Dark Mode
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk dark mode
    |
    */
    'dark_mode' => [
        'enabled' => true,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Sidebar Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk sidebar
    |
    */
    'sidebar' => [
        'width' => '16rem',
        'collapsed_width' => '4.5rem',
        'is_collapsible_on_desktop' => true,
        'groups' => [
            'are_collapsible' => true,
        ],
    ],
];