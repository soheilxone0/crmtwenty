@extends('platform::layouts.auth')

@section('title', __('platform::app.auth.forgot-title'))

@section('content')
    <h1>@lang('platform::app.auth.forgot-title')</h1>
    <p class="lead">@lang('platform::app.auth.forgot-lead')</p>

    @include('platform::partials.flash')

    <form method="POST" action="{{ route('platform.password.email') }}" data-busy="@lang('platform::app.auth.sending')">
        @csrf

        @include('platform::partials.field', ['name' => 'email', 'label' => __('platform::app.auth.email'), 'type' => 'email', 'icon' => 'envelope-simple', 'required' => true, 'autocomplete' => 'email'])

        <button class="btn btn-primary btn-block" type="submit"><i class="pi pi-paper-plane-tilt"></i> <span class="label">@lang('platform::app.auth.send-link')</span></button>
    </form>

    <p class="auth-foot"><a href="{{ route('platform.session.create') }}"><i class="pi pi-arrow-right"></i> @lang('platform::app.auth.back-to-login')</a></p>
@endsection
