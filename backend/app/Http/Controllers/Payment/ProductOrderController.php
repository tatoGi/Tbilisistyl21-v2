<?php

namespace App\Http\Controllers\Payment;

use App\Actions\CreateProductOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductOrderRequest;

class ProductOrderController extends Controller
{
    public function store(CreateProductOrderRequest $request, CreateProductOrderAction $action)
    {
        $result = $action->execute($request->validated() + [
            'ip' => $request->ip(),
            'userAgent' => $request->userAgent(),
        ]);
        return response()->json($result, $result['status']);
    }
}
