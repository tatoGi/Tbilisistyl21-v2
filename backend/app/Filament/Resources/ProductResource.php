<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AdminOnlyResource;
use App\Filament\Concerns\HasContentBlocks;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Media;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ProductResource extends Resource
{
    use AdminOnlyResource;
    use HasContentBlocks;

    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $locales = ['ka' => 'ქართული', 'en' => 'English', 'ru' => 'Русский', 'ua' => 'Українська'];

        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Product details')
                            ->schema([
                                Forms\Components\Fieldset::make('Title')
                                    ->schema(collect($locales)->map(
                                        fn ($label, $code) => Forms\Components\TextInput::make("title.{$code}")
                                            ->label($label)
                                            ->required($code === 'ka')
                                    )->values()->all())
                                    ->columns(2),
                                Forms\Components\Fieldset::make('Description')
                                    ->schema(collect($locales)->map(
                                        fn ($label, $code) => Forms\Components\Textarea::make("description.{$code}")
                                            ->label($label)
                                            ->rows(4)
                                    )->values()->all())
                                    ->columns(2),
                                static::imageUpload('product_image', 'Product image')
                                    ->helperText('Shown on the shop page. Saved to the media library.'),
                            ]),
                        Forms\Components\Section::make('Variants')
                            ->schema([
                                Forms\Components\Repeater::make('sizes')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\TextInput::make('size')->required(),
                                        Forms\Components\TextInput::make('quantity')->numeric()->required(),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Pricing & catalog')
                            ->schema([
                                Forms\Components\TextInput::make('price_gel')
                                    ->label('Price (GEL)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('₾'),
                                Forms\Components\TextInput::make('category'),
                                Forms\Components\Toggle::make('is_vip')
                                    ->label('VIP product'),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'draft' => 'Draft — hidden from shop',
                                        'active' => 'Active — on sale',
                                        'sold_out' => 'Sold out',
                                    ])
                                    ->required(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('image'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('')
                    // ImageColumn only passes through http(s) states; root-relative
                    // "/storage/..." gets the disk prefix added again and 404s.
                    ->disk('public')
                    ->getStateUsing(fn (Product $record): ?string => static::diskPath($record->image?->path))
                    ->square(),
                Tables\Columns\TextColumn::make('title')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ka'] ?? $state['en'] ?? '—') : $state)
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('price_gel')
                    ->money('GEL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_vip')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'active' => 'success',
                        'sold_out' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'active' => 'Active', 'sold_out' => 'Sold Out']),
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

    /** Map a Filament upload to a `media` row and set `image_id` on the product payload. */
    public static function mergeUploadedImage(array $data): array
    {
        if (!array_key_exists('product_image', $data)) {
            return $data;
        }

        $raw = static::extractFilePath($data['product_image']);
        unset($data['product_image']);

        if (!$raw) {
            $data['image_id'] = null;

            return $data;
        }

        $diskPath = static::diskPath($raw) ?? ltrim($raw, '/');
        $filename = basename($diskPath);

        if (!Storage::disk('public')->exists($diskPath)) {
            $diskPath = 'media/' . $filename;
        }

        $media = Media::firstOrCreate(
            ['filename' => $filename],
            [
                'path' => 'media/' . $filename,
                'mime_type' => Storage::disk('public')->mimeType($diskPath) ?: 'image/jpeg',
                'size' => Storage::disk('public')->size($diskPath) ?: 0,
                'alt' => null,
            ],
        );

        if ($media->path !== 'media/' . $filename) {
            $media->update(['path' => 'media/' . $filename]);
        }

        $data['image_id'] = $media->id;

        return $data;
    }

    public static function productImageForForm(?Media $image): array
    {
        if (!$image?->path) {
            return [];
        }

        return static::fileUploadState(static::publicUrl($image->path));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
