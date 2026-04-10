<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationCreated extends Notification
{
    use Queueable;

    public function __construct(
        public Invitation $invitation
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('register', ['token' => $this->invitation->token]);

        return (new MailMessage)
            ->subject('You have been invited to join '.config('app.name'))
            ->greeting('Hello '.$this->invitation->name.'!')
            ->line('You have been invited to create an account.')
            ->action('Accept Invitation', $url)
            ->line('This invitation will expire on '.$this->invitation->expires_at->format('F j, Y').'.')
            ->line('If you did not expect this invitation, you can safely ignore this email.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'email' => $this->invitation->email,
            'name' => $this->invitation->name,
        ];
    }
}
