<x-filament-panels::page>
    <div
        x-data="ticketScanner()"
        x-init="init()"
        wire:key="ticket-scanner-root"
        class="space-y-4"
    >
        <div
            x-show="!$wire.result"
            class="mx-auto w-full max-w-md overflow-hidden rounded-xl border border-gray-200 dark:border-white/10"
        >
            <div id="qr-reader" class="qr-reader"></div>
        </div>

        <div x-show="cameraError" x-cloak class="text-sm text-danger-600">
            <span x-text="cameraError"></span>
        </div>

        @if ($result)
            <div @class([
                'rounded-xl border p-4 space-y-2',
                'border-success-300 bg-success-50 dark:border-success-500/40 dark:bg-success-500/10' => $success,
                'border-danger-300 bg-danger-50 dark:border-danger-500/40 dark:bg-danger-500/10' => !$success,
            ])>
                @if ($success)
                    <p class="font-semibold text-success-700 dark:text-success-400">✓ ბილეთი დადასტურდა</p>

                    {{-- Ticket type: what wristband to hand out. Large & bold — read
                    at a glance in a noisy, fast-moving gate line. --}}
                    <div class="rounded-lg bg-amber-500 px-4 py-3 text-center">
                        <span class="text-2xl font-bold uppercase tracking-wide text-black">
                            {{ $result['ticket']['eventName'] }}
                        </span>
                    </div>

                    <p class="text-lg font-medium">{{ $result['ticket']['name'] }} {{ $result['ticket']['surname'] }}</p>
                    <p class="text-sm text-gray-400">{{ $result['ticket']['personalNumber'] }}</p>
                @else
                    <p class="font-semibold text-danger-700 dark:text-danger-400">
                        @switch($result['error'] ?? null)
                            @case('already_scanned')
                                ბილეთი უკვე დასკანერებულია
                                @break
                            @case('ticket_not_paid')
                                ბილეთი გადაუხდელია
                                @break
                            @case('ticket_not_found')
                                ბილეთი ვერ მოიძებნა
                                @break
                            @case('invalid_qr_signature')
                                არასწორი QR კოდი
                                @break
                            @default
                                ბილეთის შემოწმება ვერ მოხერხდა
                        @endswitch
                    </p>
                    @if (($result['error'] ?? null) === 'already_scanned' && !empty($result['scannedAt']))
                        <p class="text-sm text-gray-400">გამოყენებულია: {{ $result['scannedAt'] }}</p>
                    @endif
                @endif

                <x-filament::button wire:click="resetScan" x-on:click="resumeScanning()" color="gray">
                    შემდეგი სკანირება
                </x-filament::button>
            </div>
        @endif
    </div>

    <style>
        /* html5-qrcode stretches the <video> to the container width while keeping
           the camera's native height — on wide Filament layouts that produces a
           tall, tiled/repeated feed. Force a square viewport + cover crop. */
        .qr-reader,
        .qr-reader #qr-reader__scan_region {
            width: 100% !important;
            max-width: 28rem;
            margin-inline: auto;
        }

        .qr-reader video,
        .qr-reader img {
            width: 100% !important;
            max-height: min(70vh, 28rem) !important;
            object-fit: cover !important;
            border-radius: 0;
        }

        .qr-reader #qr-reader__dashboard {
            display: none !important;
        }
    </style>

    <script
        src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"
        integrity="sha384-c9d8RFSL+u3exBOJ4Yp3HUJXS4znl9f+z66d1y54ig+ea249SpqR+w1wyvXz/lk+"
        crossorigin="anonymous"
    ></script>

    <script>
        window.ticketScanner = function () {
            return {
                scanner: null,
                cameraError: null,
                init() {
                    this.startScanning();
                },
                startScanning() {
                    this.scanner = new Html5Qrcode('qr-reader');
                    this.scanner
                        .start(
                            { facingMode: 'environment' },
                            {
                                fps: 10,
                                aspectRatio: 1,
                                qrbox: (viewfinderWidth, viewfinderHeight) => {
                                    const edge = Math.min(viewfinderWidth, viewfinderHeight);
                                    const size = Math.max(180, Math.floor(edge * 0.7));

                                    return { width: size, height: size };
                                },
                            },
                            (decodedText) => this.onDecoded(decodedText),
                            () => {},
                        )
                        .catch((err) => {
                            this.cameraError = 'კამერასთან წვდომა ვერ მოხერხდა: ' + err;
                        });
                },
                onDecoded(decodedText) {
                    if (!this.scanner) return;
                    this.scanner.pause(true);
                    this.$wire.scan(decodedText);
                },
                resumeScanning() {
                    if (this.scanner) {
                        this.scanner.resume();
                    }
                },
            };
        };
    </script>
</x-filament-panels::page>
