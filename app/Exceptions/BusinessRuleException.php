<?php

declare(strict_types = 1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;

/**
 * A violated business rule with a message that is safe to show to customers.
 *
 * Renders as a 422 JSON error for API clients and as a redirect back with a
 * validation error plus an error toast for Inertia requests.
 */
abstract class BusinessRuleException extends RuntimeException
{
    /**
     * Whether the exception should be written to the application log.
     */
    protected bool $shouldLog = false;

    /**
     * The key the error is reported under in the validation error bag.
     */
    public function errorKey(): string
    {
        return 'error';
    }

    public function status(): int
    {
        return 422;
    }

    /**
     * Returning false hands the exception to the default logger.
     */
    public function report(): bool
    {
        return !$this->shouldLog;
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'errors'  => [$this->errorKey() => [$this->getMessage()]],
            ], $this->status());
        }

        Inertia::flash('toast', ['type' => 'error', 'message' => $this->getMessage()]);

        return back()->withErrors([$this->errorKey() => $this->getMessage()]);
    }
}
