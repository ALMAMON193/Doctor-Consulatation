<?php

namespace App\Http\Controllers\API\Patient;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\{Consultation, Coupon, CouponUser, DoctorProfile, Payment};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;
use App\Services\ConsultationService;

class ConsultationBookingController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function book(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id'         => 'nullable|exists:patients,id',
            'patient_member_id'  => 'nullable|exists:patient_members,id',
            'doctor_profile_id'  => 'required|exists:doctor_profiles,id',
            'coupon_code'        => 'nullable|string',
            'complaint'          => 'nullable|string|max:2000',
            'pain_level'         => 'nullable|integer|between:0,10',
            'consultation_date'  => 'nullable|date',
            'email'              => 'nullable|email',
        ]);

        // Ensure either patient or member is present
        if (empty($validated['patient_id']) && empty($validated['patient_member_id'])) {
            return response()->json(['error' => 'Patient or Patient Member is required'], 422);
        }

        $doctor = DoctorProfile::findOrFail($validated['doctor_profile_id']);

        // Apply coupon and calculate fees
        $feeDetails = ConsultationService::applyCoupon(
            $doctor,
            $validated['coupon_code'] ?? null,
            $validated['patient_id'] ?? null,
            $validated['patient_member_id'] ?? null
        );

        if ($feeDetails['error']) {
            return response()->json(['error' => $feeDetails['error']], 422);
        }

        // Create consultation record
        $consultation = Consultation::create([
            'patient_id'         => $validated['patient_id'] ?? null,
            'patient_member_id'  => $validated['patient_member_id'] ?? null,
            'doctor_profile_id'  => $doctor->id,
            'fee_amount'         => $feeDetails['fee'],
            'discount_amount'    => $feeDetails['discount'],
            'final_amount'       => $feeDetails['final'],
            'coupon_code'        => $feeDetails['coupon_code'],
            'complaint'          => $validated['complaint'] ?? null,
            'pain_level'         => $validated['pain_level'] ?? 0,
            'consultation_date'  => $validated['consultation_date'] ?? now(),
        ]);

        // Create payment record BEFORE Stripe session
        $payment = Payment::create([
            'consultation_id' => $consultation->id,
            'amount'          => $feeDetails['final'],
            'currency'        => 'usd',
            'status'          => 'pending',
        ]);

        // Prepare product name
        $productName = 'Consultation';
        if ($feeDetails['message']) {
            $productName .= " ({$feeDetails['message']})";
        }

        // Create Stripe checkout session
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'usd',
                    'product_data' => ['name' => $productName],
                    'unit_amount'  => (int) round($feeDetails['final'] * 100),
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => route('payment.success'),
            'cancel_url'  => route('payment.fail'),
            'metadata'    => [
                'payment_id'      => $payment->id,
                'consultation_id' => $consultation->id,
                'coupon_code'     => $feeDetails['coupon_code'],
                'discount_amount' => $feeDetails['discount'],
            ],
            'customer_email' => $validated['email'] ?? null,
        ]);

        // Update payment with Stripe session ID
        $payment->update(['payment_intent_id' => $session->id]);

        return response()->json([
            'checkout_url' => $session->url,
            'consultation' => $consultation,
            'payment'      => $payment,
        ], 201);
    }


    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException $e) {
            Log::error('Stripe webhook: Invalid payload');
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook: Invalid signature');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $sessionId = $event->data->object->id;
                $payment = Payment::where('payment_intent_id', $sessionId)->first();
                if ($payment && $payment->status !== 'completed') {
                    $payment->update(['status' => 'completed', 'paid_at' => now()]);
                    Consultation::where('id', $payment->consultation_id)
                        ->update(['payment_status' => 'paid']);
                }
                break;

            case 'payment_intent.payment_failed':
                Log::warning('Payment failed for session: ' . $event->data->object->id);
                break;
        }

        return response()->json(['status' => 'ok']);
    }

    //payment success
    public function success(): JsonResponse
    {
        return $this->sendResponse([], __('Payment successful'));
    }

    public function cancel(): JsonResponse
    {
        return $this->sendResponse([], __('Payment cancelled'));
    }
}
