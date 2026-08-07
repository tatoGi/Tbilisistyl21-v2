<?php

namespace App\Actions;

use App\Jobs\SendProductOrderEmailJob;
use App\Jobs\SendTicketEmailJob;
use App\Models\JokerTicket;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\SoldTicket;
use App\Models\Ticket;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPaymentCallbackAction
{
    public function __construct(private PaymentService $paymentService) {}

    public function execute(string $ref, int $pgOrderId): array
    {
        $soldTicket = SoldTicket::where('id', $ref)
            ->where('pg_order_id', $pgOrderId)
            ->first();

        if (!$soldTicket) {
            $productOrder = ProductOrder::where('id', $ref)
                ->where('pg_order_id', $pgOrderId)
                ->first();

            if ($productOrder) {
                return $this->processProductOrder($productOrder, $ref, $pgOrderId);
            }

            Log::channel('payment')->warning('callback: order not found', [
                'ref' => $ref,
                'pg_order_id' => $pgOrderId,
            ]);

            return ['error' => 'ticket_not_found', 'status' => 404];
        }

        if ($soldTicket->status === 'paid') {
            return ['status' => 200];
        }

        $details = $this->paymentService->getOrderDetails(
            $soldTicket->pg_order_id,
            $soldTicket->pg_password,
        );

        $pgStatus = strtolower($details['status'] ?? '');
        $isPaid = in_array($pgStatus, ['paid', 'completed', 'fullypaid'], true);

        Log::channel('payment')->info('callback: gateway order status', [
            'ref' => $ref,
            'pg_order_id' => $pgOrderId,
            'pg_status' => $pgStatus,
            'is_paid' => $isPaid,
        ]);

        if (!$isPaid) {
            Log::channel('payment')->warning('callback: payment not paid', [
                'ref' => $ref,
                'pg_order_id' => $pgOrderId,
                'pg_status' => $pgStatus,
            ]);

            $soldTicket->update([
                'status' => 'failed',
                'failed_at' => now(),
                'fail_reason' => 'payment_' . $pgStatus,
            ]);

            return ['error' => 'payment_failed', 'status' => 400];
        }

        if (!$this->paymentService->verifyPaidAmount((float) $soldTicket->amount, $details)) {
            Log::channel('payment')->warning('callback: amount mismatch', [
                'ref' => $ref,
                'pg_order_id' => $pgOrderId,
                'expected_gel' => (float) $soldTicket->amount,
                'reported' => $this->paymentService->extractPaidAmount($details),
            ]);

            $soldTicket->update([
                'status' => 'failed',
                'failed_at' => now(),
                'fail_reason' => 'amount_mismatch',
            ]);

            return ['error' => 'amount_mismatch', 'status' => 400];
        }

        return DB::transaction(function () use ($soldTicket) {
            $locked = SoldTicket::where('id', $soldTicket->id)->lockForUpdate()->first();

            if ($locked->status === 'paid') {
                return ['status' => 200];
            }

            $maxTickets = config('app.max_tickets_per_person', 3);

            $paidCount = SoldTicket::where('personal_number', $locked->personal_number)
                ->where('status', 'paid')
                ->where('id', '!=', $locked->id)
                ->lockForUpdate()
                ->get()
                ->count();

            if ($paidCount >= $maxTickets) {
                $locked->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'fail_reason' => 'max_tickets_reached',
                ]);

                return ['error' => 'max_tickets_reached', 'status' => 400];
            }

            $decremented = DB::table('tickets')
                ->where('id', $locked->original_ticket_id)
                ->where('status', 'active')
                ->where('quantity', '>', 0)
                ->decrement('quantity');

            if ($decremented === 0) {
                $locked->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'fail_reason' => 'sold_out',
                ]);

                return ['error' => 'sold_out', 'status' => 400];
            }

            $ticket = Ticket::find($locked->original_ticket_id);
            if ($ticket && $ticket->quantity <= 0) {
                $ticket->update(['status' => 'sold_out']);
            }

            $locked->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            if ($locked->isJokerEvent()) {
                JokerTicket::create([
                    'sold_ticket_id' => $locked->id,
                    'personal_number' => $locked->personal_number,
                    'email' => $locked->email,
                    'name' => $locked->name,
                    'surname' => $locked->surname,
                ]);
            }

            SendTicketEmailJob::dispatch($locked->id);

            return ['status' => 200];
        });
    }

    private function processProductOrder(ProductOrder $order, string $ref, int $pgOrderId): array
    {
        if ($order->status === 'paid') {
            return ['status' => 200];
        }

        $details = $this->paymentService->getOrderDetails(
            $order->pg_order_id,
            $order->pg_password,
        );

        $pgStatus = strtolower($details['status'] ?? '');
        $isPaid = in_array($pgStatus, ['paid', 'completed', 'fullypaid'], true);

        Log::channel('payment')->info('callback: gateway order status', [
            'collection' => 'productOrders',
            'ref' => $ref,
            'pg_order_id' => $pgOrderId,
            'pg_status' => $pgStatus,
            'is_paid' => $isPaid,
        ]);

        if (!$isPaid) {
            Log::channel('payment')->warning('callback: payment not paid', [
                'collection' => 'productOrders',
                'ref' => $ref,
                'pg_order_id' => $pgOrderId,
                'pg_status' => $pgStatus,
            ]);

            $order->update(['status' => 'failed']);

            return ['error' => 'payment_failed', 'status' => 400];
        }

        if (!$this->paymentService->verifyPaidAmount((float) $order->amount, $details)) {
            Log::channel('payment')->warning('callback: amount mismatch', [
                'collection' => 'productOrders',
                'ref' => $ref,
                'pg_order_id' => $pgOrderId,
                'expected_gel' => (float) $order->amount,
                'reported' => $this->paymentService->extractPaidAmount($details),
            ]);

            $order->update(['status' => 'failed']);

            return ['error' => 'amount_mismatch', 'status' => 400];
        }

        return DB::transaction(function () use ($order) {
            $locked = ProductOrder::where('id', $order->id)->lockForUpdate()->first();

            if ($locked->status === 'paid') {
                return ['status' => 200];
            }

            $decremented = DB::table('product_sizes')
                ->where('product_id', $locked->product_id)
                ->where('size', $locked->size)
                ->where('quantity', '>', 0)
                ->decrement('quantity');

            if ($decremented === 0) {
                Log::channel('payment')->warning('callback: product size sold out', [
                    'ref' => $locked->id,
                    'product_id' => $locked->product_id,
                    'size' => $locked->size,
                ]);

                $locked->update(['status' => 'failed']);

                return ['error' => 'sold_out', 'status' => 400];
            }

            // Raw decrement skips the ProductSize model events that normally
            // flush the public products cache.
            Cache::forget(Product::API_CACHE_KEY);

            $locked->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            SendProductOrderEmailJob::dispatch($locked->id);

            return ['status' => 200];
        });
    }

}
