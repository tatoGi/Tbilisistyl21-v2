<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Components\Builder as BlockBuilder;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    use Translatable;

    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    public static function getTranslatableLocales(): array
    {
        return ['ka', 'en', 'ru', 'ua'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Page')
                            ->schema([
                                // `title` and `nav_label` are Spatie-translatable: the
                                // panel locale switcher (top-right) controls which
                                // language is edited.
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
                                    ->default(false),
                                Forms\Components\TextInput::make('nav_order')
                                    ->label('Menu order')
                                    ->helperText('Lower numbers appear first.')
                                    ->numeric()
                                    ->default(100),
                                Forms\Components\Toggle::make('featured_on_home')
                                    ->label('Feature on homepage')
                                    ->default(false),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    /**
     * The content block builder. Block payloads are stored as
     * `[{ type, data: {...} }]` in the `content_blocks` JSON column. Text fields
     * keep a `{ka,en,ru,ua}` shape so the frontend can localize them.
     */
    protected static function contentBlocksBuilder(): BlockBuilder
    {
        return BlockBuilder::make('content_blocks')
            ->label('')
            ->blockNumbers(false)
            ->collapsible()
            ->cloneable()
            ->afterStateHydrated(function (BlockBuilder $component, ?array $state): void {
                if (is_array($state) && $state !== []) {
                    $component->state(static::blocksForForm($state));
                }
            })
            ->blocks([
                BlockBuilder\Block::make('hero')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        static::localizedInput('heading', 'Heading'),
                        static::localizedInput('subheading', 'Subheading', textarea: true),
                        static::localizedInput('ctaLabel', 'Button label'),
                        Forms\Components\TextInput::make('ctaHref')->label('Button link'),
                        static::imageUpload('image', 'Background image'),
                    ]),

                BlockBuilder\Block::make('richText')
                    ->label('Text')
                    ->icon('heroicon-o-bars-3-bottom-left')
                    ->schema([
                        static::localizedInput('content', 'Content', textarea: true),
                    ]),

                BlockBuilder\Block::make('image')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        static::imageUpload('image', 'Image'),
                        static::localizedInput('caption', 'Caption'),
                        Forms\Components\Select::make('width')
                            ->options(['full' => 'Full width', 'contained' => 'Contained'])
                            ->default('full'),
                    ]),

                BlockBuilder\Block::make('gallery')
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        Forms\Components\Repeater::make('images')
                            ->label('Images')
                            ->schema([
                                static::imageUpload('image', 'Image'),
                                static::localizedInput('caption', 'Caption'),
                            ])
                            ->minItems(1)
                            ->columns(2),
                        Forms\Components\Select::make('columns')
                            ->options(['2' => '2', '3' => '3', '4' => '4'])
                            ->default('3'),
                    ]),

                BlockBuilder\Block::make('contact')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Forms\Components\Toggle::make('showPayments')
                            ->label('Show payment methods (Visa / Mastercard)')
                            ->default(true),
                    ]),

                BlockBuilder\Block::make('cta')
                    ->label('Call to action')
                    ->icon('heroicon-o-cursor-arrow-rays')
                    ->schema([
                        static::localizedInput('label', 'Button label'),
                        Forms\Components\TextInput::make('href')->label('Button link')->required(),
                    ]),
            ]);
    }

    /** A 4-language ({ka,en,ru,ua}) input group inside a block's `data` payload. */
    protected static function localizedInput(string $name, string $label, bool $textarea = false): Forms\Components\Fieldset
    {
        $locales = ['ka' => 'ქართული', 'en' => 'English', 'ru' => 'Русский', 'ua' => 'Українська'];

        return Forms\Components\Fieldset::make($label)
            ->schema(collect($locales)->map(function ($langLabel, $code) use ($name, $textarea) {
                // Builder already nests block fields under `data` — do NOT prefix `data.`
                $key = "{$name}.{$code}";
                return $textarea
                    ? Forms\Components\Textarea::make($key)->label($langLabel)->rows(6)
                    : Forms\Components\TextInput::make($key)->label($langLabel);
            })->values()->all())
            ->columns(2);
    }

    protected static function imageUpload(string $statePath, string $label): Forms\Components\FileUpload
    {
        return Forms\Components\FileUpload::make($statePath)
            ->label($label)
            ->image()
            ->maxSize(5120)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
            ->disk('public')
            ->directory('media')
            ->visibility('public')
            ->fetchFileInformation(false);
    }

    /** Convert stored paths to the uuid-keyed array shape Filament FileUpload expects. */
    public static function fileUploadState(mixed $path): array
    {
        if (is_array($path)) {
            // Already hydrated (uuid => path) — keep as-is.
            return $path;
        }

        $diskPath = is_string($path) ? static::diskPath($path) : null;

        return $diskPath ? [(string) Str::uuid() => $diskPath] : [];
    }

    /** Convert stored `/storage/...` paths to disk-relative paths for FileUpload. */
    public static function blocksForForm(array $blocks): array
    {
        return array_map(function ($block) {
            $data = $block['data'] ?? [];

            if (array_key_exists('image', $data)) {
                $data['image'] = static::fileUploadState($data['image']);
            }

            if (isset($data['images']) && is_array($data['images'])) {
                $data['images'] = array_map(function ($item) {
                    if (array_key_exists('image', $item)) {
                        $item['image'] = static::fileUploadState($item['image']);
                    }
                    return $item;
                }, $data['images']);
            }

            $block['data'] = $data;

            return $block;
        }, $blocks);
    }

    /** Normalize stored/API paths to a public-disk relative path for FileUpload. */
    public static function diskPath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (str_starts_with($path, '/storage/')) {
            return ltrim(substr($path, strlen('/storage/')), '/');
        }
        if (str_starts_with($path, 'storage/')) {
            return substr($path, strlen('storage/'));
        }
        return ltrim($path, '/');
    }

    /** Normalize all image paths inside content blocks (for DB + API). */
    public static function normalizeBlockPaths(array $blocks): array
    {
        return array_map(function ($block) {
            $data = $block['data'] ?? [];
            if (array_key_exists('image', $data)) {
                $data['image'] = static::publicUrl(static::extractFilePath($data['image']));
            }
            if (isset($data['images']) && is_array($data['images'])) {
                $data['images'] = array_map(function ($item) {
                    if (array_key_exists('image', $item)) {
                        $item['image'] = static::publicUrl(static::extractFilePath($item['image']));
                    }
                    return $item;
                }, $data['images']);
            }
            $block['data'] = $data;
            return $block;
        }, $blocks);
    }

    /** Pull a disk/API path out of a FileUpload value (string or uuid-keyed array). */
    public static function extractFilePath(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = reset($value) ?: null;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** Stored/API path: always `/storage/...` for frontend consumption. */
    public static function publicUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, '/storage/') || str_starts_with($path, '/images/')) {
            return $path;
        }
        $relative = static::diskPath($path);
        return $relative ? '/storage/' . $relative : null;
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
