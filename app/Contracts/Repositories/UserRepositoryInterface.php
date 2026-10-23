<?php

declare(strict_types = 1);

namespace App\Contracts\Repositories;

use App\DTOs\UserFilters;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateForAdmin(UserFilters $filters): LengthAwarePaginator;

    /**
     * Load the order statistics shown on the admin customer page.
     */
    public function loadAdminDetails(User $user): User;

    public function countCustomers(): int;

    public function countCustomersSince(CarbonInterface $since): int;

    public function countActiveAdmins(): int;

    /**
     * @return Collection<int, User>
     */
    public function activeAdmins(): Collection;
}
