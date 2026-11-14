@extends('platform::layouts.auth')

@section('title', __('platform::app.auth.reset-title'))

@section('content')
    <h1>@lang('platform::app.auth.reset-title')</h1>
    <p class="lead">@lang('platform::app.auth.reset-lead')</p>

    @include('platform::partials.flash')

    <form method="POST" action="{{ route('platform.password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        @include('platform::partials.field', ['name' => 'email', 'label' => __('platform::app.auth.email'), 'type' => 'email', 'icon' => 'envelope-simple', 'required' => true, 'value' => $email])

        @include('platform::partials.field', ['name' => 'password', 'label' => __('platform::app.auth.new-password'), 'type' => 'password', 'icon' => 'lock-key', 'required' => true, 'autocomplete' => 'new-password', 'hint' => __('platform::app.auth.password-hint')])

        @include('platform::partials.field', ['name' => 'password_confirmation', 'label' => __('platform::app.auth.confirm-password'), 'type' => 'password', 'icon' => 'lock-key', 'required' => true, 'autocomplete' => 'new-password'])

        <button class="btn btn-primary btn-block" type="submit"><i class="pi pi-check"></i> @lang('platform::app.auth.save-password')</button>
    </form>
@endsection
