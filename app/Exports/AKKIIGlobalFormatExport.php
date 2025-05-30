<?php

namespace App\Exports;

use App\Models\AKKII;
use App\Models\Product;
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

class AKKIIGlobalFormatExport implements FromCollection, WithEvents, WithTitle
{
    protected $startDate;
    protected $endDate;
    protected $reportTitle;

    public function __construct($startDate = null, $endDate = null, $reportTitle = 'PT. BANYU BIRU SENTOSA') // Changed default title
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->reportTitle = $reportTitle; // This will be the main title "PT. BANYU BIRU SENTOSA"
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
        return "Global"; // Sheet name
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

                // Titles
                // Main Title
                $sheet->setCellValue('A1', strtoupper($this->reportTitle));
                $sheet->mergeCells('A1:J1'); // Spanning 10 columns
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Subtitle 1
                $sheet->setCellValue('A2', 'LAPORAN REALISASI CITES');
                $sheet->mergeCells('A2:J2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Subtitle 2 (Month/Year)
                $periodString = "BULAN DESEMBER 2023"; // Default from image
                if ($this->startDate) {
                    try {
                        $periodString = "BULAN " . strtoupper(Carbon::parse($this->startDate)->translatedFormat('F Y'));
                    } catch (\Exception $e) {
                        // Keep default if date parsing fails
                    }
                }
                $sheet->setCellValue('A3', $periodString);
                $sheet->mergeCells('A3:J3');
                $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Header table
                $headers = [
                    'NO.', 'NO. REKOM', 'NO. CITES', 'TGL. TERBIT', 'JUMLAH CITES',
                    'TGL EKSPOR', 'JUMLAH REALISASI', 'SISA CITES', 'NEGARA TUJUAN', 'BERLAKU'
                ];
                
                $headerRow = 5; // Start headers at row 5
                foreach ($headers as $index => $header) {
                    $col = chr(65 + $index); // A, B, C, ..., J
                    $sheet->setCellValue("{$col}{$headerRow}", $header);
                    $sheet->getStyle("{$col}{$headerRow}")->getFont()->setBold(true);
                    $sheet->getStyle("{$col}{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle("{$col}{$headerRow}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFCCCCCC'); // Light grey background
                }
                $sheet->getRowDimension($headerRow)->setRowHeight(30); // Set header row height
                
                // Get AKKII data for the period
                // Adjust 'with' part based on your actual relations for no_rekom, no_cites_own, etc.
                $query = AKKII::with(['items']) // Assuming 'items' relation exists
                    ->whereHas('items') // Ensure AKKII has items to display
                    ->orderBy('tanggal_terbit', 'asc'); // Or any other relevant order

                if ($this->startDate) {
                    $query->whereDate('tanggal_terbit', '>=', $this->startDate);
                }

                if ($this->endDate) {
                    $query->whereDate('tanggal_terbit', '<=', $this->endDate);
                }

                $akkiis = $query->get();
                
                // Fill data
                $currentRow = $headerRow + 1; // Data starts after header row
                $no = 1;
                $totalJumlahCites = 0;
                $totalJumlahRealisasi = 0;
                $totalSisaCites = 0;
                
                foreach ($akkiis as $akkii) {
                    foreach ($akkii->items as $item) {
                        // NO.
                        $sheet->setCellValue('A' . $currentRow, $no);
                        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        // NO. REKOM - Adjust 'no_rekom' if field name is different or from a relation
                        $sheet->setCellValue('B' . $currentRow, $akkii->no_rekom ?? '');
                        
                        // NO. CITES - Adjust 'no_cites_own' if field name is different
                        $sheet->setCellValue('C' . $currentRow, $akkii->no_cites_own ?? '');

                        // TGL. TERBIT
                        $tglTerbit = $akkii->tanggal_terbit ? Carbon::parse($akkii->tanggal_terbit)->format('j-M-y') : '';
                        $sheet->setCellValue('D' . $currentRow, $tglTerbit);
                        $sheet->getStyle('D' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


                        // JUMLAH CITES
                        $sheet->setCellValue('E' . $currentRow, $item->qty_cites ?? 0);
                        $sheet->getStyle('E' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $totalJumlahCites += ($item->qty_cites ?? 0);

                        // TGL EKSPOR - Adjust 'tanggal_ekspor' if field name is different
                        $tglEkspor = $akkii->tanggal_ekspor ? Carbon::parse($akkii->tanggal_ekspor)->format('j-M-y') : '';
                        $sheet->setCellValue('F' . $currentRow, $tglEkspor);
                        $sheet->getStyle('F' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        // JUMLAH REALISASI
                        $sheet->setCellValue('G' . $currentRow, $item->qty_realisasi ?? 0);
                        $sheet->getStyle('G' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $totalJumlahRealisasi += ($item->qty_realisasi ?? 0);

                        // SISA CITES
                        $sheet->setCellValue('H' . $currentRow, $item->qty_sisa ?? 0);
                        $sheet->getStyle('H' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $totalSisaCites += ($item->qty_sisa ?? 0);

                        // NEGARA TUJUAN - Adjust 'negara_tujuan_text' or use relation e.g., $akkii->customer->name
                        $sheet->setCellValue('I' . $currentRow, $akkii->negara_tujuan_text ?? '');
                        
                        // BERLAKU - Adjust 'tanggal_berlaku' if field name is different
                        $tglBerlaku = $akkii->tanggal_berlaku ? Carbon::parse($akkii->tanggal_berlaku)->format('j-M-y') : '';
                        $sheet->setCellValue('J' . $currentRow, $tglBerlaku);
                        $sheet->getStyle('J' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        
                        $currentRow++;
                        $no++;
                    }
                }
                
                // Add total row if there was data
                if ($no > 1) {
                    $totalRow = $currentRow;
                    // JUMLAH CITES Total
                    $sheet->setCellValue('E' . $totalRow, $totalJumlahCites);
                    $sheet->getStyle('E' . $totalRow)->getFont()->setBold(true);
                    $sheet->getStyle('E' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // JUMLAH REALISASI Total
                    $sheet->setCellValue('G' . $totalRow, $totalJumlahRealisasi);
                    $sheet->getStyle('G' . $totalRow)->getFont()->setBold(true);
                    $sheet->getStyle('G' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // SISA CITES Total
                    $sheet->setCellValue('H' . $totalRow, $totalSisaCites);
                    $sheet->getStyle('H' . $totalRow)->getFont()->setBold(true);
                    $sheet->getStyle('H' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    
                    // Style for the cells containing totals
                    $sheet->getStyle('E'.$totalRow.':H'.$totalRow)->getFill()
                         ->setFillType(Fill::FILL_SOLID)
                         ->getStartColor()->setARGB('FFEEEEEE'); // Light grey for total cells
                } else {
                    // No data, set totalRow to currentRow to avoid issues with border styling
                    $totalRow = $currentRow;
                }

                // Border untuk seluruh tabel (headers + data + total row)
                $tableEndRow = ($no > 1) ? $totalRow : $headerRow; // If no data, border up to header
                if ($tableEndRow >= $headerRow) { // Ensure there's at least a header row to border
                    $styleArray = [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FF000000'],
                            ],
                        ],
                    ];
                    $sheet->getStyle('A'.$headerRow.':J' . $tableEndRow)->applyFromArray($styleArray);
                }
                
                // Auto-size columns
                foreach (range('A', 'J') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                // Removed old footer
            },
        ];
    }
}