@extends('platform::layouts.app')

@section('title', __('platform::app.dashboard.title'))

@section('subtitle', __('platform::app.dashboard.subtitle'))

@section('actions')
    <a class="btn btn-primary" href="{{ route('platform.companies.create') }}"><i class="pi pi-plus"></i> @lang('platform::app.companies.new')</a>
@endsection

@section('content')
    @php($jalali = \Crmtwenty\Persian\Support\Jalali::class)

    <div class="grid grid-4" style="margin-bottom: 24px">
        <div class="card stat">
            <div class="ico"><i class="pi pi-buildings"></i></div>
            <div><b>{{ $jalali::toPersianDigits((string) $totals['companies']) }}</b><span>@lang('platform::app.dashboard.companies')</span></div>
        </div>

        <div class="card stat">
            <div class="ico ok"><i class="pi pi-check-circle"></i></div>
            <div><b>{{ $jalali::toPersianDigits((string) $totals['active']) }}</b><span>@lang('platform::app.dashboard.active')</span></div>
        </div>

        <div class="card stat">
            <div class="ico violet"><i class="pi pi-users-three"></i></div>
            <div><b>{{ $jalali::toPersianDigits((string) $totals['users']) }}</b><span>@lang('platform::app.dashboard.users')</span></div>
        </div>

        <div class="card stat">
            <div class="ico warn"><i class="pi pi-target"></i></div>
            <div><b>{{ $jalali::toPersianDigits((string) $totals['leads']) }}</b><span>@lang('platform::app.dashboard.leads')</span></div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2><i class="pi pi-buildings"></i> @lang('platform::app.dashboard.list')</h2>
        </div>

        @if ($companies->isEmpty())
            <div class="empty">
                <i class="pi pi-buildings"></i>
                <h3>@lang('platform::app.dashboard.empty-title')</h3>
                <p>@lang('platform::app.dashboard.empty-text')</p>
                <a class="btn btn-primary" href="{{ route('platform.companies.create') }}"><i class="pi pi-plus"></i> @lang('platform::app.companies.new')</a>
            </div>
        @else
            <div style="overflow-x:auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>@lang('platform::app.companies.company')</th>
                            <th>@lang('platform::app.companies.manager')</th>
                            <th>@lang('platform::app.companies.status')</th>
                            <th>@lang('platform::app.companies.users')</th>
                            <th>@lang('platform::app.companies.leads')</th>
                            <th>@lang('platform::app.companies.created-at')</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($companies as $company)
                            <tr>
                                <td>
                                    <div class="company-cell">
                                        <div class="company-logo" style="{{ $company->isPrimary() ? 'background:linear-gradient(135deg,#0ea5e9,#6366f1)' : '' }}">{{ mb_substr($company->name, 0, 1) }}</div>
                                        <div>
                                            <strong>{{ $company->name }}</strong>
                                            <span>
                                                @if ($company->isPrimary())
                                                    @lang('platform::app.companies.primary')
                                                @else
                                                    @lang('platform::app.companies.code', ['code' => $jalali::toPersianDigits((string) $company->id)])
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $company->manager_name ?: '—' }}</div>
                                    <div class="muted mono" style="font-size:12px">{{ $company->manager_username ?: $company->manager_email }}</div>
                                </td>
                                <td>@include('platform::partials.status', ['status' => $company->status])</td>
                                <td>{{ $jalali::toPersianDigits((string) ($stats[$company->id]['users'] ?? 0)) }}</td>
                                <td>{{ $jalali::toPersianDigits((string) ($stats[$company->id]['leads'] ?? 0)) }}</td>
                                <td class="muted">{{ $company->created_at ? $jalali::format($company->created_at, 'j F Y') : '—' }}</td>
                                <td style="text-align:end">
                                    <a class="btn btn-ghost btn-sm" href="{{ route('platform.companies.edit', $company->id) }}"><i class="pi pi-gear-six"></i> @lang('platform::app.companies.manage')</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
