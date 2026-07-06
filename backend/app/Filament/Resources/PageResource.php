<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasContentBlocks;
use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    use HasContentBlocks, Translatable;

    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    public static function getTranslatableLocales(): array
    {
        return ['ka', 'en', 'ru', 'ua'];
    }

    /**
     * Normalize nav/footer toggles and order fields.
     * Filament Translatable saves can drop sidebar values from $data — merge from $source.
     */
    public static function normalizeSettings(array $data, ?Page $existing = null, ?array $source = null): array
    {
        $merged = $source ? array_merge($source, $data) : $data;

        $bool = function (string $key) use ($merged, $existing): bool {
            if (array_key_exists($key, $merged) && $merged[$key] !== null) {
                return (bool) $merged[$key];
            }

            return (bool) ($existing?->{$key} ?? false);
        };

        $int = function (string $key) use ($merged, $existing): int {
            if (array_key_exists($key, $merged) && $merged[$key] !== null && $merged[$key] !== '') {
                return (int) $merged[$key];
            }

            return (int) ($existing?->{$key} ?? 100);
        };

        $data['show_in_nav'] = $bool('show_in_nav');
        $data['show_in_footer'] = $bool('show_in_footer');
        $data['featured_on_home'] = $bool('featured_on_home');
        $data['nav_order'] = $int('nav_order');
        $data['footer_order'] = $int('footer_order');

        return $data;
    }

    /** Keys saved on every locale update (not Spatie-translatable). */
    public static function persistedSettingsKeys(): array
    {
        return [
            'show_in_nav',
            'show_in_footer',
            'featured_on_home',
            'nav_order',
            'footer_order',
            'slug',
            'route_path',
            'is_published',
            'content_blocks',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Page')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('nav_label')
                                    ->label('Menu label (optional)')
                                    ->helperText('Shown in the site menu. Falls back to the title.')
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Section::make('Content blocks')
                            ->schema([
                                static::contentBlocksBuilder(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Settings')
                            ->schema([
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('URL path, e.g. "main-stage". Lowercase, no spaces.')
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug((string) $state))),
                                Forms\Components\TextInput::make('route_path')
                                    ->label('Custom route (optional)')
                                    ->helperText('For pages on a fixed React route, e.g. "/dashboard/shop". Menus link here instead of /{slug}.')
                                    ->maxLength(255),
                                Forms\Components\Toggle::make('is_published')
                                    ->default(true),
                            ]),
                        Forms\Components\Section::make('Navigation')
                            ->schema([
                                Forms\Components\Toggle::make('show_in_nav')
                                    ->label('Show in site menu')
                                    ->default(false)
                                    ->dehydrated(),
                                Forms\Components\TextInput::make('nav_order')
                                    ->label('Menu order')
                                    ->helperText('Lower numbers appear first.')
                                    ->numeric()
                                    ->default(100)
                                    ->dehydrated(),
                                Forms\Components\Toggle::make('show_in_footer')
                                    ->label('Show in site footer')
                                    ->default(false)
                                    ->dehydrated(),
                                Forms\Components\TextInput::make('footer_order')
                                    ->label('Footer order')
                                    ->helperText('Lower numbers appear first in the footer links.')
                                    ->numeric()
                                    ->default(100)
                                    ->dehydrated(),
                                Forms\Components\Toggle::make('featured_on_home')
                                    ->label('Feature on homepage')
                                    ->default(false)
                                    ->dehydrated(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nav_order')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('content_blocks')
                    ->label('Blocks')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) : 0)
                    ->badge()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('show_in_nav')
                    ->label('In menu')
                    ->boolean(),
                Tables\Columns\IconColumn::make('show_in_footer')
                    ->label('In footer')
                    ->boolean(),
                Tables\Columns\TextColumn::make('nav_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('featured_on_home')
                    ->label('Featured')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
