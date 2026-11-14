<x-admin::layouts.anonymous>
    <x-slot:title>
        @lang('persian::app.verification.resend-title')
    </x-slot>

    <div class="flex h-[100vh] flex-col items-center justify-center gap-10">
        <div class="flex flex-col items-center gap-5">
            @include('persian::auth.partials.logo')

            <div class="box-shadow flex min-w-[300px] max-w-[360px] flex-col rounded-md bg-white dark:bg-gray-900">
                <x-admin::form :action="route('admin.verification.store')">
                    <p class="p-4 text-xl font-bold text-gray-800 dark:text-white">
                        @lang('persian::app.verification.resend-title')
                    </p>

                    <div class="border-y p-4 dark:border-gray-800">
                        <p class="mb-4 text-sm leading-6 text-gray-600 dark:text-gray-300">
                            @lang('persian::app.verification.resend-info')
                        </p>

                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label class="required">
                                @lang('persian::app.registration.email')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="email"
                                class="w-[300px] max-w-full"
                                id="email"
                                name="email"
                                dir="ltr"
                                :value="old('email', request('email'))"
                                rules="required|email"
                                :label="trans('persian::app.registration.email')"
                                placeholder="name@example.com"
                            />

                            <x-admin::form.control-group.error control-name="email" />
                        </x-admin::form.control-group>
                    </div>

                    <div class="flex items-center justify-between gap-4 p-4">
                        <a
                            class="cursor-pointer text-xs font-semibold leading-6 text-brandColor"
                            href="{{ route('admin.session.create') }}"
                        >
                            @lang('persian::app.registration.back-to-login')
                        </a>

                        <button class="primary-button">
                            @lang('persian::app.verification.resend-submit')
                        </button>
                    </div>
                </x-admin::form>
            </div>
        </div>
    </div>
</x-admin::layouts.anonymous>
