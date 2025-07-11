<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VerifyStripeSignature
{
    public function handle(Request $request, Closure $next)
    {
        $secret = config('services.stripe.webhook_secret');

        try {
            Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                $secret
            );
        } catch (SignatureVerificationException $e) {
            throw new AccessDeniedHttpException('Stripe signature mismatch.');
        }

        return $next($request);
    }
}
