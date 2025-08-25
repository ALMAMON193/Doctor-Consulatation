<?php

namespace App\Http\Controllers\API\APP\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Payment;
use App\Models\Withdrawal;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class WalletAPIController extends Controller
{
    use ApiResponse;

    /**
     * Get doctor's wallet summary and latest transactions.
     */
    public function wallet(Request $request)
    {
        try {
            $doctor = Auth::user();
            $doctorProfileId = $doctor->doctorProfile->id ?? null;
            if (!$doctorProfileId) {
                return $this->sendError('Doctor profile not found.');
            }

            $commissionRate = 0.15; // 15% commission

            // Total earnings from completed consultations/payments
            $totalEarnings = Payment::where('status', 'completed')
                ->whereHas('consultation', function ($q) use ($doctorProfileId) {
                    $q->where('doctor_id', $doctorProfileId)
                        ->where('consultation_status', 'completed')
                        ->whereIn('payment_status', ['paid', 'completed']);
                })
                ->sum('amount');

            // Apply commission for displayed balances
            $totalBalance = $totalEarnings * (1 - $commissionRate);

            // Total approved withdrawals
            $totalWithdrawals = Withdrawal::where('doctor_profile_id', $doctorProfileId)
                ->where('status', 'success')
                ->sum('amount');

            $withdrawableBalance = max($totalBalance - $totalWithdrawals, 0);

            // Fetch latest 6 consultations with payments (any status)
            $consultations = Consultation::where('doctor_id', $doctorProfileId)
                ->whereHas('payment')
                ->with('payment')
                ->get()
                ->map(function ($c) {
                    return [
                        'id'     => $c->id,
                        'type'   => 'Consultation Payment',
                        'amount' => $c->payment?->amount ?? 0,
                        'status' => ucfirst($c->consultation_status),
                        'date'   => $c->created_at,
                    ];
                });

            // Fetch withdrawals (any status)
            $withdrawals = Withdrawal::where('doctor_profile_id', $doctorProfileId)
                ->get()
                ->map(function ($w) {
                    return [
                        'id'     => $w->id,
                        'type'   => 'Withdrawal',
                        'amount' => $w->amount,
                        'status' => ucfirst($w->status),
                        'date'   => $w->created_at,
                    ];
                });

            // Merge consultations and withdrawals, sort by date desc, take latest 6
            $transactions = $consultations->merge($withdrawals)
                ->sortByDesc('date')
                ->take(6)
                ->values();

            return $this->sendResponse([
                'total_balance'        => round($totalBalance, 2),
                'withdrawable_balance' => round($withdrawableBalance, 2),
                'transactions'         => $transactions,
            ], 'Wallet data fetched successfully');

        } catch (Exception $e) {
            Log::error('Wallet fetch failed: ' . $e->getMessage());
            return $this->sendError('Failed to fetch wallet data');
        }
    }
    /**
     * Submit a withdrawal request.
     */
    public function requestWithdraw(Request $request)
    {
        $request->validate ([
            'amount' => 'required|numeric|min:50',
            'account_number' => 'required|string',
            'account_name' => 'required|string',
            'payment_method' => 'required|string',
        ]);
        try {
            $doctor = Auth::user ();
            $doctorProfileId = $doctor->doctorProfile->id ?? null;

            if (!$doctorProfileId) {
                return $this->sendError ('Doctor profile not found.');
            }

            // Calculate withdrawable balance
            $totalEarnings = Payment::where ('status', 'completed')
                ->whereHas ('consultation', function ($q) use ($doctorProfileId) {
                    $q->where ('doctor_id', $doctorProfileId)
                        ->where ('consultation_status', 'completed')
                        ->whereIn ('payment_status', ['paid', 'completed']);
                })
                ->sum ('amount');

            $totalWithdrawals = Withdrawal::where ('doctor_profile_id', $doctorProfileId)
                ->where ('status', 'success')
                ->sum ('amount');

            $withdrawableBalance = max ($totalEarnings - $totalWithdrawals, 0);

            if ($request->amount > $withdrawableBalance) {
                return $this->sendError ('Insufficient balance to withdraw', 422);
            }

            // Create withdrawal request
            $withdrawal = Withdrawal::create ([
                'doctor_profile_id' => $doctorProfileId,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
                'status' => 'pending',
            ]);
            return $this->sendResponse ($withdrawal, 'Withdrawal request submitted successfully');
        } catch (Exception $e) {
            Log::error('Withdrawal request failed: ' . $e->getMessage());
            return $this->sendError('Failed to submit withdrawal request');
        }
    }

    /**
     * View latest transaction history for the doctor.
     */
    public function viewTransactionHistory(Request $request)
    {
        try {
            $doctor = Auth::user();
            $doctorProfileId = $doctor->doctorProfile->id ?? null;

            if (!$doctorProfileId) {
                return $this->sendError('Doctor profile not found.');
            }

            // ✅ Fetch consultations with payments
            $consultations = Consultation::where('doctor_id', $doctorProfileId)
                ->whereHas('payment')
                ->with('payment')
                ->get()
                ->map(function ($c) {
                    return [
                        'id'     => $c->id,
                        'type'   => 'Payment',
                        'amount' => $c->payment?->amount ?? 0,
                        'status' => ucfirst($c->consultation_status),
                        'date'   => $c->created_at,
                    ];
                });

            // ✅ Fetch withdrawals
            $withdrawals = Withdrawal::where('doctor_profile_id', $doctorProfileId)
                ->get()
                ->map(function ($w) {
                    return [
                        'id'     => $w->id,
                        'type'   => 'Withdrawal',
                        'amount' => $w->amount,
                        'status' => ucfirst($w->status),
                        'date'   => $w->created_at,
                    ];
                });

            // ✅ Merge, sort by date descending, take latest 6
            $transactions = $consultations->merge($withdrawals)
                ->sortByDesc('date')
                ->take(6)
                ->values()
                ->map(function ($t) {
                    // Format date same as wallet
                    $t['date'] = $t['date']->format('M d, Y H:i');
                    return $t;
                });

            return $this->sendResponse( $transactions, __('Transaction history fetched successfully'));

        } catch (Exception $e) {
            Log::error('Transaction history fetch failed: ' . $e->getMessage());
            return $this->sendError('Failed to fetch transaction history');
        }
    }

}
