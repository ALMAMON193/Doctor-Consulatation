<?php

namespace App\Notifications;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalStatusNotification extends Notification
{

    protected Withdrawal $withdrawal;

    public function __construct(Withdrawal $withdrawal)
    {
        $this->withdrawal = $withdrawal;
    }

    public function via($notifiable): array
    {
        return ['database']; // Email + in-app notification
    }

    public function toArray($notifiable): array
    {
        return [
            'withdrawal_id' => $this->withdrawal->id,
            'amount' => $this->withdrawal->amount,
            'status' => $this->withdrawal->status,
            'message' => $this->withdrawal->status === 'success'
                ? "Bank info provided and amount transferred. Please check."
                : "Withdrawal rejected. Reason: {$this->withdrawal->remarks}"
        ];
    }
}
