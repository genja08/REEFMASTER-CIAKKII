<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CitesDocumentResource\Pages;
use App\Models\CitesDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Product; // Import model Product

class CitesDocumentResource extends Resource
{
    protected static ?string $model = CitesDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('nomor')
                        ->label('Nomor CITES')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->validationMessages([
                            'unique' => 'Nomor CITES ini sudah digunakan. Silakan gunakan nomor yang berbeda.',
                        ]),
                    Forms\Components\Select::make('jenis_cites')
                        ->label('Jenis CITES')
                        ->options([
                            'Alam' => 'Alam',
                            'Transplan' => 'Transplan',
                        ])
                        ->required()
                        ->placeholder('Pilih Jenis CITES'),
                    DatePicker::make('issued_date')
                        ->label('Tgl Terbit')
                        ->required()
                        ->displayFormat('d/m/Y')
                        ->rules(['before_or_equal:expired_date'])
                        ->validationMessages([
                            'before_or_equal' => 'Tanggal Terbit tidak boleh lebih dari Tanggal Expired.',
                        ]),
                    DatePicker::make('expired_date')
                        ->label('Tgl Expired')
                        ->required()
                        ->displayFormat('d/m/Y')
                        ->rules(['after_or_equal:issued_date'])
                        ->validationMessages([
                            'after_or_equal' => 'Tanggal Expired tidak boleh sebelum Tanggal Terbit.',
                        ]),
                    TextInput::make('airport_of_arrival')->label('Airport of Arrival')->required(),
                    Select::make('customer_id')
                        ->label('Pilih Customer')
                        ->relationship('customer', 'company_name')
                        ->searchable()
                        ->required(),
                ]),

                Repeater::make('items')
                    ->relationship('items')
                    ->label('Products')
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(function (Forms\Get $get) {
                                $jenisCites = $get('../../jenis_cites');
                                
                                if ($jenisCites) {
                                    return \App\Models\Product::where('jenis_produk', $jenisCites)
                                        ->orderBy('nama_latin', 'asc')
                                        ->pluck('nama_latin', 'id')
                                        ->toArray();
                                }
                                
                                return \App\Models\Product::orderBy('nama_latin', 'asc')
                                    ->pluck('nama_latin', 'id')
                                    ->toArray();
                            })
                            ->placeholder('Select a product')
                            ->reactive() // Membuat field reactive
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('product_name', $product->nama_latin);
                                    } else {
                                        $set('product_name', '');
                                    }
                                } else {
                                    $set('product_name', '');
                                }
                            })
                            ->searchable() // Membuat select searchable
                            ->preload() // Memuat opsi di awal untuk UX lebih baik
                            ->required(), // Field ini wajib diisi
                            
                        TextInput::make('product_name')
                            ->label('Product Name')
                            ->hidden()
                            ->dehydrated(true) // Ensure the field is included in form submission
                            ->default(function (Forms\Get $get) {
                                $productId = $get('product_id');
                                if ($productId) {
                                    $product = Product::find($productId);
                                    if ($product) {
                                        return $product->nama_latin;
                                    }
                                }
                                return '';
                            })
                            ->required(),
                        // TextInput::make('product_name') // Removed this field
                        //     ->label('Product Name')
                        //     ->mutateDehydratedStateUsing(function ($state, \Filament\Forms\Get $get) {
                        //         $productId = $get('product_id');
                        //         if ($productId) {
                        //             $product = \App\Models\Product::find($productId);
                        //             return $product?->nama_Latin ?? '';
                        //         }
                        //         return '';
                        //     })
                        //     ->default(fn (\Filament\Forms\Get $get) => Product::find($get('product_id'))?->nama_Latin ?? ''),
                        TextInput::make('qty_cites')->label('Qty CITES')->numeric()->required(),
                    ])->columns(2)
                    ->columnSpanFull()
                    ->createItemButtonLabel('Add Product')
                    // ->createItemButtonAttributes([ // Baris ini dihapus
                    //     'class' => 'bg-blue-500 text-white hover:bg-blue-600 focus:ring-blue-500',
                    //     'style' => 'margin-top: 10px; margin-bottom: 10px;',
                    // ])
                    ->defaultItems(1)
                    ->addActionLabel('Add Item')
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tambahkan kolom sesuai kebutuhan
                Tables\Columns\TextColumn::make('nomor')->label('Nomor CITES')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('jenis_cites')->label('Jenis CITES')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('issued_date')->label('Tgl Terbit')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('expired_date')->label('Tgl Expired')->date('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_cites')
                    ->label('Jenis CITES')
                    ->options([
                        'Alam' => 'Alam',
                        'Transplan' => 'Transplan',
                    ]),
                Tables\Filters\Filter::make('issued_date')
                    ->form([
                        Forms\Components\DatePicker::make('issued_date_from')
                            ->label('Dari Tanggal Terbit')
                            ->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('issued_date_to')
                            ->label('Sampai Tanggal Terbit')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['issued_date_from'],
                                fn ($query) => $query->whereDate('issued_date', '>=', $data['issued_date_from'])
                            )
                            ->when(
                                $data['issued_date_to'],
                                fn ($query) => $query->whereDate('issued_date', '<=', $data['issued_date_to'])
                            );
                    }),
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'company_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCitesDocuments::route('/'),
            'create' => Pages\CreateCitesDocument::route('/create'),
            'edit' => Pages\EditCitesDocument::route('/{record}/edit'),
        ];
    }
}
