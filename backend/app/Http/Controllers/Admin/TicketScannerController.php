<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ValidateTicketAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketScannerController extends Controller
{
    public function validateTicket(Request $request, ValidateTicketAction $action): JsonResponse
    {
        $result = $action->execute($request->all());
        return response()->json($result, $result['status']);
    }
}
