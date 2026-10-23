<?php

declare(strict_types = 1);

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ContactMessageReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactMessage $contactMessage)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->contactMessage;

        return (new MailMessage)
            ->subject("Contact form: {$message->subject}")
            ->replyTo($message->email, $message->name)
            ->greeting('New message from the contact form')
            ->line("**From:** {$message->name} <{$message->email}>")
            ->line('**Message:**')
            ->line($message->message)
            ->action('Open in admin', route('admin.messages.show', $message));
    }
}
