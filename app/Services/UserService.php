<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\UserRole;
use App\Exceptions\UserManagementException;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final readonly class UserService
{
    private const string AVATAR_DIRECTORY = 'avatars';

    public function __construct(
        private UserRepositoryInterface $users,
        private ImageService $images,
    ) {}

    /**
     * Update the role and status of an account on behalf of an administrator.
     *
     * @throws UserManagementException
     */
    public function updateAccess(User $user, UserRole $role, bool $active, User $actor): User
    {
        return DB::transaction(function () use ($user, $role, $active, $actor): User {
            if ($user->is($actor) && $role !== UserRole::Admin) {
                throw UserManagementException::cannotDemoteSelf();
            }

            if ($user->is($actor) && !$active) {
                throw UserManagementException::cannotDeactivateSelf();
            }

            $losesAdminAccess = $user->isAdmin() && $user->is_active && ($role !== UserRole::Admin || !$active);

            if ($losesAdminAccess && $this->users->countActiveAdmins() <= 1) {
                throw UserManagementException::lastAdministrator();
            }

            $user->forceFill(['role' => $role, 'is_active' => $active])->save();

            return $user;
        });
    }

    /**
     * Update the customer's own profile details and avatar.
     *
     * @param array{name: string, email: string, phone?: string|null} $data
     */
    public function updateProfile(User $user, array $data, ?UploadedFile $avatar = null, bool $removeAvatar = false): User
    {
        $previousAvatar = $user->avatar_path;

        $user->fill([
            'name'  => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($avatar !== null) {
            $user->avatar_path = $this->images->storeCovered($avatar, self::AVATAR_DIRECTORY, 256, 256);
        } elseif ($removeAvatar) {
            $user->avatar_path = null;
        }

        $user->save();

        if ($previousAvatar !== null && $previousAvatar !== $user->avatar_path) {
            $this->images->delete($previousAvatar);
        }

        return $user;
    }
}
