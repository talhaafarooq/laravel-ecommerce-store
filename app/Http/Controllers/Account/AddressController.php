<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Services\AddressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AddressController extends Controller
{
    public function __construct(private readonly AddressService $addresses) {}

    public function index(Request $request): Response
    {
        return Inertia::render('account/addresses', [
            'addresses' => AddressResource::collection(
                $request->user()->addresses()->orderByDesc('is_default')->latest('id')->get(),
            )->resolve(),
        ]);
    }

    public function store(AddressRequest $request): RedirectResponse
    {
        $this->addresses->create($request->user(), $request->toData(), $request->boolean('is_default'));

        return $this->saved(__('Address added.'));
    }

    public function update(AddressRequest $request, Address $address): RedirectResponse
    {
        $this->authorize('update', $address);

        $this->addresses->update($address, $request->toData(), $request->boolean('is_default'));

        return $this->saved(__('Address updated.'));
    }

    public function makeDefault(Address $address): RedirectResponse
    {
        $this->authorize('update', $address);

        $this->addresses->makeDefault($address);

        return $this->saved(__('Default address updated.'));
    }

    public function destroy(Address $address): RedirectResponse
    {
        $this->authorize('delete', $address);

        $this->addresses->delete($address);

        return $this->saved(__('Address removed.'));
    }

    private function saved(string $message): RedirectResponse
    {
        $this->toast($message);

        return back();
    }
}
