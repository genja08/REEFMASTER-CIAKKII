<?php

namespace App\Exports;

use App\Models\AKKII;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class SimpleAKKIIExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
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
}