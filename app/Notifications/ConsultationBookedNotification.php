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
        return (new MailMessage)
            ->subject('New Consultation in Your Specialization')
            ->greeting('Hello Dr. ' . $notifiable->name)
            ->line('A patient has booked a consultation in your specialization: ' . $this->consultation->specialization->name)
            ->line('Patient Complaint: ' . ($this->consultation->complaint ?? 'Not Provided'))
            ->line('Consultation Date: ' . $this->consultation->consultation_date->format('d M, Y H:i'))
            ->action('View Consultation', url('/doctor/consultations/' . $this->consultation->id))
            ->line('Thank you for using our platform.');
    }

    /**
     * Database notification content
     * Saved in the "notifications" table
     */
    public function toArray($notifiable): array
    {
        return [
            'consultation_id' => $this->consultation->id,
            'specialization'  => $this->consultation->specialization->name,
            'patient'         => $this->consultation->patient?->name ?? $this->consultation->patientMember?->name,
            'consultation_date' => $this->consultation->consultation_date->toDateTimeString(),
        ];
    }
}
