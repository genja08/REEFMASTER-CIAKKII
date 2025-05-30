<?php

namespace App\Exports;

use App\Models\AKKII;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class AKKIIBulananExport implements FromCollection, WithEvents, WithTitle
{
    protected $startDate;
    protected $endDate;
    protected $reportTitle;

    public function __construct($startDate = null, $endDate = null, $reportTitle = 'Laporan Bulanan AKKII')
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->reportTitle = $reportTitle;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        // Hanya mengembalikan koleksi kosong karena kita akan mengisi data secara manual di event afterSheet
        return new Collection([]);
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Laporan Bulanan AKKII';
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Set page orientation to landscape and paper size to A4
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                
                // Set title
                $sheet->setCellValue('A1', $this->reportTitle);
                $sheet->mergeCells('A1:Z1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Set date range
                $dateRange = 'Periode Tanggal Terbit: ';
                if ($this->startDate) {
                    $dateRange .= Carbon::parse($this->startDate)->format('d/m/Y');
                }
                $dateRange .= ' - ';
                if ($this->endDate) {
                    $dateRange .= Carbon::parse($this->endDate)->format('d/m/Y');
                }
                $sheet->setCellValue('A2', $dateRange);
                $sheet->mergeCells('A2:Z2');
                $sheet->getStyle('A2')->getFont()->setBold(true);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Get AKKII data
                $query = AKKII::with(['customer', 'citesDocument', 'items.product'])
                    ->orderBy('tanggal_terbit', 'asc'); // Diurutkan berdasarkan tanggal terbit

                if ($this->startDate) {
                    $query->whereDate('tanggal_terbit', '>=', $this->startDate);
                }

                if ($this->endDate) {
                    $query->whereDate('tanggal_terbit', '<=', $this->endDate);
                }

                $akkiis = $query->get();
                
                // Jika tidak ada data
                if ($akkiis->isEmpty()) {
                    $sheet->setCellValue('A4', 'Tidak ada data untuk periode ini');
                    $sheet->mergeCells('A4:Z4');
                    $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    return;
                }
                
                // Mulai baris untuk data
                $startRow = 4;
                $currentCol = 'A';
                
                // Untuk setiap 3 AKKII, kita buat satu baris baru
                $akkiiCount = 0;
                $totalAkkii = $akkiis->count();
                
                foreach ($akkiis as $index => $akkii) {
                    // Jika sudah 3 AKKII, pindah ke baris baru
                    if ($akkiiCount % 3 == 0 && $akkiiCount > 0) {
                        $startRow += 20; // Tinggi untuk satu blok AKKII
                        $currentCol = 'A';
                    }
                    
                    // Kolom untuk AKKII ini
                    $endCol = $this->getColumnId(ord($currentCol) + 8); // 9 kolom per AKKII
                    
                    // Header untuk AKKII
                    $sheet->setCellValue($currentCol . $startRow, 'NOMOR CITES: ' . $akkii->nomor_cites);
                    $sheet->mergeCells($currentCol . $startRow . ':' . $endCol . $startRow);
                    $sheet->getStyle($currentCol . $startRow . ':' . $endCol . $startRow)->getFont()->setBold(true);
                    $sheet->getStyle($currentCol . $startRow . ':' . $endCol . $startRow)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFCCCCCC');
                    $sheet->getStyle($currentCol . $startRow . ':' . $endCol . $startRow)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    
                    // Informasi AKKII
                    $row = $startRow + 1;
                    $sheet->setCellValue($currentCol . $row, 'Tanggal Terbit:');
                    $sheet->setCellValue($currentCol . ($row+1), $akkii->tanggal_terbit ? Carbon::parse($akkii->tanggal_terbit)->format('d/m/Y') : '-');
                    
                    $sheet->setCellValue($currentCol . ($row+2), 'Tanggal Expired:');
                    $sheet->setCellValue($currentCol . ($row+3), $akkii->tanggal_expired ? Carbon::parse($akkii->tanggal_expired)->format('d/m/Y') : '-');
                    
                    $sheet->setCellValue($currentCol . ($row+4), 'Perusahaan:');
                    $sheet->setCellValue($currentCol . ($row+5), $akkii->customer->company_name ?? '-');
                    
                    $sheet->setCellValue($currentCol . ($row+6), 'Negara:');
                    $sheet->setCellValue($currentCol . ($row+7), $akkii->country ?? '-');
                    
                    // Header untuk items
                    $itemCol = $this->getColumnId(ord($currentCol) + 3); // Kolom untuk items (D jika currentCol adalah A)
                    $sheet->setCellValue($itemCol . $row, 'Nama Latin');
                    $sheet->setCellValue($itemCol . ($row+2), 'Jumlah Kirim');
                    $sheet->setCellValue($itemCol . ($row+4), 'Sisa');
                    
                    // Styling untuk header items
                    $sheet->getStyle($itemCol . $row)->getFont()->setBold(true);
                    $sheet->getStyle($itemCol . ($row+2))->getFont()->setBold(true);
                    $sheet->getStyle($itemCol . ($row+4))->getFont()->setBold(true);
                    
                    // Data items
                    $itemCol = $this->getColumnId(ord($itemCol) + 1); // Kolom untuk data items (E jika itemCol adalah D)
                    
                    // Jika ada items
                    if ($akkii->items->count() > 0) {
                        $itemRow = $row;
                        foreach ($akkii->items as $item) {
                            $sheet->setCellValue($itemCol . $itemRow, $item->product->nama_latin ?? '-');
                            $sheet->setCellValue($itemCol . ($itemRow+2), $item->qty_cites ?? 0);
                            $sheet->setCellValue($itemCol . ($itemRow+4), $item->qty_sisa ?? 0);
                            
                            $itemRow += 6; // Jarak antar item
                        }
                    } else {
                        $sheet->setCellValue($itemCol . $row, '-');
                        $sheet->setCellValue($itemCol . ($row+2), 0);
                        $sheet->setCellValue($itemCol . ($row+4), 0);
                    }
                    
                    // Informasi tambahan
                    $row = $startRow + 10;
                    $sheet->setCellValue($currentCol . $row, 'Tanggal Ekspor:');
                    $sheet->setCellValue($currentCol . ($row+1), $akkii->tanggal_ekspor ? Carbon::parse($akkii->tanggal_ekspor)->format('d/m/Y') : '-');
                    
                    $sheet->setCellValue($currentCol . ($row+2), 'No. AWB:');
                    $sheet->setCellValue($currentCol . ($row+3), $akkii->no_awb ?? '-');
                    
                    $sheet->setCellValue($currentCol . ($row+4), 'No. Aju:');
                    $sheet->setCellValue($currentCol . ($row+5), $akkii->no_aju ?? '-');
                    
                    $sheet->setCellValue($currentCol . ($row+6), 'No. Pendaftaran:');
                    $sheet->setCellValue($currentCol . ($row+7), $akkii->no_pendaftaran ?? '-');
                    
                    // Border untuk seluruh blok AKKII
                    $styleArray = [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                    ];
                    $sheet->getStyle($currentCol . $startRow . ':' . $endCol . ($startRow + 18))->applyFromArray($styleArray);
                    
                    // Pindah ke kolom berikutnya untuk AKKII selanjutnya
                    $currentCol = $this->getColumnId(ord($endCol) + 1);
                    $akkiiCount++;
                }
                
                // Auto-size columns untuk kolom yang digunakan
                // Kita tahu bahwa setiap AKKII menggunakan 9 kolom, dan maksimal 3 AKKII per baris
                // Jadi kita hanya perlu mengatur auto-size untuk kolom A sampai Z (lebih dari cukup)
                foreach (range('A', 'Z') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
    
    /**
     * Get column letter from column index
     * 
     * @param int $index
     * @return string
     */
    private function getColumnId($index)
    {
        $letter = '';
        while ($index > 0) {
            $temp = ($index - 1) % 26;
            $letter = chr($temp + 65) . $letter;
            $index = ($index - $temp - 1) / 26;
        }
        return $letter;
    }
}