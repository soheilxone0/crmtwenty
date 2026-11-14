@extends('platform::layouts.app')

@section('title', __('platform::app.companies.create-title'))

@section('subtitle', __('platform::app.companies.create-subtitle'))

@section('crumbs')
    <a href="{{ route('platform.dashboard') }}">@lang('platform::app.nav.companies')</a> <i class="pi pi-caret-left"></i> @lang('platform::app.companies.new')
@endsection

@section('content')
    <form method="POST" action="{{ route('platform.companies.store') }}" data-busy="@lang('platform::app.companies.creating')">
        @csrf

        <div class="grid grid-main">
            <div class="stack">
                <div class="card">
                    <div class="card-head"><h2><i class="pi pi-buildings"></i> @lang('platform::app.companies.info')</h2></div>
                    <div class="card-body">
                        @include('platform::partials.field', ['name' => 'name', 'label' => __('platform::app.companies.name'), 'icon' => 'buildings', 'required' => true])

                        @include('platform::partials.field', ['name' => 'phone', 'label' => __('platform::app.companies.phone'), 'icon' => 'phone', 'ltr' => true])

                        <div class="field">
                            <label for="f-notes">@lang('platform::app.companies.notes')</label>
                            <textarea id="f-notes" name="notes" class="input">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-head"><h2><i class="pi pi-user-gear"></i> @lang('platform::app.companies.manager-account')</h2></div>
                    <div class="card-body">
                        <div class="grid grid-2" style="gap:0 16px">
                            @include('platform::partials.field', ['name' => 'manager_name', 'label' => __('platform::app.fields.name'), 'icon' => 'user', 'required' => true])
                            @include('platform::partials.field', ['name' => 'manager_username', 'label' => __('platform::app.fields.username'), 'icon' => 'identification-card', 'required' => true, 'ltr' => true, 'hint' => __('platform::app.fields.username-hint')])
                        </div>

                        @include('platform::partials.field', ['name' => 'manager_email', 'label' => __('platform::app.fields.email'), 'type' => 'email', 'icon' => 'envelope-simple', 'required' => true, 'hint' => __('platform::app.companies.email-hint')])

                        <div class="grid grid-2" style="gap:0 16px">
                            @include('platform::partials.field', ['name' => 'manager_password', 'label' => __('platform::app.fields.password'), 'type' => 'password', 'icon' => 'lock-key', 'required' => true, 'autocomplete' => 'new-password', 'hint' => __('platform::app.auth.password-hint')])
                            @include('platform::partials.field', ['name' => 'manager_password_confirmation', 'label' => __('platform::app.auth.confirm-password'), 'type' => 'password', 'icon' => 'lock-key', 'required' => true, 'autocomplete' => 'new-password'])
                        </div>
                    </div>
                </div>
            </div>

            <div class="stack">
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-info" style="margin-bottom:16px"><i class="pi pi-info"></i><div>@lang('platform::app.companies.create-help')</div></div>

                        <ul style="margin:0 0 20px; padding-inline-start: 18px; color: var(--ink-2)">
                            <li>@lang('platform::app.companies.create-step-1')</li>
                            <li>@lang('platform::app.companies.create-step-2')</li>
                            <li>@lang('platform::app.companies.create-step-3')</li>
                        </ul>

                        <button class="btn btn-primary btn-block" type="submit"><i class="pi pi-plus"></i> <span class="label">@lang('platform::app.companies.create-button')</span></button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
