<?php

namespace App\Http\Controllers\API\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentSuccessResource;
use App\Traits\ApiResponse;
use App\Models\{Consultation, Coupon, CouponUser, DoctorProfile, Payment};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;
use App\Services\ConsultationService;
use Exception;

class ConsultationBookingController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function book(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'patient_id'         => 'nullable|exists:patients,id',
                'patient_member_id'  => 'nullable|exists:patient_members,id',
                'doctor_profile_id'  => 'required|exists:doctor_profiles,id',
                'coupon_code'        => 'nullable|string',
                'complaint'          => 'nullable|string|max:2000',
                'pain_level'         => 'nullable|integer|between:0,10',
                'consultation_date'  => 'nullable|date',
                'email'              => 'nullable|email',
                'payment_status'     => 'nullable|in:pending,paid,completed,cancelled',
            ]);

            if (empty($validated['patient_id']) && empty($validated['patient_member_id'])) {
                return response()->json(['error' => 'Patient or Patient Member is required'], 422);
            }

            $doctor = DoctorProfile::findOrFail($validated['doctor_profile_id']);
            if (!$doctor) {
                return response()->json(['error' => 'Doctor not found'], 422);
            }

            $feeDetails = ConsultationService::applyCoupon(
                $doctor,
                $validated['coupon_code'] ?? null,
                $validated['patient_id'] ?? null,
                $validated['patient_member_id'] ?? null
            );

            if ($feeDetails['error']) {
                return response()->json(['error' => $feeDetails['error']], 422);
            }

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
                'payment_status'     => $validated['payment_status'] ?? 'pending',
            ]);

            $payment = Payment::create([
                'consultation_id' => $consultation->id,
                'amount'          => $feeDetails['final'],
                'currency'        => 'usd',
                'status'          => 'pending',
            ]);

            $productName = 'Consultation';
            if ($feeDetails['message']) {
                $productName .= " ({$feeDetails['message']})";
            }

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
                'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('payment.fail'),
                'metadata'    => [
                    'payment_id'      => $payment->id,
                    'consultation_id' => $consultation->id,
                    'coupon_code'     => $feeDetails['coupon_code'],
                    'discount_amount' => $feeDetails['discount'],
                ],
                'customer_email' => $validated['email'] ?? null,
            ]);
            $payment->update(['payment_intent_id' => $session->id]);
            return response()->json(['checkout_url' => $session->url], 201);

        } catch (Exception $e) {
            Log::error('Booking Error: ' . $e->getMessage());
            return $this->sendError(__('Something went wrong while booking consultation'));
        }
    }

    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $signature, $secret);

            $payment = null;

            switch ($event->type) {

                case 'checkout.session.completed':
                    $sessionId = $event->data->object->id;

                    $payment = Payment::where('payment_intent_id', $sessionId)->first();

                    if (!$payment) {
                        Log::warning('Payment not found for session', ['session_id' => $sessionId]);
                        break;
                    }

                    if ($payment->status !== 'completed') {

                        $payment->update([
                            'status'  => 'completed',
                            'paid_at' => now(),
                        ]);

                        $consultation = Consultation::find($payment->consultation_id);

                        if ($consultation) {
                            // Mark consultation as paid
                            $consultation->update(['payment_status' => 'paid']);

                            // Update main patient's consultation count
                            if ($consultation->patient_id) {
                                DB::table('patients')->where('id', $consultation->patient_id)->increment('consulted');
                            }
                        } else {
                            Log::warning('Consultation not found', ['consultation_id' => $payment->consultation_id]);
                        }

                    } else {
                        Log::info('Payment already completed', ['payment_id' => $payment->id]);
                    }

                    break;
                case 'payment_intent.payment_failed':
                    Log::warning('Payment failed', ['session_id' => $event->data->object->id]);
                    break;
            }

            return $this->sendResponse(
                $payment ? $payment->toArray() : [],
                __('We have received your payment.')
            );

        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid payload: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Invalid signature: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook failed'], 500);
        }
    }

    public function success(Request $request): JsonResponse|PaymentSuccessResource
    {
        try {
            $sessionId = $request->query('session_id');

            if (!$sessionId) {
                return response()->json([
                    'data' => [],
                    'message' => __('Payment successful, but session ID is missing')
                ], 400);
            }

            $payment = Payment::where('payment_intent_id', $sessionId)->first();

            if (!$payment) {
                return response()->json([
                    'data' => [],
                    'message' => __('Payment not found')
                ], 404);
            }

            $consultation = Consultation::with([
                'doctorProfile.user',
                'patient',
                'patientMember',
                'patientMember.patient',
            ])->find($payment->consultation_id);

            if (!$consultation) {
                return $this->sendError(__('Consultation not found'));
            }

            return (new PaymentSuccessResource($consultation))
                ->additional([
                    'message' => __('Payment successful'),
                    'status' => 'success',
                ]);

        } catch (Exception $e) {
            Log::error('Payment success error: ' . $e->getMessage());
            return $this->sendError(__('Something went wrong while payment success'));
        }
    }

    public function cancel(): JsonResponse
    {
        return $this->sendResponse([], __('Payment cancelled'));
    }
}
