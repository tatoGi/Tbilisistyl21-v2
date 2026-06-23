<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SoldTicketResource\Pages;
use App\Models\SoldTicket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SoldTicketResource extends Resource
{
    protected static ?string $model = SoldTicket::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Shop';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('id')->disabled(),
            Forms\Components\TextInput::make('personal_number')->disabled(),
            Forms\Components\TextInput::make('email')->disabled(),
            Forms\Components\TextInput::make('name')->disabled(),
            Forms\Components\TextInput::make('surname')->disabled(),
            Forms\Components\TextInput::make('amount')->disabled(),
            Forms\Components\TextInput::make('status')->disabled(),
            Forms\Components\TextInput::make('event_name')->disabled(),
            Forms\Components\DatePicker::make('event_date')->disabled(),
            Forms\Components\TextInput::make('location')->disabled(),
            Forms\Components\DateTimePicker::make('paid_at')->disabled(),
            Forms\Components\DateTimePicker::make('scanned_at')->disabled(),
            Forms\Components\TextInput::make('scanned_by')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->searchable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('surname')->searchable(),
                Tables\Columns\TextColumn::make('personal_number')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('amount')->money('GEL')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'scanned' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('event_name')->searchable(),
                Tables\Columns\TextColumn::make('paid_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('scanned_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'scanned' => 'Scanned']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSoldTickets::route('/'),
        ];
    }
}
