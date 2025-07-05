<?php

namespace App\Http\Controllers\API\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookConsultationRequest;
use App\Http\Requests\ConfirmPaymentRequest;
use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Payment;
use App\Services\ConsultationService;
use App\Services\StripePaymentService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;
use Throwable;

class ConsultationBookingController extends Controller
{
    use ApiResponse;
    public function __construct(
        protected ConsultationService   $consultations,
        protected StripePaymentService  $stripe
    ) {}

    /* ------------------------------------------------------------------ */
    /* ১)  কনসাল্টেশন তৈরি + PaymentIntent                                */
    /* ------------------------------------------------------------------ */
    public function create(BookConsultationRequest $request)
    {
        $consultation = $this->consultations->create($request->validated());

        $payment = Payment::create([
            'consultation_id' => $consultation->id,
            'amount'          => $consultation->final_amount,
            'currency'        => 'inr',
            'payment_method'  => $request->payment_method,
            'status'          => 'pending',
        ]);

        $intent = $this->stripe->createIntent($payment);
        $payment->update(['payment_intent_id' => $intent->id]);

        $apiResponse = [
            'consultation' => $consultation,
            'payment' => $payment,
        ];
        return $this->sendResponse($apiResponse,__('Consultation created successfully'));

    }

    /* ------------------------------------------------------------------ */
    /* ২)  ক্লায়েন্ট‑সাইড কনফার্ম (ঐচ্ছিক)                                */
    /* ------------------------------------------------------------------ */
    public function confirmPayment(ConfirmPaymentRequest $request): \Illuminate\Http\JsonResponse
    {
        $payment = $this->stripe->confirm($request->payment_intent_id);

        return $payment
            ? $this->sendResponse($payment,__('Payment confirmed successfully'))
            : $this->sendError(__('Payment confirmed successfully'));
    }

    /* ------------------------------------------------------------------ */
    /* ৩)  Stripe Webhook (একটাই এন্ডপয়েন্ট)                               */
    /* ------------------------------------------------------------------ */
    public function handleWebhook(Request $request): \Illuminate\Http\JsonResponse
    {
        $payload    = $request->getContent();
        $signature  = $request->header('Stripe-Signature');
        $secret     = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (Throwable $e) {
            return $this->sendError(__('Webhook webhook error'),[],$e->getCode());
        }

        // পেমেন্ট সফল হলে স্ট্যাটাস আপডেট
        if ($event->type === 'payment_intent.succeeded') {
            $intentId = $event->data->object->id;
            $this->stripe->confirm($intentId);
        }
        return $this->sendResponse($intentId,__('Payment confirmed successfully'));

    }
}
