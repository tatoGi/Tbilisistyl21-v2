<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Enums\ThemeMode;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\SpatieLaravelTranslatablePlugin;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Support\HtmlString;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Tbilisi Style 21')
            ->brandLogo(asset('brand-logo.jpeg'))
            ->brandLogoHeight('2.4rem')
            ->favicon(asset('brand-logo.jpeg'))
            ->defaultThemeMode(ThemeMode::Dark)
            ->font('Oswald')
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Zinc,
                'warning' => Color::Amber,
                'success' => Color::Emerald,
                'danger' => Color::Rose,
            ])
            ->navigationGroups([
                NavigationGroup::make('Team')->icon('heroicon-o-user-group'),
                NavigationGroup::make('Content')->icon('heroicon-o-document-text'),
                NavigationGroup::make('Catalog')->icon('heroicon-o-squares-2x2'),
                NavigationGroup::make('Sales')->icon('heroicon-o-banknotes'),
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): HtmlString => new HtmlString($this->customStyles()),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                SpatieLaravelTranslatablePlugin::make()
                    ->defaultLocales(['ka', 'en', 'ru', 'ua']),
            ]);
    }

    /**
     * Festival flavoured polish injected into every admin page: an amber-tinted
     * sidebar/topbar gradient, glowing active nav items and subtly outlined
     * cards. Kept inline (no Tailwind build) so it works out of the box.
     */
    private function customStyles(): string
    {
        return <<<'HTML'
<style>
    :root {
        --ts-accent: #f59e0b;
        /* Stop Filament primary blue leaking into sidebar link text */
        --c-primary-400: 251 191 36;
        --c-primary-500: 245 158 11;
        --c-primary-600: 217 119 6;
    }

    /* Sidebar: festival dark gradient + amber edge */
    .fi-sidebar,
    .fi-sidebar .fi-sidebar-nav {
        background: linear-gradient(180deg, #17120a 0%, #0b0b0d 65%) !important;
    }
    .fi-sidebar { border-right: 1px solid rgba(245, 158, 11, 0.18) !important; }
    .fi-sidebar-header {
        background: transparent !important;
        border-bottom: 1px solid rgba(245, 158, 11, 0.12);
    }

    /* Group labels in festival amber */
    .fi-sidebar-group-label {
        color: rgba(245, 158, 11, 0.85) !important;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    /* Nav links: white on dark, amber when active — override Filament primary blue */
    .fi-sidebar-item-button,
    .fi-sidebar-item-button .fi-sidebar-item-label,
    .fi-sidebar-item-button .fi-sidebar-item-icon {
        color: rgba(255, 255, 255, 0.88) !important;
    }
    .fi-sidebar-item-button:hover,
    .fi-sidebar-item-button:hover .fi-sidebar-item-label,
    .fi-sidebar-item-button:hover .fi-sidebar-item-icon {
        color: #fbbf24 !important;
    }
    .fi-sidebar-item-active > .fi-sidebar-item-button,
    .fi-sidebar-item-active > .fi-sidebar-item-button .fi-sidebar-item-label,
    .fi-sidebar-item-active > .fi-sidebar-item-button .fi-sidebar-item-icon {
        color: #fbbf24 !important;
    }

    /* Active + hover nav items glow */
    .fi-sidebar-item-active > .fi-sidebar-item-button {
        background: linear-gradient(90deg, rgba(245, 158, 11, 0.22), rgba(245, 158, 11, 0)) !important;
        box-shadow: inset 3px 0 0 var(--ts-accent);
    }
    .fi-sidebar-item-button:hover {
        background: rgba(245, 158, 11, 0.10) !important;
    }

    /* Topbar accent */
    .fi-topbar nav {
        background: linear-gradient(90deg, rgba(245, 158, 11, 0.07), rgba(11, 11, 13, 0.6)) !important;
        backdrop-filter: blur(6px);
        border-bottom: 1px solid rgba(245, 158, 11, 0.12);
    }

    /* Cards / widgets: subtle amber outline + glow */
    .fi-wi .fi-section,
    .fi-wi-stats-overview-stat,
    .fi-section.fi-section-has-content-before {
        border: 1px solid rgba(245, 158, 11, 0.12) !important;
    }
    .fi-wi-stats-overview-stat {
        background: linear-gradient(160deg, rgba(245, 158, 11, 0.06), transparent) !important;
    }

    /* Primary buttons pop a little more */
    .fi-btn.fi-color-primary {
        box-shadow: 0 4px 16px -6px rgba(245, 158, 11, 0.6);
    }
</style>
HTML;
    }
}
