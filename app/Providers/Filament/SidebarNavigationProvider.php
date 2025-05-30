<?php

namespace App\Providers\Filament;

use App\Filament\Resources\AKKIIResource;
use App\Filament\Resources\CitesDocumentResource;
use App\Filament\Resources\DataCustomerResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\UserResource;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Illuminate\Support\ServiceProvider;
use App\Providers\Filament\NavigationAuthorizationProvider;

class SidebarNavigationProvider extends ServiceProvider
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
     * Mendapatkan konfigurasi navigasi untuk sidebar
     * @return array<NavigationGroup>
     */
    public static function getNavigation(): array
    {
        return [
            // Dashboard (tidak dalam grup)
            NavigationItem::make('Dashboard')
                ->icon('heroicon-o-home')
                ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.dashboard'))
                ->url(fn (): string => Dashboard::getUrl())
                ->visible(fn (): bool => NavigationAuthorizationProvider::canAccessMenu('dashboard')),
            
            // Grup Data Entry
            NavigationGroup::make()
                ->label('Data Entry')
                ->icon('heroicon-o-document')
                ->items([
                    // Nomor CITES (sebelumnya CITES Documents)
                    ...array_map(
                        function (NavigationItem $item) {
                            return $item->label('Nomor CITES');
                        },
                        array_filter(
                            CitesDocumentResource::getNavigationItems(),
                            fn (NavigationItem $item) => NavigationAuthorizationProvider::canAccessMenu('cites-documents')
                        )
                    ),
                    
                    // Data Customers
                    ...array_filter(
                        DataCustomerResource::getNavigationItems(),
                        fn (NavigationItem $item) => NavigationAuthorizationProvider::canAccessMenu('customers')
                    ),
                    
                    // Data Products (sebelumnya Products)
                    ...array_map(
                        function (NavigationItem $item) {
                            return $item->label('Data Products');
                        },
                        array_filter(
                            ProductResource::getNavigationItems(),
                            fn (NavigationItem $item) => NavigationAuthorizationProvider::canAccessMenu('products')
                        )
                    ),
                ]),
            
            // Grup Realisasi
            NavigationGroup::make()
                ->label('Realisasi')
                ->icon('heroicon-o-chart-bar')
                ->items([
                    // Bulanan AKKII (sebelumnya AKKIIs)
                    ...array_map(
                        function (NavigationItem $item) {
                            return $item->label('Bulanan AKKII');
                        },
                        array_filter(
                            AKKIIResource::getNavigationItems(),
                            fn (NavigationItem $item) => NavigationAuthorizationProvider::canAccessMenu('akkii')
                        )
                    ),
                    
                    // Bulanan BKSDA (menu baru)
                    NavigationItem::make('bulanan-bksda')
                        ->label('Bulanan BKSDA')
                        ->icon('heroicon-o-calendar')
                        ->url(fn (): string => '/admin/bulanan-bksda')
                        ->visible(fn (): bool => NavigationAuthorizationProvider::canAccessMenu('akkii')),
                    
                    // Triwulan BKSDA (menu baru)
                    NavigationItem::make('triwulan-bksda')
                        ->label('Triwulan BKSDA')
                        ->icon('heroicon-o-calendar')
                        ->url(fn (): string => '/admin/triwulan-bksda')
                        ->visible(fn (): bool => NavigationAuthorizationProvider::canAccessMenu('akkii')),
                    
                    // Tahunan BKSDA (menu baru)
                    NavigationItem::make('tahunan-bksda')
                        ->label('Tahunan BKSDA')
                        ->icon('heroicon-o-calendar')
                        ->url(fn (): string => '/admin/tahunan-bksda')
                        ->visible(fn (): bool => NavigationAuthorizationProvider::canAccessMenu('akkii')),
                ]),
            
            // Grup Pengaturan - hanya tampilkan untuk admin
            NavigationGroup::make()
                ->label('Pengaturan')
                ->icon('heroicon-o-cog-6-tooth')
                ->items([
                    // Hanya tampilkan menu User jika pengguna memiliki akses
                    ...array_filter(
                        UserResource::getNavigationItems(),
                        fn (NavigationItem $item) => NavigationAuthorizationProvider::canAccessMenu('users')
                    ),
                ]),
        ];
    }
}