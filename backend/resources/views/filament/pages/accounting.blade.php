@php
    $report = $this->report;
    $summary = $report['summary'];
    $charts = $this->charts;
    $ledgerRows = $this->ledgerRows;
    $ledgerPages = $this->ledgerPageCount;
    $totalSales = $summary['ticket_count'] + $summary['product_count'];
@endphp

<x-filament-panels::page>
    <div
        class="space-y-6"
        wire:key="accounting-{{ $dateFrom }}-{{ $dateTo }}-{{ $channel }}-{{ $activeTab }}"
    >
        {{-- Filters --}}
        <div class="rounded-xl border border-amber-500/20 bg-gradient-to-br from-gray-950 via-gray-900 to-amber-950/40 p-4 shadow-sm ring-1 ring-white/5">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-amber-200/70">From</label>
                    <input
                        type="date"
                        wire:model.live="dateFrom"
                        class="rounded-lg border-white/10 bg-gray-950 text-sm text-white shadow-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-amber-200/70">To</label>
                    <input
                        type="date"
                        wire:model.live="dateTo"
                        class="rounded-lg border-white/10 bg-gray-950 text-sm text-white shadow-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-amber-200/70">Channel</label>
                    <select
                        wire:model.live="channel"
                        class="rounded-lg border-white/10 bg-gray-950 text-sm text-white shadow-sm"
                    >
                        <option value="all">All channels</option>
                        <option value="online">Online (card)</option>
                        <option value="walk_up">Walk-up</option>
                    </select>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-filament::button color="gray" size="sm" wire:click="setRangeToday">Today</x-filament::button>
                    <x-filament::button color="gray" size="sm" wire:click="setRangeWeek">Week</x-filament::button>
                    <x-filament::button color="gray" size="sm" wire:click="setRangeMonth">Month</x-filament::button>
                    <x-filament::button color="warning" size="sm" wire:click="exportCsv" icon="heroicon-o-arrow-down-tray">
                        Export CSV
                    </x-filament::button>
                </div>
            </div>
        </div>

        {{-- Hero net --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="relative overflow-hidden rounded-2xl border border-emerald-500/30 bg-gradient-to-br from-emerald-950/80 to-gray-950 p-6 shadow-lg lg:col-span-2">
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-emerald-300/80">Estimated net</p>
                <p class="mt-2 text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                    {{ number_format($summary['estimated_net'], 2) }}
                    <span class="text-lg font-medium text-emerald-200/70">GEL</span>
                </p>
                <p class="mt-3 max-w-xl text-sm text-emerald-100/60">
                    Gross {{ number_format($summary['gross'], 2) }} − bank estimate {{ number_format($summary['estimated_bank_fee'], 2) }}
                    (2.5% on online). Walk-up has no bank fee.
                </p>
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-emerald-400/10 blur-2xl"></div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                <div class="rounded-xl border border-white/10 bg-gray-900/80 p-4">
                    <p class="text-xs uppercase tracking-wide text-gray-400">Sales in range</p>
                    <p class="mt-1 text-2xl font-semibold text-white">{{ $totalSales }}</p>
                    <p class="text-xs text-gray-500">{{ $summary['ticket_count'] }} tickets · {{ $summary['product_count'] }} products</p>
                </div>
                <div class="rounded-xl border border-amber-500/20 bg-gray-900/80 p-4">
                    <p class="text-xs uppercase tracking-wide text-amber-200/70">Surcharge collected</p>
                    <p class="mt-1 text-2xl font-semibold text-amber-300">{{ number_format($summary['surcharge'], 2) }} GEL</p>
                    <p class="text-xs text-gray-500">Buyer fee on online checkout</p>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="flex flex-wrap gap-2 border-b border-white/10 pb-1">
            @foreach ([
                'overview' => 'Overview',
                'charts' => 'Charts',
                'ledger' => 'Daily ledger',
            ] as $tab => $label)
                <button
                    type="button"
                    wire:click="setTab('{{ $tab }}')"
                    @class([
                        'rounded-t-lg px-4 py-2 text-sm font-medium transition',
                        'bg-amber-500/20 text-amber-200 ring-1 ring-amber-500/40' => $activeTab === $tab,
                        'text-gray-400 hover:bg-white/5 hover:text-white' => $activeTab !== $tab,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($activeTab === 'overview')
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-white/10 bg-gray-900 p-4">
                    <p class="text-sm text-gray-400">Gross collected</p>
                    <p class="mt-1 text-2xl font-semibold text-white">{{ number_format($summary['gross'], 2) }}</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-gray-900 p-4">
                    <p class="text-sm text-gray-400">Base revenue</p>
                    <p class="mt-1 text-2xl font-semibold text-white">{{ number_format($summary['base'], 2) }}</p>
                </div>
                <div class="rounded-xl border border-rose-500/20 bg-rose-950/30 p-4">
                    <p class="text-sm text-rose-200/80">Est. bank fee</p>
                    <p class="mt-1 text-2xl font-semibold text-rose-200">{{ number_format($summary['estimated_bank_fee'], 2) }}</p>
                </div>
                <div class="rounded-xl border border-emerald-500/20 bg-emerald-950/30 p-4">
                    <p class="text-sm text-emerald-200/80">Est. net</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-300">{{ number_format($summary['estimated_net'], 2) }}</p>
                </div>
            </div>

            {{-- Money flow --}}
            <div class="rounded-xl border border-white/10 bg-gray-900/80 p-5">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-300">Money flow</h3>
                <div class="flex flex-wrap items-center gap-2 text-sm sm:gap-3">
                    <div class="rounded-lg bg-white/5 px-3 py-2">
                        <span class="text-gray-400">Base</span>
                        <span class="ml-2 font-semibold text-white">{{ number_format($summary['base'], 2) }}</span>
                    </div>
                    <span class="text-amber-400">+</span>
                    <div class="rounded-lg bg-amber-500/10 px-3 py-2 ring-1 ring-amber-500/30">
                        <span class="text-amber-200/80">Surcharge</span>
                        <span class="ml-2 font-semibold text-amber-200">{{ number_format($summary['surcharge'], 2) }}</span>
                    </div>
                    <span class="text-gray-500">=</span>
                    <div class="rounded-lg bg-white/5 px-3 py-2">
                        <span class="text-gray-400">Gross</span>
                        <span class="ml-2 font-semibold text-white">{{ number_format($summary['gross'], 2) }}</span>
                    </div>
                    <span class="text-rose-400">−</span>
                    <div class="rounded-lg bg-rose-500/10 px-3 py-2 ring-1 ring-rose-500/30">
                        <span class="text-rose-200/80">Bank</span>
                        <span class="ml-2 font-semibold text-rose-200">{{ number_format($summary['estimated_bank_fee'], 2) }}</span>
                    </div>
                    <span class="text-gray-500">=</span>
                    <div class="rounded-lg bg-emerald-500/10 px-3 py-2 ring-1 ring-emerald-500/40">
                        <span class="text-emerald-200/80">Net</span>
                        <span class="ml-2 font-semibold text-emerald-300">{{ number_format($summary['estimated_net'], 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="overflow-hidden rounded-xl border border-white/10 bg-gray-900">
                    <div class="border-b border-white/10 px-4 py-3 text-sm font-medium text-gray-200">Tickets vs products</div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-white/10 text-sm">
                            <thead class="bg-white/5">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium text-gray-400">Kind</th>
                                    <th class="px-4 py-2 text-right font-medium text-gray-400">Gross</th>
                                    <th class="px-4 py-2 text-right font-medium text-gray-400">Base</th>
                                    <th class="px-4 py-2 text-right font-medium text-gray-400">Fee</th>
                                    <th class="px-4 py-2 text-right font-medium text-gray-400">Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                @foreach (['tickets' => 'Tickets', 'products' => 'Products'] as $key => $label)
                                    @php $row = $report['byKind'][$key]; @endphp
                                    <tr>
                                        <td class="px-4 py-2.5 text-white">{{ $label }}</td>
                                        <td class="px-4 py-2.5 text-right text-white">{{ number_format($row['gross'], 2) }}</td>
                                        <td class="px-4 py-2.5 text-right text-gray-300">{{ number_format($row['base'], 2) }}</td>
                                        <td class="px-4 py-2.5 text-right text-amber-200/80">{{ number_format($row['surcharge'], 2) }}</td>
                                        <td class="px-4 py-2.5 text-right text-gray-300">{{ $row['count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-white/10 bg-gray-900">
                    <div class="border-b border-white/10 px-4 py-3 text-sm font-medium text-gray-200">Online vs walk-up</div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-white/10 text-sm">
                            <thead class="bg-white/5">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium text-gray-400">Channel</th>
                                    <th class="px-4 py-2 text-right font-medium text-gray-400">Gross</th>
                                    <th class="px-4 py-2 text-right font-medium text-gray-400">Base</th>
                                    <th class="px-4 py-2 text-right font-medium text-gray-400">Fee</th>
                                    <th class="px-4 py-2 text-right font-medium text-gray-400">Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                @foreach (['online' => 'Online', 'walk_up' => 'Walk-up'] as $key => $label)
                                    @php $row = $report['byChannel'][$key]; @endphp
                                    <tr>
                                        <td class="px-4 py-2.5 text-white">{{ $label }}</td>
                                        <td class="px-4 py-2.5 text-right text-white">{{ number_format($row['gross'], 2) }}</td>
                                        <td class="px-4 py-2.5 text-right text-gray-300">{{ number_format($row['base'], 2) }}</td>
                                        <td class="px-4 py-2.5 text-right text-amber-200/80">{{ number_format($row['surcharge'], 2) }}</td>
                                        <td class="px-4 py-2.5 text-right text-gray-300">{{ $row['count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if ($activeTab === 'charts')
            <div
                class="grid gap-6 lg:grid-cols-2"
                x-data="accountingCharts(@js($charts))"
                x-init="init()"
                wire:ignore
            >
                <div class="rounded-xl border border-white/10 bg-gray-900 p-4">
                    <h3 class="mb-3 text-sm font-medium text-gray-200">Daily gross</h3>
                    <div class="relative h-64">
                        <canvas x-ref="daily"></canvas>
                    </div>
                </div>
                <div class="rounded-xl border border-white/10 bg-gray-900 p-4">
                    <h3 class="mb-3 text-sm font-medium text-gray-200">Tickets vs products</h3>
                    <div class="relative mx-auto h-64 max-w-xs">
                        <canvas x-ref="kind"></canvas>
                    </div>
                </div>
                <div class="rounded-xl border border-white/10 bg-gray-900 p-4">
                    <h3 class="mb-3 text-sm font-medium text-gray-200">Ticket types (gross)</h3>
                    <div class="relative h-64">
                        <canvas x-ref="ticketTypes"></canvas>
                    </div>
                </div>
                <div class="rounded-xl border border-white/10 bg-gray-900 p-4">
                    <h3 class="mb-3 text-sm font-medium text-gray-200">Online vs walk-up</h3>
                    <div class="relative mx-auto h-64 max-w-xs">
                        <canvas x-ref="channel"></canvas>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-white/10 bg-gray-900">
                <div class="border-b border-white/10 px-4 py-3 text-sm font-medium text-gray-200">Ticket types detail</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead class="bg-white/5">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-400">Type</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-400">Gross</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-400">Base</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-400">Fee</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-400">Count</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            @foreach (['joker' => 'Joker', 'techno' => 'Techno', 'standard' => 'Standard'] as $key => $label)
                                @php $row = $report['byTicketType'][$key]; @endphp
                                <tr>
                                    <td class="px-4 py-2.5 text-white">{{ $label }}</td>
                                    <td class="px-4 py-2.5 text-right text-white">{{ number_format($row['gross'], 2) }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-300">{{ number_format($row['base'], 2) }}</td>
                                    <td class="px-4 py-2.5 text-right text-amber-200/80">{{ number_format($row['surcharge'], 2) }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-300">{{ $row['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($activeTab === 'ledger')
            <div class="overflow-hidden rounded-xl border border-white/10 bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-4 py-3">
                    <h3 class="text-sm font-medium text-gray-200">Daily ledger</h3>
                    <p class="text-xs text-gray-500">
                        Page {{ $ledgerPage }} / {{ $ledgerPages }}
                        · {{ count($report['byDay']) }} days
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead class="bg-white/5">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-400">Date</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-400">Gross (GEL)</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-400">Sales</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-400 sm:w-1/3">Share of period</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            @php $maxGross = max(1, collect($report['byDay'])->max('gross') ?: 1); @endphp
                            @forelse ($ledgerRows as $day)
                                @php $pct = min(100, ($day['gross'] / $maxGross) * 100); @endphp
                                <tr>
                                    <td class="px-4 py-2.5 font-medium text-white">{{ $day['date'] }}</td>
                                    <td class="px-4 py-2.5 text-right text-white">{{ number_format($day['gross'], 2) }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-300">{{ $day['count'] }}</td>
                                    <td class="px-4 py-2.5">
                                        <div class="h-2 overflow-hidden rounded-full bg-white/10">
                                            <div
                                                class="h-full rounded-full bg-gradient-to-r from-amber-500 to-amber-300"
                                                style="width: {{ $pct }}%"
                                            ></div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-gray-500">
                                        No paid / scanned sales in this range.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if (count($report['byDay']) > $ledgerPerPage)
                    <div class="flex items-center justify-between gap-3 border-t border-white/10 px-4 py-3">
                        <x-filament::button
                            color="gray"
                            size="sm"
                            wire:click="previousLedgerPage"
                            :disabled="$ledgerPage <= 1"
                        >
                            Previous
                        </x-filament::button>
                        <x-filament::button
                            color="gray"
                            size="sm"
                            wire:click="nextLedgerPage"
                            :disabled="$ledgerPage >= $ledgerPages"
                        >
                            Next
                        </x-filament::button>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @assets
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    @endassets

    @script
    <script>
        Alpine.data('accountingCharts', (payload) => ({
            payload,
            charts: [],

            init() {
                this.$nextTick(() => this.render());
            },

            destroy() {
                this.charts.forEach((c) => c.destroy());
                this.charts = [];
            },

            render() {
                if (typeof Chart === 'undefined') {
                    return;
                }

                this.destroy();

                const text = '#d1d5db';
                const grid = 'rgba(255,255,255,0.06)';

                if (this.$refs.daily) {
                    this.charts.push(new Chart(this.$refs.daily, {
                        type: 'line',
                        data: {
                            labels: this.payload.daily.labels,
                            datasets: [
                                {
                                    label: 'Gross GEL',
                                    data: this.payload.daily.gross,
                                    borderColor: '#f59e0b',
                                    backgroundColor: 'rgba(245, 158, 11, 0.15)',
                                    fill: true,
                                    tension: 0.35,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { labels: { color: text } } },
                            scales: {
                                x: { ticks: { color: text }, grid: { color: grid } },
                                y: { ticks: { color: text }, grid: { color: grid }, beginAtZero: true },
                            },
                        },
                    }));
                }

                if (this.$refs.kind) {
                    this.charts.push(new Chart(this.$refs.kind, {
                        type: 'doughnut',
                        data: {
                            labels: this.payload.kind.labels,
                            datasets: [{
                                data: this.payload.kind.values,
                                backgroundColor: ['#f59e0b', '#10b981'],
                                borderWidth: 0,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'bottom', labels: { color: text } } },
                        },
                    }));
                }

                if (this.$refs.ticketTypes) {
                    this.charts.push(new Chart(this.$refs.ticketTypes, {
                        type: 'bar',
                        data: {
                            labels: this.payload.ticketTypes.labels,
                            datasets: [{
                                label: 'Gross GEL',
                                data: this.payload.ticketTypes.values,
                                backgroundColor: ['#f43f5e', '#06b6d4', '#f59e0b'],
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { ticks: { color: text }, grid: { display: false } },
                                y: { ticks: { color: text }, grid: { color: grid }, beginAtZero: true },
                            },
                        },
                    }));
                }

                if (this.$refs.channel) {
                    this.charts.push(new Chart(this.$refs.channel, {
                        type: 'doughnut',
                        data: {
                            labels: this.payload.channel.labels,
                            datasets: [{
                                data: this.payload.channel.values,
                                backgroundColor: ['#3b82f6', '#f97316'],
                                borderWidth: 0,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'bottom', labels: { color: text } } },
                        },
                    }));
                }
            },
        }));
    </script>
    @endscript
</x-filament-panels::page>
