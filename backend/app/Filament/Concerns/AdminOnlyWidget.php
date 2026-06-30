<?php

namespace App\Filament\Concerns;

trait AdminOnlyWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
