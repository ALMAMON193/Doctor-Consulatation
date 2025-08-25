<?php

namespace App\Http\Controllers\API\APP\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\APP\Doctor\WalletHistoryResource;
use App\Models\Payment;
use App\Models\Withdrawal;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class WalletAPIController extends Controller
{
    use ApiResponse;

    /**
     * Get doctor's wallet summary and transaction history.
     */
    public function wallet(Request $request)
    {
        try {
            $doctor = Auth::user(); // Logged-in doctor
            $doctorProfileId = $doctor->doctorProfile->id ?? null;

            // Total earnings from completed consultations
            $totalEarnings = Payment::whereHas('consultation', function ($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id)
                    ->where('consultation_status', 'completed');
            })
                ->where('status', 'completed')
                ->sum('amount');

            // Total approved withdrawals
            $totalWithdrawals = Withdrawal::where('doctor_profile_id', $doctorProfileId)
                ->where('status', 'success')
                ->sum('amount');

            // Compute balances
            $totalBalance = $totalEarnings;
            $withdrawableBalance = max($totalBalance - $totalWithdrawals, 0);

            // Fetch latest 6 consultations with payment info
            $transactions = \App\Models\Consultation::where('doctor_id', $doctor->id)
                ->with('payment') // eager load payment relation
                ->orderByDesc('created_at')
                ->take(6)
                ->get()
                ->map(function ($consultation) {
                    return [
                        'id'     => $consultation->id,
                        'type'   => 'Payment', // Always Payment
                        'amount' => $consultation->payment?->amount ?? 0, // From payment table
                        'status' => ucfirst($consultation->consultation_status), // From consultation table
                        'date'   => $consultation->created_at->format('M d, Y H:i'),
                    ];
                });

            return $this->sendResponse([
                'total_balance'        => floatval($totalBalance),
                'withdrawable_balance' => floatval($withdrawableBalance),
                'transactions'         => $transactions,
            ], __('Wallet data fetched successfully'));

        } catch (Exception $e) {
            Log::error('Wallet fetch failed: '.$e->getMessage());
            return $this->sendError('Failed to fetch wallet data');
        }
    }
    /**
     * Submit a withdrawal request for the doctor.
     */
    public function requestWithdraw(Request $request)
    {
        try {
            $doctor = auth()->user(); // Get currently logged-in doctor

            // Validate incoming request
            $request->validate([
                'amount'         => 'required|numeric|min:50', // Minimum withdrawal amount 1
                'account_number' => 'required|string',
                'account_name'   => 'required|string',
            ]);

            // Calculate withdrawable balance
            $totalEarnings = Payment::whereHas('consultation', function ($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id)
                    ->where('consultation_status', 'completed');
            })
                ->where('status', 'completed')
                ->sum('amount');

            $totalWithdrawals = Withdrawal::where('doctor_profile_id', $doctor->doctorProfile->id ?? null)
                ->where('status', 'success')
                ->sum('amount');

            $withdrawableBalance = max($totalEarnings - $totalWithdrawals, 0); // Compute withdrawable balance

            // Check if requested amount exceeds withdrawable balance
            if ($request->amount > $withdrawableBalance) {
                return $this->errorResponse('Insufficient balance to withdraw', 422);
            }
            // Create a new withdrawal request with status 'pending'
            $withdrawal = Withdrawal::create([
                'doctor_profile_id' => $doctor->doctorProfile->id,
                'amount'            => $request->amount,
                'payment_method'    => $request->payment_method,
                'account_number'    => $request->account_number,
                'account_name'      => $request->account_name,
                'status'            => 'pending',
            ]);
            // Return success response
            return $this->sendResponse($withdrawal, "Withdrawal request submitted successfully");
        } catch (Exception $e) {
            // Log the error for debugging purposes
            Log::error('Withdrawal request failed: '.$e->getMessage());
            // Return a safe error response
            return $this->sendError('Failed to submit withdrawal request');
        }
    }
    public function viewTransactionHistory(Request $request)
    {
        try {
            $doctor = auth()->user(); // Logged-in doctor

            // Fetch completed or pending consultations for the doctor
            $transactions = \App\Models\Consultation::where('doctor_id', $doctor->id)
                ->with(['payment']) // eager load payment relation
                ->orderByDesc('created_at')
                ->take(6)
                ->get()
                ->map(function ($consultation) {
                    return [
                        'id'     => $consultation->id,
                        'type'   => 'Payment', // Type is Payment
                        'amount' => $consultation->payment?->amount ?? 0, // Amount from payment table
                        'status' => ucfirst($consultation->consultation_status), // Status from consultation table
                        'date'   => $consultation->created_at->format('M d, Y H:i'),
                    ];
                });

            return $this->sendResponse([
                'transactions' => $transactions
            ], 'Transaction history fetched successfully');

        } catch (\Exception $e) {
            Log::error('Transaction history fetch failed: '.$e->getMessage());
            return $this->sendError('Failed to fetch transaction history');
        }
    }
}
