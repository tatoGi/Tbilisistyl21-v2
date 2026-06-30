<?php

namespace App\Http\Controllers\Payment;

use App\Actions\ProcessPaymentCallbackAction;
use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    public function handle(Request $request, ProcessPaymentCallbackAction $action)
    {
        $ref = $request->query('ref');
        $pgOrderId = (int) $request->query('ID');

        $result = $action->execute($ref, $pgOrderId);

        if (isset($result['error'])) {
            $frontendUrl = config('app.frontend_url');
            return redirect("{$frontendUrl}/dashboard/fail?error={$result['error']}");
        }

        $frontendUrl = config('app.frontend_url');
        return redirect("{$frontendUrl}/dashboard/success");
    }

    public function redirect(Request $request, PaymentService $paymentService)
    {
        $token = $request->query('token');
        $data = $paymentService->verifyRedirectToken($token);

        if (!$data) {
            $frontendUrl = config('app.frontend_url');
            return redirect("{$frontendUrl}/dashboard/fail?error=invalid_token");
        }

        $frontendUrl = config('app.frontend_url');
        return redirect("{$frontendUrl}/dashboard/success");
    }
}
