<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasContentBlocks;
use App\Filament\Resources\DjResource\Pages;
use App\Models\Dj;
use App\Models\Media;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DjResource extends Resource
{
    use HasContentBlocks;

    protected static ?string $model = Dj::class;

    protected static ?string $navigationIcon = 'heroicon-o-microphone';

    protected static ?string $navigationGroup = 'DJ Voting';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'DJs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('DJ details')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Stage name')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Shown as-is in every language.')
                                    ->columnSpanFull(),
                                static::localizedInput('bio', 'Short bio', textarea: true)
                                    ->columnSpanFull(),
                                static::imageUpload('photo_upload', 'Photo')
                                    ->helperText('Shown on the festival voting card.')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Display')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options(['draft' => 'Draft', 'published' => 'Published'])
                                    ->default('draft')
                                    ->required()
                                    ->helperText('Only published DJs appear on the ballot.'),
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
                Tables\Columns\ImageColumn::make('photo.path')
                    ->label('Photo')
                    ->disk('public')
                    ->visibility('public')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'published' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('votes_count')
                    ->label('Votes (all rounds)')
                    ->counts('votes')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order')
                    ->numeric()
                    ->sortable(),
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

    public static function photoForForm(?Media $photo): array
    {
        if (!$photo?->path) {
            return [];
        }

        return static::fileUploadState(static::publicUrl($photo->path));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDjs::route('/'),
            'create' => Pages\CreateDj::route('/create'),
            'edit' => Pages\EditDj::route('/{record}/edit'),
        ];
    }
}
