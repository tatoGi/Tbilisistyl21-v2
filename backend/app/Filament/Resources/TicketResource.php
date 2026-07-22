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
                                    ->helperText('Total capacity. The site shows “remaining available” = quantity − sold.'),
                                Forms\Components\DatePicker::make('event_date')
                                    ->label('Event date')
                                    ->required(),
                                Forms\Components\TextInput::make('location')
                                    ->required()
                                    ->placeholder('e.g. Tbilisi'),
                            ])
                            ->columns(2),
                        Forms\Components\Section::make('Tier card (redesign)')
                            ->description('Optional — enriches the redesigned ticket card. Existing tickets work without these (the description is shown instead of a feature list).')
                            ->collapsed()
                            ->schema([
                                Forms\Components\Fieldset::make('Category label')
                                    ->schema(collect($locales)->map(
                                        fn ($label, $code) => Forms\Components\TextInput::make("category.{$code}")
                                            ->label($label)
                                            ->placeholder('e.g. STANDARD / JOKER PASS')
                                    )->values()->all())
                                    ->columns(2),
                                Forms\Components\Fieldset::make('Included features (one per line)')
                                    ->schema(collect($locales)->map(
                                        fn ($label, $code) => Forms\Components\Textarea::make("features.{$code}")
                                            ->label($label)
                                            ->rows(4)
                                            ->placeholder("წვდომა მთელ ტერიტორიაზე\nმთავარი სცენა\nღია ბარი")
                                    )->values()->all())
                                    ->columns(2),
                                Forms\Components\Toggle::make('is_featured')
                                    ->label('Highlight as “Popular”')
                                    ->helperText('Adds the POPULAR ribbon and gold highlight to this card.'),
                                Forms\Components\Toggle::make('is_joker')
                                    ->label('Joker ticket')
                                    ->helperText('Joker buyers get the joker artwork in the email PDF and enter the Joker draw.'),
                                Forms\Components\Toggle::make('is_techno')
                                    ->label('Techno ticket')
                                    ->helperText('Techno buyers automatically get the built-in techno artwork in the email PDF — no upload needed in Site settings.'),
                            ]),
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
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('')
                    // ImageColumn only passes through states that start with
                    // http(s); a root-relative "/storage/..." value gets the disk
                    // prefix added AGAIN (/storage//storage/…) and 404s. Hand it
                    // the disk-relative path and let ->disk('public') build the URL.
                    ->disk('public')
                    ->getStateUsing(fn (Ticket $record): ?string => static::diskPath($record->image))
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
                Tables\Columns\IconColumn::make('is_joker')
                    ->label('Joker')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_techno')
                    ->label('Techno')
                    ->boolean(),
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
