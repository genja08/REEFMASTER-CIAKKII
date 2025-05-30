<?php

namespace App\Exports;

use App\Models\AKKII;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class BasicAKKIIExport implements FromCollection, WithHeadings, WithTitle
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

        return $query->get();
    }
    
    /**
     * @return string
     */
    public function title(): string
    {
        return 'Laporan AKKII';
    }
    
    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nomor CITES',
            'Tanggal Terbit',
            'Tanggal Expired',
            'Tanggal Ekspor',
            'Perusahaan',
            'Negara',
            'Status',
            'Dibuat Pada',
            'Diperbarui Pada'
        ];
    }
}