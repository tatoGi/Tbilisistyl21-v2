<?php

namespace App\Http\Controllers\Payment;

use App\Actions\CreateTicketOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTicketOrderRequest;

class TicketOrderController extends Controller
{
    public function store(CreateTicketOrderRequest $request, CreateTicketOrderAction $action)
    {
        $result = $action->execute($request->validated());
        return response()->json($result, $result['status']);
    }
}
