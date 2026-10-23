<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;

#[Signature('shop:create-admin {email : The administrator\'s email address} {--name=Administrator : Display name}')]
#[Description('Create (or promote) an administrator account')]
final class CreateAdmin extends Command
{
    public function handle(): int
    {
        $email = $this->stringArgument('email');
        $user  = User::query()->where('email', $email)->first();

        if ($user !== null) {
            $user->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();
            $this->components->info("{$email} is now an administrator.");

            return self::SUCCESS;
        }

        $secret = password('Password for the new administrator', required: true);

        $validator = Validator::make(
            ['email' => $email, 'password' => $secret],
            ['email' => ['required', 'email', 'max:255'], 'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        (new User(['name' => $this->stringOption('name'), 'email' => $email, 'password' => $secret]))
            ->forceFill(['role' => UserRole::Admin, 'is_active' => true, 'email_verified_at' => now()])
            ->save();

        $this->components->info("Administrator {$email} created.");

        return self::SUCCESS;
    }

    private function stringArgument(string $name): string
    {
        $value = $this->argument($name);

        return is_string($value) ? $value : '';
    }

    private function stringOption(string $name): string
    {
        $value = $this->option($name);

        return is_string($value) ? $value : '';
    }
}
