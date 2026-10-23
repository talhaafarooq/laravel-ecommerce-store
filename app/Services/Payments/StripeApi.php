<?php

declare(strict_types = 1);

namespace App\Services\Payments;

use Illuminate\Contracts\Config\Repository as Config;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

/**
 * Thin adapter around the Stripe SDK used by the payment services.
 *
 * Keeping the SDK behind this class makes the payment flow easy to fake in
 * tests and keeps Stripe specifics out of the business logic.
 */
class StripeApi
{
    public function __construct(private readonly Config $config) {}

    public function isConfigured(): bool
    {
        return filled($this->config->get('services.stripe.secret'));
    }

    /**
     * @param array<string, mixed> $params
     *
     * @throws ApiErrorException
     */
    public function createCheckoutSession(array $params, string $idempotencyKey): Session
    {
        return $this->client()->checkout->sessions->create($params, ['idempotency_key' => $idempotencyKey]);
    }

    /**
     * @throws ApiErrorException
     */
    public function retrieveCheckoutSession(string $sessionId): Session
    {
        return $this->client()->checkout->sessions->retrieve($sessionId);
    }

    /**
     * Verify the webhook signature and build the event.
     *
     * @throws SignatureVerificationException|UnexpectedValueException
     */
    public function constructWebhookEvent(string $payload, string $signature): Event
    {
        $secret = (string) $this->config->get('services.stripe.webhook_secret');

        if ($secret === '') {
            throw new UnexpectedValueException('The Stripe webhook secret is not configured.');
        }

        return Webhook::constructEvent(
            $payload,
            $signature,
            $secret,
            (int) $this->config->get('services.stripe.webhook_tolerance', 300),
        );
    }

    protected function client(): StripeClient
    {
        return new StripeClient([
            'api_key'             => (string) $this->config->get('services.stripe.secret'),
            'max_network_retries' => 2,
        ]);
    }
}
