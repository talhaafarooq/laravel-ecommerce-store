<?php

declare(strict_types = 1);

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactMessageReceivedNotification;
use Illuminate\Support\Facades\Notification;

final class ContactService
{
    /**
     * Store the message and alert the store team.
     *
     * @param array{name: string, email: string, phone?: string|null, subject: string, message: string} $data
     */
    public function submit(array $data, ?User $user = null, ?string $ipAddress = null): ContactMessage
    {
        $message = ContactMessage::query()->create([
            'user_id'    => $user?->id,
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'subject'    => $data['subject'],
            'message'    => $data['message'],
            'ip_address' => $ipAddress,
        ]);

        Notification::route('mail', (string) config('shop.contact.admin_email'))
            ->notify(new ContactMessageReceivedNotification($message));

        return $message;
    }
}
