<?php

namespace Crmtwenty\Platform\Http\Controllers;

use Crmtwenty\Platform\Models\Company;
use Crmtwenty\Platform\Services\CompanyManager;
use Crmtwenty\Platform\Support\Logins;
use Crmtwenty\Platform\Support\Tenancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CompanyController extends Controller
{
    public function __construct(
        protected CompanyManager $manager,
        protected Logins $logins,
        protected Tenancy $tenancy,
    ) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('platform.dashboard');
    }

    public function create(): View
    {
        return view('platform::companies.create');
    }

    public function store(): RedirectResponse
    {
        $data = $this->validate(request(), [
            'name' => 'required|string|max:120',
            'phone' => 'nullable|string|max:40',
            'notes' => 'nullable|string|max:2000',
            'manager_name' => 'required|string|max:120',
            'manager_username' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/'],
            'manager_email' => 'required|email|max:190',
            'manager_password' => 'required|string|min:8|confirmed',
        ]);

        $errors = [];

        if ($this->logins->taken('username', $data['manager_username'], null, null)) {
            $errors['manager_username'] = trans('platform::app.validation.username-taken');
        }

        if ($this->logins->taken('email', $data['manager_email'], null, null)) {
            $errors['manager_email'] = trans('platform::app.validation.email-taken');
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        try {
            $company = $this->manager->create($data);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput(request()->except('manager_password', 'manager_password_confirmation'))
                ->with('error', trans('platform::app.companies.create-failed', ['error' => $e->getMessage()]));
        }

        return redirect()->route('platform.companies.edit', $company->id)
            ->with('success', trans('platform::app.companies.created', ['name' => $company->name]));
    }

    public function edit(int $id): View
    {
        $company = Company::query()->findOrFail($id);

        $users = collect();

        try {
            $users = DB::connection(Tenancy::CONNECTION)
                ->table($company->table_prefix.'users')
                ->leftJoin($company->table_prefix.'roles', $company->table_prefix.'roles.id', '=', $company->table_prefix.'users.role_id')
                ->orderBy($company->table_prefix.'users.id')
                ->get([
                    $company->table_prefix.'users.id',
                    $company->table_prefix.'users.name',
                    $company->table_prefix.'users.email',
                    $company->table_prefix.'users.status',
                    $company->table_prefix.'users.created_at',
                    $company->table_prefix.'roles.name as role',
                ]);
        } catch (Throwable $e) {
            report($e);
        }

        return view('platform::companies.edit', [
            'company' => $company,
            'users' => $users,
            'stats' => [
                'users' => $users->count(),
                'leads' => $this->tenancy->countIn($company, 'leads'),
                'persons' => $this->tenancy->countIn($company, 'persons'),
                'organizations' => $this->tenancy->countIn($company, 'organizations'),
            ],
        ]);
    }

    public function update(int $id): RedirectResponse
    {
        $company = Company::query()->findOrFail($id);

        $data = $this->validate(request(), [
            'name' => 'required|string|max:120',
            'phone' => 'nullable|string|max:40',
            'notes' => 'nullable|string|max:2000',
            'status' => ['required', Rule::in([Company::STATUS_ACTIVE, Company::STATUS_SUSPENDED])],
        ]);

        $company->update($data);

        return back()->with('success', trans('platform::app.companies.updated'));
    }

    public function password(int $id): RedirectResponse
    {
        $company = Company::query()->findOrFail($id);

        $data = $this->validate(request(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (! $company->manager_user_id) {
            return back()->with('error', trans('platform::app.companies.no-manager'));
        }

        $this->manager->resetManagerPassword($company, $data['password']);

        return back()->with('success', trans('platform::app.companies.password-changed'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $company = Company::query()->findOrFail($id);

        if ($company->isPrimary()) {
            return back()->with('error', trans('platform::app.companies.primary-delete'));
        }

        $this->validate(request(), [
            'confirm_name' => ['required', 'string', Rule::in([$company->name])],
        ], [
            'confirm_name.in' => trans('platform::app.companies.confirm-mismatch'),
        ]);

        $this->manager->delete($company);

        return redirect()->route('platform.dashboard')
            ->with('success', trans('platform::app.companies.deleted', ['name' => $company->name]));
    }
}
