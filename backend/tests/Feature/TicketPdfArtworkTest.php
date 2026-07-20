<?php

namespace Tests\Feature;

use App\Jobs\SendTicketEmailJob;
use App\Models\SiteSetting;
use App\Models\SoldTicket;
use App\Services\EmailService;
use App\Services\PdfService;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketPdfArtworkTest extends TestCase
{
    use RefreshDatabase;

    // 1x1 transparent PNG so dompdf/QR embedding has valid image data.
    private const TINY_PNG_B64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function createPaidTicket(string $eventName): SoldTicket
    {
        return SoldTicket::create([
            'id' => 'PDFT0001',
            'personal_number' => '01001234567',
            'email' => 'buyer@test.com',
            'name' => 'Beka',
            'surname' => 'Abzianidze',
            'amount' => 747,
            'status' => 'paid',
            'event_name' => $eventName,
            'event_date' => '2026-09-21',
            'location' => 'Tbilisi',
            'qr_code' => '{"ticketId":"PDFT0001"}',
        ]);
    }

    private function capturePdfData(SoldTicket $ticket): array
    {
        $captured = [];

        $this->mock(QrCodeService::class, function ($mock) {
            $mock->shouldReceive('generate')
                ->andReturn('data:image/png;base64,' . self::TINY_PNG_B64);
        });

        $this->mock(PdfService::class, function ($mock) use (&$captured) {
            $mock->shouldReceive('generateTicketPdf')
                ->once()
                ->andReturnUsing(function (array $data) use (&$captured) {
                    $captured['data'] = $data;

                    return '%PDF-fake';
                });
        });

        $this->mock(EmailService::class, function ($mock) {
            $mock->shouldReceive('sendTicketEmail')->once();
        });

        (new SendTicketEmailJob($ticket->id))->handle(
            app(EmailService::class),
            app(PdfService::class),
            app(QrCodeService::class),
        );

        return $captured['data'];
    }

    public function test_regular_ticket_uses_standard_artwork(): void
    {
        SiteSetting::set('ticketPdf', [
            'artwork' => 'media/standard.png',
            'jokerArtwork' => 'media/joker.png',
        ]);
        Storage::disk('public')->put('media/standard.png', base64_decode(self::TINY_PNG_B64));

        $data = $this->capturePdfData($this->createPaidTicket('VIP Ticket 1-Day'));

        $this->assertNotNull($data['artworkPath']);
        $this->assertStringEndsWith('standard.png', $data['artworkPath']);
    }

    public function test_joker_ticket_uses_joker_artwork(): void
    {
        SiteSetting::set('ticketPdf', [
            'artwork' => 'media/standard.png',
            'jokerArtwork' => 'media/joker.png',
        ]);
        Storage::disk('public')->put('media/joker.png', base64_decode(self::TINY_PNG_B64));

        $data = $this->capturePdfData($this->createPaidTicket('3-Day Joker Ticket'));

        $this->assertNotNull($data['artworkPath']);
        $this->assertStringEndsWith('joker.png', $data['artworkPath']);
    }

    public function test_missing_artwork_setting_degrades_to_null(): void
    {
        $data = $this->capturePdfData($this->createPaidTicket('VIP Ticket 1-Day'));

        $this->assertNull($data['artworkPath']);
    }

    public function test_uploaded_but_deleted_file_degrades_to_null(): void
    {
        SiteSetting::set('ticketPdf', ['artwork' => 'media/gone.png']);

        $data = $this->capturePdfData($this->createPaidTicket('VIP Ticket 1-Day'));

        $this->assertNull($data['artworkPath']);
    }

    public function test_sold_ticket_joker_detection(): void
    {
        $this->assertTrue($this->createPaidTicket('3-Day JOKER Ticket')->isJokerEvent());

        SoldTicket::where('id', 'PDFT0001')->delete();
        $this->assertTrue($this->createPaidTicket('3-დღიანი ჯოკერ ბილეთი')->isJokerEvent());

        SoldTicket::where('id', 'PDFT0001')->delete();
        $flagged = $this->createPaidTicket('Any Name At All');
        $flagged->update(['is_joker' => true]);
        $this->assertTrue($flagged->isJokerEvent());

        SoldTicket::where('id', 'PDFT0001')->delete();
        $this->assertFalse($this->createPaidTicket('VIP Ticket 1-Day')->isJokerEvent());
    }

    public function test_joker_ticket_with_georgian_name_uses_joker_artwork(): void
    {
        SiteSetting::set('ticketPdf', [
            'artwork' => 'media/standard.png',
            'jokerArtwork' => 'media/joker.png',
        ]);
        Storage::disk('public')->put('media/joker.png', base64_decode(self::TINY_PNG_B64));

        $data = $this->capturePdfData($this->createPaidTicket('3-დღიანი ჯოკერ ბილეთი'));

        $this->assertStringEndsWith('joker.png', $data['artworkPath']);
    }

    public function test_pdf_renders_with_and_without_artwork(): void
    {
        Storage::disk('public')->put('media/art.png', base64_decode(self::TINY_PNG_B64));

        $base = [
            'ticketId' => 'PDFT0001',
            'name' => 'Beka',
            'surname' => 'Abzianidze',
            'personalNumber' => '01001234567',
            'eventName' => 'VIP Ticket 1-Day',
            'eventDate' => 'September 21',
            'location' => 'Tbilisi',
            'amount' => 747,
            'currency' => 'GEL',
            'qrCode' => 'data:image/png;base64,' . self::TINY_PNG_B64,
        ];

        $withArt = app(PdfService::class)->generateTicketPdf(
            $base + ['artworkPath' => Storage::disk('public')->path('media/art.png')],
        );
        $withoutArt = app(PdfService::class)->generateTicketPdf(
            $base + ['artworkPath' => null],
        );

        $this->assertStringStartsWith('%PDF', $withArt);
        $this->assertStringStartsWith('%PDF', $withoutArt);
    }
}
