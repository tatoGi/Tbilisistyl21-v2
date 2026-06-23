<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class TicketResource extends Resource
{
    use Translatable;

    protected static ?string $model = Ticket::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Shop';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\Textarea::make('description'),
            Forms\Components\TextInput::make('price_gel')->numeric()->required(),
            Forms\Components\TextInput::make('quantity')->numeric()->required(),
            Forms\Components\DatePicker::make('event_date')->required(),
            Forms\Components\TextInput::make('location')->required(),
            Forms\Components\Select::make('status')
                ->options(['draft' => 'Draft', 'active' => 'Active', 'sold_out' => 'Sold Out'])
                ->required(),
            Forms\Components\TextInput::make('sale_url')->url(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->searchable(),
            Tables\Columns\TextColumn::make('price_gel')->money('GEL')->sortable(),
            Tables\Columns\TextColumn::make('quantity')->sortable(),
            Tables\Columns\TextColumn::make('event_date')->date()->sortable(),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'warning',
                    'active' => 'success',
                    'sold_out' => 'danger',
                    default => 'gray',
                }),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options(['draft' => 'Draft', 'active' => 'Active', 'sold_out' => 'Sold Out']),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
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

    public static function afterSave(): void
    {
        Cache::forget('tickets:active');
    }

    public static function afterDelete(): void
    {
        Cache::forget('tickets:active');
    }
}
