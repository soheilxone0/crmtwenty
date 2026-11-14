@extends('platform::layouts.base')

@section('body')
    <div class="auth">
        <aside class="auth-side">
            <div class="brand">
                <img src="{{ asset('platform/mark.svg') }}" alt="">
                <div>
                    <strong>{{ config('app.name') }}</strong>
                    <span>@lang('platform::app.name')</span>
                </div>
            </div>

            <div>
                <h2>@lang('platform::app.auth.side-title')</h2>
                <p>@lang('platform::app.auth.side-text')</p>

                <ul class="auth-points">
                    <li><i class="pi pi-buildings"></i> @lang('platform::app.auth.point-1')</li>
                    <li><i class="pi pi-shield-check"></i> @lang('platform::app.auth.point-2')</li>
                    <li><i class="pi pi-users-three"></i> @lang('platform::app.auth.point-3')</li>
                </ul>
            </div>

            <small style="color:#a5b4fc">&copy; {{ \Crmtwenty\Persian\Support\Jalali::format(now(), 'Y') }} {{ config('app.name') }}</small>
        </aside>

        <main class="auth-main">
            <div class="auth-card @yield('card-class')">
                <img src="{{ asset('platform/mark.svg') }}" alt="" width="52" height="52" style="border-radius:14px">

                @yield('content')
            </div>
        </main>
    </div>
@endsection
