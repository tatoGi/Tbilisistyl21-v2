<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentSurchargeService;
use App\Services\TicketService;

class TicketController extends Controller
{
    public function __construct(private TicketService $ticketService) {}

    public function index()
    {
        return response()->json(['data' => $this->ticketService->listActive()]);
    }

    public function show(string $id)
    {
        $ticket = $this->ticketService->find($id);
        if (!$ticket) {
            return response()->json(['error' => 'not_found'], 404);
        }

        $data = $ticket->toArray();
        $data['price_gel'] = app(PaymentSurchargeService::class)->payable((float) $ticket->price_gel);

        return response()->json(['data' => $data]);
    }
}
