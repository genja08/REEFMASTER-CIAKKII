<?php

namespace App\Exports;

use App\Models\AKKII;
use App\Models\DataCustomer;
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

class AKKIISKWFormatExport implements FromCollection, WithEvents, WithTitle
{
    protected $startDate;
    protected $endDate;
    protected $reportTitle;
    protected $jenisAkkii;

    public function __construct($startDate = null, $endDate = null, $reportTitle = 'Rekapitulasi Realisasi CITES', $jenisAkkii = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->reportTitle = $reportTitle;
        $this->jenisAkkii = $jenisAkkii;
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
                $jenisText = $this->jenisAkkii ? " " . strtoupper($this->jenisAkkii) : "";
                $sheet->setCellValue('A1', strtoupper($this->reportTitle) . $jenisText);
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
                
                // Filter by jenis_akkii if specified
                if ($this->jenisAkkii) {
                    $query->where('jenis_akkii', $this->jenisAkkii);
                }

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
                                $sheet->setCellValue('D' . $row, $akkii->tanggal_terbit ? date('d/m/Y', strtotime($akkii->tanggal_terbit)) : '-');
                                $sheet->setCellValue('E' . $row, $akkii->tanggal_expired ? date('d/m/Y', strtotime($akkii->tanggal_expired)) : '-');
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
                            $sheet->setCellValue('D' . $row, $akkii->tanggal_terbit ? date('d/m/Y', strtotime($akkii->tanggal_terbit)) : '-');
                            $sheet->setCellValue('E' . $row, $akkii->tanggal_expired ? date('d/m/Y', strtotime($akkii->tanggal_expired)) : '-');
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
                $sheet->setCellValue('I' . $row, 'Surabaya, ' . date('d/m/Y'));
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