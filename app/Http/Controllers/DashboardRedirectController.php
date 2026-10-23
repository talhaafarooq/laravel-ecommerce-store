<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Fortify's home route: sends each user to the dashboard for their role.
 */
final class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return $request->user()?->can('access-admin')
            ? to_route('admin.dashboard')
            : to_route('account.dashboard');
    }
}
