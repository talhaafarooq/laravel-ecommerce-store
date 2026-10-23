<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MessageController extends Controller
{
    public function index(Request $request): Response
    {
        $unreadOnly = $request->boolean('unread');

        $messages = ContactMessage::query()
            ->when($unreadOnly, static fn ($query) => $query->unread())
            ->latest()
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/messages/index', [
            'messages' => ContactMessageResource::collection($messages),
            'filters'  => ['unread' => $unreadOnly],
        ]);
    }

    public function show(ContactMessage $message): Response
    {
        $message->markAsRead();

        return Inertia::render('admin/messages/show', [
            'message' => ContactMessageResource::make($message)->resolve(),
        ]);
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        $this->toast(__('Message deleted.'));

        return to_route('admin.messages.index');
    }
}
