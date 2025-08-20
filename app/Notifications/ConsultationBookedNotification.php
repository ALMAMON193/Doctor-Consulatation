<?php

namespace App\Notifications;

use App\Models\Consultation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConsultationBookedNotification extends Notification
{
    protected Consultation $consultation;

    public function __construct(Consultation $consultation)
    {
        $this->consultation = $consultation;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database']; // DB + Email
    }

    public function toMail($notifiable): MailMessage
    {
        $consultation = $this->consultation;

        return (new MailMessage)
            ->subject('New Consultation in Your Specialization')
            ->greeting('Hello Dr. ' . $notifiable->name)
            ->line('A patient has booked a consultation in your specialization: ' . $consultation->specialization->name)
            ->line('Patient Complaint: ' . ($consultation->complaint ?? 'Not Provided'))
            ->line('Consultation Date: ' . optional($consultation->consultation_date)->format('d M, Y H:i'))
            // ✅ Accept button
            ->action('Accept Consultation', url('/api/doctor/consultations/' . $consultation->id . '/accept'))
            // ✅ View button
            ->action('View Consultation', url('/api/doctor/consultations/' . $consultation->id))
            ->line('Please accept this consultation if you are available. Thank you for using our platform.');
    }
    /**
     * Database notification content
     * Saved in the "notifications" table
     */
    public function toArray($notifiable): array
    {
        return [
            'consultation_id'   => $this->consultation->id,
            'specialization'    => $this->consultation->specialization->name,
            'patient'           => $this->consultation->patient?->name ?? $this->consultation->patientMember?->name,
            'consultation_date' => optional($this->consultation->consultation_date)->toDateTimeString(),
            'actions' => [
                'accept_consultation' => url('/api/doctor/consultations/' . $this->consultation->id . '/accept'),
                'view_consultation'   => url('/api/doctor/consultations/' . $this->consultation->id),
            ]
        ];
    }

}
