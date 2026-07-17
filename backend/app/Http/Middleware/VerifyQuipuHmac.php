<?php

namespace App\Http\Middleware;

use App\Services\PaymentService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            Log::channel('payment')->warning('callback: HMAC verification failed', [
                'query' => $request->except('sig'),
                'sig_present' => (bool) $sig,
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid callback signature'], 403);
        }

        return $next($request);
    }
}
