<?php

namespace Crmtwenty\Platform\Http\Middleware;

use Closure;
use Crmtwenty\Platform\Support\Tenancy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only signed-in platform administrators may use the platform panel.
 */
class PlatformAuthenticate
{
    public function __construct(protected Tenancy $tenancy) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->tenancy->enabled()) {
            return redirect()->route('platform.setup.create');
        }

        $admin = auth()->guard('platform')->user();

        if (! $admin) {
            return redirect()->guest(route('platform.session.create'));
        }

        if (! $admin->status) {
            auth()->guard('platform')->logout();

            return redirect()->route('platform.session.create')
                ->with('error', trans('platform::app.auth.disabled'));
        }

        return $next($request);
    }
}
