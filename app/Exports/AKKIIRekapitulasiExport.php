<?php

namespace App\Exports;

use App\Models\AKKII;
use App\Models\Product;
use App\Models\DataCustomer;
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
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class AKKIIRekapitulasiExport implements WithMultipleSheets
{
    protected $startDate;
    protected $endDate;
    protected $reportTitle;
    protected $month;
    protected $year;

    public function __construct($startDate = null, $endDate = null, $reportTitle = 'Rekapitulasi Realisasi CITES')
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->reportTitle = $reportTitle;
        
        // Mendapatkan bulan dan tahun dari tanggal
        if ($this->startDate) {
            $date = Carbon::parse($this->startDate);
            $this->month = $date->translatedFormat('F');
            $this->year = $date->format('Y');
        } else {
            $date = Carbon::now();
            $this->month = $date->translatedFormat('F');
            $this->year = $date->format('Y');
        }
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];
        
        // Sheet 1: Bulan 2023 (atau tahun yang sesuai)
        $sheets[] = new AKKIIBulanSheet($this->startDate, $this->endDate, $this->reportTitle, $this->month, $this->year);
        
        // Sheet 2: Global
        $sheets[] = new AKKIIGlobalSheet($this->startDate, $this->endDate, $this->reportTitle);
        
        // Sheet 3: SKW
        $sheets[] = new AKKIISKWSheet($this->startDate, $this->endDate, $this->reportTitle);
        
        return $sheets;
    }
}

/**
 * Sheet 1: Bulan YYYY
 */
class AKKIIBulanSheet implements FromCollection, WithEvents, WithTitle
{
    protected $startDate;
    protected $endDate;
    protected $reportTitle;
    protected $month;
    protected $year;

    public function __construct($startDate = null, $endDate = null, $reportTitle = 'Rekapitulasi Realisasi CITES', $month = null, $year = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->reportTitle = $reportTitle;
        $this->month = $month;
        $this->year = $year;
        
        if (!$this->month || !$this->year) {
            $date = $startDate ? Carbon::parse($startDate) : Carbon::now();
            $this->month = $date->translatedFormat('F');
            $this->year = $date->format('Y');
        }
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
        return "Bulan {$this->month} {$this->year}";
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
                $sheet->setCellValue('A1', strtoupper($this->reportTitle));
                $sheet->mergeCells('A1:K1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Set subtitle
                $sheet->setCellValue('A2', strtoupper("BULAN {$this->month} {$this->year}"));
                $sheet->mergeCells('A2:K2');
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
                    $sheet->getStyle("{$col}{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
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
                            
                            // Align numbers to center
                            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            
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
                        
                        // Align numbers to center
                        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        
                        $row++;
                        $no++;
                    }
                }
                
                // Add total row
                $lastRow = $row - 1;
                $sheet->setCellValue('G' . $row, 'TOTAL');
                $sheet->getStyle('G' . $row)->getFont()->setBold(true);
                
                // Sum columns H, I, J
                $sheet->setCellValue('H' . $row, '=SUM(H5:H' . $lastRow . ')');
                $sheet->setCellValue('I' . $row, '=SUM(I5:I' . $lastRow . ')');
                $sheet->setCellValue('J' . $row, '=SUM(J5:J' . $lastRow . ')');
                
                // Format total row
                $sheet->getStyle('G' . $row . ':K' . $row)->getFont()->setBold(true);
                $sheet->getStyle('H' . $row . ':J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('G' . $row . ':K' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFEEEEEE');
                
                // Border untuk seluruh tabel
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ];
                $sheet->getStyle('A4:K' . $row)->applyFromArray($styleArray);
                
                // Auto-size columns
                foreach (range('A', 'K') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                // Set row height
                $sheet->getRowDimension(4)->setRowHeight(30);
                
                // Add footer
                $row += 3;
                $sheet->setCellValue('I' . $row, 'Surabaya, ' . Carbon::now()->format('d/m/Y'));
                $row += 1;
                $sheet->setCellValue('I' . $row, 'Mengetahui,');
                $row += 5;
                $sheet->setCellValue('I' . $row, '______________________');
                $row += 1;
                $sheet->setCellValue('I' . $row, 'Kepala SKW Wilayah I');
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
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                
                // Set title
                $sheet->setCellValue('A1', strtoupper($this->reportTitle));
                $sheet->mergeCells('A1:G1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Set subtitle
                $sheet->setCellValue('A2', strtoupper("GLOBAL"));
                $sheet->mergeCells('A2:G2');
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
                    $sheet->getStyle("{$col}{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle("{$col}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFCCCCCC');
                }
                
                // Get all products first
                $products = Product::orderBy('nama_latin')->get();
                
                // Get AKKII data for the period
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
                
                // Prepare product data
                $productData = [];
                
                // Initialize all products with zero values
                foreach ($products as $product) {
                    $productData[$product->id] = [
                        'nama_latin' => $product->nama_latin,
                        'qty_cites' => 0,
                        'qty_realisasi' => 0,
                        'qty_sisa' => 0,
                        'keterangan' => ''
                    ];
                }
                
                // Fill with actual data from AKKII items
                foreach ($akkiis as $akkii) {
                    foreach ($akkii->items as $item) {
                        if (!$item->product) continue;
                        
                        $productId = $item->product->id;
                        
                        if (!isset($productData[$productId])) {
                            $productData[$productId] = [
                                'nama_latin' => $item->product->nama_latin,
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
                $totalQtyCites = 0;
                $totalQtyRealisasi = 0;
                $totalQtySisa = 0;
                
                foreach ($productData as $productId => $data) {
                    // Skip products with no data
                    if ($data['qty_cites'] == 0 && $data['qty_realisasi'] == 0 && $data['qty_sisa'] == 0) {
                        continue;
                    }
                    
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
                    
                    // Align numbers to center
                    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    
                    // Add to totals
                    $totalQtyCites += $data['qty_cites'];
                    $totalQtyRealisasi += $data['qty_realisasi'];
                    $totalQtySisa += $data['qty_sisa'];
                    
                    $row++;
                    $no++;
                }
                
                // Add total row
                $sheet->setCellValue('B' . $row, 'TOTAL');
                $sheet->getStyle('B' . $row)->getFont()->setBold(true);
                
                $sheet->setCellValue('C' . $row, $totalQtyCites);
                $sheet->setCellValue('D' . $row, $totalQtyRealisasi);
                $sheet->setCellValue('E' . $row, $totalQtySisa);
                
                // Calculate total percentage
                $totalPercentage = 0;
                if ($totalQtyCites > 0) {
                    $totalPercentage = ($totalQtyRealisasi / $totalQtyCites) * 100;
                }
                $sheet->setCellValue('F' . $row, number_format($totalPercentage, 2) . '%');
                
                // Format total row
                $sheet->getStyle('B' . $row . ':G' . $row)->getFont()->setBold(true);
                $sheet->getStyle('C' . $row . ':F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B' . $row . ':G' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFEEEEEE');
                
                // Border untuk seluruh tabel
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ];
                $sheet->getStyle('A4:G' . $row)->applyFromArray($styleArray);
                
                // Auto-size columns
                foreach (range('A', 'G') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                // Set row height
                $sheet->getRowDimension(4)->setRowHeight(30);
                
                // Add footer
                $row += 3;
                $sheet->setCellValue('F' . $row, 'Surabaya, ' . Carbon::now()->format('d/m/Y'));
                $row += 1;
                $sheet->setCellValue('F' . $row, 'Mengetahui,');
                $row += 5;
                $sheet->setCellValue('F' . $row, '______________________');
                $row += 1;
                $sheet->setCellValue('F' . $row, 'Kepala SKW Wilayah I');
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
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                
                // Set title
                $sheet->setCellValue('A1', strtoupper($this->reportTitle));
                $sheet->mergeCells('A1:J1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Set subtitle
                $sheet->setCellValue('A2', strtoupper("SKW"));
                $sheet->mergeCells('A2:J2');
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
                    $sheet->getStyle("{$col}{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle("{$col}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFCCCCCC');
                }
                
                // Get all companies first
                $companies = DataCustomer::orderBy('company_name')->get();
                
                // Get AKKII data for the period
                $query = AKKII::with(['customer', 'items.product'])
                    ->whereHas('customer')
                    ->orderBy('customer_id')
                    ->orderBy('tanggal_terbit', 'asc');

                if ($this->startDate) {
                    $query->whereDate('tanggal_terbit', '>=', $this->startDate);
                }

                if ($this->endDate) {
                    $query->whereDate('tanggal_terbit', '<=', $this->endDate);
                }

                $akkiis = $query->get();
                
                // Group data by company
                $companyData = [];
                
                foreach ($companies as $company) {
                    $companyData[$company->id] = [
                        'company_name' => $company->company_name,
                        'akkiis' => []
                    ];
                }
                
                foreach ($akkiis as $akkii) {
                    if (!$akkii->customer) continue;
                    
                    $companyId = $akkii->customer->id;
                    
                    if (!isset($companyData[$companyId])) {
                        $companyData[$companyId] = [
                            'company_name' => $akkii->customer->company_name,
                            'akkiis' => []
                        ];
                    }
                    
                    $companyData[$companyId]['akkiis'][] = $akkii;
                }
                
                // Fill data
                $row = 5;
                $no = 1;
                $totalQtyCites = 0;
                $totalQtyRealisasi = 0;
                $totalQtySisa = 0;
                
                foreach ($companyData as $companyId => $data) {
                    // Skip companies with no data
                    if (empty($data['akkiis'])) {
                        continue;
                    }
                    
                    $firstRow = $row;
                    $totalRows = 0;
                    $companyQtyCites = 0;
                    $companyQtyRealisasi = 0;
                    $companyQtySisa = 0;
                    
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
                                
                                // Align numbers to center
                                $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                
                                // Add to totals
                                $companyQtyCites += $item->qty_cites ?? 0;
                                $companyQtyRealisasi += $item->qty_realisasi ?? 0;
                                $companyQtySisa += $item->qty_sisa ?? 0;
                                
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
                            
                            // Align numbers to center
                            $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            
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
                        
                        // Align number to center
                        $sheet->getStyle('A' . $firstRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('A' . $firstRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                        
                        // Align company name to left and vertical center
                        $sheet->getStyle('B' . $firstRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                        
                        // Add to global totals
                        $totalQtyCites += $companyQtyCites;
                        $totalQtyRealisasi += $companyQtyRealisasi;
                        $totalQtySisa += $companyQtySisa;
                        
                        $no++;
                    }
                }
                
                // Add total row
                $sheet->setCellValue('F' . $row, 'TOTAL');
                $sheet->getStyle('F' . $row)->getFont()->setBold(true);
                
                $sheet->setCellValue('G' . $row, $totalQtyCites);
                $sheet->setCellValue('H' . $row, $totalQtyRealisasi);
                $sheet->setCellValue('I' . $row, $totalQtySisa);
                
                // Format total row
                $sheet->getStyle('F' . $row . ':J' . $row)->getFont()->setBold(true);
                $sheet->getStyle('G' . $row . ':I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F' . $row . ':J' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFEEEEEE');
                
                // Border untuk seluruh tabel
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ];
                $sheet->getStyle('A4:J' . $row)->applyFromArray($styleArray);
                
                // Auto-size columns
                foreach (range('A', 'J') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                // Set row height
                $sheet->getRowDimension(4)->setRowHeight(30);
                
                // Add footer
                $row += 3;
                $sheet->setCellValue('I' . $row, 'Surabaya, ' . Carbon::now()->format('d/m/Y'));
                $row += 1;
                $sheet->setCellValue('I' . $row, 'Mengetahui,');
                $row += 5;
                $sheet->setCellValue('I' . $row, '______________________');
                $row += 1;
                $sheet->setCellValue('I' . $row, 'Kepala SKW Wilayah I');
            },
        ];
    }
}
