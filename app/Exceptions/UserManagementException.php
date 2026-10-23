<?php

declare(strict_types = 1);

namespace App\Exceptions;

final class UserManagementException extends BusinessRuleException
{
    public static function cannotDemoteSelf(): self
    {
        return new self('You cannot remove your own administrator role.');
    }

    public static function cannotDeactivateSelf(): self
    {
        return new self('You cannot deactivate your own account.');
    }

    public static function lastAdministrator(): self
    {
        return new self('The store must keep at least one active administrator.');
    }

    public function errorKey(): string
    {
        return 'user';
    }
}
