<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        return response()->json(['data' => $ticket]);
    }
}
