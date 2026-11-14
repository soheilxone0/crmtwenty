<?php

namespace Crmtwenty\Platform\Http\Controllers;

use Crmtwenty\Platform\Models\Company;
use Crmtwenty\Platform\Support\Tenancy;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected Tenancy $tenancy) {}

    public function index(): View
    {
        $companies = Company::query()->orderByDesc('id')->get();

        $stats = $companies->mapWithKeys(fn (Company $company) => [
            $company->id => [
                'users' => $this->tenancy->countIn($company, 'users'),
                'leads' => $this->tenancy->countIn($company, 'leads'),
                'persons' => $this->tenancy->countIn($company, 'persons'),
            ],
        ]);

        return view('platform::dashboard', [
            'companies' => $companies,
            'stats' => $stats,
            'totals' => [
                'companies' => $companies->count(),
                'active' => $companies->where('status', Company::STATUS_ACTIVE)->count(),
                'users' => $stats->sum('users'),
                'leads' => $stats->sum('leads'),
            ],
        ]);
    }
}
