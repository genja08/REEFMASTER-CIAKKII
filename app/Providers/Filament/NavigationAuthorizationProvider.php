<?php

namespace App\Providers\Filament;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class NavigationAuthorizationProvider extends ServiceProvider
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
     * Memeriksa apakah pengguna memiliki akses ke menu tertentu
     */
    public static function canAccessMenu(string $menuKey): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        // Jika pengguna adalah admin (id=1 atau email tertentu), berikan akses ke semua menu
        if ($user->id === 1 || $user->email === 'admin@example.com') {
            return true;
        }
        
        // Definisikan aturan akses menu berdasarkan peran sederhana
        // Gunakan field is_admin atau role jika tersedia di tabel users
        $isAdmin = $user->is_admin ?? false;
        $userRole = $user->role ?? 'user';
        
        // Definisikan aturan akses menu
        $menuPermissions = [
            'dashboard' => true, // Semua pengguna dapat mengakses dashboard
            'cites-documents' => $isAdmin || $userRole === 'staff',
            'akkii' => $isAdmin || $userRole === 'staff',
            'products' => $isAdmin || $userRole === 'staff',
            'customers' => $isAdmin || $userRole === 'staff',
            'bulanan-bksda' => $isAdmin || $userRole === 'staff',
            'triwulan-bksda' => $isAdmin || $userRole === 'staff',
            'tahunan-bksda' => $isAdmin || $userRole === 'staff',
            'users' => $isAdmin, // Hanya admin yang dapat mengakses pengguna
        ];
        
        return $menuPermissions[$menuKey] ?? false;
    }
}