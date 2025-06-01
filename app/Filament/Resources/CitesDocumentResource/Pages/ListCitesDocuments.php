<?php

namespace App\Filament\Resources\CitesDocumentResource\Pages;

use App\Filament\Resources\CitesDocumentResource;
use App\Models\CitesDocument;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCitesDocuments extends ListRecords
{
    protected static string $resource = CitesDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua CITES')
                ->badge(CitesDocument::query()->count()),
            'alam' => Tab::make('Alam')
                ->badge(CitesDocument::query()->where('jenis_cites', 'Alam')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_cites', 'Alam')),
            'transplan' => Tab::make('Transplan')
                ->badge(CitesDocument::query()->where('jenis_cites', 'Transplan')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_cites', 'Transplan')),
        ];
    }
}
