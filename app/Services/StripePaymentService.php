<?php

namespace App\Services;

use App\Models\Payment;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripePaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a PaymentIntent for Stripe.
     */
    public function createIntent(Payment $payment): PaymentIntent
    {
        return PaymentIntent::create([
            'amount' => (int) round($payment->amount * 100),
            'currency' => $payment->currency,
            'description' => 'Consultation #' . $payment->consultation_id,
            'metadata' => ['payment_id' => $payment->id],
            'automatic_payment_methods' => ['enabled' => true],
        ]);
    }

    /**
     * Confirm payment after webhook or manual confirmation.
     */
    public function confirm(string $intentId): ?Payment
    {
        $intent = PaymentIntent::retrieve($intentId);
        $payment = Payment::where('payment_intent_id', $intentId)->first();

        if ($intent->status === 'succeeded' && $payment) {
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'currency' => $intent->currency,
            ]);
        } elseif ($intent->status === 'requires_payment_method' && $payment) {
            $payment->update([
                'status' => 'failed',
                'failure_reason' => $intent->last_payment_error?->message,
            ]);
        }

        return $payment;
    }

    /**
     * Generate a payment link using Stripe Checkout.
     */
    public function createCheckoutSession(Payment $payment): string
    {
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $payment->currency,
                    'product_data' => ['name' => 'Consultation #' . $payment->consultation_id],
                    'unit_amount' => (int) round($payment->amount * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => url('/payment/success'),
            'cancel_url' => url('/payment/failed'),
            'metadata' => ['payment_id' => $payment->id],
        ]);

        return $session->url;
    }
}
