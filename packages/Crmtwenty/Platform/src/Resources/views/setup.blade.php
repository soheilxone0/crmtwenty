@extends('platform::layouts.auth')

@section('title', __('platform::app.setup.title'))

@section('card-class', 'auth-wide')

@section('content')
    <h1>@lang('platform::app.setup.title')</h1>
    <p class="lead">@lang('platform::app.setup.lead')</p>

    @include('platform::partials.flash')

    <form method="POST" action="{{ route('platform.setup.store') }}" data-busy="@lang('platform::app.setup.working')">
        @csrf

        <div class="section-title"><i class="pi pi-shield-check"></i> @lang('platform::app.setup.verify')</div>

        <p class="hint" style="margin:-6px 0 14px">@lang('platform::app.setup.verify-hint')</p>

        <div class="grid grid-2" style="gap:0 16px">
            @include('platform::partials.field', ['name' => 'crm_email', 'label' => __('platform::app.setup.crm-email'), 'type' => 'email', 'icon' => 'envelope-simple', 'required' => true])
            @include('platform::partials.field', ['name' => 'crm_password', 'label' => __('platform::app.setup.crm-password'), 'type' => 'password', 'icon' => 'lock-key', 'required' => true])
        </div>

        <div class="section-title"><i class="pi pi-buildings"></i> @lang('platform::app.setup.primary')</div>

        @include('platform::partials.field', ['name' => 'company_name', 'label' => __('platform::app.setup.company-name'), 'icon' => 'buildings', 'required' => true, 'hint' => __('platform::app.setup.company-hint')])

        <div class="section-title"><i class="pi pi-user-gear"></i> @lang('platform::app.setup.admin')</div>

        <div class="grid grid-2" style="gap:0 16px">
            @include('platform::partials.field', ['name' => 'name', 'label' => __('platform::app.fields.name'), 'icon' => 'user', 'required' => true])
            @include('platform::partials.field', ['name' => 'username', 'label' => __('platform::app.fields.username'), 'icon' => 'identification-card', 'required' => true, 'ltr' => true, 'hint' => __('platform::app.fields.username-hint')])
        </div>

        @include('platform::partials.field', ['name' => 'email', 'label' => __('platform::app.fields.email'), 'type' => 'email', 'icon' => 'envelope-simple', 'required' => true, 'hint' => __('platform::app.setup.email-hint')])

        <div class="grid grid-2" style="gap:0 16px">
            @include('platform::partials.field', ['name' => 'password', 'label' => __('platform::app.fields.password'), 'type' => 'password', 'icon' => 'lock-key', 'required' => true, 'autocomplete' => 'new-password', 'hint' => __('platform::app.auth.password-hint')])
            @include('platform::partials.field', ['name' => 'password_confirmation', 'label' => __('platform::app.auth.confirm-password'), 'type' => 'password', 'icon' => 'lock-key', 'required' => true, 'autocomplete' => 'new-password'])
        </div>

        <button class="btn btn-primary btn-block" type="submit"><i class="pi pi-rocket-launch"></i> <span class="label">@lang('platform::app.setup.submit')</span></button>
    </form>
@endsection
