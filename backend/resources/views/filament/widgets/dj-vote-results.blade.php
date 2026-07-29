<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Vote results</x-slot>
        <x-slot name="description">{{ $this->totalVotes() }} votes in this round</x-slot>

        @php($rows = $this->rows())

        @if (count($rows) === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400">No DJs on this ballot yet.</p>
        @else
            <div class="space-y-3">
                @foreach ($rows as $row)
                    <div>
                        <div class="flex justify-between text-sm font-medium">
                            <span>{{ $row['name'] }}</span>
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ $row['votes'] }} · {{ $row['percent'] }}%
                            </span>
                        </div>
                        <div class="mt-1 h-2 w-full overflow-hidden rounded bg-gray-200 dark:bg-gray-700">
                            <div class="h-full rounded bg-primary-600" style="width: {{ $row['percent'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
