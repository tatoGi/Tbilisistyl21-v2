<?php

namespace App\Filament\Concerns;

trait EditorOnlyResource
{
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isEditor());
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isEditor());
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isEditor());
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isEditor());
    }
}
