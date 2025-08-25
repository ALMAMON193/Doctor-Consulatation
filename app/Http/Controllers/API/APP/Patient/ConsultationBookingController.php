<?php

namespace App\Http\Controllers\API\APP\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\APP\Patient\BookConsultationRequest;
use App\Models\{Consultation, DoctorProfile, PatientMember, Payment, Specialization};
use App\Notifications\ConsultationBookedNotification;
use App\Services\ConsultationService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class ConsultationBookingController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret')); // Stripe Secret Key
    }

    /**
     * Create Consultation & PaymentIntent for Flutter
     */
    public function book(BookConsultationRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $authUser = auth()->user();

            // ✅ Ensure patient ownership
            if (!empty($validated['patient_id']) && $authUser->patient->id !== $validated['patient_id']) {
                return $this->sendError(__('Unauthorized access to patient record.'));
            }

            if (!empty($validated['patient_member_id'])) {
                $member = PatientMember::find($validated['patient_member_id']);
                if (!$member || $member->patient_id !== $authUser->patient->id) {
                    return $this->sendError(__('Unauthorized access to patient member record.'));
                }
            }

            if (empty($validated['patient_id']) && empty($validated['patient_member_id'])) {
                return response()->json(['error' => 'Patient or Patient Member is required'], 422);
            }

            // ✅ Specialization & Fee
            $specialization = Specialization::findOrFail($validated['specialization_id']);
            $feeAmount = (float) $specialization->price;

            $feeDetails = ConsultationService::applyCoupon(
                $validated['coupon_code'] ?? null,
                $validated['patient_id'] ?? null,
                $validated['patient_member_id'] ?? null,
                $feeAmount
            );

            if ($feeDetails['error']) {
                return response()->json(['error' => $feeDetails['error']], 422);
            }

            // ✅ Create Consultation
            $consultation = Consultation::create([
                'patient_id' => $validated['patient_id'] ?? null,
                'patient_member_id' => $validated['patient_member_id'] ?? null,
                'specialization_id' => $validated['specialization_id'],
                'fee_amount' => (float) $feeDetails['fee'],
                'discount_amount' => (float) $feeDetails['discount'],
                'final_amount' => (float) $feeDetails['final'],
                'coupon_code' => $feeDetails['coupon_code'],
                'complaint' => $validated['complaint'] ?? null,
                'pain_level' => $validated['pain_level'] ?? 0,
                'consultation_date' => $validated['consultation_date'] ?? now(),
                'consultation_type' => $validated['consultation_type'],
                'payment_status' => 'pending',
            ]);

            // ✅ Create Payment
            $payment = Payment::create([
                'consultation_id' => $consultation->id,
                'amount' => (float) $feeDetails['final'],
                'currency' => 'usd',
                'status' => 'pending',
            ]);

            // ✅ Create PaymentIntent
            $intent = PaymentIntent::create([
                'amount' => (int) round($feeDetails['final'] * 100),
                'currency' => 'usd',
                'payment_method_types' => [$validated['payment_method'] ?? 'card'],
                'metadata' => [
                    'payment_id' => $payment->id,
                    'consultation_id' => $consultation->id,
                    'coupon_code' => $feeDetails['coupon_code'],
                ],
            ]);
            // Save Stripe PaymentIntent ID
            $payment->update(['payment_intent_id' => $intent->id]);

            return $this->sendResponse([
                'client_secret' => $intent->client_secret, // Flutter will use this
                'payment_id' => $payment->id,
                'consultation_id' => $consultation->id,
                'amount' => $payment->amount,
            ], __('PaymentIntent created successfully'));

        } catch (Exception $e) {
            Log::error('Booking Error: ' . $e->getMessage());
            return $this->sendError(__('Something went wrong'), ['error' => $e->getMessage()]);
        }
    }

    /**
     * Stripe Webhook - update payment status
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $signature, $secret);

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $intentId = $event->data->object->id;
                    $payment = Payment::where('payment_intent_id', $intentId)->first();

                    if ($payment && $payment->status !== 'completed') {
                        $payment->update(['status' => 'completed', 'paid_at' => now()]);
                        $consultation = Consultation::find($payment->consultation_id);
                        if ($consultation) {
                            $consultation->update(['payment_status' => 'paid']);
                            if ($consultation->patient_id) {
                                DB::table('patients')->where('id', $consultation->patient_id)->increment('consulted');
                            } elseif ($consultation->patient_member_id) {
                                $consultation->patientMember?->patient?->increment('consulted');
                            }

                            // Notify doctors
                            $doctors = DoctorProfile::with('user')
                                ->whereHas('specializations', function ($q) use ($consultation) {
                                    $q->where('specializations.id', $consultation->specialization_id);
                                })
                                ->get();
                            foreach ($doctors as $doc) {
                                if ($doc->user) {
                                    $doc->user->notify(new ConsultationBookedNotification($consultation));
                                }
                            }
                        }
                    }
                    break;
                case 'payment_intent.payment_failed':
                    Log::warning('Payment failed', ['intent_id' => $event->data->object->id]);
                    break;
            }
            return response()->json(['status' => 'success']);

        } catch (Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook failed'], 500);
        }
    }

    /**
     * Get Stripe Publishable Key for Flutter
     */
    public function publishKey()
    {
        return $this->sendResponse([
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        ], __('Publish key fetched'));
    }
    public function checkCoupon(Request $request): JsonResponse
    {
        try {
            // Validate request
            $request->validate([
                'specialization_id' => 'required|exists:specializations,id',
                'coupon_code' => 'required|string',
            ]);

            // Fetch specialization price
            $specialization = \App\Models\Specialization::findOrFail($request->specialization_id);
            $originalPrice = (float) $specialization->price;

            // Fetch coupon
            $coupon = \App\Models\Coupon::where('code', $request->coupon_code)
                ->where('status', 'active')
                ->first();

            if (!$coupon) {
                return $this->sendError(__('Invalid or expired coupon'), ['coupon_code' => $request->coupon_code]);
            }

            // Calculate discount
            $discount = 0;
            $discountType = null;

            if ($coupon->discount_percentage > 0) {
                $discount = ($originalPrice * $coupon->discount_percentage) / 100;
                $discountType = 'percentage';
            } elseif ($coupon->discount_amount > 0) {
                $discount = $coupon->discount_amount;
                $discountType = 'amount';
            }

            $finalPrice = max($originalPrice - $discount, 0);

            // Return response
            return $this->sendResponse([
                'specialization_id' => $specialization->id,
                'specialization_name' => $specialization->name,
                'original_price' => $originalPrice,
                'discount_type' => $discountType, // "percentage" or "amount"
                'discount_value' => $discount,    // actual discount applied
                'final_price' => $finalPrice,
                'coupon_code' => $coupon->code,
                'valid_from' => $coupon->valid_from,
                'valid_to' => $coupon->valid_to,
                'usage_limit' => $coupon->usage_limit,
                'used_count' => $coupon->used_count,
                'status' => $coupon->status,
            ], __('Coupon details fetched successfully'));

        } catch (Exception $e) {
            return $this->sendError(__('Something went wrong'), ['error' => $e->getMessage()]);
        }
    }

}
