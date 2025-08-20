<?php

namespace App\Http\Controllers\API\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookConsultationRequest;
use App\Http\Resources\PaymentSuccessResource;
use App\Notifications\ConsultationBookedNotification;
use App\Traits\ApiResponse;
use App\Models\{Consultation, PatientMember, Payment, Specialization, DoctorProfile};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use App\Services\ConsultationService;
use Exception;

class ConsultationBookingController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret')); // Set Stripe API key
    }
    // Book consultation and create Stripe checkout session
    public function book(BookConsultationRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated(); // Validate request
            $authUser = auth()->user(); // Get authenticated user

            if (!empty($validated['patient_id']) && $authUser->patient->id !== $validated['patient_id'])
                return $this->sendError(__('Unauthorized access to patient record.')); // Check patient ownership

            if (!empty($validated['patient_member_id'])) { // Check member ownership
                $member = PatientMember::find($validated['patient_member_id']);
                if ($member->patient_id !== $authUser->patient->id)
                    return $this->sendError(__('Unauthorized access to patient member record.'));
            }

            if (empty($validated['patient_id']) && empty($validated['patient_member_id']))
                return response()->json(['error' => 'Patient or Patient Member is required'], 422); // Require patient

            $specialization = Specialization::findOrFail($validated['specialization_id']); // Get specialization
            $feeAmount = (float) $specialization->price; // Dynamic fee

            $feeDetails = ConsultationService::applyCoupon( // Apply coupon
                $validated['coupon_code'] ?? null,
                $validated['patient_id'] ?? null,
                $validated['patient_member_id'] ?? null,
                $feeAmount
            );

            if ($feeDetails['error'])
                return response()->json(['error' => $feeDetails['error']], 422); // Coupon error

            $consultation = Consultation::create([ // Create consultation
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
                'payment_status' => $validated['payment_status'] ?? 'pending',
            ]);

            $payment = Payment::create([ // Create payment
                'consultation_id' => $consultation->id,
                'amount' => (float) $feeDetails['final'],
                'currency' => 'usd',
                'status' => 'pending',
            ]);

            $productName = 'Consultation'; // Product name
            if ($feeDetails['message']) $productName .= " ({$feeDetails['message']})"; // Add coupon info

            $session = Session::create([ // Stripe session
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => ['name' => $productName],
                        'unit_amount' => (int) round($feeDetails['final'] * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}', // Success redirect
                'cancel_url' => route('payment.fail'),
                'metadata' => [
                    'payment_id' => $payment->id,
                    'consultation_id' => $consultation->id,
                    'coupon_code' => $feeDetails['coupon_code'],
                    'discount_amount' => $feeDetails['discount'],
                ],
                'customer_email' => $validated['email'] ?? null,
            ]);

            $payment->update(['payment_intent_id' => $session->id]); // Save session ID
            return $this->sendResponse(['checkout_url' => $session->url], __('Checkout session created')); // Return checkout URL

        } catch (Exception $e) {
            Log::error('Booking Error: ' . $e->getMessage()); // Log error
            return $this->sendError(__('Something Went to Wrong', ['error' => $e->getMessage()])); // Return error
        }
    }
    // Handle Stripe webhook for completed payment
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent(); // Get payload
        $signature = $request->header('Stripe-Signature'); // Get signature
        $secret = config('services.stripe.webhook_secret'); // Webhook secret

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $signature, $secret); // Verify event
            $payment = null;

            switch ($event->type) {
                case 'checkout.session.completed':
                    $sessionId = $event->data->object->id; // Get session ID
                    $payment = Payment::where('payment_intent_id', $sessionId)->first(); // Find payment
                    if ($payment && $payment->status !== 'completed') {
                        $payment->update(['status' => 'completed', 'paid_at' => now()]); // Mark completed

                        $consultation = Consultation::find($payment->consultation_id); // Get consultation
                        if ($consultation) {
                            $consultation->update(['payment_status' => 'paid']); // Update consultation
                            if ($consultation->patient_id) DB::table('patients')->where('id', $consultation->patient_id)->increment('consulted'); // Increment consulted
                            elseif ($consultation->patient_member_id) $consultation->patientMember?->patient?->increment('consulted'); // Member increment

                            $doctors = DoctorProfile::with('user')
                                ->whereHas('specializations', function ($q) use ($consultation) {
                                    $q->where('specializations.id', $consultation->specialization_id);
                                })
                                ->get();

                            foreach ($doctors as $doc) {
                                if ($doc->user) {
                                    Log::info('Sending notification to user_id: ' . $doc->user->id);
                                    $doc->user->notify(new ConsultationBookedNotification($consultation));
                                }
                            }

                        }
                    }
                    break;

                case 'payment_intent.payment_failed':
                    Log::warning('Payment failed', ['session_id' => $event->data->object->id]); // Log failed
                    break;
            }

            return response()->json(['status' => 'success']); // Return success

        } catch (Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage()); // Log webhook error
            return response()->json(['error' => 'Webhook failed'], 500); // Return error
        }
    }
    // Handle payment success redirect (works for web)
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id'); // Get session ID
        if (!$sessionId) return redirect()->route('home')->with('error', 'Session ID missing'); // Redirect home

        $payment = Payment::where('payment_intent_id', $sessionId)->first(); // Find payment
        if (!$payment) return redirect()->route('home')->with('error', 'Payment not found'); // Redirect if not found

        $consultation = Consultation::with(['patient', 'patientMember'])->find($payment->consultation_id); // Get consultation
        if (!$consultation) return redirect()->route('home')->with('error', 'Consultation not found'); // Redirect if missing

        return $this->sendResponse ($consultation,__('Payment Successfully')); // Render json
    }
    // Payment cancel redirect
    public function cancel(): JsonResponse
    {
        return $this->sendResponse([], __('Payment cancelled')); // Cancel message
    }
}
