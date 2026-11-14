<x-admin::layouts.anonymous>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.users.forget-password.create.page-title')
    </x-slot>

    <div class="ct-auth flex min-h-[100vh] flex-col items-center justify-center gap-8 px-4 py-10">
        <div class="flex flex-col items-center gap-5">
            <!-- Logo -->
            @if ($logo = core()->getConfigData('general.general.admin_logo.logo_image'))
                <img
                    class="h-10 w-[110px]"
                    src="{{ Storage::url($logo) }}"
                    alt="{{ config('app.name') }}"
                />
            @else
                <div class="ct-auth-brand">
                    <img
                        src="{{ vite()->asset('images/logo.svg') }}"
                        alt=""
                    />

                    <strong>{{ config('app.name') }}</strong>
                </div>
            @endif

            <div class="ct-auth-card flex w-full min-w-[300px] max-w-[400px] flex-col bg-white dark:bg-gray-900">
                {!! view_render_event('admin.sessions.forgor_password.form_controls.before') !!}

                <!-- Forget Password Form -->
                <x-admin::form :action="route('admin.forgot_password.store')">
                    <div class="p-4">
                        <p class="text-xl font-bold text-gray-800 dark:text-white">
                            @lang('admin::app.users.forget-password.create.title')
                        </p>
                    </div>

                    <div class="border-y p-4 dark:border-gray-800">
                        <!-- Registered Email -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.users.forget-password.create.email')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="email"
                                class="w-full"
                                id="email"
                                name="email"
                                rules="required|email"
                                :value="old('email')"
                                :label="trans('admin::app.users.forget-password.create.email')"
                                :placeholder="trans('admin::app.users.forget-password.create.email')"
                            />

                            <x-admin::form.control-group.error control-name="email" />
                        </x-admin::form.control-group>
                    </div>

                    <div class="flex items-center justify-between p-4">
                        <!-- Back to Sign In link -->
                        <a
                            class="cursor-pointer text-xs font-semibold leading-6 text-brandColor"
                            href="{{ route('admin.session.create') }}"
                        >
                            @lang('admin::app.users.forget-password.create.sign-in-link')
                        </a>

                        <!-- Form Submit Button -->
                        <button
                            class="primary-button">
                            @lang('admin::app.users.forget-password.create.submit-btn')
                        </button>
                    </div>
                </x-admin::form>

                {!! view_render_event('admin.sessions.forgor_password.form_controls.after') !!}
            </div>
        </div>

        <!-- Footer -->
        <div class="text-sm font-normal text-gray-500 dark:text-gray-400">
            &copy; {{ \Crmtwenty\Persian\Support\Jalali::format(now(), 'Y') }} {{ config('app.name') }}
        </div>
    </div>
</x-admin::layouts.anonymous>
