<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the whole /admin area.
 *
 * Deliberately simple: a single `is_admin` boolean on the user record, the
 * same model as the matsart.ca project. A full policy/gate setup would be
 * over-engineering for a site with exactly one privileged role.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // `auth` runs before this, but check defensively so this middleware
        // can never be mounted on a route without an auth guard behind it.
        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->isAdmin()) {
            abort(403, 'You do not have access to the site administration.');
        }

        return $next($request);
    }
}
