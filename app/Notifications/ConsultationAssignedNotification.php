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

        // Get doctor name safely
        $doctorName = $consultation->doctorProfile?->user?->name ??
            $consultation->doctorProfile?->name ??
            $consultation->assign_application ??
            'Unknown Doctor';

        return (new MailMessage)
            ->subject('Your Consultation Has Been Assigned')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Your consultation request has been assigned to Dr. ' . $doctorName)
            ->line('Specialization: ' . $consultation->specialization->name)
            ->line('Consultation Date: ' . optional($consultation->consultation_date)->format('d M, Y H:i'))
            ->line('Well notify you with updates from your doctor.')
            ->action('View Consultation', url('/patient/consultations/' . $consultation->id));
    }

    public function toArray($notifiable): array
    {
        // Get doctor name safely
        $doctorName = $this->consultation->doctorProfile?->user?->name ??
                     $this->consultation->doctorProfile?->name ??
                     $this->consultation->assign_application ??
                     'Unknown Doctor';

        return [
            'consultation_id'   => $this->consultation->id,
            'doctor'            => $doctorName,
            'specialization'    => $this->consultation->specialization->name,
            'consultation_date' => optional($this->consultation->consultation_date)->toDateTimeString(),
            'actions' => [
                'view_consultation' => url('/patient/consultations/' . $this->consultation->id),
            ]
        ];
    }
}
