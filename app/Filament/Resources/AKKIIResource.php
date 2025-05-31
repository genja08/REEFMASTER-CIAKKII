<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AKKIIResource\Pages;
use App\Filament\Resources\AKKIIResource\RelationManagers;
use App\Models\AKKII;
use App\Models\Product;
use App\Models\CitesDocument;
use App\Models\DataCustomer;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Closure;

class AKKIIResource extends Resource
{
    protected static ?string $model = AKKII::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([
                Select::make('cites_document_id')
                    ->label('Dokumen CITES')
                    ->relationship('citesDocument', 'nomor')
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                        if ($state) {
                            $cites = \App\Models\CitesDocument::find($state);
                            if ($cites) {
                                // Tambahkan debug untuk melihat data CITES
                                \Illuminate\Support\Facades\Log::info('CITES data:', $cites->toArray());
                                
                                // Set nilai field-field
                                $set('nomor_cites', $cites->nomor ?? '');
                                $set('tanggal_terbit', $cites->issued_date ?? null);
                                $set('tanggal_expired', $cites->expired_date ?? null);
                                $set('airport_of_arrival', $cites->airport_of_arrival ?? '');
                                $set('customer_id', $cites->customer_id ?? null);
                                
                                // Jika customer_id ada, trigger afterStateUpdated untuk customer_id
                                if ($cites->customer_id) {
                                    $customer = \App\Models\DataCustomer::find($cites->customer_id);
                                    if ($customer) {
                                        $set('company_address', $customer->company_address ?? '');
                                        $set('country', $customer->country ?? '');
                                        $set('office_phone', $customer->office_phone ?? '');
                                        $set('email', $customer->email ?? '');
                                        $set('contact_person', $customer->contact_person ?? '');
                                        $set('tujuan', $customer->tujuan ?? '');
                                        $set('mobile_phone', $customer->mobile_phone ?? '');
                                        $set('airport_of_arrival', $customer->airport_of_arrival ?? '');
                                    }
                                }
                            } else {
                                \Illuminate\Support\Facades\Log::warning('CITES document not found with ID: ' . $state);
                            }
                        } else {
                            \Illuminate\Support\Facades\Log::warning('No CITES document ID provided');
                        }
                    }),
                    
                Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'company_name')
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                        if ($state) {
                            $customer = \App\Models\DataCustomer::find($state);
                            if ($customer) {
                                // Tambahkan debug untuk melihat data customer
                                \Illuminate\Support\Facades\Log::info('Customer data:', $customer->toArray());
                                
                                // Set nilai field-field
                                $set('company_address', $customer->company_address ?? '');
                                $set('country', $customer->country ?? '');
                                $set('office_phone', $customer->office_phone ?? '');
                                $set('email', $customer->email ?? '');
                                $set('contact_person', $customer->contact_person ?? '');
                                $set('tujuan', $customer->tujuan ?? '');
                                $set('mobile_phone', $customer->mobile_phone ?? '');
                                $set('airport_of_arrival', $customer->airport_of_arrival ?? '');
                            } else {
                                \Illuminate\Support\Facades\Log::warning('Customer not found with ID: ' . $state);
                            }
                        } else {
                            \Illuminate\Support\Facades\Log::warning('No customer ID provided');
                        }
                    }),
                    
                TextInput::make('nomor_cites')
                    ->label('Nomor CITES')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabled()
                    ->dehydrated(true)
                    ->helperText('Otomatis terisi dari dokumen CITES'),
                    
                TextInput::make('company_address')
                    ->label('Alamat Perusahaan')
                    ->disabled()
                    ->dehydrated(true)
                    ->helperText('Otomatis terisi dari data customer'),
                    
                TextInput::make('country')
                    ->label('Negara')
                    ->disabled()
                    ->dehydrated(true),
                    
                TextInput::make('office_phone')
                    ->label('Telepon Kantor')
                    ->disabled()
                    ->dehydrated(true),
                    
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->disabled()
                    ->dehydrated(true)
                    ->beforeStateDehydrated(function ($state) {
                        // Bersihkan email dari spasi di awal dan akhir
                        return trim($state);
                    })
                    ->formatStateUsing(function ($state) {
                        // Bersihkan email dari spasi saat ditampilkan
                        return trim($state);
                    }),
                    
                TextInput::make('contact_person')
                    ->label('Kontak Person')
                    ->disabled()
                    ->dehydrated(true),
                    
                TextInput::make('tujuan')
                    ->label('Tujuan')
                    ->disabled()
                    ->dehydrated(true),
                    
                TextInput::make('mobile_phone')
                    ->label('Telepon Seluler')
                    ->disabled()
                    ->dehydrated(true),
                    
                TextInput::make('airport_of_arrival')
                    ->label('Bandara Kedatangan')
                    ->disabled()
                    ->dehydrated(true)
                    ->helperText('Otomatis terisi dari data customer/CITES'),
                ]),

                Grid::make(3)->schema([
                DatePicker::make('tanggal_terbit')
                    ->label('Tanggal Terbit')
                    ->disabled()
                    ->dehydrated(true)
                    ->displayFormat('d/m/Y')
                    ->helperText('Otomatis terisi dari dokumen CITES'),
                    
                DatePicker::make('tanggal_expired')
                    ->label('Tanggal Expired')
                    ->disabled()
                    ->dehydrated(true)
                    ->displayFormat('d/m/Y')
                    ->helperText('Otomatis terisi dari dokumen CITES'),
                DatePicker::make('tanggal_ekspor')
                    ->label('Tanggal Ekspor')
                    ->displayFormat('d/m/Y'),
                    
                TextInput::make('no_awb')
                    ->label('No. AWB'),
                    
                TextInput::make('no_aju')
                    ->label('No. Aju'),
                    
                TextInput::make('no_pendaftaran')
                    ->label('No. Pendaftaran'),
                    
                DatePicker::make('tanggal_pendaftaran')
                    ->label('Tanggal Pendaftaran')
                    ->displayFormat('d/m/Y'),
                ]),

                Repeater::make('items')
                    ->relationship('items')
                    ->label('Produk')
                    ->defaultItems(1)
                    ->schema([
                        Select::make('product_id')
                            ->label('Nama Latin')
                            ->relationship('product', 'nama_latin')
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->options(function (Forms\Get $get) {
                                $citesDocumentId = $get('../../cites_document_id');
                                
                                if ($citesDocumentId) {
                                    // Dapatkan semua product_id dari CitesItem yang terkait dengan cites_document_id
                                    $citesItems = \App\Models\CitesItem::where('cites_document_id', $citesDocumentId)->get();
                                    
                                    if ($citesItems->isNotEmpty()) {
                                        $productIds = $citesItems->pluck('product_id')->toArray();
                                        
                                        // Dapatkan nama produk berdasarkan product_id
                                        $products = \App\Models\Product::whereIn('id', $productIds)->pluck('nama_latin', 'id')->toArray();
                                        
                                        return $products;
                                    }
                                }
                                
                                return [];
                            })
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get, $livewire) {
                                if ($state) {
                                    $product = \App\Models\Product::find($state);
                                    if ($product) {
                                        // Log untuk debugging
                                        \Illuminate\Support\Facades\Log::info('AKKIIResource - Product selected:', [
                                            'product_id' => $product->id,
                                            'nama_latin' => $product->nama_latin
                                        ]);
                                        
                                        // Dapatkan cites_document_id dari form
                                        $citesDocumentId = $get('../../cites_document_id');
                                        
                                        if ($citesDocumentId) {
                                            // Cari CitesItem yang sesuai dengan product_id dan cites_document_id
                                            $citesItem = \App\Models\CitesItem::where('product_id', $state)
                                                ->where('cites_document_id', $citesDocumentId)
                                                ->first();
                                            
                                            if ($citesItem) {
                                                \Illuminate\Support\Facades\Log::info('AKKIIResource - CitesItem found:', [
                                                    'cites_item_id' => $citesItem->id,
                                                    'product_id' => $citesItem->product_id,
                                                    'product_name' => $citesItem->product_name,
                                                    'qty_cites' => $citesItem->qty_cites
                                                ]);
                                                
                                                // Jika qty_cites sudah diisi, hitung qty_sisa
                                                $qtyCites = $get('qty_cites');
                                                if (!empty($qtyCites)) {
                                                    $qtySisa = $citesItem->qty_cites - (int)$qtyCites;
                                                    if ($qtySisa < 0) $qtySisa = 0;
                                                    
                                                    $set('qty_sisa', $qtySisa);
                                                }
                                            } else {
                                                \Illuminate\Support\Facades\Log::warning('AKKIIResource - CitesItem not found for product_id: ' . $state . ' and cites_document_id: ' . $citesDocumentId);
                                                // Tampilkan pesan error
                                                $livewire->notify('warning', 'Produk ini tidak terdaftar dalam dokumen CITES yang dipilih.');
                                                // Reset product_id
                                                $set('product_id', null);
                                            }
                                        } else {
                                            \Illuminate\Support\Facades\Log::warning('AKKIIResource - No cites_document_id found in form');
                                            // Tampilkan pesan error
                                            $livewire->notify('warning', 'Silakan pilih dokumen CITES terlebih dahulu.');
                                            // Reset product_id
                                            $set('product_id', null);
                                        }
                                    }
                                }
                            }),
                        TextInput::make('qty_cites')
                            ->label('Jumlah Kirim')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get, $livewire) {
                                // Jika product_id ada, cari data CitesItem
                                $productId = $get('product_id');
                                $citesDocumentId = $get('../../cites_document_id');
                                
                                // Validasi nilai tidak boleh negatif
                                if ((int)$state < 0) {
                                    $livewire->notify('error', 'Jumlah kirim tidak boleh kurang dari 0.');
                                    $set('qty_cites', 0);
                                    $state = 0;
                                }
                                
                                if ($productId && $citesDocumentId) {
                                    $citesItem = \App\Models\CitesItem::where('product_id', $productId)
                                        ->where('cites_document_id', $citesDocumentId)
                                        ->first();
                                    
                                    if ($citesItem && !empty($state)) {
                                        // Hitung qty_sisa = qty_cites dari CitesItem - jumlah kirim
                                        $qtySisa = $citesItem->qty_cites - (int)$state;
                                        
                                        // Validasi jumlah kirim tidak melebihi qty_cites
                                        if ($qtySisa < 0) {
                                            $livewire->notify('error', "Jumlah kirim tidak boleh melebihi qty CITES ({$citesItem->qty_cites}).");
                                            $set('qty_cites', $citesItem->qty_cites);
                                            $qtySisa = 0;
                                        }
                                        
                                        $set('qty_sisa', $qtySisa);
                                        
                                        \Illuminate\Support\Facades\Log::info('AKKIIResource - Calculated qty_sisa from qty_cites input:', [
                                            'cites_qty_cites' => $citesItem->qty_cites,
                                            'input_qty_cites' => $state,
                                            'calculated_qty_sisa' => $qtySisa
                                        ]);
                                    }
                                }
                            })
                            ->helperText(function (Forms\Get $get) {
                                $productId = $get('product_id');
                                $citesDocumentId = $get('../../cites_document_id');
                                
                                if ($productId && $citesDocumentId) {
                                    $citesItem = \App\Models\CitesItem::where('product_id', $productId)
                                        ->where('cites_document_id', $citesDocumentId)
                                        ->first();
                                    
                                    if ($citesItem) {
                                        return "Qty CITES tersedia: {$citesItem->qty_cites}";
                                    }
                                }
                                
                                if (!$citesDocumentId) {
                                    return 'Pilih dokumen CITES terlebih dahulu';
                                }
                                
                                return 'Pilih produk terlebih dahulu';
                            }),
                            
                        TextInput::make('qty_sisa')
                            ->label('Jumlah Sisa')
                            ->numeric()
                            ->required()
                            ->disabled()
                            ->dehydrated(true)
                            ->helperText('Dihitung otomatis: Qty Product CITES - Jumlah Kirim'),
                    ])
            ->columns(3)
            ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_cites')
                    ->label('Nomor CITES')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('citesDocument.nomor')
                    ->label('Dokumen CITES')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('customer.company_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('tanggal_terbit')
                    ->label('Tanggal Terbit')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('tanggal_expired')
                    ->label('Tanggal Expired')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('tanggal_ekspor')
                    ->label('Tanggal Ekspor')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('no_awb')
                    ->label('No. AWB')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'company_name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('tanggal_ekspor')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal_ekspor_from')
                            ->label('Dari Tanggal')
                            ->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('tanggal_ekspor_to')
                            ->label('Sampai Tanggal')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['tanggal_ekspor_from'],
                                fn ($query) => $query->whereDate('tanggal_ekspor', '>=', $data['tanggal_ekspor_from'])
                            )
                            ->when(
                                $data['tanggal_ekspor_to'],
                                fn ($query) => $query->whereDate('tanggal_ekspor', '<=', $data['tanggal_ekspor_to'])
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListAKKIIS::route('/'),
            'create' => Pages\CreateAKKII::route('/create'),
            'edit' => Pages\EditAKKII::route('/{record}/edit'),
        ];
    }
}
