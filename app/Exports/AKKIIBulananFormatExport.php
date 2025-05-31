<?php

namespace App\Exports;

use App\Models\AKKII;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class AKKIIBulananFormatExport implements FromCollection, WithEvents, WithTitle
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return collect(); // Data akan ditangani di AfterSheet
    }

    public function title(): string
    {
        return 'Data Bulanan Format';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Set page orientation to landscape and paper size to A4
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.5);
                $sheet->getPageMargins()->setRight(0.5);
                $sheet->getPageMargins()->setLeft(0.5);
                $sheet->getPageMargins()->setBottom(0.5);

                // Ambil data CITES berdasarkan filter tanggal
                $citesGroups = AKKII::with(['items.product'])
                    ->when($this->startDate, fn($q) => $q->whereDate('tanggal_terbit', '>=', $this->startDate))
                    ->when($this->endDate, fn($q) => $q->whereDate('tanggal_terbit', '<=', $this->endDate))
                    ->orderBy('tanggal_terbit', 'asc') // Ensure consistent order
                    ->get();

                // Ambil semua produk unik dari dokumen CITES, sorted by nama_latin
                $allProducts = $citesGroups->flatMap(fn($doc) => $doc->items->pluck('product'))
                                        ->filter() // Remove null products if any
                                        ->unique('id')
                                        ->sortBy('nama_latin') // Sort products by nama_latin
                                        ->values();

                // Calculate the last column for main title merging
                // 2 fixed columns (NO, NAMA LATIN) + 3 columns per CITES document
                $maxColIndexForTitles = 2 + (count($citesGroups) * 3);
                $lastColForTitles = Coordinate::stringFromColumnIndex($maxColIndexForTitles);

                // Main Titles
                $currentYear = $this->endDate ? date('Y', strtotime($this->endDate)) : date('Y');
                $sheet->setCellValue('A1', 'REKAPITULASI REALISASI CITES BULANAN');
                $sheet->mergeCells("A1:{$lastColForTitles}1");
                $sheet->getStyle('A1')->getFont()->setBold(true);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A2', 'PT. XYZ'); // Placeholder, user might want to change this
                $sheet->mergeCells("A2:{$lastColForTitles}2");
                $sheet->getStyle('A2')->getFont()->setBold(true);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A3', 'TAHUN ' . $currentYear);
                $sheet->mergeCells("A3:{$lastColForTitles}3");
                $sheet->getStyle('A3')->getFont()->setBold(true);
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Row offset for content after main titles and one blank row
                $rowOffset = 5; // Start content from row 5 (1-3 titles, 4 blank)

                // Buat header untuk informasi CITES
                $citesHeaderLabels = [
                    'Nomor CITES', 'Tanggal Terbit', 'Tanggal Expired', 'Tanggal Ekspor',
                    'No. AWB', 'Tujuan', 'No Aju', 'No Pendaftaran', 'Tanggal Pendaftaran',
                ];

                $citesInfoStartRow = $rowOffset;
                for ($i = 0; $i < count($citesHeaderLabels); $i++) {
                    $r = $citesInfoStartRow + $i;
                    $sheet->setCellValue("A{$r}", $citesHeaderLabels[$i]);
                    $sheet->mergeCells("A{$r}:B{$r}");
                    $sheet->getStyle("A{$r}")->getFont()->setBold(true);
                }

                // Freeze panes so that the CITES info and main headers stay visible when scrolling
                // Freeze just below the last CITES info row and main table headers (+1 more row)
                $freezeRow = $citesInfoStartRow + count($citesHeaderLabels) + 3; // +1 blank, +1 header, +1 extra row
                $sheet->freezePane("C{$freezeRow}");

                // Buat kolom untuk setiap dokumen CITES
                $colDataIndex = 3; // Data starts from column C
                foreach ($citesGroups as $cites) {
                    $colLetter = Coordinate::stringFromColumnIndex($colDataIndex);
                    // Merge 3 columns to the right for each CITES info cell
                    $mergeEndColLetter = Coordinate::stringFromColumnIndex($colDataIndex + 2);
                    for ($i = 0; $i < count($citesHeaderLabels); $i++) {
                        $row = $citesInfoStartRow + $i;
                        $value = match ($i) {
                            0 => $cites->nomor_cites,
                            1 => $cites->tanggal_terbit ? date('d/m/Y', strtotime($cites->tanggal_terbit)) : '-',
                            2 => $cites->tanggal_expired ? date('d/m/Y', strtotime($cites->tanggal_expired)) : '-',
                            3 => $cites->tanggal_ekspor ? date('d/m/Y', strtotime($cites->tanggal_ekspor)) : '-',
                            4 => $cites->no_awb ?? '-',
                            5 => $cites->tujuan ?? '-',
                            6 => $cites->no_aju ?? '-',
                            7 => $cites->no_pendaftaran ?? '-',
                            8 => $cites->tanggal_pendaftaran ? date('d/m/Y', strtotime($cites->tanggal_pendaftaran)) : '-',
                        };
                        $sheet->setCellValue("{$colLetter}{$row}", $value);
                        $sheet->mergeCells("{$colLetter}{$row}:{$mergeEndColLetter}{$row}");
                    }
                    $colDataIndex += 3;
                }

                // Styling untuk header informasi CITES
                $lastCitesInfoCol = Coordinate::stringFromColumnIndex($colDataIndex - 1);
                $citesInfoEndRow = $citesInfoStartRow + count($citesHeaderLabels) - 1;
                $sheet->getStyle("A{$citesInfoStartRow}:{$lastCitesInfoCol}{$citesInfoEndRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("A{$citesInfoStartRow}:A{$citesInfoEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                if ($colDataIndex > 2) { // Only apply if there's data
                    $sheet->getStyle("B{$citesInfoStartRow}:{$lastCitesInfoCol}{$citesInfoEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }


                // Buat header untuk tabel utama (Product Data Table)
                $productTableHeaderStartRow = $citesInfoEndRow + 2; // +1 for blank row, +1 for header start
                $sheet->setCellValue("A{$productTableHeaderStartRow}", "NO.");
                $sheet->setCellValue("B{$productTableHeaderStartRow}", "NAMA LATIN");

                $sheet->mergeCells("A{$productTableHeaderStartRow}:A" . ($productTableHeaderStartRow + 1));
                $sheet->mergeCells("B{$productTableHeaderStartRow}:B" . ($productTableHeaderStartRow + 1));

                $productTableColIndex = 3; // Starts from column C
                foreach ($citesGroups as $cites) {
                    $baseColLetter = Coordinate::stringFromColumnIndex($productTableColIndex);
                    $endMergeColLetter = Coordinate::stringFromColumnIndex($productTableColIndex + 2);

                    $sheet->setCellValue("{$baseColLetter}{$productTableHeaderStartRow}", $cites->nomor_cites);
                    $sheet->mergeCells("{$baseColLetter}{$productTableHeaderStartRow}:{$endMergeColLetter}{$productTableHeaderStartRow}");

                    $subHeaderRow = $productTableHeaderStartRow + 1;
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($productTableColIndex) . "{$subHeaderRow}", "CITES");
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($productTableColIndex + 1) . "{$subHeaderRow}", "KIRIM");
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($productTableColIndex + 2) . "{$subHeaderRow}", "SISA");
                    
                    $productTableColIndex += 3;
                }
                
                $lastProductTableColLetter = Coordinate::stringFromColumnIndex($productTableColIndex - 1);
                if ($productTableColIndex > 3) { // Only apply if there are CITES columns
                    $sheet->getStyle("A{$productTableHeaderStartRow}:{$lastProductTableColLetter}" . ($productTableHeaderStartRow + 1))->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E0E0']],
                    ]);
                } else { // Style for NO and NAMA LATIN if no CITES data
                     $sheet->getStyle("A{$productTableHeaderStartRow}:B" . ($productTableHeaderStartRow + 1))->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E0E0']],
                    ]);
                }


                // Isi data produk
                $productDataStartRow = $productTableHeaderStartRow + 2;
                $currentDataRow = $productDataStartRow;
                $no = 1;
                $grandTotals = array_fill(0, count($citesGroups) * 3, 0); // Renamed for clarity

                // Initialize subtotals for specific product types
                $productTypesForSubtotal = ['Hard Coral', 'Substrat', 'Live Rock'];
                $subtotalsByType = [];
                foreach ($productTypesForSubtotal as $type) {
                    $subtotalsByType[$type] = array_fill(0, count($citesGroups) * 3, 0);
                }

                foreach ($allProducts as $product) {
                    $sheet->setCellValue("A{$currentDataRow}", $no++);
                    $sheet->setCellValue("B{$currentDataRow}", $product->nama_latin);

                    $productTableColDataIndex = 3; // Data starts from column C
                    $citesGroupCounter = 0;
                    foreach ($citesGroups as $cites) {
                        $item = $cites->items->firstWhere('product_id', $product->id);
                        
                        $qtyCites = $item->qty_cites ?? 0;
                        $qtyRealisasi = $item->qty_realisasi ?? 0;
                        $qtySisa = $item->qty_sisa ?? 0;

                        $sheet->setCellValueByColumnAndRow($productTableColDataIndex, $currentDataRow, $qtyCites);
                        $sheet->setCellValueByColumnAndRow($productTableColDataIndex + 1, $currentDataRow, $qtyRealisasi);
                        $sheet->setCellValueByColumnAndRow($productTableColDataIndex + 2, $currentDataRow, $qtySisa);
                        
                        // Accumulate grand totals
                        $grandTotals[$citesGroupCounter * 3 + 0] += $qtyCites;
                        $grandTotals[$citesGroupCounter * 3 + 1] += $qtyRealisasi;
                        $grandTotals[$citesGroupCounter * 3 + 2] += $qtySisa;

                        // Accumulate subtotals by product type
                        if ($product && isset($product->jenis_coral) && in_array($product->jenis_coral, $productTypesForSubtotal)) {
                            $subtotalsByType[$product->jenis_coral][$citesGroupCounter * 3 + 0] += $qtyCites;
                            $subtotalsByType[$product->jenis_coral][$citesGroupCounter * 3 + 1] += $qtyRealisasi;
                            $subtotalsByType[$product->jenis_coral][$citesGroupCounter * 3 + 2] += $qtySisa;
                        }
                        
                        $productTableColDataIndex += 3;
                        $citesGroupCounter++;
                    }
                    $currentDataRow++;
                }
                $productDataActualEndRow = $currentDataRow -1; // Last row of actual product data

                // Style the main product data rows (excluding totals/subtotals)
                if ($productDataStartRow <= $productDataActualEndRow) {
                    $rangeToStyleData = "A{$productDataStartRow}:{$lastProductTableColLetter}{$productDataActualEndRow}";
                     if ($productTableColIndex <= 3) { // if no cites groups, only style A and B for data
                        $rangeToStyleData = "A{$productDataStartRow}:B{$productDataActualEndRow}";
                    }
                    $sheet->getStyle($rangeToStyleData)->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                     // Align numbers to center for product data
                    if ($productTableColIndex > 3) {
                        $dataColIdx = 3;
                        foreach ($citesGroups as $cites) {
                            for ($i = 0; $i < 3; $i++) {
                                $colLetter = Coordinate::stringFromColumnIndex($dataColIdx + $i);
                                $sheet->getStyle("{$colLetter}{$productDataStartRow}:{$colLetter}{$productDataActualEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            }
                            $dataColIdx += 3;
                        }
                    }
                    // Align NO. to center for product data
                    $sheet->getStyle("A{$productDataStartRow}:A{$productDataActualEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // Add Subtotal rows
                foreach ($productTypesForSubtotal as $type) {
                    // Only add subtotal row if there are CITES columns to sum into
                    if (count($citesGroups) > 0) {
                        $subtotalRowIndex = $currentDataRow;
                        $sheet->setCellValue("A{$subtotalRowIndex}", "JUMLAH " . strtoupper($type));
                        $sheet->mergeCells("A{$subtotalRowIndex}:B{$subtotalRowIndex}");
                        
                        $subtotalStyleArray = [
                            'font' => ['bold' => true],
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                        ];
                        $sheet->getStyle("A{$subtotalRowIndex}:{$lastProductTableColLetter}{$subtotalRowIndex}")->applyFromArray($subtotalStyleArray);
                        $sheet->getStyle("A{$subtotalRowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        $subtotalColDataIndex = 3; // Starts from column C
                        for ($i = 0; $i < count($citesGroups) * 3; $i++) {
                            $cellVal = $subtotalsByType[$type][$i];
                            $sheet->setCellValueByColumnAndRow($subtotalColDataIndex + $i, $subtotalRowIndex, $cellVal);
                            $cellCoordinate = Coordinate::stringFromColumnIndex($subtotalColDataIndex + $i) . $subtotalRowIndex;
                            // Font bold already applied to whole row, ensure number alignment
                            $sheet->getStyle($cellCoordinate)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }
                        $currentDataRow++;
                    } else if ($no > 1) { // If no CITES groups but there were products, still add label
                        $subtotalRowIndex = $currentDataRow;
                        $sheet->setCellValue("A{$subtotalRowIndex}", "JUMLAH " . strtoupper($type));
                        $sheet->mergeCells("A{$subtotalRowIndex}:B{$subtotalRowIndex}");
                         $subtotalStyleArray = [
                            'font' => ['bold' => true],
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER]
                        ];
                        $sheet->getStyle("A{$subtotalRowIndex}:B{$subtotalRowIndex}")->applyFromArray($subtotalStyleArray);
                        $currentDataRow++;
                    }
                }
                
                // Add GRAND TOTAL row if there's data
                if ($no > 1) { // if at least one product was processed
                    $grandTotalRowIndex = $currentDataRow;
                    $sheet->setCellValue("A{$grandTotalRowIndex}", "TOTAL");
                    $sheet->mergeCells("A{$grandTotalRowIndex}:B{$grandTotalRowIndex}");

                    $grandTotalStyleArray = [
                        'font' => ['bold' => true],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                    ];
                    $rangeForGrandTotalStyle = "A{$grandTotalRowIndex}:B{$grandTotalRowIndex}";
                     if ($productTableColIndex > 3) {
                         $rangeForGrandTotalStyle = "A{$grandTotalRowIndex}:{$lastProductTableColLetter}{$grandTotalRowIndex}";
                     }

                    $sheet->getStyle($rangeForGrandTotalStyle)->applyFromArray($grandTotalStyleArray);
                    $sheet->getStyle("A{$grandTotalRowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    if (count($citesGroups) > 0) {
                        $totalColIndex = 3;
                        for ($i = 0; $i < count($citesGroups) * 3; $i++) {
                            $sheet->setCellValueByColumnAndRow($totalColIndex + $i, $grandTotalRowIndex, $grandTotals[$i]);
                            $cellCoordinate = Coordinate::stringFromColumnIndex($totalColIndex + $i) . $grandTotalRowIndex;
                            // Font bold already applied, ensure number alignment
                            $sheet->getStyle($cellCoordinate)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }
                    }
                    $currentDataRow++; // Increment for styling range consideration if any style applied to all data + totals
                }
                
                // Auto-size columns
                // Iterate up to the maximum column index used
                $maxColUsed = $productTableColIndex > 2 ? $productTableColIndex -1 : 2; // Max of CITES info or product table
                if ($colDataIndex -1 > $maxColUsed) $maxColUsed = $colDataIndex -1;


                for ($i = 1; $i <= $maxColUsed; $i++) {
                    $colLetter = Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                }
                 // Set specific width for NAMA LATIN if needed, as AutoSize might be too wide or narrow
                $sheet->getColumnDimension('B')->setWidth(30);


            },
        ];
    }
}
