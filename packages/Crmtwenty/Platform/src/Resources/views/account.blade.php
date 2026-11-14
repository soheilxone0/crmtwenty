@extends('platform::layouts.app')

@section('title', __('platform::app.account.title'))

@section('subtitle', __('platform::app.account.subtitle'))

@section('content')
    <div class="grid grid-main">
        <div class="card">
            <div class="card-head"><h2><i class="pi pi-user"></i> @lang('platform::app.account.profile')</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('platform.account.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-2" style="gap:0 16px">
                        @include('platform::partials.field', ['name' => 'name', 'label' => __('platform::app.fields.name'), 'icon' => 'user', 'required' => true, 'value' => $admin->name])
                        @include('platform::partials.field', ['name' => 'username', 'label' => __('platform::app.fields.username'), 'icon' => 'identification-card', 'required' => true, 'ltr' => true, 'value' => $admin->username])
                    </div>

                    @include('platform::partials.field', ['name' => 'email', 'label' => __('platform::app.fields.email'), 'type' => 'email', 'icon' => 'envelope-simple', 'required' => true, 'value' => $admin->email])

                    <div class="section-title"><i class="pi pi-lock-key"></i> @lang('platform::app.account.change-password')</div>

                    @include('platform::partials.field', ['name' => 'current_password', 'label' => __('platform::app.account.current-password'), 'type' => 'password', 'icon' => 'lock-key', 'autocomplete' => 'current-password'])

                    <div class="grid grid-2" style="gap:0 16px">
                        @include('platform::partials.field', ['name' => 'password', 'label' => __('platform::app.auth.new-password'), 'type' => 'password', 'icon' => 'lock-key', 'autocomplete' => 'new-password', 'hint' => __('platform::app.account.password-optional')])
                        @include('platform::partials.field', ['name' => 'password_confirmation', 'label' => __('platform::app.auth.confirm-password'), 'type' => 'password', 'icon' => 'lock-key', 'autocomplete' => 'new-password'])
                    </div>

                    <button class="btn btn-primary" type="submit"><i class="pi pi-check"></i> @lang('platform::app.actions.save')</button>
                </form>
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head"><h2><i class="pi pi-shield-check"></i> @lang('platform::app.account.admins')</h2></div>
                <div class="card-body" style="padding-top:8px">
                    @foreach ($admins as $item)
                        <div class="kv" style="align-items:center">
                            <div class="company-cell">
                                <div class="avatar" style="width:34px;height:34px;font-size:13px">{{ mb_substr($item->name, 0, 1) }}</div>
                                <div><strong>{{ $item->name }}</strong><span class="mono">{{ $item->email }}</span></div>
                            </div>

                            @if ($item->id !== $admin->id)
                                <form method="POST" action="{{ route('platform.admins.destroy', $item->id) }}" data-confirm="@lang('platform::app.account.delete-admin-confirm')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-soft-danger btn-sm" type="submit"><i class="pi pi-trash"></i></button>
                                </form>
                            @else
                                <span class="badge badge-brand">@lang('platform::app.account.you')</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2><i class="pi pi-user-plus"></i> @lang('platform::app.account.new-admin')</h2></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('platform.admins.store') }}">
                        @csrf

                        @include('platform::partials.field', ['name' => 'name', 'label' => __('platform::app.fields.name'), 'icon' => 'user', 'required' => true, 'value' => ''])
                        @include('platform::partials.field', ['name' => 'username', 'label' => __('platform::app.fields.username'), 'icon' => 'identification-card', 'required' => true, 'ltr' => true, 'value' => ''])
                        @include('platform::partials.field', ['name' => 'email', 'label' => __('platform::app.fields.email'), 'type' => 'email', 'icon' => 'envelope-simple', 'required' => true, 'value' => ''])
                        @include('platform::partials.field', ['name' => 'password', 'label' => __('platform::app.fields.password'), 'type' => 'password', 'icon' => 'lock-key', 'required' => true, 'autocomplete' => 'new-password'])
                        @include('platform::partials.field', ['name' => 'password_confirmation', 'label' => __('platform::app.auth.confirm-password'), 'type' => 'password', 'icon' => 'lock-key', 'required' => true, 'autocomplete' => 'new-password'])

                        <button class="btn btn-ghost btn-block" type="submit"><i class="pi pi-user-plus"></i> @lang('platform::app.account.add-admin')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
