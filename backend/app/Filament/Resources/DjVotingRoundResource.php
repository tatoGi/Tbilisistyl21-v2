<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DjVotingRoundResource\Pages;
use App\Models\Dj;
use App\Models\DjVotingRound;
use App\Rules\NoOverlappingRound;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DjVotingRoundResource extends Resource
{
    protected static ?string $model = DjVotingRound::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';

    protected static ?string $navigationGroup = 'DJ Voting';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Voting rounds';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Round')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('djs')
                            ->label('DJs on the ballot')
                            ->relationship('djs', 'name', fn ($query) => $query->published())
                            ->multiple()
                            ->preload()
                            ->required()
                            ->helperText('Only published DJs can be added.'),
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Starts at')
                            ->seconds(false)
                            ->required()
                            ->default(now())
                            ->rules(fn (?DjVotingRound $record, Get $get) => [
                                new NoOverlappingRound(
                                    $record?->id,
                                    $get('starts_at'),
                                    (int) $get('duration_hours'),
                                ),
                            ]),
                        Forms\Components\TextInput::make('duration_hours')
                            ->label('Duration (hours)')
                            ->numeric()
                            ->minValue(1)
                            ->default(24)
                            ->required()
                            ->helperText('Voting closes automatically after this many hours.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('state')
                    ->label('State')
                    ->badge()
                    ->state(fn (DjVotingRound $record) => $record->state())
                    ->color(fn (string $state) => match ($state) {
                        'open' => 'success',
                        'scheduled' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('starts_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('ends_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('votes_count')
                    ->label('Total votes')
                    ->counts('votes')
                    ->sortable(),
            ])
            ->defaultSort('starts_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('startNow')
                    ->label('Start now')
                    ->icon('heroicon-o-play-circle')
                    ->requiresConfirmation()
                    ->visible(fn (DjVotingRound $record) => $record->state() === 'scheduled')
                    ->action(function (DjVotingRound $record) {
                        // Pulling the start forward can collide with another
                        // round, so the invariant is re-checked here too,
                        // via the same predicate the form rule uses.
                        $hours = $record->starts_at->diffInHours($record->ends_at);
                        $now = now();
                        $end = $now->copy()->addHours($hours);

                        if (NoOverlappingRound::conflictExists($record->id, $now, $end)) {
                            Notification::make()
                                ->title('Another round is already running')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update(['starts_at' => $now, 'ends_at' => $end]);
                    }),
                Tables\Actions\Action::make('close')
                    ->label('Close now')
                    ->icon('heroicon-o-stop-circle')
                    ->requiresConfirmation()
                    ->visible(fn (DjVotingRound $record) => $record->isOpen())
                    ->action(fn (DjVotingRound $record) => $record->update(['ends_at' => now()])),
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
            'index' => Pages\ListDjVotingRounds::route('/'),
            'create' => Pages\CreateDjVotingRound::route('/create'),
            'edit' => Pages\EditDjVotingRound::route('/{record}/edit'),
        ];
    }
}
