<?php

namespace App\Exports;

use App\Models\AKKII;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class AKKIIBulan2023Export implements WithMultipleSheets
{
    protected $startDate;
    protected $endDate;
    protected $reportTitle;

    public function __construct($startDate = null, $endDate = null, $reportTitle = 'Rekapitulasi Realisasi CITES')
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->reportTitle = $reportTitle;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];
        
        // Sheet 1: Bulan 2023
        $sheets[] = new AKKIIBulan2023Sheet($this->startDate, $this->endDate, $this->reportTitle);
        
        // Sheet 2: Global
        $sheets[] = new AKKIIGlobalSheet($this->startDate, $this->endDate, $this->reportTitle);
        
        // Sheet 3: SKW
        $sheets[] = new AKKIISKWSheet($this->startDate, $this->endDate, $this->reportTitle);
        
        return $sheets;
    }
}

/**
 * Sheet 1: Bulan 2023
 */
class AKKIIBulan2023Sheet implements FromCollection, WithEvents, WithTitle
{
    protected $startDate;
    protected $endDate;
    protected $reportTitle;

    public function __construct($startDate = null, $endDate = null, $reportTitle = 'Rekapitulasi Realisasi CITES')
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
        // Mendapatkan nama bulan dan tahun dari tanggal
        $month = '';
        $year = '';
        
        if ($this->startDate) {
            $date = Carbon::parse($this->startDate);
            $month = $date->translatedFormat('F');
            $year = $date->format('Y');
        } else {
            $date = Carbon::now();
            $month = $date->translatedFormat('F');
            $year = $date->format('Y');
        }
        
        return "Bulan {$month} {$year}";
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
                
                // Set title
                $sheet->setCellValue('A1', strtoupper($this->reportTitle));
                $sheet->mergeCells('A1:L1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Set subtitle
                $month = '';
                $year = '';
                
                if ($this->startDate) {
                    $date = Carbon::parse($this->startDate);
                    $month = $date->translatedFormat('F');
                    $year = $date->format('Y');
                } else {
                    $date = Carbon::now();
                    $month = $date->translatedFormat('F');
                    $year = $date->format('Y');
                }
                
                $sheet->setCellValue('A2', strtoupper("BULAN {$month} {$year}"));
                $sheet->mergeCells('A2:L2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Header table
                $headers = [
                    'NO', 'NOMOR CITES', 'TANGGAL TERBIT', 'TANGGAL EXPIRED', 
                    'NAMA PERUSAHAAN', 'NEGARA TUJUAN', 'NAMA LATIN', 
                    'JUMLAH KUOTA', 'JUMLAH REALISASI', 'SISA KUOTA', 'KETERANGAN'
                ];
                
                $row = 4;
                foreach ($headers as $index => $header) {
                    $col = chr(65 + $index); // A, B, C, ...
                    $sheet->setCellValue("{$col}{$row}", $header);
                    $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
                    $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("{$col}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFCCCCCC');
                }
                
                // Get AKKII data
                $query = AKKII::with(['customer', 'citesDocument', 'items.product'])
                    ->orderBy('tanggal_terbit', 'asc');

                if ($this->startDate) {
                    $query->whereDate('tanggal_terbit', '>=', $this->startDate);
                }

                if ($this->endDate) {
                    $query->whereDate('tanggal_terbit', '<=', $this->endDate);
                }

                $akkiis = $query->get();
                
                // Jika tidak ada data
                if ($akkiis->isEmpty()) {
                    $row++;
                    $sheet->setCellValue('A' . $row, 'Tidak ada data untuk periode ini');
                    $sheet->mergeCells('A' . $row . ':K' . $row);
                    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    return;
                }
                
                // Fill data
                $row = 5;
                $no = 1;
                
                foreach ($akkiis as $akkii) {
                    if ($akkii->items->count() > 0) {
                        foreach ($akkii->items as $item) {
                            $sheet->setCellValue('A' . $row, $no);
                            $sheet->setCellValue('B' . $row, $akkii->nomor_cites);
                            $sheet->setCellValue('C' . $row, $akkii->tanggal_terbit ? Carbon::parse($akkii->tanggal_terbit)->format('d/m/Y') : '-');
                            $sheet->setCellValue('D' . $row, $akkii->tanggal_expired ? Carbon::parse($akkii->tanggal_expired)->format('d/m/Y') : '-');
                            $sheet->setCellValue('E' . $row, $akkii->customer->company_name ?? '-');
                            $sheet->setCellValue('F' . $row, $akkii->country ?? '-');
                            $sheet->setCellValue('G' . $row, $item->product->nama_latin ?? '-');
                            $sheet->setCellValue('H' . $row, $item->qty_cites ?? 0);
                            $sheet->setCellValue('I' . $row, $item->qty_realisasi ?? 0);
                            $sheet->setCellValue('J' . $row, $item->qty_sisa ?? 0);
                            $sheet->setCellValue('K' . $row, $item->keterangan ?? '-');
                            
                            $row++;
                            $no++;
                        }
                    } else {
                        $sheet->setCellValue('A' . $row, $no);
                        $sheet->setCellValue('B' . $row, $akkii->nomor_cites);
                        $sheet->setCellValue('C' . $row, $akkii->tanggal_terbit ? Carbon::parse($akkii->tanggal_terbit)->format('d/m/Y') : '-');
                        $sheet->setCellValue('D' . $row, $akkii->tanggal_expired ? Carbon::parse($akkii->tanggal_expired)->format('d/m/Y') : '-');
                        $sheet->setCellValue('E' . $row, $akkii->customer->company_name ?? '-');
                        $sheet->setCellValue('F' . $row, $akkii->country ?? '-');
                        $sheet->setCellValue('G' . $row, '-');
                        $sheet->setCellValue('H' . $row, 0);
                        $sheet->setCellValue('I' . $row, 0);
                        $sheet->setCellValue('J' . $row, 0);
                        $sheet->setCellValue('K' . $row, '-');
                        
                        $row++;
                        $no++;
                    }
                }
                
                // Border untuk seluruh tabel
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ];
                $sheet->getStyle('A4:K' . ($row - 1))->applyFromArray($styleArray);
                
                // Auto-size columns
                foreach (range('A', 'K') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}

/**
 * Sheet 2: Global
 */
class AKKIIGlobalSheet implements FromCollection, WithEvents, WithTitle
{
    protected $startDate;
    protected $endDate;
    protected $reportTitle;

    public function __construct($startDate = null, $endDate = null, $reportTitle = 'Rekapitulasi Realisasi CITES')
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
        return "Global";
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
                
                // Set title
                $sheet->setCellValue('A1', strtoupper($this->reportTitle));
                $sheet->mergeCells('A1:I1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Set subtitle
                $sheet->setCellValue('A2', strtoupper("GLOBAL"));
                $sheet->mergeCells('A2:I2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Header table
                $headers = [
                    'NO', 'NAMA LATIN', 'JUMLAH KUOTA', 'JUMLAH REALISASI', 
                    'SISA KUOTA', 'PERSENTASE REALISASI', 'KETERANGAN'
                ];
                
                $row = 4;
                foreach ($headers as $index => $header) {
                    $col = chr(65 + $index); // A, B, C, ...
                    $sheet->setCellValue("{$col}{$row}", $header);
                    $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
                    $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("{$col}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFCCCCCC');
                }
                
                // Get AKKII data grouped by product
                $query = AKKII::with(['items.product'])
                    ->whereHas('items.product')
                    ->orderBy('tanggal_terbit', 'asc');

                if ($this->startDate) {
                    $query->whereDate('tanggal_terbit', '>=', $this->startDate);
                }

                if ($this->endDate) {
                    $query->whereDate('tanggal_terbit', '<=', $this->endDate);
                }

                $akkiis = $query->get();
                
                // Jika tidak ada data
                if ($akkiis->isEmpty()) {
                    $row++;
                    $sheet->setCellValue('A' . $row, 'Tidak ada data untuk periode ini');
                    $sheet->mergeCells('A' . $row . ':G' . $row);
                    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    return;
                }
                
                // Group data by product
                $productData = [];
                
                foreach ($akkiis as $akkii) {
                    foreach ($akkii->items as $item) {
                        if (!$item->product) continue;
                        
                        $productId = $item->product->id;
                        $productName = $item->product->nama_latin;
                        
                        if (!isset($productData[$productId])) {
                            $productData[$productId] = [
                                'nama_latin' => $productName,
                                'qty_cites' => 0,
                                'qty_realisasi' => 0,
                                'qty_sisa' => 0,
                                'keterangan' => ''
                            ];
                        }
                        
                        $productData[$productId]['qty_cites'] += $item->qty_cites ?? 0;
                        $productData[$productId]['qty_realisasi'] += $item->qty_realisasi ?? 0;
                        $productData[$productId]['qty_sisa'] += $item->qty_sisa ?? 0;
                    }
                }
                
                // Fill data
                $row = 5;
                $no = 1;
                
                foreach ($productData as $productId => $data) {
                    $sheet->setCellValue('A' . $row, $no);
                    $sheet->setCellValue('B' . $row, $data['nama_latin']);
                    $sheet->setCellValue('C' . $row, $data['qty_cites']);
                    $sheet->setCellValue('D' . $row, $data['qty_realisasi']);
                    $sheet->setCellValue('E' . $row, $data['qty_sisa']);
                    
                    // Calculate percentage
                    $percentage = 0;
                    if ($data['qty_cites'] > 0) {
                        $percentage = ($data['qty_realisasi'] / $data['qty_cites']) * 100;
                    }
                    $sheet->setCellValue('F' . $row, number_format($percentage, 2) . '%');
                    
                    $sheet->setCellValue('G' . $row, $data['keterangan']);
                    
                    $row++;
                    $no++;
                }
                
                // Border untuk seluruh tabel
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ];
                $sheet->getStyle('A4:G' . ($row - 1))->applyFromArray($styleArray);
                
                // Auto-size columns
                foreach (range('A', 'G') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}

/**
 * Sheet 3: SKW
 */
class AKKIISKWSheet implements FromCollection, WithEvents, WithTitle
{
    protected $startDate;
    protected $endDate;
    protected $reportTitle;

    public function __construct($startDate = null, $endDate = null, $reportTitle = 'Rekapitulasi Realisasi CITES')
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
        return "SKW";
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
                
                // Set title
                $sheet->setCellValue('A1', strtoupper($this->reportTitle));
                $sheet->mergeCells('A1:I1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Set subtitle
                $sheet->setCellValue('A2', strtoupper("SKW"));
                $sheet->mergeCells('A2:I2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Header table
                $headers = [
                    'NO', 'NAMA PERUSAHAAN', 'NOMOR CITES', 'TANGGAL TERBIT', 
                    'TANGGAL EXPIRED', 'NAMA LATIN', 'JUMLAH KUOTA', 
                    'JUMLAH REALISASI', 'SISA KUOTA', 'KETERANGAN'
                ];
                
                $row = 4;
                foreach ($headers as $index => $header) {
                    $col = chr(65 + $index); // A, B, C, ...
                    $sheet->setCellValue("{$col}{$row}", $header);
                    $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
                    $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("{$col}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFCCCCCC');
                }
                
                // Get AKKII data grouped by company
                $query = AKKII::with(['customer', 'items.product'])
                    ->whereHas('customer')
                    ->orderBy('tanggal_terbit', 'asc');

                if ($this->startDate) {
                    $query->whereDate('tanggal_terbit', '>=', $this->startDate);
                }

                if ($this->endDate) {
                    $query->whereDate('tanggal_terbit', '<=', $this->endDate);
                }

                $akkiis = $query->get();
                
                // Jika tidak ada data
                if ($akkiis->isEmpty()) {
                    $row++;
                    $sheet->setCellValue('A' . $row, 'Tidak ada data untuk periode ini');
                    $sheet->mergeCells('A' . $row . ':J' . $row);
                    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    return;
                }
                
                // Group data by company
                $companyData = [];
                
                foreach ($akkiis as $akkii) {
                    if (!$akkii->customer) continue;
                    
                    $companyId = $akkii->customer->id;
                    $companyName = $akkii->customer->company_name;
                    
                    if (!isset($companyData[$companyId])) {
                        $companyData[$companyId] = [
                            'company_name' => $companyName,
                            'akkiis' => []
                        ];
                    }
                    
                    $companyData[$companyId]['akkiis'][] = $akkii;
                }
                
                // Fill data
                $row = 5;
                $no = 1;
                
                foreach ($companyData as $companyId => $data) {
                    $firstRow = $row;
                    $totalRows = 0;
                    
                    foreach ($data['akkiis'] as $akkii) {
                        if ($akkii->items->count() > 0) {
                            foreach ($akkii->items as $item) {
                                $sheet->setCellValue('C' . $row, $akkii->nomor_cites);
                                $sheet->setCellValue('D' . $row, $akkii->tanggal_terbit ? Carbon::parse($akkii->tanggal_terbit)->format('d/m/Y') : '-');
                                $sheet->setCellValue('E' . $row, $akkii->tanggal_expired ? Carbon::parse($akkii->tanggal_expired)->format('d/m/Y') : '-');
                                $sheet->setCellValue('F' . $row, $item->product->nama_latin ?? '-');
                                $sheet->setCellValue('G' . $row, $item->qty_cites ?? 0);
                                $sheet->setCellValue('H' . $row, $item->qty_realisasi ?? 0);
                                $sheet->setCellValue('I' . $row, $item->qty_sisa ?? 0);
                                $sheet->setCellValue('J' . $row, $item->keterangan ?? '-');
                                
                                $row++;
                                $totalRows++;
                            }
                        } else {
                            $sheet->setCellValue('C' . $row, $akkii->nomor_cites);
                            $sheet->setCellValue('D' . $row, $akkii->tanggal_terbit ? Carbon::parse($akkii->tanggal_terbit)->format('d/m/Y') : '-');
                            $sheet->setCellValue('E' . $row, $akkii->tanggal_expired ? Carbon::parse($akkii->tanggal_expired)->format('d/m/Y') : '-');
                            $sheet->setCellValue('F' . $row, '-');
                            $sheet->setCellValue('G' . $row, 0);
                            $sheet->setCellValue('H' . $row, 0);
                            $sheet->setCellValue('I' . $row, 0);
                            $sheet->setCellValue('J' . $row, '-');
                            
                            $row++;
                            $totalRows++;
                        }
                    }
                    
                    // Set company name and number
                    if ($totalRows > 0) {
                        $sheet->setCellValue('A' . $firstRow, $no);
                        $sheet->setCellValue('B' . $firstRow, $data['company_name']);
                        
                        if ($totalRows > 1) {
                            $sheet->mergeCells('A' . $firstRow . ':A' . ($firstRow + $totalRows - 1));
                            $sheet->mergeCells('B' . $firstRow . ':B' . ($firstRow + $totalRows - 1));
                        }
                        
                        $no++;
                    }
                }
                
                // Border untuk seluruh tabel
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ];
                $sheet->getStyle('A4:J' . ($row - 1))->applyFromArray($styleArray);
                
                // Auto-size columns
                foreach (range('A', 'J') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}