<?php

namespace Tests\Feature\Api;

use App\Contracts\Payments\PaymentGateway;
use App\Models\Course;
use App\Models\Product;
use App\Models\Program;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class PaystackInitializeApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{tenant: Tenant, course: Course, product: Product}
     */
    private function tenantWithOneTimeProduct(): array
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'C',
            'slug' => 'c',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Buy',
            'slug' => 'buy',
            'type' => Product::TYPE_ONE_TIME,
            'amount_minor' => 50_000,
            'currency' => 'NGN',
            'course_id' => $course->id,
            'is_active' => true,
        ]);

        return ['tenant' => $tenant, 'course' => $course, 'product' => $product];
    }

    public function test_initialize_returns_503_when_paystack_not_configured(): void
    {
        config(['services.paystack.secret_key' => '']);
        $data = $this->tenantWithOneTimeProduct();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/tenants/{$data['tenant']->slug}/payments/paystack/initialize", [
            'product_id' => $data['product']->id,
        ])->assertStatus(503)->assertJsonPath('message', 'Payments are not configured.');
    }

    public function test_initialize_rejects_free_product(): void
    {
        config(['services.paystack.secret_key' => 'sk_test_x']);
        $this->instance(PaymentGateway::class, new FakePaymentGateway);

        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'C',
            'slug' => 'c',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $freeProduct = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Free',
            'slug' => 'free',
            'type' => Product::TYPE_FREE,
            'currency' => 'NGN',
            'course_id' => $course->id,
            'is_active' => true,
        ]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/tenants/{$tenant->slug}/payments/paystack/initialize", [
            'product_id' => $freeProduct->id,
        ])->assertStatus(422)->assertJsonPath('message', 'Product is not available for one-time purchase.');
    }

    public function test_initialize_returns_checkout_payload_when_gateway_succeeds(): void
    {
        config(['services.paystack.secret_key' => 'sk_test_x']);
        $this->instance(PaymentGateway::class, new FakePaymentGateway);

        $data = $this->tenantWithOneTimeProduct();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/tenants/{$data['tenant']->slug}/payments/paystack/initialize", [
            'product_id' => $data['product']->id,
            'callback_url' => 'https://example.com/callback',
        ]);

        $response->assertOk()
            ->assertJsonPath('access_code', 'test_access_code');

        $this->assertStringContainsString('checkout.paystack.test', (string) $response->json('authorization_url'));
        $this->assertNotEmpty($response->json('reference'));
        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'tenant_id' => $data['tenant']->id,
            'product_id' => $data['product']->id,
            'status' => 'pending',
        ]);
    }
}
