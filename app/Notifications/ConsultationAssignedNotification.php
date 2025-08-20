<?php

namespace App\Notifications;

use App\Models\Consultation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConsultationAssignedNotification extends Notification
{
    protected Consultation $consultation;

    public function __construct(Consultation $consultation)
    {
        $this->consultation = $consultation;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $consultation = $this->consultation;

        return (new MailMessage)
            ->subject('Your Consultation Has Been Assigned')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Your consultation request has been assigned to Dr. ' . $consultation->doctor->user->name)
            ->line('Specialization: ' . $consultation->specialization->name)
            ->line('Consultation Date: ' . optional($consultation->consultation_date)->format('d M, Y H:i'))
            ->line('We’ll notify you with updates from your doctor.')
            ->action('View Consultation', url('/patient/consultations/' . $consultation->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'consultation_id'   => $this->consultation->id,
            'doctor'            => $this->consultation->doctor->user->name,
            'specialization'    => $this->consultation->specialization->name,
            'consultation_date' => optional($this->consultation->consultation_date)->toDateTimeString(),
            'actions' => [
                'view_consultation' => url('/patient/consultations/' . $this->consultation->id),
            ]
        ];
    }
}
