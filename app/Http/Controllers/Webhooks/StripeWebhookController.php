<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payments\StripeApi;
use App\Services\Payments\StripePaymentHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

final class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeApi $stripe, StripePaymentHandler $handler): JsonResponse
    {
        try {
            $event = $stripe->constructWebhookEvent($request->getContent(), (string) $request->header('Stripe-Signature'));
        } catch (SignatureVerificationException|UnexpectedValueException $exception) {
            Log::warning('Rejected a Stripe webhook.', ['reason' => $exception->getMessage(), 'ip' => $request->ip()]);

            return response()->json(['message' => 'Invalid webhook signature.'], 400);
        }

        $handler->handleWebhook($event);

        return response()->json(['received' => true]);
    }
}
