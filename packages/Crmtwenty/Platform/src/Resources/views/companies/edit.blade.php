@extends('platform::layouts.app')

@php($jalali = \Crmtwenty\Persian\Support\Jalali::class)

@section('title', $company->name)

@section('subtitle')
    @include('platform::partials.status', ['status' => $company->status])
    <span class="muted" style="margin-inline-start:8px">
        @lang('platform::app.companies.created-on', ['date' => $company->created_at ? $jalali::format($company->created_at, 'j F Y') : '—'])
    </span>
@endsection

@section('crumbs')
    <a href="{{ route('platform.dashboard') }}">@lang('platform::app.nav.companies')</a> <i class="pi pi-caret-left"></i> {{ $company->name }}
@endsection

@section('actions')
    <a class="btn btn-ghost" href="{{ route('admin.session.create') }}" target="_blank"><i class="pi pi-arrow-square-out"></i> @lang('platform::app.companies.login-page')</a>
@endsection

@section('content')
    <div class="grid grid-4" style="margin-bottom: 24px">
        <div class="card stat"><div class="ico violet"><i class="pi pi-users-three"></i></div><div><b>{{ $jalali::toPersianDigits((string) $stats['users']) }}</b><span>@lang('platform::app.companies.users')</span></div></div>
        <div class="card stat"><div class="ico warn"><i class="pi pi-target"></i></div><div><b>{{ $jalali::toPersianDigits((string) $stats['leads']) }}</b><span>@lang('platform::app.companies.leads')</span></div></div>
        <div class="card stat"><div class="ico"><i class="pi pi-address-book"></i></div><div><b>{{ $jalali::toPersianDigits((string) $stats['persons']) }}</b><span>@lang('platform::app.companies.persons')</span></div></div>
        <div class="card stat"><div class="ico ok"><i class="pi pi-buildings"></i></div><div><b>{{ $jalali::toPersianDigits((string) $stats['organizations']) }}</b><span>@lang('platform::app.companies.organizations')</span></div></div>
    </div>

    <div class="grid grid-main">
        <div class="stack">
            <div class="card">
                <div class="card-head"><h2><i class="pi pi-pencil-simple-line"></i> @lang('platform::app.companies.info')</h2></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('platform.companies.update', $company->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-2" style="gap:0 16px">
                            @include('platform::partials.field', ['name' => 'name', 'label' => __('platform::app.companies.name'), 'icon' => 'buildings', 'required' => true, 'value' => $company->name])
                            @include('platform::partials.field', ['name' => 'phone', 'label' => __('platform::app.companies.phone'), 'icon' => 'phone', 'ltr' => true, 'value' => $company->phone])
                        </div>

                        <div class="field">
                            <label for="f-status">@lang('platform::app.companies.status')</label>
                            <select id="f-status" name="status" class="input">
                                <option value="active" @selected(old('status', $company->status) === 'active')>@lang('platform::app.status.active')</option>
                                <option value="suspended" @selected(old('status', $company->status) === 'suspended')>@lang('platform::app.status.suspended')</option>
                            </select>
                            <span class="hint">@lang('platform::app.companies.status-hint')</span>
                        </div>

                        <div class="field">
                            <label for="f-notes">@lang('platform::app.companies.notes')</label>
                            <textarea id="f-notes" name="notes" class="input">{{ old('notes', $company->notes) }}</textarea>
                        </div>

                        <button class="btn btn-primary" type="submit"><i class="pi pi-check"></i> @lang('platform::app.actions.save')</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <h2><i class="pi pi-users-three"></i> @lang('platform::app.companies.members')</h2>
                    <span class="hint">@lang('platform::app.companies.members-hint')</span>
                </div>

                <div style="overflow-x:auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>@lang('platform::app.fields.name')</th>
                                <th>@lang('platform::app.fields.email')</th>
                                <th>@lang('platform::app.companies.role')</th>
                                <th>@lang('platform::app.companies.status')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td>
                                        <div class="company-cell">
                                            <div class="avatar" style="width:34px;height:34px;font-size:13px">{{ mb_substr($user->name, 0, 1) }}</div>
                                            <div>
                                                <strong>{{ $user->name }}</strong>
                                                @if ((int) $user->id === (int) $company->manager_user_id)
                                                    <span class="badge badge-brand" style="height:20px;font-size:11px">@lang('platform::app.companies.manager')</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="mono">{{ $user->email }}</td>
                                    <td>{{ $user->role ?? '—' }}</td>
                                    <td>
                                        @if ($user->status)
                                            <span class="badge badge-ok">@lang('platform::app.status.enabled')</span>
                                        @else
                                            <span class="badge badge-muted">@lang('platform::app.status.disabled')</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="muted" style="text-align:center">—</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head"><h2><i class="pi pi-user-gear"></i> @lang('platform::app.companies.manager')</h2></div>
                <div class="card-body">
                    <div class="kv"><span>@lang('platform::app.fields.name')</span><b>{{ $company->manager_name ?: '—' }}</b></div>
                    <div class="kv"><span>@lang('platform::app.fields.username')</span><b class="mono">{{ $company->manager_username ?: '—' }}</b></div>
                    <div class="kv"><span>@lang('platform::app.fields.email')</span><b class="mono">{{ $company->manager_email ?: '—' }}</b></div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2><i class="pi pi-key"></i> @lang('platform::app.companies.reset-password')</h2></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('platform.companies.password', $company->id) }}">
                        @csrf
                        @method('PUT')

                        @include('platform::partials.field', ['name' => 'password', 'label' => __('platform::app.auth.new-password'), 'type' => 'password', 'icon' => 'lock-key', 'required' => true, 'autocomplete' => 'new-password', 'hint' => __('platform::app.auth.password-hint')])
                        @include('platform::partials.field', ['name' => 'password_confirmation', 'label' => __('platform::app.auth.confirm-password'), 'type' => 'password', 'icon' => 'lock-key', 'required' => true, 'autocomplete' => 'new-password'])

                        <button class="btn btn-ghost btn-block" type="submit"><i class="pi pi-key"></i> @lang('platform::app.companies.set-password')</button>
                    </form>
                </div>
            </div>

            @unless ($company->isPrimary())
                <div class="card danger-zone">
                    <div class="card-head"><h2><i class="pi pi-trash"></i> @lang('platform::app.companies.delete')</h2></div>
                    <div class="card-body">
                        <p class="hint" style="margin-top:0">@lang('platform::app.companies.delete-warning')</p>

                        <form method="POST" action="{{ route('platform.companies.destroy', $company->id) }}" data-confirm="@lang('platform::app.companies.delete-confirm')">
                            @csrf
                            @method('DELETE')

                            @include('platform::partials.field', ['name' => 'confirm_name', 'label' => __('platform::app.companies.type-name', ['name' => $company->name]), 'required' => true])

                            <button class="btn btn-danger btn-block" type="submit"><i class="pi pi-trash"></i> @lang('platform::app.companies.delete-button')</button>
                        </form>
                    </div>
                </div>
            @endunless
        </div>
    </div>
@endsection
