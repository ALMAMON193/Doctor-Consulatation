<?php

namespace App\Http\Controllers\API\Patient;

use App\Http\Controllers\Controller;
use App\Models\{Consultation, Coupon, DoctorProfile, Payment};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;

class ConsultationBookingController extends Controller
{
    public function __construct()
    {
        // Initialise Stripe SDK once for this controller
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /* ----------------------------------------------------------------------
     | 1)  Create Checkout Session and local consultation/payment rows
     |-----------------------------------------------------------------------*/
    public function book(Request $request): JsonResponse
    {
        /* ------------ 1. Validate request -------------------------------- */
        $validated = $request->validate([
            'patient_id'        => ['required', 'exists:patients,id'],
            'doctor_profile_id' => ['required', 'exists:doctor_profiles,id'],
            'coupon_code'       => ['nullable', 'string'],
            'complaint'         => ['nullable', 'string', 'max:2000'],
            'pain_level'        => ['nullable', 'integer', 'between:0,10'],
            'consultation_date' => ['nullable', 'date'],
            'email'             => ['nullable', 'email'],   // used for Stripe receipt
        ]);

        /* ------------ 2. Get consultation fee --------------------------- */
        $doctor = DoctorProfile::findOrFail($validated['doctor_profile_id']);
        $fee    = (float) $doctor->consultation_fee;

        /* ------------ 3. Apply coupon (if any) -------------------------- */
        $discount = 0;
        if (!empty($validated['coupon_code'])) {
            $coupon = Coupon::active()
                ->where('code', $validated['coupon_code'])
                ->where(function ($q) use ($doctor) {
                    $q->whereNull('doctor_profile_id')
                        ->orWhere('doctor_profile_id', $doctor->id);
                })
                ->first();

            abort_unless($coupon, 422, __('Invalid or expired coupon.'));

            $discount = $coupon->discount_percentage
                ? round($fee * ($coupon->discount_percentage / 100), 2)
                : min($coupon->discount_amount, $fee);

            $coupon->increment('used_count');
            if ($coupon->used_count >= $coupon->usage_limit) {
                $coupon->update(['status' => 'used']);
            }
        }

        $final = max($fee - $discount, 0);

        /* ------------ 4. Create Consultation row ----------------------- */
        $consultation = Consultation::create([
            'patient_id'        => $validated['patient_id'],
            'doctor_profile_id' => $doctor->id,
            'fee_amount'        => $fee,
            'coupon_code'       => $validated['coupon_code'] ?? null,
            'discount_amount'   => $discount,
            'final_amount'      => $final,
            'complaint'         => $validated['complaint'] ?? null,
            'pain_level'        => $validated['pain_level'] ?? 0,
            'consultation_date' => $validated['consultation_date'] ?? now(),
        ]);

        /* ------------ 5. Create Payment row (pending) ------------------- */
        $payment = Payment::create([
            'consultation_id' => $consultation->id,
            'amount'          => $final,
            'currency'        => 'usd',
            'status'          => 'pending',
        ]);

        /* ------------ 6. Create Stripe Checkout Session ---------------- */
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'usd',
                    'product_data' => ['name' => 'Consultation #' . $consultation->id],
                    'unit_amount'  => (int) round($final * 100),  // dollars → cents
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => url('/payment/success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url'  => url('/payment/cancelled'),
            'metadata'    => [
                'payment_id'       => $payment->id,
                'consultation_id'  => $consultation->id,
            ],
            'customer_email' => $validated['email'] ?? null, // optional receipt
        ]);

        // Save Stripe session ID
        $payment->update(['payment_intent_id' => $session->id]);

        /* ------------ 7. Return checkout URL to client ----------------- */
        return response()->json([
            'checkout_url' => $session->url,
            'consultation' => $consultation,
            'payment'      => $payment,
        ], 201);
    }

    /* ----------------------------------------------------------------------
     | 2)  Stripe Webhook
     |-----------------------------------------------------------------------*/
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload   = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException $e) {
            Log::error('Stripe webhook: Invalid payload');
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook: Invalid signature');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $sessionId = $event->data->object->id;

            // Mark payment & consultation as successful
            $payment = Payment::where('payment_intent_id', $sessionId)->first();
            if ($payment && $payment->status !== 'completed') {
                $payment->update(['status' => 'completed', 'paid_at' => now()]);
                Consultation::where('id', $payment->consultation_id)
                    ->update(['payment_status' => 'paid']);   // add column if needed
            }
        }

        // Always respond 200
        return response()->json(['status' => 'ok']);
    }
}
