@php
    $report = $this->report;
    $summary = $report['summary'];
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">From</label>
                    <input
                        type="date"
                        wire:model.live="dateFrom"
                        class="rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">To</label>
                    <input
                        type="date"
                        wire:model.live="dateTo"
                        class="rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Channel</label>
                    <select
                        wire:model.live="channel"
                        class="rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white"
                    >
                        <option value="all">All</option>
                        <option value="online">Online</option>
                        <option value="walk_up">Walk-up</option>
                    </select>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-filament::button color="gray" size="sm" wire:click="setRangeToday">Today</x-filament::button>
                    <x-filament::button color="gray" size="sm" wire:click="setRangeWeek">Week</x-filament::button>
                    <x-filament::button color="gray" size="sm" wire:click="setRangeMonth">Month</x-filament::button>
                    <x-filament::button size="sm" wire:click="exportCsv">Export CSV</x-filament::button>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-xl border border-amber-500/20 bg-white p-4 shadow-sm dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Gross collected</p>
                <p class="mt-1 text-2xl font-semibold">{{ number_format($summary['gross'], 2) }} GEL</p>
            </div>
            <div class="rounded-xl border border-amber-500/20 bg-white p-4 shadow-sm dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Base revenue</p>
                <p class="mt-1 text-2xl font-semibold">{{ number_format($summary['base'], 2) }} GEL</p>
            </div>
            <div class="rounded-xl border border-amber-500/20 bg-white p-4 shadow-sm dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Surcharge collected</p>
                <p class="mt-1 text-2xl font-semibold">{{ number_format($summary['surcharge'], 2) }} GEL</p>
            </div>
            <div class="rounded-xl border border-amber-500/20 bg-white p-4 shadow-sm dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Estimated bank fee</p>
                <p class="mt-1 text-2xl font-semibold">{{ number_format($summary['estimated_bank_fee'], 2) }} GEL</p>
            </div>
            <div class="rounded-xl border border-amber-500/20 bg-white p-4 shadow-sm dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Estimated net</p>
                <p class="mt-1 text-2xl font-semibold">{{ number_format($summary['estimated_net'], 2) }} GEL</p>
            </div>
            <div class="rounded-xl border border-amber-500/20 bg-white p-4 shadow-sm dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Counts</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ $summary['ticket_count'] }} tickets · {{ $summary['product_count'] }} products
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-4 py-3 font-medium dark:border-white/10">Tickets vs products</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium">Kind</th>
                                <th class="px-4 py-2 text-right font-medium">Gross</th>
                                <th class="px-4 py-2 text-right font-medium">Base</th>
                                <th class="px-4 py-2 text-right font-medium">Surcharge</th>
                                <th class="px-4 py-2 text-right font-medium">Count</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                            @foreach (['tickets' => 'Tickets', 'products' => 'Products'] as $key => $label)
                                @php $row = $report['byKind'][$key]; @endphp
                                <tr>
                                    <td class="px-4 py-2">{{ $label }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($row['gross'], 2) }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($row['base'], 2) }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($row['surcharge'], 2) }}</td>
                                    <td class="px-4 py-2 text-right">{{ $row['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-4 py-3 font-medium dark:border-white/10">Ticket types</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium">Type</th>
                                <th class="px-4 py-2 text-right font-medium">Gross</th>
                                <th class="px-4 py-2 text-right font-medium">Base</th>
                                <th class="px-4 py-2 text-right font-medium">Surcharge</th>
                                <th class="px-4 py-2 text-right font-medium">Count</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                            @foreach (['joker' => 'Joker', 'techno' => 'Techno', 'standard' => 'Standard'] as $key => $label)
                                @php $row = $report['byTicketType'][$key]; @endphp
                                <tr>
                                    <td class="px-4 py-2">{{ $label }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($row['gross'], 2) }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($row['base'], 2) }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($row['surcharge'], 2) }}</td>
                                    <td class="px-4 py-2 text-right">{{ $row['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-4 py-3 font-medium dark:border-white/10">By day</div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Date</th>
                            <th class="px-4 py-2 text-right font-medium">Gross</th>
                            <th class="px-4 py-2 text-right font-medium">Count</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($report['byDay'] as $day)
                            <tr>
                                <td class="px-4 py-2">{{ $day['date'] }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($day['gross'], 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ $day['count'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-gray-500">No paid sales in this range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
