@extends('platform::layouts.auth')

@section('title', __('platform::app.auth.login-title'))

@section('content')
    <h1>@lang('platform::app.auth.login-title')</h1>
    <p class="lead">@lang('platform::app.auth.login-lead')</p>

    @include('platform::partials.flash')

    <form method="POST" action="{{ route('platform.session.store') }}">
        @csrf

        @include('platform::partials.field', ['name' => 'login', 'label' => __('platform::app.auth.login'), 'icon' => 'user', 'required' => true, 'ltr' => true, 'autocomplete' => 'username'])

        @include('platform::partials.field', ['name' => 'password', 'label' => __('platform::app.auth.password'), 'type' => 'password', 'icon' => 'lock-key', 'required' => true, 'autocomplete' => 'current-password'])

        <div class="row" style="margin: -4px 0 20px">
            <label class="check"><input type="checkbox" name="remember" value="1"> @lang('platform::app.auth.remember')</label>
            <span class="spacer"></span>
            <a href="{{ route('platform.password.request') }}">@lang('platform::app.auth.forgot')</a>
        </div>

        <button class="btn btn-primary btn-block" type="submit"><i class="pi pi-sign-in"></i> @lang('platform::app.auth.login-button')</button>
    </form>

    <p class="auth-foot">@lang('platform::app.auth.crm-user') <a href="{{ route('admin.session.create') }}">@lang('platform::app.auth.crm-login')</a></p>
@endsection
