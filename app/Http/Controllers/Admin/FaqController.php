<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FaqRequest;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class FaqController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/faqs', [
            'faqs' => FaqResource::collection(Faq::query()->ordered()->get())->resolve(),
        ]);
    }

    public function store(FaqRequest $request): RedirectResponse
    {
        Faq::query()->create($request->faqAttributes());

        $this->toast(__('Question added.'));

        return back();
    }

    public function update(FaqRequest $request, Faq $faq): RedirectResponse
    {
        $faq->update($request->faqAttributes());

        $this->toast(__('Question updated.'));

        return back();
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        $this->toast(__('Question deleted.'));

        return back();
    }
}
