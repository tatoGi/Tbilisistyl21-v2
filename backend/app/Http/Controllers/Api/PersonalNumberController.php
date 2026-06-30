<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckPersonalNumberRequest;
use App\Models\SoldTicket;

class PersonalNumberController extends Controller
{
    public function check(CheckPersonalNumberRequest $request)
    {
        $maxTickets = config('app.max_tickets_per_person', 3);

        $reservedCount = SoldTicket::where('personal_number', $request->personalNumber)
            ->whereIn('status', ['paid', 'pending'])
            ->count();

        return response()->json([
            'canPurchase' => $reservedCount < $maxTickets,
        ]);
    }
}
