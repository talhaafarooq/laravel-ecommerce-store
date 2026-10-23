<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserAccessRequest;
use App\Http\Requests\Admin\UserIndexRequest;
use App\Http\Resources\AddressResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class UserController extends Controller
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function index(UserIndexRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('admin/users/index', [
            'users'   => UserResource::collection($this->users->paginateForAdmin($filters)),
            'filters' => $filters->toArray(),
            'roles'   => UserRole::options(),
        ]);
    }

    public function show(User $user, OrderRepositoryInterface $orders): Response
    {
        $user = $this->users->loadAdminDetails($user);

        return Inertia::render('admin/users/show', [
            'user'      => UserResource::make($user)->resolve(),
            'addresses' => AddressResource::collection($user->addresses)->resolve(),
            'orders'    => OrderResource::collection($orders->recentForCustomer($user, 10))->resolve(),
            'roles'     => UserRole::options(),
        ]);
    }

    public function updateAccess(UpdateUserAccessRequest $request, User $user, UserService $users): RedirectResponse
    {
        $users->updateAccess($user, $request->role(), $request->boolean('is_active'), $request->user());

        $this->toast(__('Access for :name updated.', ['name' => $user->name]));

        return back();
    }
}
