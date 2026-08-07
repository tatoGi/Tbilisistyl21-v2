<x-filament-panels::page>
    <div
        x-data="ticketScanner()"
        x-init="init()"
        x-on:remove="destroy()"
        class="space-y-4"
    >
        {{-- wire:ignore keeps Livewire from remorphing the camera DOM (which
             otherwise re-runs Alpine and starts a second <video>). --}}
        <div
            wire:ignore
            x-show="!$wire.result"
            class="mx-auto aspect-square w-full max-w-sm overflow-hidden rounded-xl border border-gray-200 dark:border-white/10"
        >
            <div id="qr-reader" class="qr-reader h-full w-full"></div>
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
        .qr-reader,
        .qr-reader #qr-reader__scan_region {
            width: 100% !important;
            height: 100% !important;
        }

        .qr-reader video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }

        .qr-reader video:not(:first-of-type),
        .qr-reader img {
            display: none !important;
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
                cameraError: null,

                init() {
                    this.startScanning();
                },

                destroy() {
                    return this.stopScanning();
                },

                getActiveScanner() {
                    return window.__ticketQrScanner || null;
                },

                setActiveScanner(scanner) {
                    window.__ticketQrScanner = scanner;
                },

                async stopScanning() {
                    const scanner = this.getActiveScanner();
                    if (!scanner) {
                        const el = document.getElementById('qr-reader');
                        if (el) {
                            el.innerHTML = '';
                        }
                        return;
                    }

                    try {
                        const state = scanner.getState?.();
                        if (state === 2 || state === 3) {
                            await scanner.stop();
                        }
                    } catch (e) {
                        // already stopped
                    }

                    try {
                        scanner.clear();
                    } catch (e) {
                        // ignore
                    }

                    this.setActiveScanner(null);

                    const el = document.getElementById('qr-reader');
                    if (el) {
                        el.innerHTML = '';
                    }
                },

                async startScanning() {
                    if (window.__ticketQrStarting) {
                        return;
                    }

                    // Already running from a previous Alpine mount — keep it.
                    const existing = this.getActiveScanner();
                    if (existing) {
                        try {
                            const state = existing.getState?.();
                            if (state === 2 || state === 3) {
                                return;
                            }
                        } catch (e) {
                            // fall through and restart
                        }
                    }

                    if (typeof Html5Qrcode === 'undefined') {
                        this.cameraError = 'კამერის ბიბლიოთეკა ვერ ჩაიტვირთა';
                        return;
                    }

                    window.__ticketQrStarting = true;
                    this.cameraError = null;

                    try {
                        await this.stopScanning();

                        const scanner = new Html5Qrcode('qr-reader');
                        this.setActiveScanner(scanner);

                        await scanner.start(
                            { facingMode: 'environment' },
                            {
                                fps: 10,
                                qrbox: (viewfinderWidth, viewfinderHeight) => {
                                    const edge = Math.min(viewfinderWidth, viewfinderHeight);
                                    const size = Math.max(160, Math.floor(edge * 0.65));

                                    return { width: size, height: size };
                                },
                            },
                            (decodedText) => this.onDecoded(decodedText),
                            () => {},
                        );
                    } catch (err) {
                        this.cameraError = 'კამერასთან წვდომა ვერ მოხერხდა: ' + err;
                        this.setActiveScanner(null);
                    } finally {
                        window.__ticketQrStarting = false;
                    }
                },

                onDecoded(decodedText) {
                    const scanner = this.getActiveScanner();
                    if (!scanner) {
                        return;
                    }

                    try {
                        scanner.pause(true);
                    } catch (e) {
                        // ignore
                    }

                    this.$wire.scan(decodedText);
                },

                resumeScanning() {
                    const scanner = this.getActiveScanner();
                    if (!scanner) {
                        this.startScanning();
                        return;
                    }

                    try {
                        scanner.resume();
                    } catch (e) {
                        this.startScanning();
                    }
                },
            };
        };
    </script>
</x-filament-panels::page>
