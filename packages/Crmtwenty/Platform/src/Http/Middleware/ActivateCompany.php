<?php

namespace Crmtwenty\Platform\Http\Middleware;

use Closure;
use Crmtwenty\Platform\Models\Company;
use Crmtwenty\Platform\Services\CompanyManager;
use Crmtwenty\Platform\Support\Logins;
use Crmtwenty\Platform\Support\Tenancy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Points every CRM request at the data of the signed-in user's company.
 *
 * Runs right after the session has started (before route model binding and authentication),
 * so the user, every repository and every query already work on the right company.
 */
class ActivateCompany
{
    /**
     * Routes where the company is chosen from the submitted e-mail address / username
     * instead of the session.
     */
    public const LOGIN_ROUTES = [
        'admin.session.store',
        'admin.forgot_password.store',
        'admin.reset_password.create',
        'admin.reset_password.store',
    ];

    /**
     * Public web form endpoints (embedded on other sites, no session): the company is the
     * owner of the form.
     */
    public const WEB_FORM_ROUTES = [
        'admin.settings.web_forms.form_js',
        'admin.settings.web_forms.preview',
        'admin.settings.web_forms.form_store',
    ];

    public function __construct(
        protected Tenancy $tenancy,
        protected Logins $logins,
        protected CompanyManager $companies,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->tenancy->enabled()) {
            $this->companies->upgradeBase();

            return $next($request);
        }

        $route = (string) optional($request->route())->getName();

        /**
         * The platform panel always works on the platform's own tables.
         */
        if (str_starts_with($route, 'platform.')) {
            return $next($request);
        }

        if (in_array($route, self::LOGIN_ROUTES, true)) {
            return $this->fromLogin($request, $next);
        }

        if (in_array($route, self::WEB_FORM_ROUTES, true)
            && ($owner = $this->tenancy->companyForWebForm((string) $request->route('id')))) {
            $this->enter($owner);

            return $next($request);
        }

        $company = null;

        if ($id = $request->session()->get(Tenancy::SESSION_KEY)) {
            $company = Company::query()->find($id);

            if (! $company || ! $company->isActive()) {
                return $this->signOut($request, $company);
            }
        }

        $company ??= $this->logins->currentCompany();

        if ($company && $company->isActive()) {
            $this->enter($company);
        }

        return $next($request);
    }

    /**
     * Choose the company from the username / e-mail address in the request.
     */
    protected function fromLogin(Request $request, Closure $next): Response
    {
        $entry = $this->logins->find($request->input('email'));

        $company = $entry?->company;

        if ($company && ! $company->isActive()) {
            return redirect()->route('admin.session.create')
                ->withInput($request->only('email'))
                ->with('error', trans('platform::app.company-suspended'));
        }

        if ($company) {
            $request->session()->put(Tenancy::SESSION_KEY, $company->id);

            $this->enter($company);
        } else {
            $request->session()->forget(Tenancy::SESSION_KEY);
        }

        return $next($request);
    }

    /**
     * Activate the company, bringing its tables up to date after a deployment.
     */
    protected function enter(Company $company): void
    {
        try {
            $this->companies->upgrade($company);
        } catch (Throwable $e) {
            report($e);
        }

        $this->tenancy->activate($company);

        if (! $company->last_activity_at || $company->last_activity_at->lt(now()->subMinutes(10))) {
            try {
                $company->forceFill(['last_activity_at' => now()])->saveQuietly();
            } catch (Throwable) {
                // Not important enough to fail the request.
            }
        }
    }

    /**
     * The company was suspended or removed: end the session.
     */
    protected function signOut(Request $request, ?Company $company): Response
    {
        auth()->guard('user')->logout();

        $request->session()->forget(Tenancy::SESSION_KEY);

        if ($request->expectsJson()) {
            return response()->json(['message' => trans('platform::app.company-suspended')], 401);
        }

        return redirect()->route('admin.session.create')
            ->with('error', trans($company ? 'platform::app.company-suspended' : 'platform::app.company-missing'));
    }
}
