<?php

namespace Crmtwenty\Platform\Http\Controllers;

use Crmtwenty\Platform\Models\Company;
use Crmtwenty\Platform\Models\PlatformAdmin;
use Crmtwenty\Platform\Support\Logins;
use Crmtwenty\Platform\Support\PlatformSchema;
use Crmtwenty\Platform\Support\Revision;
use Crmtwenty\Platform\Support\Tenancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * One-time activation of the multi-company platform.
 *
 * Only an administrator of the existing installation can do it: they confirm with their CRM
 * credentials and create the first platform administrator. The existing data becomes the
 * platform's first (primary) company.
 */
class SetupController extends Controller
{
    public function __construct(
        protected Tenancy $tenancy,
        protected Logins $logins,
    ) {}

    public function create(): View|RedirectResponse
    {
        if ($this->tenancy->enabled()) {
            return redirect()->route('platform.session.create');
        }

        return view('platform::setup');
    }

    public function store(): RedirectResponse
    {
        if ($this->tenancy->enabled()) {
            return redirect()->route('platform.session.create');
        }

        $key = 'platform-setup|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            abort(429);
        }

        $data = $this->validate(request(), [
            'crm_email' => 'required|email',
            'crm_password' => 'required|string',
            'company_name' => 'required|string|max:120',
            'name' => 'required|string|max:120',
            'username' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/'],
            'email' => 'required|email|max:190',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $crmAdmin = DB::connection(Tenancy::CONNECTION)
            ->table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('users.email', Logins::normalize($data['crm_email']))
            ->where('users.status', 1)
            ->where('roles.permission_type', 'all')
            ->select('users.*')
            ->first();

        if (! $crmAdmin || ! Hash::check($data['crm_password'], $crmAdmin->password)) {
            RateLimiter::hit($key, 300);

            throw ValidationException::withMessages([
                'crm_email' => trans('platform::app.setup.invalid-crm-admin'),
            ]);
        }

        RateLimiter::clear($key);

        PlatformSchema::migrate();

        DB::connection(Tenancy::CONNECTION)->transaction(function () use ($data) {
            PlatformAdmin::query()->create([
                'name' => $data['name'],
                'username' => Logins::normalize($data['username']),
                'email' => Logins::normalize($data['email']),
                'password' => Hash::make($data['password']),
                'status' => true,
            ]);

            $primary = Company::query()->firstOrCreate(['table_prefix' => ''], [
                'name' => $data['company_name'],
                'status' => Company::STATUS_ACTIVE,
                'migrated_revision' => Revision::current(),
            ]);

            $manager = DB::connection(Tenancy::CONNECTION)->table('users')->orderBy('id')->first();

            if ($manager) {
                $primary->forceFill([
                    'manager_user_id' => $manager->id,
                    'manager_name' => $manager->name,
                    'manager_email' => $manager->email,
                    'manager_username' => property_exists($manager, 'username') ? $manager->username : null,
                ])->save();
            }

            $this->logins->syncCompany($primary);
        });

        $this->tenancy->flush();

        $admin = PlatformAdmin::query()->where('username', Logins::normalize($data['username']))->firstOrFail();

        auth()->guard('platform')->login($admin);

        request()->session()->regenerate();

        return redirect()->route('platform.dashboard')
            ->with('success', trans('platform::app.setup.done'));
    }
}
