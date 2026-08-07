<?php

namespace Tests\Feature;

use App\Services\PdfService;
use Tests\TestCase;

/**
 * The QR sits in a white card with an orange border. If the image is narrower
 * than the card's content box the leftover width collects on one side (the
 * cell is right-aligned), so the white frame looks lopsided and the QR never
 * fills it. These tests measure the real geometry inside the generated PDF.
 */
class TicketPdfQrLayoutTest extends TestCase
{
    // 1x1 transparent PNG — placement geometry doesn't depend on pixel content.
    private const TINY_PNG_B64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private const PAGE_HEIGHT = 1000;

    private function ticketPdf(): string
    {
        return app(PdfService::class)->generateTicketPdf([
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
            'artworkPath' => null,
        ]);
    }

    private function contentStream(string $pdf): string
    {
        $content = '';

        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches);

        foreach ($matches[1] as $stream) {
            $inflated = @gzuncompress($stream);

            if ($inflated !== false && str_contains($inflated, ' re')) {
                $content .= $inflated . "\n";
            }
        }

        $this->assertNotSame('', $content, 'No drawable content stream found in the PDF.');

        return $content;
    }

    /** The QR bitmap: the only square image small enough to be the QR. */
    private function qrPlacement(string $content): array
    {
        preg_match_all('/q\s+([\d.]+) 0 0 ([\d.]+) ([\d.]+) ([\d.]+) cm\s*\/\w+ Do/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as [, $w, $h, $x, $y]) {
            if (abs((float) $w - (float) $h) < 0.5 && (float) $w < 250) {
                return [
                    'x' => (float) $x,
                    'top' => self::PAGE_HEIGHT - (float) $y - (float) $h,
                    'w' => (float) $w,
                    'h' => (float) $h,
                ];
            }
        }

        $this->fail('QR image placement not found in the PDF.');
    }

    /** The smallest filled rectangle that encloses the QR — the white card. */
    private function cardAround(string $content, array $qr): array
    {
        preg_match_all('/([\d.]+) ([\d.]+) ([\d.]+) ([\d.]+) re\s*f/', $content, $matches, PREG_SET_ORDER);

        $best = null;

        foreach ($matches as [, $x, $y, $w, $h]) {
            $rect = [
                'x' => (float) $x,
                'top' => self::PAGE_HEIGHT - (float) $y - (float) $h,
                'w' => (float) $w,
                'h' => (float) $h,
            ];

            $encloses = $rect['x'] <= $qr['x'] + 0.5
                && $rect['top'] <= $qr['top'] + 0.5
                && $rect['x'] + $rect['w'] >= $qr['x'] + $qr['w'] - 0.5
                && $rect['top'] + $rect['h'] >= $qr['top'] + $qr['h'] - 0.5;

            if ($encloses && ($best === null || $rect['w'] * $rect['h'] < $best['w'] * $best['h'])) {
                $best = $rect;
            }
        }

        $this->assertNotNull($best, 'White QR card not found around the QR image.');

        return $best;
    }

    public function test_qr_sits_evenly_inside_its_white_card(): void
    {
        $content = $this->contentStream($this->ticketPdf());
        $qr = $this->qrPlacement($content);
        $card = $this->cardAround($content, $qr);

        $left = $qr['x'] - $card['x'];
        $right = ($card['x'] + $card['w']) - ($qr['x'] + $qr['w']);
        $top = $qr['top'] - $card['top'];
        $bottom = ($card['top'] + $card['h']) - ($qr['top'] + $qr['h']);

        $message = sprintf(
            'Uneven white frame around the QR: left=%.2f right=%.2f top=%.2f bottom=%.2f',
            $left, $right, $top, $bottom,
        );

        $this->assertEqualsWithDelta($left, $right, 0.5, $message);
        $this->assertEqualsWithDelta($top, $bottom, 0.5, $message);
        $this->assertEqualsWithDelta($left, $top, 0.5, $message);
    }

    public function test_qr_card_is_square(): void
    {
        $content = $this->contentStream($this->ticketPdf());
        $card = $this->cardAround($content, $this->qrPlacement($content));

        $this->assertEqualsWithDelta(
            $card['w'],
            $card['h'],
            0.5,
            sprintf('QR card is not square: %.2f x %.2f', $card['w'], $card['h']),
        );
    }
}
