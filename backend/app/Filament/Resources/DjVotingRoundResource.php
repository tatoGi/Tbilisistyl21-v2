<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasContentBlocks;
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
    use HasContentBlocks;

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
                            ->maxLength(255)
                            ->helperText('Admin-only label — never shown on the site.'),
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
                                    (int) $get('duration_value'),
                                    (string) $get('duration_unit'),
                                ),
                            ]),
                        Forms\Components\TextInput::make('duration_value')
                            ->label('Duration')
                            ->numeric()
                            ->minValue(1)
                            ->default(24)
                            ->required()
                            ->live(onBlur: true)
                            ->helperText('Voting closes automatically once this runs out.'),
                        Forms\Components\Select::make('duration_unit')
                            ->label('Duration unit')
                            ->options([
                                'hours' => 'Hours',
                                'days' => 'Days',
                                'months' => 'Months',
                            ])
                            ->default('hours')
                            ->required()
                            ->live()
                            ->helperText('Use months for a long run, e.g. 5 months to keep voting open until December.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Public copy')
                    ->description('Shown above the ballot on the festival page. Leave a language blank to use the built-in translation for it.')
                    ->collapsed()
                    ->schema([
                        static::localizedInput('heading', 'Heading')
                            ->columnSpanFull(),
                        static::localizedInput('subtitle', 'Subtitle', textarea: true)
                            ->columnSpanFull(),
                    ]),
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
