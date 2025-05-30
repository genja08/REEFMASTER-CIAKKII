<?php

namespace App\Exports;

use App\Models\AKKII;
use App\Models\AKKII_Item;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class AKKIIMultipleItemsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;
    protected $title;

    public function __construct($startDate = null, $endDate = null, $title = 'Laporan Bulanan AKKII')
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->title = $title;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        // Dapatkan semua AKKII berdasarkan filter tanggal
        $query = AKKII::with(['customer', 'citesDocument', 'items.product'])
            ->orderBy('tanggal_ekspor', 'asc');

        if ($this->startDate) {
            $query->whereDate('tanggal_ekspor', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('tanggal_ekspor', '<=', $this->endDate);
        }

        $akkiis = $query->get();

        // Buat koleksi baru untuk menyimpan data yang akan diekspor
        $exportData = new Collection();

        // Untuk setiap AKKII, tambahkan baris untuk setiap item
        foreach ($akkiis as $akkii) {
            if ($akkii->items->count() > 0) {
                foreach ($akkii->items as $item) {
                    // Buat objek baru yang berisi data AKKII dan item
                    $exportRow = (object)[
                        'akkii' => $akkii,
                        'item' => $item
                    ];
                    $exportData->push($exportRow);
                }
            } else {
                // Jika tidak ada item, tetap tambahkan AKKII
                $exportRow = (object)[
                    'akkii' => $akkii,
                    'item' => null
                ];
                $exportData->push($exportRow);
            }
        }

        return $exportData;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No.',
            'Tanggal Ekspor',
            'Nomor CITES',
            'Tanggal Terbit',
            'Tanggal Expired',
            'Nama Perusahaan',
            'Negara',
            'Alamat',
            'Kontak Person',
            'Telepon',
            'Email',
            'No. AWB',
            'No. Aju',
            'No. Pendaftaran',
            'Tanggal Pendaftaran',
            'Nama Latin',
            'Jumlah Kirim',
            'Sisa',
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        static $index = 0;
        $index++;

        $akkii = $row->akkii;
        $item = $row->item;

        return [
            $index,
            $akkii->tanggal_ekspor ? Carbon::parse($akkii->tanggal_ekspor)->format('d/m/Y') : '-',
            $akkii->nomor_cites,
            $akkii->tanggal_terbit ? Carbon::parse($akkii->tanggal_terbit)->format('d/m/Y') : '-',
            $akkii->tanggal_expired ? Carbon::parse($akkii->tanggal_expired)->format('d/m/Y') : '-',
            $akkii->customer->company_name ?? '-',
            $akkii->country ?? '-',
            $akkii->company_address ?? '-',
            $akkii->contact_person ?? '-',
            $akkii->office_phone ?? '-',
            $akkii->email ?? '-',
            $akkii->no_awb ?? '-',
            $akkii->no_aju ?? '-',
            $akkii->no_pendaftaran ?? '-',
            $akkii->tanggal_pendaftaran ? Carbon::parse($akkii->tanggal_pendaftaran)->format('d/m/Y') : '-',
            $item ? ($item->product->nama_latin ?? '-') : '-',
            $item ? ($item->qty_cites ?? 0) : 0,
            $item ? ($item->qty_sisa ?? 0) : 0,
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return void
     */
    public function styles(Worksheet $sheet)
    {
        // Judul laporan
        $sheet->mergeCells('A1:R1');
        $sheet->setCellValue('A1', strtoupper($this->title));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Periode laporan
        $sheet->mergeCells('A2:R2');
        $periodText = 'Periode: ';
        if ($this->startDate && $this->endDate) {
            $periodText .= Carbon::parse($this->startDate)->format('d/m/Y') . ' - ' . Carbon::parse($this->endDate)->format('d/m/Y');
        } elseif ($this->startDate) {
            $periodText .= 'Mulai ' . Carbon::parse($this->startDate)->format('d/m/Y');
        } elseif ($this->endDate) {
            $periodText .= 'Sampai ' . Carbon::parse($this->endDate)->format('d/m/Y');
        } else {
            $periodText .= 'Semua Data';
        }
        $sheet->setCellValue('A2', $periodText);
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Header tabel
        $sheet->getStyle('A3:R3')->getFont()->setBold(true);
        $sheet->getStyle('A3:R3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCCC');
        $sheet->getStyle('A3:R3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Border untuk seluruh tabel
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        
        // Hitung jumlah baris data
        $dataCount = count($this->collection());
        if ($dataCount > 0) {
            $sheet->getStyle('A3:R' . ($dataCount + 3))->applyFromArray($styleArray);
        } else {
            $sheet->getStyle('A3:R4')->applyFromArray($styleArray);
        }
        
        // Atur lebar kolom
        $sheet->getColumnDimension('A')->setWidth(5);  // No.
        $sheet->getColumnDimension('B')->setWidth(15); // Tanggal Ekspor
        $sheet->getColumnDimension('C')->setWidth(20); // Nomor CITES
        $sheet->getColumnDimension('D')->setWidth(15); // Tanggal Terbit
        $sheet->getColumnDimension('E')->setWidth(15); // Tanggal Expired
        $sheet->getColumnDimension('F')->setWidth(25); // Nama Perusahaan
        $sheet->getColumnDimension('G')->setWidth(15); // Negara
        $sheet->getColumnDimension('H')->setWidth(30); // Alamat
        $sheet->getColumnDimension('I')->setWidth(20); // Kontak Person
        $sheet->getColumnDimension('J')->setWidth(15); // Telepon
        $sheet->getColumnDimension('K')->setWidth(25); // Email
        $sheet->getColumnDimension('L')->setWidth(15); // No. AWB
        $sheet->getColumnDimension('M')->setWidth(15); // No. Aju
        $sheet->getColumnDimension('N')->setWidth(20); // No. Pendaftaran
        $sheet->getColumnDimension('O')->setWidth(20); // Tanggal Pendaftaran
        $sheet->getColumnDimension('P')->setWidth(25); // Nama Latin
        $sheet->getColumnDimension('Q')->setWidth(15); // Jumlah Kirim
        $sheet->getColumnDimension('R')->setWidth(15); // Sisa
    }
}