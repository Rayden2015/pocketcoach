<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\EnrollmentService;
use App\Services\Payments\PaystackClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function __construct(
        private PaystackClient $paystack,
        private EnrollmentService $enrollments,
    ) {}

    public function handle(Request $request): Response
    {
        $raw = $request->getContent();
        $signature = (string) $request->header('x-paystack-signature', '');

        if (! $this->paystack->verifyWebhookSignature($raw, $signature)) {
            return response('Invalid signature.', 400);
        }

        $event = $request->input('event');
        if ($event !== 'charge.success') {
            return response('Ignored.', 200);
        }

        $data = $request->input('data');
        if (! is_array($data)) {
            return response('No data.', 200);
        }

        $reference = $data['reference'] ?? null;
        if (! is_string($reference) || $reference === '') {
            Log::warning('paystack.webhook.missing_reference');

            return response('No reference.', 200);
        }

        $payment = Payment::query()->where('reference', $reference)->with('product.course')->first();
        if ($payment === null) {
            Log::info('paystack.webhook.unknown_reference', ['reference' => $reference]);

            return response('OK.', 200);
        }

        if ($payment->status === Payment::STATUS_SUCCESS) {
            return response('Already processed.', 200);
        }

        $amount = isset($data['amount']) ? (int) $data['amount'] : null;
        $currency = isset($data['currency']) ? strtoupper((string) $data['currency']) : null;

        if ($amount !== (int) $payment->amount_minor || $currency !== strtoupper((string) $payment->currency)) {
            Log::error('paystack.webhook.amount_mismatch', [
                'payment_id' => $payment->id,
                'expected_amount' => $payment->amount_minor,
                'actual_amount' => $amount,
                'expected_currency' => $payment->currency,
                'actual_currency' => $currency,
            ]);

            return response('Mismatch.', 200);
        }

        DB::transaction(function () use ($payment, $request): void {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => now(),
                'provider_payload' => $request->all(),
            ]);

            $product = $payment->product;
            if ($product === null) {
                Log::warning('paystack.webhook.no_product', ['payment_id' => $payment->id]);

                return;
            }

            $this->enrollments->enrollFromProduct(
                $payment->user,
                $payment->tenant,
                $product,
                'purchase',
            );
        });

        return response('OK.', 200);
    }
}
