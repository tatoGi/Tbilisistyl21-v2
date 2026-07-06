<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AdminOnlyResource;
use App\Filament\Concerns\HasContentBlocks;
use App\Filament\Resources\TicketResource\Pages;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TicketResource extends Resource
{
    use AdminOnlyResource;
    use HasContentBlocks;

    protected static ?string $model = Ticket::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $locales = ['ka' => 'ქართული', 'en' => 'English', 'ru' => 'Русский', 'ua' => 'Українська'];

        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Ticket details')
                            ->description('Same fields shown on the public /dashboard/tickets page.')
                            ->schema([
                                Forms\Components\Fieldset::make('Title')
                                    ->schema(collect($locales)->map(
                                        fn ($label, $code) => Forms\Components\TextInput::make("title.{$code}")
                                            ->label($label)
                                            ->required($code === 'ka')
                                    )->values()->all())
                                    ->columns(2),
                                static::localizedInput('description', 'Description', rich: true),
                                static::imageUpload('image', 'Cover image')
                                    ->helperText('Same upload as page images (media library). Optional — hidden on the site when empty.'),
                            ]),
                        Forms\Components\Section::make('Event & pricing')
                            ->schema([
                                Forms\Components\TextInput::make('price_gel')
                                    ->label('Price (GEL)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('₾'),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->helperText('Remaining inventory shown as “X available” on the site.'),
                                Forms\Components\DatePicker::make('event_date')
                                    ->label('Event date')
                                    ->required(),
                                Forms\Components\TextInput::make('location')
                                    ->required()
                                    ->placeholder('e.g. Tbilisi'),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Publish')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'draft' => 'Draft — hidden from site',
                                        'active' => 'Active — on sale',
                                        'sold_out' => 'Sold out — visible, not purchasable',
                                    ])
                                    ->default('draft')
                                    ->required(),
                                Forms\Components\TextInput::make('sale_url')
                                    ->label('External sale URL (optional)')
                                    ->url()
                                    ->helperText('Leave empty to use the built-in checkout.'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('')
                    // Root-relative /storage URL (no ->disk): same-origin, so it
                    // works regardless of APP_URL (prod config:cache can leave it
                    // as http://localhost, which would blank the image).
                    // Use $record (not $state): $state inside getStateUsing calls
                    // getState() → getStateUsing → … infinite recursion → 500.
                    ->getStateUsing(fn (Ticket $record): ?string => static::publicUrl($record->image))
                    ->square(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ka'] ?? $state['en'] ?? '—') : $state)
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('price_gel')
                    ->label('Price')
                    ->money('GEL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Stock')
                    ->formatStateUsing(fn ($state, Ticket $record): string => match (true) {
                        $record->status === 'sold_out', (int) $state <= 0 => 'Sold out',
                        default => "{$state} available",
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'active' => 'success',
                        'sold_out' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
