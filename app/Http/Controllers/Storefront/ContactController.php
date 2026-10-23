<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ContactRequest;
use App\Services\ContactService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ContactController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('storefront/contact');
    }

    public function store(ContactRequest $request, ContactService $contact): RedirectResponse
    {
        $contact->submit($request->messageData(), $request->user(), $request->ip());

        $this->toast(__('Thanks for reaching out! We will reply within one business day.'));

        return to_route('contact');
    }
}
