<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'Site settings';

    protected static string $view = 'filament.pages.site-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isEditor());
    }

    public function mount(): void
    {
        $rawSurcharge = SiteSetting::get('payment_surcharge_percent', ['percent' => 3]);
        $surchargePercent = is_numeric($rawSurcharge)
            ? (float) $rawSurcharge
            : (float) (is_array($rawSurcharge) ? ($rawSurcharge['percent'] ?? 3) : 3);

        $this->form->fill([
            'landing' => SiteSetting::get('landing', [
                'title' => [],
                'subtitle' => [],
                'buttonLabel' => [],
                'image' => null,
            ]),
            'hero' => SiteSetting::get('hero', [
                'badge' => [],
                'heading' => [],
                'subheading' => [],
                'image' => null,
            ]),
            'ticketPdf' => SiteSetting::get('ticketPdf', [
                'artwork' => null,
                'jokerArtwork' => null,
            ]),
            'instagramUrl' => SiteSetting::get('instagramUrl'),
            'tiktokUrl' => SiteSetting::get('tiktokUrl'),
            'contact' => SiteSetting::get('contact', [
                'phone' => null, 'phoneHref' => null, 'email' => null, 'address' => null,
            ]),
            'payment_surcharge_percent' => $surchargePercent,
        ]);
    }

    public function form(Form $form): Form
    {
        $locales = ['ka' => 'ქართული', 'en' => 'English', 'ru' => 'Русский', 'ua' => 'Українська'];

        return $form
            ->schema([
                Forms\Components\Section::make('Landing splash')
                    ->description('First page ("enter the energy") shown at the site root (/). Button leads to the festival page.')
                    ->schema([
                        Forms\Components\Fieldset::make('Title')
                            ->schema(collect($locales)->map(
                                fn ($label, $code) => Forms\Components\TextInput::make("landing.title.{$code}")->label($label)
                            )->values()->all())
                            ->columns(2),
                        Forms\Components\Fieldset::make('Subtitle')
                            ->schema(collect($locales)->map(
                                fn ($label, $code) => Forms\Components\TextInput::make("landing.subtitle.{$code}")->label($label)
                            )->values()->all())
                            ->columns(2),
                        Forms\Components\Fieldset::make('Button label')
                            ->schema(collect($locales)->map(
                                fn ($label, $code) => Forms\Components\TextInput::make("landing.buttonLabel.{$code}")->label($label)
                            )->values()->all())
                            ->columns(2),
                        Forms\Components\FileUpload::make('landing.image')
                            ->label('Background image')
                            ->disk('public')
                            ->directory('media')
                            ->visibility('public')
                            ->image()
                            ->imageEditor(),
                    ]),
                Forms\Components\Section::make('Festival hero')
                    ->description('Headline and background for the festival landing page (/dashboard/festival).')
                    ->schema([
                        Forms\Components\Fieldset::make('Badge (optional)')
                            ->schema(collect($locales)->map(
                                fn ($label, $code) => Forms\Components\TextInput::make("hero.badge.{$code}")->label($label)
                            )->values()->all())
                            ->columns(2),
                        Forms\Components\Fieldset::make('Heading')
                            ->schema(collect($locales)->map(
                                fn ($label, $code) => Forms\Components\TextInput::make("hero.heading.{$code}")->label($label)
                            )->values()->all())
                            ->columns(2),
                        Forms\Components\Fieldset::make('Subheading')
                            ->schema(collect($locales)->map(
                                fn ($label, $code) => Forms\Components\RichEditor::make("hero.subheading.{$code}")
                                    ->label($label)
                                    ->toolbarButtons(['bold', 'italic', 'strike', 'h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'link', 'undo', 'redo'])
                            )->values()->all())
                            ->columns(2),
                        Forms\Components\FileUpload::make('hero.image')
                            ->label('Hero background image')
                            ->disk('public')
                            ->directory('media')
                            ->visibility('public')
                            ->image()
                            ->imageEditor(),
                    ]),
                Forms\Components\Section::make('Ticket email PDF')
                    ->description('Artwork embedded in the ticket PDF that buyers receive by email. Joker tickets use their own image; all other tickets use the standard one. Techno tickets use a built-in image and need no upload here. Recommended: portrait/square PNG or JPEG, ~1200px wide.')
                    ->schema([
                        Forms\Components\FileUpload::make('ticketPdf.artwork')
                            ->label('Standard ticket artwork')
                            ->disk('public')
                            ->directory('media')
                            ->visibility('public')
                            ->image(),
                        Forms\Components\FileUpload::make('ticketPdf.jokerArtwork')
                            ->label('Joker ticket artwork')
                            ->disk('public')
                            ->directory('media')
                            ->visibility('public')
                            ->image(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Social links')
                    ->schema([
                        Forms\Components\TextInput::make('instagramUrl')->label('Instagram URL')->url(),
                        Forms\Components\TextInput::make('tiktokUrl')->label('TikTok URL')->url(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Contact')
                    ->schema([
                        Forms\Components\TextInput::make('contact.phone')->label('Phone (display)'),
                        Forms\Components\TextInput::make('contact.phoneHref')->label('Phone (tel: link)'),
                        Forms\Components\TextInput::make('contact.email')->label('Email')->email(),
                        Forms\Components\TextInput::make('contact.address')->label('Address'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Payments')
                    ->description('Online checkout surcharge added to catalog price (buyer sees total only). Walk-up sales are never surcharged.')
                    ->visible(fn () => auth()->user()?->isAdmin() ?? false)
                    ->schema([
                        Forms\Components\TextInput::make('payment_surcharge_percent')
                            ->label('Online surcharge %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(20)
                            ->step(0.1)
                            ->suffix('%')
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(SiteSettingService $service): void
    {
        $data = $this->form->getState();

        if (auth()->user()?->isAdmin()) {
            $percent = round((float) ($data['payment_surcharge_percent'] ?? 3), 2);
            SiteSetting::set('payment_surcharge_percent', ['percent' => $percent]);
        }

        foreach (['landing', 'hero', 'ticketPdf', 'instagramUrl', 'tiktokUrl', 'contact'] as $key) {
            SiteSetting::set($key, $data[$key] ?? null);
        }

        $service->clearCache();

        Notification::make()->title('Saved')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save')
                ->submit('save'),
        ];
    }
}
