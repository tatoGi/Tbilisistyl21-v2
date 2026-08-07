<?php

namespace App\Filament\Pages;

use App\Actions\ValidateTicketAction;
use Filament\Pages\Page;

class TicketScanner extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Ticket Scanner';

    protected static ?string $title = 'Ticket Scanner';

    protected static string $view = 'filament.pages.ticket-scanner';

    public ?array $result = null;

    public bool $success = false;

    public function scan(string $qrData): void
    {
        $decoded = json_decode($qrData, true);

        if (!is_array($decoded)) {
            $this->success = false;
            $this->result = ['error' => 'invalid_qr_signature'];
            return;
        }

        $outcome = app(ValidateTicketAction::class)->execute($decoded);

        $this->success = $outcome['status'] === 200;
        $this->result = $outcome;
    }

    public function resetScan(): void
    {
        $this->result = null;
        $this->success = false;
    }
}
