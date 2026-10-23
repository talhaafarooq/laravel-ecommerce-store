<?php

declare(strict_types = 1);

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\StripeApi;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiConnectionException;

function stripeConfigured(): void
{
    config([
        'services.stripe.key'            => 'pk_test_x',
        'services.stripe.secret'         => 'sk_test_x',
        'services.stripe.webhook_secret' => 'whsec_test',
    ]);
}

/** Place a stripe order through the real checkout, with Stripe's API mocked. */
function placeStripeOrder(): Order
{
    stripeConfigured();
    $user    = createCustomer();
    $product = createProduct(['price' => 4000, 'stock_quantity' => 5]);
    cartWith($user, [$product->id => 1]);

    test()->mock(StripeApi::class, function ($mock): void {
        $mock->shouldReceive('isConfigured')->andReturnTrue();
        $mock->shouldReceive('createCheckoutSession')->once()->andReturn(Session::constructFrom([
            'id'  => 'cs_test_123',
            'url' => 'https://checkout.stripe.test/pay/cs_test_123',
        ]));
    });

    test()->actingAs($user)->post(route('checkout.store'), checkoutPayload(['payment_method' => 'stripe']));

    return Order::query()->firstOrFail();
}

function stripeEvent(string $type, array $session): array
{
    return [
        'id'   => 'evt_1', 'object' => 'event', 'type' => $type,
        'data' => ['object' => array_merge(['object' => 'checkout.session', 'id' => 'cs_test_123'], $session)],
    ];
}

function postWebhook(array $event, ?string $secret = 'whsec_test')
{
    // Webhooks use the real client so signatures are genuinely verified.
    app()->instance(StripeApi::class, new StripeApi(app('config')));

    $payload   = json_encode($event);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", (string) $secret);

    return test()->call('POST', route('webhooks.stripe'), [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
    ], $payload);
}

it('sends the customer to Stripe and records a pending payment', function (): void {
    $order = placeStripeOrder();

    $payment = $order->payments()->firstOrFail();

    expect($order->payment_method)->toBe(PaymentMethod::Stripe)
        ->and($order->payment_status)->toBe(PaymentStatus::Unpaid)
        ->and($payment->reference)->toBe('cs_test_123')
        ->and($payment->status)->toBe(TransactionStatus::Pending)
        ->and($payment->amount->amount)->toBe($order->grand_total->amount);
});

it('redirects to the hosted checkout page via an Inertia location response', function (): void {
    stripeConfigured();
    $user = createCustomer();
    cartWith($user, [createProduct()->id => 1]);
    $this->mock(StripeApi::class, function ($mock): void {
        $mock->shouldReceive('isConfigured')->andReturnTrue();
        $mock->shouldReceive('createCheckoutSession')->andReturn(Session::constructFrom(['id' => 'cs_x', 'url' => 'https://checkout.stripe.test/x']));
    });

    $this->actingAs($user)->withHeader('X-Inertia', 'true')->post(route('checkout.store'), checkoutPayload(['payment_method' => 'stripe']))
        ->assertStatus(409)->assertHeader('X-Inertia-Location', 'https://checkout.stripe.test/x');
});

it('keeps the order and reports an error when Stripe cannot start a session', function (): void {
    stripeConfigured();
    $user = createCustomer();
    cartWith($user, [createProduct()->id => 1]);
    $this->mock(StripeApi::class, function ($mock): void {
        $mock->shouldReceive('isConfigured')->andReturnTrue();
        $mock->shouldReceive('createCheckoutSession')->andThrow(new ApiConnectionException('network down'));
    });

    $this->actingAs($user)->post(route('checkout.store'), checkoutPayload(['payment_method' => 'stripe']))->assertRedirect();

    $order = Order::query()->firstOrFail();
    expect($order->payment_status)->toBe(PaymentStatus::Unpaid)
        ->and($order->payments)->toHaveCount(1)
        ->and($order->payments->first()->status)->toBe(TransactionStatus::Pending)
        ->and($order->payments->first()->reference)->toBeNull();
});

describe('webhook', function (): void {
    it('marks the order paid and moves it to processing', function (): void {
        $order = placeStripeOrder();

        postWebhook(stripeEvent('checkout.session.completed', [
            'payment_status' => 'paid', 'status' => 'complete', 'amount_total' => $order->grand_total->amount, 'currency' => 'usd',
        ]))->assertOk();

        $order->refresh();
        expect($order->payment_status)->toBe(PaymentStatus::Paid)
            ->and($order->status)->toBe(OrderStatus::Processing)
            ->and($order->paid_at)->not->toBeNull()
            ->and($order->payments->first()->status)->toBe(TransactionStatus::Succeeded);
    });

    it('is idempotent when Stripe retries delivery', function (): void {
        $order = placeStripeOrder();
        $event = stripeEvent('checkout.session.completed', [
            'payment_status' => 'paid', 'amount_total' => $order->grand_total->amount, 'currency' => 'usd',
        ]);

        postWebhook($event)->assertOk();
        postWebhook($event)->assertOk();

        expect($order->statusHistories()->where('to_status', OrderStatus::Processing)->count())->toBe(1);
    });

    it('rejects a payment whose amount does not match the order', function (): void {
        $order = placeStripeOrder();

        postWebhook(stripeEvent('checkout.session.completed', [
            'payment_status' => 'paid', 'amount_total' => 1, 'currency' => 'usd',
        ]))->assertOk();

        expect($order->fresh()->payment_status)->toBe(PaymentStatus::Failed)
            ->and($order->payments->first()->status)->toBe(TransactionStatus::Failed);
    });

    it('marks an expired session as failed but keeps the order open', function (): void {
        $order = placeStripeOrder();

        postWebhook(stripeEvent('checkout.session.expired', ['status' => 'expired']))->assertOk();

        expect($order->fresh()->status)->toBe(OrderStatus::Pending)->and($order->fresh()->payment_status)->toBe(PaymentStatus::Failed);
    });

    it('ignores sessions it does not know about', function (): void {
        stripeConfigured();

        postWebhook(stripeEvent('checkout.session.completed', ['id' => 'cs_unknown', 'payment_status' => 'paid']))->assertOk();

        expect(Payment::query()->count())->toBe(0);
    });

    it('rejects a bad signature', function (): void {
        stripeConfigured();

        postWebhook(stripeEvent('checkout.session.completed', []), secret: 'wrong-secret')->assertStatus(400);
    });

    it('rejects calls when no webhook secret is configured', function (): void {
        config(['services.stripe.webhook_secret' => '']);

        postWebhook(stripeEvent('checkout.session.completed', []))->assertStatus(400);
    });
});

describe('return from Stripe', function (): void {
    it('confirms a paid session immediately', function (): void {
        $order = placeStripeOrder();
        $this->mock(StripeApi::class, function ($mock) use ($order): void {
            $mock->shouldReceive('isConfigured')->andReturnTrue();
            $mock->shouldReceive('retrieveCheckoutSession')->with('cs_test_123')->andReturn(Session::constructFrom([
                'id'           => 'cs_test_123', 'payment_status' => 'paid', 'status' => 'complete',
                'amount_total' => $order->grand_total->amount, 'currency' => 'usd',
            ]));
        });

        $this->actingAs($order->user)->get(route('checkout.stripe.success', [$order, 'session_id' => 'cs_test_123']))
            ->assertRedirect(route('checkout.complete', $order));

        expect($order->fresh()->payment_status)->toBe(PaymentStatus::Paid);
    });

    it('does not trust the success url without a paid session', function (): void {
        $order = placeStripeOrder();

        $this->actingAs($order->user)->get(route('checkout.stripe.success', [$order, 'session_id' => 'cs_forged']))
            ->assertRedirect(route('account.orders.show', $order));

        expect($order->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
    });

    it('does not let another customer confirm someone elses order', function (): void {
        $order = placeStripeOrder();

        $this->actingAs(createCustomer())->get(route('checkout.stripe.success', [$order, 'session_id' => 'cs_test_123']))->assertForbidden();
    });

    it('records a cancelled payment attempt and allows retry', function (): void {
        $order = placeStripeOrder();

        $this->actingAs($order->user)->get(route('checkout.stripe.cancel', $order))->assertRedirect(route('account.orders.show', $order));

        expect($order->payments->first()->status)->toBe(TransactionStatus::Failed)
            ->and($order->fresh()->payment_status)->toBe(PaymentStatus::Failed);
    });
});
