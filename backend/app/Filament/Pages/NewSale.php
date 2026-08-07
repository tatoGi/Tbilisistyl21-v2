<?php

namespace App\Filament\Pages;

use App\Actions\CreateWalkUpProductSaleAction;
use App\Actions\CreateWalkUpTicketSaleAction;
use App\Models\Product;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class NewSale extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'New Sale';

    protected static ?string $title = 'New Sale';

    protected static string $view = 'filament.pages.new-sale';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isSeller());
    }

    public function mount(): void
    {
        $this->form->fill(['type' => 'ticket', 'discountAmount' => 0]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->options(['ticket' => 'Ticket', 'product' => 'Product'])
                    ->required()
                    ->live()
                    ->default('ticket'),
                Forms\Components\Select::make('ticketId')
                    ->label('Ticket')
                    ->options(fn () => Ticket::active()->get()->mapWithKeys(
                        fn (Ticket $t) => [$t->id => $t->setLocale('ka')->title . ' — ' . $t->price_gel . ' GEL']
                    ))
                    ->visible(fn (Forms\Get $get) => $get('type') === 'ticket')
                    ->required(fn (Forms\Get $get) => $get('type') === 'ticket'),
                Forms\Components\Select::make('productId')
                    ->label('Product')
                    ->options(fn () => Product::active()->get()->mapWithKeys(
                        fn (Product $p) => [$p->id => $p->setLocale('ka')->title]
                    ))
                    ->live()
                    ->visible(fn (Forms\Get $get) => $get('type') === 'product')
                    ->required(fn (Forms\Get $get) => $get('type') === 'product'),
                Forms\Components\Select::make('size')
                    ->options(function (Forms\Get $get) {
                        $product = Product::with('sizes')->find($get('productId'));

                        return $product
                            ? $product->sizes->pluck('size', 'size')
                            : [];
                    })
                    ->visible(fn (Forms\Get $get) => $get('type') === 'product')
                    ->required(fn (Forms\Get $get) => $get('type') === 'product'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('surname')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('personalNumber')
                    ->label('Personal number')
                    ->required()
                    ->minLength(11)
                    ->maxLength(11),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->visible(fn (Forms\Get $get) => $get('type') === 'product')
                    ->required(fn (Forms\Get $get) => $get('type') === 'product'),
                Forms\Components\TextInput::make('discountAmount')
                    ->label('Discount (GEL)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $soldBy = auth()->user()->name;

        if ($data['type'] === 'ticket') {
            $result = app(CreateWalkUpTicketSaleAction::class)->execute([
                'ticketId' => $data['ticketId'],
                'name' => $data['name'],
                'surname' => $data['surname'],
                'personalNumber' => $data['personalNumber'],
                'email' => $data['email'],
                'discountAmount' => $data['discountAmount'] ?? 0,
                'soldBy' => $soldBy,
            ]);
        } else {
            $result = app(CreateWalkUpProductSaleAction::class)->execute([
                'productId' => $data['productId'],
                'size' => $data['size'],
                'name' => $data['name'],
                'surname' => $data['surname'],
                'personalNumber' => $data['personalNumber'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'discountAmount' => $data['discountAmount'] ?? 0,
                'soldBy' => $soldBy,
            ]);
        }

        if ($result['status'] !== 200) {
            Notification::make()
                ->title('Sale failed: ' . $result['error'])
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Sale recorded — ticket emailed to the buyer')
            ->success()
            ->send();

        $this->form->fill([
            'type' => 'ticket',
            'discountAmount' => 0,
            'name' => null,
            'surname' => null,
            'personalNumber' => null,
            'email' => null,
            'phone' => null,
            'ticketId' => null,
            'productId' => null,
            'size' => null,
        ]);
    }
}
