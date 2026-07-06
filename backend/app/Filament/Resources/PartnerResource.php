<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasContentBlocks;
use App\Filament\Resources\PartnerResource\Pages;
use App\Models\Media;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class PartnerResource extends Resource
{
    use HasContentBlocks;

    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        $locales = ['ka' => 'ქართული', 'en' => 'English', 'ru' => 'Русский', 'ua' => 'Українська'];

        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Partner details')
                            ->schema([
                                Forms\Components\Fieldset::make('Name')
                                    ->schema(collect($locales)->map(
                                        fn ($label, $code) => Forms\Components\TextInput::make("name.{$code}")
                                            ->label($label)
                                            ->required($code === 'ka')
                                    )->values()->all())
                                    ->columns(2)
                                    ->columnSpanFull(),
                                static::localizedInput('description', 'Description', textarea: true)
                                    ->columnSpanFull(),
                                static::imageUpload('logo_upload', 'Logo')
                                    ->helperText('Upload the partner logo. Shown on the festival page.')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('url')
                                    ->label('Website URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Display')
                            ->schema([
                                Forms\Components\TextInput::make('order')
                                    ->numeric()
                                    ->default(0)
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
            ->columns([
                Tables\Columns\ImageColumn::make('logo.path')
                    ->label('Logo')
                    ->disk('public')
                    ->visibility('public')
                    ->square()
                    ->defaultImageUrl(null),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->formatStateUsing(fn ($state, Partner $record) => $record->getTranslation('name', 'ka') ?: $record->getTranslation('name', 'en'))
                    ->searchable(query: function ($query, string $search) {
                        $query->where('name->ka', 'like', "%{$search}%")
                            ->orWhere('name->en', 'like', "%{$search}%");
                    }),
                Tables\Columns\TextColumn::make('url')
                    ->label('Website')
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Map a Filament upload to a `media` row and set `logo_id` on the partner payload. */
    public static function mergeUploadedLogo(array $data): array
    {
        if (!array_key_exists('logo_upload', $data)) {
            return $data;
        }

        $raw = static::extractFilePath($data['logo_upload']);
        unset($data['logo_upload']);

        if (!$raw) {
            $data['logo_id'] = null;

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

        $data['logo_id'] = $media->id;

        return $data;
    }

    public static function logoForForm(?Media $logo): array
    {
        if (!$logo?->path) {
            return [];
        }

        return static::fileUploadState(static::publicUrl($logo->path));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
