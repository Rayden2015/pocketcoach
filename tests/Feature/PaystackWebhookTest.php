<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Program;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaystackWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_invalid_webhook_signature(): void
    {
        $this->call(
            'POST',
            '/api/webhooks/paystack',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"event":"charge.success"}',
        )->assertStatus(400);
    }

    public function test_charge_success_fulfills_enrollment(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::query()->create([
            'name' => 'Test Coach',
            'slug' => 'test-coach',
        ]);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Program',
            'slug' => 'program',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'Course',
            'slug' => 'course',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Paid',
            'slug' => 'paid',
            'type' => Product::TYPE_ONE_TIME,
            'amount_minor' => 1_000,
            'currency' => 'NGN',
            'course_id' => $course->id,
            'is_active' => true,
        ]);
        $payment = Payment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'product_id' => $product->id,
            'reference' => 'pc_test_reference_123',
            'status' => Payment::STATUS_PENDING,
            'amount_minor' => 1_000,
            'currency' => 'NGN',
            'customer_email' => $user->email,
        ]);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => $payment->reference,
                'amount' => $payment->amount_minor,
                'currency' => $payment->currency,
            ],
        ];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha512', $raw, (string) config('services.paystack.secret_key'));

        $this->call(
            'POST',
            '/api/webhooks/paystack',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
            ],
            $raw,
        )->assertOk();

        $payment->refresh();
        $this->assertSame(Payment::STATUS_SUCCESS, $payment->status);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
        ]);
    }
}
