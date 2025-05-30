<?php

namespace App\Providers\Filament;

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\ServiceProvider;

class SidebarThemeProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        //
    }

    /**
     * Mendapatkan konfigurasi tema untuk sidebar
     */
    public static function getThemeConfig(): array
    {
        return [
            // Warna utama untuk sidebar
            'sidebar' => [
                'backgroundColor' => 'rgb(249, 250, 251)', // Light gray background
                'activeBackgroundColor' => 'rgb(243, 244, 246)', // Slightly darker when active
                'color' => 'rgb(17, 24, 39)', // Dark text
                'activeColor' => 'rgb(234, 88, 12)', // Orange text when active
                'activeIconColor' => 'rgb(234, 88, 12)', // Orange icon when active
                'iconColor' => 'rgb(107, 114, 128)', // Gray icon
                'borderColor' => 'rgb(229, 231, 235)', // Light border
            ],
            
            // Warna untuk grup navigasi
            'navigationGroups' => [
                'Data Entry' => [
                    'iconColor' => 'rgb(220, 38, 38)', // Red for data entry group
                ],
                'Realisasi' => [
                    'iconColor' => 'rgb(37, 99, 235)', // Blue for realisasi group
                ],
                'Pengaturan' => [
                    'iconColor' => 'rgb(5, 150, 105)', // Green for settings group
                ],
            ],
            
            // Kustomisasi untuk item navigasi tertentu
            'navigationItems' => [
                'Dashboard' => [
                    'iconColor' => 'rgb(234, 88, 12)', // Orange for dashboard
                ],
                'Nomor CITES' => [
                    'iconColor' => 'rgb(220, 38, 38)', // Red for CITES
                ],
                'Data Customers' => [
                    'iconColor' => 'rgb(79, 70, 229)', // Indigo for customers
                ],
                'Data Products' => [
                    'iconColor' => 'rgb(16, 185, 129)', // Emerald for products
                ],
                'Bulanan AKKII' => [
                    'iconColor' => 'rgb(245, 158, 11)', // Amber for AKKII
                ],
                'Bulanan BKSDA' => [
                    'iconColor' => 'rgb(59, 130, 246)', // Blue for Bulanan BKSDA
                ],
                'Triwulan BKSDA' => [
                    'iconColor' => 'rgb(139, 92, 246)', // Purple for Triwulan BKSDA
                ],
                'Tahunan BKSDA' => [
                    'iconColor' => 'rgb(236, 72, 153)', // Pink for Tahunan BKSDA
                ],
                'Users' => [
                    'iconColor' => 'rgb(124, 58, 237)', // Purple for users
                ],
            ],
        ];
    }
}