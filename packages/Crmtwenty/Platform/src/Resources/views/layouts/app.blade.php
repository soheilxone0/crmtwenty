@extends('platform::layouts.base')

@section('body')
    @php($admin = auth()->guard('platform')->user())

    <div class="shell">
        <aside class="sidebar">
            <a class="brand" href="{{ route('platform.dashboard') }}">
                <img src="{{ asset('platform/mark.svg') }}" alt="">
                <div>
                    <strong>{{ config('app.name') }}</strong>
                    <span>@lang('platform::app.name')</span>
                </div>
            </a>

            <nav class="nav">
                <a href="{{ route('platform.dashboard') }}" class="{{ request()->routeIs('platform.dashboard', 'platform.companies.edit') ? 'active' : '' }}">
                    <i class="pi pi-buildings"></i><span class="label">@lang('platform::app.nav.companies')</span>
                </a>

                <a href="{{ route('platform.companies.create') }}" class="{{ request()->routeIs('platform.companies.create') ? 'active' : '' }}">
                    <i class="pi pi-plus-circle"></i><span class="label">@lang('platform::app.nav.new-company')</span>
                </a>

                <a href="{{ route('platform.account.edit') }}" class="{{ request()->routeIs('platform.account.*') ? 'active' : '' }}">
                    <i class="pi pi-user-gear"></i><span class="label">@lang('platform::app.nav.account')</span>
                </a>

                <a href="{{ route('admin.session.create') }}" target="_blank">
                    <i class="pi pi-arrow-square-out"></i><span class="label">@lang('platform::app.nav.crm-login')</span>
                </a>
            </nav>

            <div class="sidebar-foot">
                <div class="avatar">{{ mb_substr($admin->name, 0, 1) }}</div>

                <div class="who">
                    <strong>{{ $admin->name }}</strong>
                    <span class="ltr">{{ '@'.$admin->username }}</span>
                </div>

                <form method="POST" action="{{ route('platform.session.destroy') }}">
                    @csrf
                    <button class="icon-btn" type="submit" title="@lang('platform::app.nav.logout')"><i class="pi pi-sign-out"></i></button>
                </form>
            </div>
        </aside>

        <main class="main">
            <div class="topbar">
                <div>
                    @hasSection('crumbs')
                        <div class="crumbs">@yield('crumbs')</div>
                    @endif

                    <h1>@yield('title')</h1>

                    @hasSection('subtitle')
                        <p>@yield('subtitle')</p>
                    @endif
                </div>

                <div class="row">@yield('actions')</div>
            </div>

            @include('platform::partials.flash')

            @yield('content')
        </main>
    </div>
@endsection
