<?php

namespace App\Http\Controllers\API\WEB\Dashboard\WithDrawRequest;

use App\Http\Controllers\Controller;
use App\Http\Resources\WEB\Dashboard\WithdrawRequest\WithdrawalResource;
use App\Models\Payment;
use App\Models\Withdrawal;
use App\Notifications\WithdrawalStatusNotification;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponse;
    // Get all withdrawal requests (for admin)
    public function withdrawRequests(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $statusFilter = $request->input('status'); // optional: pending, success, cancelled

        // Build query
        $query = Withdrawal::with('doctorProfile.user')
            ->when($statusFilter, function ($q) use ($statusFilter) {
                $q->where('status', $statusFilter);
            })
            ->orderByDesc('created_at');

        // Paginate
        $withdrawals = $query->paginate($perPage);

        // Analytics
        $analytics = [
            'allWithdrawRequest' => Withdrawal::count(),
            'completeRequest' => Withdrawal::where('status', 'success')->count(),
            'cancelRequest' => Withdrawal::where('status', 'cancelled')->count(),
            'pendingRequest' => Withdrawal::where('status', 'pending')->count(),
        ];

        // Format response using resource
        $collection = WithdrawalResource::collection($withdrawals)->resolve();
        $withdrawals->setCollection(collect($collection));

        $apiResponse = [
            'analytics' => $analytics,
            'list' => $withdrawals->items(),
            'pagination' => [
                'total' => $withdrawals->total(),
                'per_page' => $withdrawals->perPage(),
                'current_page' => $withdrawals->currentPage(),
                'last_page' => $withdrawals->lastPage(),
                'from' => $withdrawals->firstItem(),
                'to' => $withdrawals->lastItem(),
            ],
        ];
        return $this->sendResponse($apiResponse, __('Transaction history fetched successfully'));
    }

    // Accept a withdrawal request
    public function acceptRequest(Request $request)
    {
        $request->validate([
            'withdrawal_id' => 'required|exists:withdrawals,id',
            'remarks' => 'nullable|string',
        ]);

        $withdrawal = Withdrawal::findOrFail($request->withdrawal_id);

        if ($withdrawal->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request already processed.']);
        }

        // Fetch the latest completed payment for this doctor
        $payment = Payment::whereHas('consultation', function ($q) use ($withdrawal) {
            $q->where('doctor_id', $withdrawal->doctorProfile->id);
        })
            ->where('status', 'completed')
            ->latest()
            ->first();

        $transactionId = $payment ? $payment->payment_intent_id : null;

        $withdrawal->update([
            'status' => 'success',
            'transaction_id' => $transactionId,
            'approved_at' => now(),
            'remarks' => $request->remarks ?? null,
        ]);

        // Notify doctor
        $withdrawal->doctorProfile->user->notify(new WithdrawalStatusNotification($withdrawal));

        return $this->sendResponse([], __('Withdraw approved successfully'));
    }

    // Reject a withdrawal request (admin)
    public function rejectRequest(Request $request)
    {
        $request->validate([
            'withdrawal_id' => 'required|exists:withdrawals,id',
            'remarks' => 'required|string',
        ]);

        $withdrawal = Withdrawal::findOrFail($request->withdrawal_id);

        if ($withdrawal->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request already processed.']);
        }

        $withdrawal->update([
            'status' => 'cancelled',
            'rejected_at' => now(),
            'remarks' => $request->remarks,
        ]);

        // Notify doctor
        $withdrawal->doctorProfile->user->notify(new WithdrawalStatusNotification($withdrawal));

        return $this->sendResponse([], __('Withdraw request rejected successfully'));
    }


}
