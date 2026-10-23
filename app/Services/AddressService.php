<?php

declare(strict_types = 1);

namespace App\Services;

use App\DTOs\AddressData;
use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Manages a customer's address book, keeping exactly one default address.
 */
final class AddressService
{
    public function create(User $user, AddressData $data, bool $makeDefault = false): Address
    {
        return DB::transaction(function () use ($user, $data, $makeDefault): Address {
            $isFirst = !$user->addresses()->exists();

            /** @var Address $address */
            $address = $user->addresses()->create([
                ...$data->toAttributes(),
                'is_default' => false,
            ]);

            if ($makeDefault || $isFirst) {
                $this->makeDefault($address);
            }

            return $address;
        });
    }

    public function update(Address $address, AddressData $data, bool $makeDefault = false): Address
    {
        return DB::transaction(function () use ($address, $data, $makeDefault): Address {
            $address->update($data->toAttributes());

            if ($makeDefault) {
                $this->makeDefault($address);
            }

            return $address;
        });
    }

    public function delete(Address $address): void
    {
        DB::transaction(static function () use ($address): void {
            $wasDefault = $address->is_default;
            $userId     = $address->user_id;

            $address->delete();

            if ($wasDefault) {
                Address::query()
                    ->where('user_id', $userId)
                    ->oldest('id')
                    ->limit(1)
                    ->update(['is_default' => true]);
            }
        });
    }

    public function makeDefault(Address $address): void
    {
        DB::transaction(static function () use ($address): void {
            Address::query()
                ->where('user_id', $address->user_id)
                ->whereKeyNot($address->getKey())
                ->update(['is_default' => false]);

            $address->forceFill(['is_default' => true])->save();
        });
    }

    /**
     * Save the address used at checkout unless an identical one already exists.
     */
    public function rememberFromCheckout(User $user, AddressData $data): void
    {
        $exists = $user->addresses()
            ->where('line1', $data->line1)
            ->where('postal_code', $data->postalCode)
            ->where('country', $data->country)
            ->exists();

        if (!$exists) {
            $this->create($user, $data);
        }
    }
}
