<?php

namespace App\Filament\Concerns;

use App\Models\Media;
use Filament\Forms;
use Filament\Forms\Components\Builder as BlockBuilder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Shared Filament content-block builder and image path helpers.
 * Used by PageResource and PostResource (stored in `content_blocks` JSON).
 */
trait HasContentBlocks
{
    /**
     * The content block builder. Block payloads are stored as
     * `[{ type, data: {...} }]` in the `content_blocks` JSON column. Text fields
     * keep a `{ka,en,ru,ua}` shape so the frontend can localize them.
     */
    protected static function contentBlocksBuilder(): BlockBuilder
    {
        // NOTE: Do NOT add ->afterStateHydrated() here. Filament's Builder relies on
        // its own afterStateHydrated callback to key each item by a random UUID, which
        // its drag-and-drop reorder action needs. afterStateHydrated holds a single
        // closure, so overriding it drops the UUID keying and corrupts reorder. Image
        // paths are converted for the form by mutateFormDataBeforeFill (blocksForForm)
        // in EditPage/EditPost instead.
        return BlockBuilder::make('content_blocks')
            ->label('')
            ->blockNumbers(false)
            ->collapsible()
            ->cloneable()
            ->blocks([
                BlockBuilder\Block::make('hero')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        static::localizedInput('heading', 'Heading'),
                        static::localizedInput('subheading', 'Subheading', rich: true),
                        static::localizedInput('ctaLabel', 'Button label'),
                        Forms\Components\TextInput::make('ctaHref')->label('Button link'),
                        static::imageUpload('image', 'Background image'),
                    ]),

                BlockBuilder\Block::make('richText')
                    ->label('Text')
                    ->icon('heroicon-o-bars-3-bottom-left')
                    ->schema([
                        static::localizedInput('content', 'Content', rich: true),
                    ]),

                BlockBuilder\Block::make('image')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        static::imageUpload('image', 'Image'),
                        static::localizedInput('caption', 'Caption'),
                        Forms\Components\Select::make('width')
                            ->options(['full' => 'Full width', 'contained' => 'Contained'])
                            ->default('full'),
                        Forms\Components\Select::make('fit')
                            ->label('Image display')
                            ->helperText('Crop keeps a uniform 16:9 banner; Full shows the whole image without cropping.')
                            ->options([
                                'cover' => 'Crop to banner (16:9)',
                                'full' => 'Show full image (no crop)',
                            ])
                            ->default('cover'),
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

                BlockBuilder\Block::make('eventInfo')
                    ->label('Event info card')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        static::localizedInput('label', 'Card label'),
                        Forms\Components\Repeater::make('rows')
                            ->label('Rows (label / value)')
                            ->schema([
                                static::localizedInput('k', 'Label'),
                                static::localizedInput('v', 'Value'),
                            ])
                            ->columns(2)
                            ->defaultItems(3),
                        static::localizedInput('note', 'Note', textarea: true),
                    ]),

                BlockBuilder\Block::make('tags')
                    ->label('Tag pills')
                    ->icon('heroicon-o-hashtag')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('Tags')
                            ->schema([
                                static::localizedInput('label', 'Tag'),
                            ])
                            ->defaultItems(3),
                    ]),

                BlockBuilder\Block::make('stats')
                    ->label('Stat chips')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('Stats (shown as a row under the page title)')
                            ->schema([
                                Forms\Components\TextInput::make('value')
                                    ->label('Value')
                                    ->placeholder('60×15მ'),
                                static::localizedInput('label', 'Label'),
                            ])
                            ->columns(2)
                            ->defaultItems(4),
                    ]),
            ]);
    }

    /** Toolbar for the visual rich-text editor. Output is stored as HTML and
     *  rendered on the frontend via the `.rich-text` styles. */
    private const RICH_TOOLBAR = [
        'bold', 'italic', 'strike', 'h2', 'h3',
        'bulletList', 'orderedList', 'blockquote', 'link', 'undo', 'redo',
    ];

    /**
     * A 4-language ({ka,en,ru,ua}) input group inside a block's `data` payload.
     * `rich` renders a visual HTML editor; `textarea` a plain multi-line box;
     * otherwise a single-line input.
     */
    protected static function localizedInput(string $name, string $label, bool $textarea = false, bool $rich = false): Forms\Components\Fieldset
    {
        $locales = ['ka' => 'ქართული', 'en' => 'English', 'ru' => 'Русский', 'ua' => 'Українська'];

        return Forms\Components\Fieldset::make($label)
            ->schema(collect($locales)->map(function ($langLabel, $code) use ($name, $textarea, $rich) {
                $key = "{$name}.{$code}";
                if ($rich) {
                    return Forms\Components\RichEditor::make($key)
                        ->label($langLabel)
                        ->toolbarButtons(self::RICH_TOOLBAR);
                }
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

    /**
     * Map a Filament upload to a `media` row and set the owning foreign key.
     *
     * An absent upload key means the field was not part of this form submission
     * and is left untouched; an empty one means the user cleared the image.
     */
    public static function mergeUploadedMedia(array $data, string $uploadField, string $foreignKey): array
    {
        if (!array_key_exists($uploadField, $data)) {
            return $data;
        }

        $raw = static::extractFilePath($data[$uploadField]);
        unset($data[$uploadField]);

        if (!$raw) {
            $data[$foreignKey] = null;

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

        $data[$foreignKey] = $media->id;

        return $data;
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
}
