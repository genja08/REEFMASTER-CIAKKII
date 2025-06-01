<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Produk')
                ->badge(Product::query()->count()),
            'alam' => Tab::make('Alam')
                ->badge(Product::query()->where('jenis_produk', 'Alam')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_produk', 'Alam')),
            'transplan' => Tab::make('Transplan')
                ->badge(Product::query()->where('jenis_produk', 'Transplan')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_produk', 'Transplan')),
        ];
    }
}
