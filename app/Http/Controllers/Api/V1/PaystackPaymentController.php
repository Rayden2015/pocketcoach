<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Payments\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaystackPaymentController extends Controller
{
    public function __construct(
        private PaymentGateway $paymentGateway,
    ) {}

    public function initialize(Request $request, Tenant $tenant): JsonResponse
    {
        if (empty(config('services.paystack.secret_key'))) {
            return response()->json(['message' => 'Payments are not configured.'], 503);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'callback_url' => ['nullable', 'url'],
        ]);

        $product = Product::query()->where('tenant_id', $tenant->id)->whereKey($validated['product_id'])->firstOrFail();

        if ($product->type !== Product::TYPE_ONE_TIME || ! $product->is_active) {
            return response()->json(['message' => 'Product is not available for one-time purchase.'], 422);
        }

        if ($product->amount_minor === null || $product->amount_minor < 1) {
            return response()->json(['message' => 'Product has no price.'], 422);
        }

        if ($product->course_id === null && $product->program_id === null) {
            return response()->json(['message' => 'Product is not linked to any course or program.'], 422);
        }

        $user = $request->user();
        $reference = 'pc_'.Str::lower(Str::random(32));

        try {
            $payload = DB::transaction(function () use ($tenant, $user, $product, $reference, $validated): array {
                $payment = Payment::query()->create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'provider' => 'paystack',
                    'reference' => $reference,
                    'status' => Payment::STATUS_PENDING,
                    'amount_minor' => $product->amount_minor,
                    'currency' => $product->currency,
                    'customer_email' => $user->email,
                ]);

                $init = $this->paymentGateway->initializeTransaction(
                    email: $user->email,
                    amountMinor: (int) $product->amount_minor,
                    currency: $product->currency,
                    reference: $payment->reference,
                    callbackUrl: $validated['callback_url'] ?? null,
                );

                $payment->update([
                    'authorization_url' => $init->authorizationUrl,
                    'paystack_access_code' => $init->accessCode,
                ]);

                return [
                    'authorization_url' => $init->authorizationUrl,
                    'access_code' => $init->accessCode,
                    'reference' => $init->reference,
                ];
            });
        } catch (RequestException $e) {
            $status = $e->response !== null ? $e->response->status() : 0;

            return response()->json([
                'message' => 'Unable to start payment.',
                'detail' => $e->getMessage(),
            ], $status >= 400 && $status < 600 ? $status : 502);
        }

        return response()->json($payload);
    }
}
