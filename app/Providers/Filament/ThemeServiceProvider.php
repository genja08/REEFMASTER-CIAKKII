<?php

namespace App\Providers\Filament;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Mendaftarkan file CSS kustom
        FilamentAsset::register([
            Css::make('custom-theme', resource_path('css/filament/admin/theme.css')),
        ]);
    }
}