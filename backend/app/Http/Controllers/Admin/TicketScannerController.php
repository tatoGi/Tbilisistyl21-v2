<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ValidateTicketAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateTicketRequest;
use Illuminate\Http\JsonResponse;

class TicketScannerController extends Controller
{
    public function validateTicket(ValidateTicketRequest $request, ValidateTicketAction $action): JsonResponse
    {
        $result = $action->execute($request->validated(), $request->user()->name);
        return response()->json($result, $result['status']);
    }
}
