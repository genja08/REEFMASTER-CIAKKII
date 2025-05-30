<?php

namespace App\Filament\Resources\AKKIIResource\Pages;

use App\Exports\AKKIIBulananFormatExport;
use App\Exports\AKKIIGlobalFormatExport;
use App\Exports\AKKIISKWFormatExport;
use App\Filament\Resources\AKKIIResource;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class ListAKKIIS extends ListRecords
{
    protected static string $resource = AKKIIResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
                
            // Tambahkan action untuk export Excel format Bulanan
            Actions\Action::make('exportBulananFormat')
                ->label('Export Format Bulanan')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->form([
                    DatePicker::make('start_date')
                        ->label('Tanggal Terbit Awal')
                        ->default(now()->startOfMonth()),
                    
                    DatePicker::make('end_date')
                        ->label('Tanggal Terbit Akhir')
                        ->default(now()->endOfMonth()),
                    
                    TextInput::make('report_title')
                        ->label('Judul Laporan')
                        ->default('Rekapitulasi Realisasi CITES')
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Generate filename
                    $filename = Str::slug($data['report_title']) . '-bulanan-' . 
                        ($data['start_date'] ? date('d-m-Y', strtotime($data['start_date'])) : 'all') . 
                        '-to-' . 
                        ($data['end_date'] ? date('d-m-Y', strtotime($data['end_date'])) : 'all') . 
                        '.xlsx';
                    
                    // Create export instance
                    $export = new AKKIIBulananFormatExport(
                        $data['start_date'] ?? null,
                        $data['end_date'] ?? null,
                        $data['report_title'] ?? 'Rekapitulasi Realisasi CITES'
                    );
                    
                    // Download file
                    try {
                        // Show success notification before download
                        Notification::make()
                            ->success()
                            ->title('Export berhasil')
                            ->body('File Excel Format Bulanan sedang diunduh')
                            ->send();
                            
                        return Excel::download($export, $filename);
                    } catch (\Exception $e) {
                        // Log error
                        \Illuminate\Support\Facades\Log::error('Excel export error: ' . $e->getMessage());
                        
                        // Show error notification
                        Notification::make()
                            ->danger()
                            ->title('Error exporting file')
                            ->body($e->getMessage())
                            ->send();
                        
                        // Return null to prevent redirect
                        return null;
                    }
                }),
                
            // Tambahkan action untuk export Excel format Global
            Actions\Action::make('exportGlobalFormat')
                ->label('Export Format Global')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    DatePicker::make('start_date')
                        ->label('Tanggal Terbit Awal')
                        ->default(now()->startOfMonth()),
                    
                    DatePicker::make('end_date')
                        ->label('Tanggal Terbit Akhir')
                        ->default(now()->endOfMonth()),
                    
                    TextInput::make('report_title')
                        ->label('Judul Laporan')
                        ->default('Rekapitulasi Realisasi CITES')
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Generate filename
                    $filename = Str::slug($data['report_title']) . '-global-' . 
                        ($data['start_date'] ? date('d-m-Y', strtotime($data['start_date'])) : 'all') . 
                        '-to-' . 
                        ($data['end_date'] ? date('d-m-Y', strtotime($data['end_date'])) : 'all') . 
                        '.xlsx';
                    
                    // Create export instance
                    $export = new AKKIIGlobalFormatExport(
                        $data['start_date'] ?? null,
                        $data['end_date'] ?? null,
                        $data['report_title'] ?? 'Rekapitulasi Realisasi CITES'
                    );
                    
                    // Download file
                    try {
                        // Show success notification before download
                        Notification::make()
                            ->success()
                            ->title('Export berhasil')
                            ->body('File Excel Format Global sedang diunduh')
                            ->send();
                            
                        return Excel::download($export, $filename);
                    } catch (\Exception $e) {
                        // Log error
                        \Illuminate\Support\Facades\Log::error('Excel export error: ' . $e->getMessage());
                        
                        // Show error notification
                        Notification::make()
                            ->danger()
                            ->title('Error exporting file')
                            ->body($e->getMessage())
                            ->send();
                        
                        // Return null to prevent redirect
                        return null;
                    }
                }),
                
            // Tambahkan action untuk export Excel format SKW
            Actions\Action::make('exportSKWFormat')
                ->label('Export Format SKW')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->form([
                    DatePicker::make('start_date')
                        ->label('Tanggal Terbit Awal')
                        ->default(now()->startOfMonth()),
                    
                    DatePicker::make('end_date')
                        ->label('Tanggal Terbit Akhir')
                        ->default(now()->endOfMonth()),
                    
                    TextInput::make('report_title')
                        ->label('Judul Laporan')
                        ->default('Rekapitulasi Realisasi CITES')
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Generate filename
                    $filename = Str::slug($data['report_title']) . '-skw-' . 
                        ($data['start_date'] ? date('d-m-Y', strtotime($data['start_date'])) : 'all') . 
                        '-to-' . 
                        ($data['end_date'] ? date('d-m-Y', strtotime($data['end_date'])) : 'all') . 
                        '.xlsx';
                    
                    // Create export instance
                    $export = new AKKIISKWFormatExport(
                        $data['start_date'] ?? null,
                        $data['end_date'] ?? null,
                        $data['report_title'] ?? 'Rekapitulasi Realisasi CITES'
                    );
                    
                    // Download file
                    try {
                        // Show success notification before download
                        Notification::make()
                            ->success()
                            ->title('Export berhasil')
                            ->body('File Excel Format SKW sedang diunduh')
                            ->send();
                            
                        return Excel::download($export, $filename);
                    } catch (\Exception $e) {
                        // Log error
                        \Illuminate\Support\Facades\Log::error('Excel export error: ' . $e->getMessage());
                        
                        // Show error notification
                        Notification::make()
                            ->danger()
                            ->title('Error exporting file')
                            ->body($e->getMessage())
                            ->send();
                        
                        // Return null to prevent redirect
                        return null;
                    }
                }),
        ];
    }
}
