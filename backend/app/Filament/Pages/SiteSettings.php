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

    public function mount(): void
    {
        $this->form->fill([
            'hero' => SiteSetting::get('hero', ['heading' => [], 'subheading' => []]),
            'instagramUrl' => SiteSetting::get('instagramUrl'),
            'tiktokUrl' => SiteSetting::get('tiktokUrl'),
            'contact' => SiteSetting::get('contact', [
                'phone' => null, 'phoneHref' => null, 'email' => null, 'address' => null,
            ]),
        ]);
    }

    public function form(Form $form): Form
    {
        $locales = ['ka' => 'ქართული', 'en' => 'English', 'ru' => 'Русский', 'ua' => 'Українська'];

        return $form
            ->schema([
                Forms\Components\Section::make('Festival hero')
                    ->description('Headline shown on the festival landing page.')
                    ->schema([
                        Forms\Components\Fieldset::make('Heading')
                            ->schema(collect($locales)->map(
                                fn ($label, $code) => Forms\Components\TextInput::make("hero.heading.{$code}")->label($label)
                            )->values()->all())
                            ->columns(2),
                        Forms\Components\Fieldset::make('Subheading')
                            ->schema(collect($locales)->map(
                                fn ($label, $code) => Forms\Components\Textarea::make("hero.subheading.{$code}")->label($label)->rows(2)
                            )->values()->all())
                            ->columns(2),
                    ]),
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
            ])
            ->statePath('data');
    }

    public function save(SiteSettingService $service): void
    {
        $data = $this->form->getState();

        foreach (['hero', 'instagramUrl', 'tiktokUrl', 'contact'] as $key) {
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
