<?php

namespace App\Filament\Resources\ProductOrderResource\Pages;

use App\Filament\Resources\ProductOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProductOrder extends CreateRecord
{
    protected static string $resource = ProductOrderResource::class;
}
