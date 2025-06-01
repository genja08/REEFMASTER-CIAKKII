<?php

namespace App\Filament\Resources\AKKIIResource\Pages;

use App\Exports\AKKIIBulananFormatExport;
use App\Exports\AKKIIGlobalFormatExport;
use App\Exports\AKKIISKWFormatExport;
use App\Filament\Resources\AKKIIResource;
use App\Models\AKKII;
use Filament\Actions;
use Filament\Forms\Components\DatePicker; // Keep for other exports if needed, or remove if not used elsewhere in this file
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class ListAKKIIS extends ListRecords
{
    protected static string $resource = AKKIIResource::class;
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua AKKII')
                ->badge(AKKII::query()->count()),
            'alam' => Tab::make('Alam')
                ->badge(AKKII::query()->where('jenis_akkii', 'Alam')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_akkii', 'Alam')),
            'transplan' => Tab::make('Transplan')
                ->badge(AKKII::query()->where('jenis_akkii', 'Transplan')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_akkii', 'Transplan')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
                
            // Export Format Bulanan (Alam)
            Actions\Action::make('exportBulananAlamFormat')
                ->label('Export Bulanan (Alam)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info') // Changed color for distinction
                ->form([
                    Select::make('month')
                        ->label('Bulan Ekspor')
                        ->options([
                            '1' => 'Januari',
                            '2' => 'Februari',
                            '3' => 'Maret',
                            '4' => 'April',
                            '5' => 'Mei',
                            '6' => 'Juni',
                            '7' => 'Juli',
                            '8' => 'Agustus',
                            '9' => 'September',
                            '10' => 'Oktober',
                            '11' => 'November',
                            '12' => 'Desember',
                        ])
                        ->default(now()->month)
                        ->required(),
                    TextInput::make('year')
                        ->label('Tahun Ekspor')
                        ->numeric()
                        ->default(now()->year)
                        ->required()
                        ->minValue(2000)
                        ->maxValue(2100),
                ])
                ->action(function (array $data) {
                    $month = $data['month'];
                    $year = $data['year'];
                    $jenisAkkii = 'Alam'; // Specify type
                    $monthName = \DateTime::createFromFormat('!m', $month)->format('F');

                    // Generate filename
                    $filename = 'rekap-akkii-bulanan-alam-' . strtolower($monthName) . '-' . $year . '.xlsx';
                    
                    // Create export instance
                    $export = new AKKIIBulananFormatExport(
                        $month,
                        $year,
                        $jenisAkkii
                    );
                    
                    // Download file
                    try {
                        // Show success notification before download
                        Notification::make()
                            ->success()
                            ->title('Export berhasil')
                            ->body('File Excel Format Bulanan (Alam) sedang diunduh')
                            ->send();
                            
                        return Excel::download($export, $filename);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Excel export error (Alam): ' . $e->getMessage());
                        Notification::make()
                            ->danger()
                            ->title('Error exporting file (Alam)')
                            ->body($e->getMessage())
                            ->send();
                        return null;
                    }
                }),

            // Export Format Bulanan (Transplan)
            Actions\Action::make('exportBulananTransplanFormat')
                ->label('Export Bulanan (Transplan)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning') // Changed color for distinction
                ->form([
                    Select::make('month')
                        ->label('Bulan Ekspor')
                        ->options([
                            '1' => 'Januari',
                            '2' => 'Februari',
                            '3' => 'Maret',
                            '4' => 'April',
                            '5' => 'Mei',
                            '6' => 'Juni',
                            '7' => 'Juli',
                            '8' => 'Agustus',
                            '9' => 'September',
                            '10' => 'Oktober',
                            '11' => 'November',
                            '12' => 'Desember',
                        ])
                        ->default(now()->month)
                        ->required(),
                    TextInput::make('year')
                        ->label('Tahun Ekspor')
                        ->numeric()
                        ->default(now()->year)
                        ->required()
                        ->minValue(2000)
                        ->maxValue(2100),
                ])
                ->action(function (array $data) {
                    $month = $data['month'];
                    $year = $data['year'];
                    $jenisAkkii = 'Transplan'; // Specify type
                    $monthName = \DateTime::createFromFormat('!m', $month)->format('F');

                    // Generate filename
                    $filename = 'rekap-akkii-bulanan-transplan-' . strtolower($monthName) . '-' . $year . '.xlsx';
                    
                    // Create export instance
                    $export = new AKKIIBulananFormatExport(
                        $month,
                        $year,
                        $jenisAkkii
                    );
                    
                    // Download file
                    try {
                        Notification::make()
                            ->success()
                            ->title('Export berhasil')
                            ->body('File Excel Format Bulanan (Transplan) sedang diunduh')
                            ->send();
                            
                        return Excel::download($export, $filename);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Excel export error (Transplan): ' . $e->getMessage());
                        Notification::make()
                            ->danger()
                            ->title('Error exporting file (Transplan)')
                            ->body($e->getMessage())
                            ->send();
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
                        
                    Select::make('jenis_akkii')
                        ->label('Jenis AKKII')
                        ->options([
                            'Alam' => 'Alam',
                            'Transplan' => 'Transplan',
                        ])
                        ->placeholder('Semua Jenis'),
                ])
                ->action(function (array $data) {
                    // Get jenis_akkii for filename
                    $jenisAkkii = $data['jenis_akkii'] ?? null;
                    $jenisText = $jenisAkkii ? '-' . strtolower($jenisAkkii) : '';
                    
                    // Generate filename
                    $filename = Str::slug($data['report_title']) . '-global' . $jenisText . '-' . 
                        ($data['start_date'] ? date('d-m-Y', strtotime($data['start_date'])) : 'all') . 
                        '-to-' . 
                        ($data['end_date'] ? date('d-m-Y', strtotime($data['end_date'])) : 'all') . 
                        '.xlsx';
                    
                    // Create export instance
                    $export = new AKKIIGlobalFormatExport(
                        $data['start_date'] ?? null,
                        $data['end_date'] ?? null,
                        $data['report_title'] ?? 'Rekapitulasi Realisasi CITES',
                        $jenisAkkii
                    );
                    
                    // Download file
                    try {
                        // Show success notification before download
                        $jenisText = $jenisAkkii ? " ({$jenisAkkii})" : "";
                        Notification::make()
                            ->success()
                            ->title('Export berhasil')
                            ->body("File Excel Format Global{$jenisText} sedang diunduh")
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
                        
                    Select::make('jenis_akkii')
                        ->label('Jenis AKKII')
                        ->options([
                            'Alam' => 'Alam',
                            'Transplan' => 'Transplan',
                        ])
                        ->placeholder('Semua Jenis'),
                ])
                ->action(function (array $data) {
                    // Get jenis_akkii for filename
                    $jenisAkkii = $data['jenis_akkii'] ?? null;
                    $jenisText = $jenisAkkii ? '-' . strtolower($jenisAkkii) : '';
                    
                    // Generate filename
                    $filename = Str::slug($data['report_title']) . '-skw' . $jenisText . '-' . 
                        ($data['start_date'] ? date('d-m-Y', strtotime($data['start_date'])) : 'all') . 
                        '-to-' . 
                        ($data['end_date'] ? date('d-m-Y', strtotime($data['end_date'])) : 'all') . 
                        '.xlsx';
                    
                    // Create export instance
                    $export = new AKKIISKWFormatExport(
                        $data['start_date'] ?? null,
                        $data['end_date'] ?? null,
                        $data['report_title'] ?? 'Rekapitulasi Realisasi CITES',
                        $jenisAkkii
                    );
                    
                    // Download file
                    try {
                        // Show success notification before download
                        $jenisText = $jenisAkkii ? " ({$jenisAkkii})" : "";
                        Notification::make()
                            ->success()
                            ->title('Export berhasil')
                            ->body("File Excel Format SKW{$jenisText} sedang diunduh")
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
