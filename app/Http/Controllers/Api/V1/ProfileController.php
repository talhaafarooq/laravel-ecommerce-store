<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;

final class ProfileController extends Controller
{
    public function update(ProfileUpdateRequest $request, UserService $users): UserResource
    {
        return UserResource::make($users->updateProfile(
            $request->user(),
            $request->profileData(),
            $request->file('avatar'),
            $request->boolean('remove_avatar'),
        ));
    }
}
