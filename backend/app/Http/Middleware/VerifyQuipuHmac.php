<?php

namespace App\Http\Middleware;

use App\Services\PaymentService;
use Closure;
use Illuminate\Http\Request;

class VerifyQuipuHmac
{
    public function __construct(private PaymentService $paymentService)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $ref = $request->query('ref');
        $sig = $request->query('sig');

        if (!$ref || !$sig || !$this->paymentService->verifyCallbackHmac($ref, $sig)) {
            return response()->json(['error' => 'Invalid callback signature'], 403);
        }

        return $next($request);
    }
}
