<?php

namespace App\Http\Controllers\Payment;

use App\Actions\ProcessPaymentCallbackAction;
use App\Http\Controllers\Controller;
use App\Models\ProductOrder;
use App\Models\SoldTicket;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    public function handle(Request $request, ProcessPaymentCallbackAction $action)
    {
        $ref = $request->query('ref');
        $pgOrderId = (int) $request->query('ID');

        Log::channel('payment')->info('callback: received', [
            'query' => $request->except('sig'),
            'ip' => $request->ip(),
        ]);

        $result = $action->execute($ref, $pgOrderId);

        Log::channel('payment')->info('callback: result', [
            'ref' => $ref,
            'pg_order_id' => $pgOrderId,
            'result' => $result,
        ]);

        if (isset($result['error'])) {
            $frontendUrl = config('app.frontend_url');
            return redirect("{$frontendUrl}/dashboard/fail?error={$result['error']}");
        }

        $frontendUrl = config('app.frontend_url');
        return redirect("{$frontendUrl}/dashboard/success");
    }

    public function redirect(Request $request, PaymentService $paymentService)
    {
        $frontendUrl = config('app.frontend_url');

        $token = $request->query('token');
        $data = $paymentService->verifyRedirectToken($token);

        if (!$data) {
            Log::channel('payment')->warning('redirect: invalid token', [
                'token_present' => (bool) $token,
            ]);

            return redirect("{$frontendUrl}/dashboard/fail?error=invalid_token");
        }

        // Load the order created just before this redirect so we can forward the
        // buyer to the gateway's hosted payment page. The PG password stays
        // server-side — it is only ever appended here, never sent to the client.
        $record = $data['collection'] === 'soldTickets'
            ? SoldTicket::where('pg_order_id', $data['pgOrderId'])->first()
            : ProductOrder::where('pg_order_id', $data['pgOrderId'])->first();

        if (!$record || !$record->pg_hpp_url || !$record->pg_password) {
            Log::channel('payment')->warning('redirect: order record missing or incomplete', [
                'collection' => $data['collection'],
                'pg_order_id' => $data['pgOrderId'],
                'record_found' => (bool) $record,
                'has_hpp_url' => (bool) ($record->pg_hpp_url ?? null),
                'has_password' => (bool) ($record->pg_password ?? null),
            ]);

            return redirect("{$frontendUrl}/dashboard/fail?error=order_not_found");
        }

        Log::channel('payment')->info('redirect: forwarding buyer to HPP', [
            'collection' => $data['collection'],
            'pg_order_id' => $data['pgOrderId'],
        ]);

        $separator = str_contains($record->pg_hpp_url, '?') ? '&' : '?';
        $hppUrl = $record->pg_hpp_url . $separator
            . 'id=' . $data['pgOrderId']
            . '&password=' . urlencode($record->pg_password);

        // The per-order HPP password rides in the redirect URL because that is the
        // gateway's hosted-payment-page contract. `Referrer-Policy: no-referrer`
        // stops that URL from leaking onward via the Referer header as
        // defence-in-depth. (A stronger option is an auto-submitting POST form,
        // pending confirmation the gateway accepts the password via POST.)
        return redirect()->away($hppUrl)
            ->header('Referrer-Policy', 'no-referrer');
    }
}
