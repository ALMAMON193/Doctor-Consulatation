<?php

namespace App\Http\Controllers\API\APP\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\APP\Patient\BookConsultationRequest;
use App\Models\{Consultation, DoctorProfile, PatientMember, Payment, Specialization, Coupon};
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
        Stripe::setApiKey(config('services.stripe.secret')); // Set Stripe secret key
    }

    /**
     * Book a consultation & create Stripe PaymentIntent
     */
    public function book(BookConsultationRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated(); // Get validated input
            $authUser = auth()->user(); // Currently logged-in user

            // Check patient ownership
            if (!empty($validated['patient_id']) && $authUser->patient->id !== $validated['patient_id']) {
                return $this->sendError(__('Unauthorized access to patient record.'));
            }

            // Check patient member ownership
            if (!empty($validated['patient_member_id'])) {
                $member = PatientMember::find($validated['patient_member_id']); // Fetch member
                if (!$member || $member->patient_id !== $authUser->patient->id) {
                    return $this->sendError(__('Unauthorized access to patient member record.'));
                }
            }

            // Ensure either patient or patient member is provided
            if (empty($validated['patient_id']) && empty($validated['patient_member_id'])) {
                return response()->json(['error' => 'Patient or Patient Member is required'], 422);
            }

            // Fetch specialization and fee
            $specialization = Specialization::findOrFail($validated['specialization_id']); // Fetch specialization
            $feeAmount = (float) $specialization->price; // Base fee

            // Apply coupon if provided
            $feeDetails = ConsultationService::applyCoupon(
                $validated['coupon_code'] ?? null,
                $validated['patient_id'] ?? null,
                $validated['patient_member_id'] ?? null,
                $feeAmount
            );

            if ($feeDetails['error']) { // If coupon invalid
                return response()->json(['error' => $feeDetails['error']], 422);
            }

            // Create consultation record
            $consultation = Consultation::create([
                'patient_id' => $validated['patient_id'] ?? ($member->patient_id ?? null),
                'patient_member_id' => $validated['patient_member_id'] ?? null,
                'specialization_id' => $validated['specialization_id'],
                'fee_amount' => (float) $feeDetails['fee'], // Original fee
                'discount_amount' => (float) $feeDetails['discount'], // Discount applied
                'final_amount' => (float) $feeDetails['final'], // Final payable
                'coupon_code' => $feeDetails['coupon_code'], // Applied coupon
                'complaint' => $validated['complaint'] ?? null, // Patient complaint
                'pain_level' => $validated['pain_level'] ?? 0, // Pain severity
                'consultation_date' => $validated['consultation_date'] ?? now(),
                'consultation_type' => $validated['consultation_type'], // Online/Offline
                'payment_status' => 'pending',
            ]);

            // Create payment record
            $payment = Payment::create([
                'consultation_id' => $consultation->id,
                'amount' => (float) $feeDetails['final'], // Final amount
                'currency' => 'usd',
                'status' => 'pending',
            ]);

            // Create Stripe PaymentIntent
            $intent = PaymentIntent::create([
                'amount' => (int) round($feeDetails['final'] * 100), // in cents
                'currency' => 'usd',
                'payment_method_types' => [$validated['payment_method'] ?? 'card'],
                'metadata' => [
                    'payment_id' => $payment->id,
                    'consultation_id' => $consultation->id,
                    'coupon_code' => $feeDetails['coupon_code'],
                ],
            ]);

            // Save PaymentIntent ID
            $payment->update(['payment_intent_id' => $intent->id]);

            // Return PaymentIntent info to Flutter
            return $this->sendResponse([
                'client_secret' => $intent->client_secret,
                'payment_id' => $payment->id,
                'consultation_id' => $consultation->id,
                'amount' => $payment->amount,
            ], __('PaymentIntent created successfully'));

        } catch (Exception $e) {
            Log::error('Booking Error: ' . $e->getMessage()); // Log exception
            return $this->sendError(__('Something went wrong'), ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle Stripe Webhook to update payment status
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent(); // Raw payload
        $signature = $request->header('Stripe-Signature'); // Webhook signature
        $secret = config('services.stripe.webhook_secret'); // Webhook secret

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $signature, $secret); // Validate event

            switch ($event->type) {

                case 'payment_intent.succeeded':
                    $intentId = $event->data->object->id; // Stripe payment ID
                    $payment = Payment::where('payment_intent_id', $intentId)->first(); // Fetch payment

                    if ($payment && $payment->status !== 'completed') {
                        $payment->update(['status' => 'completed', 'paid_at' => now()]); // Update payment
                        $consultation = Consultation::find($payment->consultation_id);

                        if ($consultation) {
                            $consultation->update(['payment_status' => 'paid']); // Update consultation

                            // Increment consulted count
                            if ($consultation->patient_id) {
                                DB::table('patients')->where('id', $consultation->patient_id)->increment('consulted');
                            } elseif ($consultation->patient_member_id) {
                                $consultation->patientMember?->patient?->increment('consulted');
                            }

                            // Notify all doctors of this specialization
                            $doctors = DoctorProfile::with('user')
                                ->whereHas('specializations', function ($q) use ($consultation) {
                                    $q->where('specializations.id', $consultation->specialization_id);
                                })
                                ->get();

                            foreach ($doctors as $doc) {
                                $doc->user?->notify(new ConsultationBookedNotification($consultation));
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
            Log::error('Webhook error: ' . $e->getMessage()); // Log webhook errors
            return response()->json(['error' => 'Webhook failed'], 500);
        }
    }

    /**
     * Return Stripe publishable key to Flutter
     */
    public function publishKey()
    {
        return $this->sendResponse([
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        ], __('Publish key fetched'));
    }

    /**
     * Validate coupon & calculate discount
     */
    public function checkCoupon(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'specialization_id' => 'required|exists:specializations,id',
                'coupon_code' => 'required|string',
            ]);

            $specialization = Specialization::findOrFail($request->specialization_id); // Fetch specialization
            $originalPrice = (float) $specialization->price;

            $coupon = Coupon::where('code', $request->coupon_code)
                ->where('status', 'active')
                ->first();

            if (!$coupon) return $this->sendError(__('Invalid or expired coupon'), ['coupon_code' => $request->coupon_code]);

            // Calculate discount
            $discount = 0;
            $discountType = null;

            if ($coupon->discount_percentage > 0) {
                $discount = ($originalPrice * $coupon->discount_percentage) / 100; // Percentage discount
                $discountType = 'percentage';
            } elseif ($coupon->discount_amount > 0) {
                $discount = $coupon->discount_amount; // Fixed amount discount
                $discountType = 'amount';
            }

            $finalPrice = max($originalPrice - $discount, 0); // Final price can't be negative

            return $this->sendResponse([
                'specialization_id' => $specialization->id,
                'specialization_name' => $specialization->name,
                'original_price' => $originalPrice,
                'discount_type' => $discountType,
                'discount_value' => $discount,
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
