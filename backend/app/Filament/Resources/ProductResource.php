<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AdminOnlyResource;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class ProductResource extends Resource
{
    use AdminOnlyResource;
    use Translatable;

    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\Textarea::make('description'),
            Forms\Components\TextInput::make('price_gel')->numeric()->required(),
            Forms\Components\TextInput::make('category'),
            Forms\Components\Toggle::make('is_vip'),
            Forms\Components\Select::make('status')
                ->options(['draft' => 'Draft', 'active' => 'Active', 'sold_out' => 'Sold Out'])
                ->required(),
            Forms\Components\Repeater::make('sizes')
                ->relationship()
                ->schema([
                    Forms\Components\TextInput::make('size')->required(),
                    Forms\Components\TextInput::make('quantity')->numeric()->required(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('image')
                ->label('')
                ->getStateUsing(fn ($record) => $record->image ? '/storage/media/' . $record->image->filename : null)
                ->square(),
            Tables\Columns\TextColumn::make('title')->searchable(),
            Tables\Columns\TextColumn::make('price_gel')->money('GEL')->sortable(),
            Tables\Columns\TextColumn::make('category')->searchable(),
            Tables\Columns\IconColumn::make('is_vip')->boolean(),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'warning',
                    'active' => 'success',
                    'sold_out' => 'danger',
                    default => 'gray',
                }),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options(['draft' => 'Draft', 'active' => 'Active', 'sold_out' => 'Sold Out']),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function afterSave(): void
    {
        Cache::forget('products:active');
    }

    public static function afterDelete(): void
    {
        Cache::forget('products:active');
    }
}
