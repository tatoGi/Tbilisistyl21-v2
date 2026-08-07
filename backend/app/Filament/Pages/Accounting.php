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

    /** @var 'overview'|'charts'|'ledger' */
    public string $activeTab = 'overview';

    public int $ledgerPage = 1;

    public int $ledgerPerPage = 10;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->setRangeMonth();
    }

    public function updatedDateFrom(): void
    {
        $this->ledgerPage = 1;
    }

    public function updatedDateTo(): void
    {
        $this->ledgerPage = 1;
    }

    public function updatedChannel(): void
    {
        $this->ledgerPage = 1;
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['overview', 'charts', 'ledger'], true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    public function setRangeToday(): void
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->ledgerPage = 1;
    }

    public function setRangeWeek(): void
    {
        $this->dateFrom = now()->startOfWeek()->toDateString();
        $this->dateTo = now()->endOfWeek()->toDateString();
        $this->ledgerPage = 1;
    }

    public function setRangeMonth(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->endOfMonth()->toDateString();
        $this->ledgerPage = 1;
    }

    public function previousLedgerPage(): void
    {
        $this->ledgerPage = max(1, $this->ledgerPage - 1);
    }

    public function nextLedgerPage(): void
    {
        $total = count($this->report['byDay'] ?? []);
        $maxPage = max(1, (int) ceil($total / $this->ledgerPerPage));
        $this->ledgerPage = min($maxPage, $this->ledgerPage + 1);
    }

    public function getReportProperty(): array
    {
        [$from, $to] = $this->rangeBounds();

        return app(AccountingReportService::class)->bundle(
            $from,
            $to,
            $this->channel ?: 'all',
        );
    }

    /**
     * @return list<array{date: string, gross: float, count: int}>
     */
    public function getLedgerRowsProperty(): array
    {
        $days = $this->report['byDay'] ?? [];
        $offset = ($this->ledgerPage - 1) * $this->ledgerPerPage;

        return array_slice($days, $offset, $this->ledgerPerPage);
    }

    public function getLedgerPageCountProperty(): int
    {
        $total = count($this->report['byDay'] ?? []);

        return max(1, (int) ceil($total / $this->ledgerPerPage));
    }

    /**
     * Chart.js payloads for the Charts tab (JSON-safe).
     *
     * @return array{daily: array, kind: array, ticketTypes: array, channel: array}
     */
    public function getChartsProperty(): array
    {
        $report = $this->report;
        $days = $report['byDay'];

        return [
            'daily' => [
                'labels' => array_column($days, 'date'),
                'gross' => array_map(fn ($d) => $d['gross'], $days),
                'counts' => array_map(fn ($d) => $d['count'], $days),
            ],
            'kind' => [
                'labels' => ['Tickets', 'Products'],
                'values' => [
                    $report['byKind']['tickets']['gross'],
                    $report['byKind']['products']['gross'],
                ],
            ],
            'ticketTypes' => [
                'labels' => ['Joker', 'Techno', 'Standard'],
                'values' => [
                    $report['byTicketType']['joker']['gross'],
                    $report['byTicketType']['techno']['gross'],
                    $report['byTicketType']['standard']['gross'],
                ],
                'counts' => [
                    $report['byTicketType']['joker']['count'],
                    $report['byTicketType']['techno']['count'],
                    $report['byTicketType']['standard']['count'],
                ],
            ],
            'channel' => [
                'labels' => ['Online', 'Walk-up'],
                'values' => [
                    $report['byChannel']['online']['gross'],
                    $report['byChannel']['walk_up']['gross'],
                ],
            ],
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        [$from, $to] = $this->rangeBounds();
        $channel = $this->channel ?: 'all';
        $rows = app(AccountingReportService::class)->csvRows($from, $to, $channel);
        $filename = 'accounting-'.$this->dateFrom.'-'.$this->dateTo.'.csv';

        // Human-readable English headers for accountants (not DB field names).
        $headers = [
            'Type',
            'Order ID',
            'Paid At',
            'Channel',
            'Title',
            'Base Amount (GEL)',
            'Surcharge Rate (%)',
            'Surcharge Amount (GEL)',
            'Gross Amount (GEL)',
            'Estimated Bank Fee (GEL)',
            'Buyer Email',
            'Sold By',
        ];

        return response()->streamDownload(function () use ($rows, $headers) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel on Windows opens Georgian titles correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, [
                    match ($row['type']) {
                        'ticket' => 'Ticket',
                        'product' => 'Product',
                        default => (string) $row['type'],
                    },
                    $row['id'],
                    $row['paid_at'],
                    match ($row['channel']) {
                        'online' => 'Online',
                        'walk_up' => 'Walk-up',
                        default => (string) $row['channel'],
                    },
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
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
