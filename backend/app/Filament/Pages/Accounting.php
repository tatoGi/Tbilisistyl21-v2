<?php

namespace App\Filament\Pages;

use App\Services\AccountingReportService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Accounting extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Accounting';

    protected static ?string $title = 'Accounting';

    protected static string $view = 'filament.pages.accounting';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $channel = 'all';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->setRangeMonth();
    }

    public function setRangeToday(): void
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function setRangeWeek(): void
    {
        $this->dateFrom = now()->startOfWeek()->toDateString();
        $this->dateTo = now()->endOfWeek()->toDateString();
    }

    public function setRangeMonth(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->endOfMonth()->toDateString();
    }

    public function getReportProperty(): array
    {
        [$from, $to] = $this->rangeBounds();
        $service = app(AccountingReportService::class);
        $channel = $this->channel ?: 'all';

        return [
            'summary' => $service->summary($from, $to, $channel),
            'byKind' => $service->breakdownByKind($from, $to, $channel),
            'byTicketType' => $service->breakdownByTicketType($from, $to, $channel),
            'byDay' => $service->breakdownByDay($from, $to, $channel),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        [$from, $to] = $this->rangeBounds();
        $channel = $this->channel ?: 'all';
        $rows = app(AccountingReportService::class)->csvRows($from, $to, $channel);
        $filename = 'accounting-'.$this->dateFrom.'-'.$this->dateTo.'.csv';

        $headers = [
            'type',
            'id',
            'paid_at',
            'channel',
            'title',
            'base_amount',
            'surcharge_rate',
            'surcharge_amount',
            'amount',
            'estimated_bank_fee',
            'email',
            'sold_by',
        ];

        return response()->streamDownload(function () use ($rows, $headers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['type'],
                    $row['id'],
                    $row['paid_at'],
                    $row['channel'],
                    $row['title'],
                    $row['base_amount'],
                    $row['surcharge_rate'],
                    $row['surcharge_amount'],
                    $row['amount'],
                    $row['estimated_bank_fee'],
                    $row['email'],
                    $row['sold_by'],
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rangeBounds(): array
    {
        $from = Carbon::parse($this->dateFrom ?: now()->toDateString())->startOfDay();
        $to = Carbon::parse($this->dateTo ?: now()->toDateString())->endOfDay();

        return [$from, $to];
    }
}
