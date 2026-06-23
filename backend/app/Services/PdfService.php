<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    public function generateTicketPdf(array $data): string
    {
        $pdf = Pdf::loadView('pdf.ticket', $data)
            ->setPaper([0, 0, 650, 1000]);

        return $pdf->output();
    }
}
