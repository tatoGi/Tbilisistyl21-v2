<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckPersonalNumberRequest;
use App\Models\SoldTicket;

class PersonalNumberController extends Controller
{
    public function check(CheckPersonalNumberRequest $request)
    {
        $count = SoldTicket::where('personal_number', $request->personalNumber)
            ->where('status', 'paid')
            ->count();

        return response()->json([
            'count' => $count,
            'remaining' => max(0, 3 - $count),
        ]);
    }
}
