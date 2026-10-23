<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Flash a toast notification for the next Inertia page.
     *
     * @param 'success'|'error'|'info'|'warning' $type
     */
    protected function toast(string $message, string $type = 'success'): void
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);
    }

    /**
     * Read an optional free-text search term from the query string.
     */
    protected function searchTerm(Request $request, string $key = 'search'): ?string
    {
        $term = $request->string($key)->trim()->limit(100, '')->toString();

        return $term === '' ? null : $term;
    }
}
