<x-admin::layouts.anonymous>
    <x-slot:title>
        @lang('persian::app.registration.title')
    </x-slot>

    <div class="flex min-h-[100vh] flex-col items-center justify-center gap-10 py-10">
        <div class="flex flex-col items-center gap-5">
            @include('persian::auth.partials.logo')

            <div class="box-shadow flex min-w-[300px] flex-col rounded-md bg-white dark:bg-gray-900">
                <x-admin::form :action="route('admin.register.store')">
                    <p class="p-4 text-xl font-bold text-gray-800 dark:text-white">
                        @lang('persian::app.registration.title')
                    </p>

                    <div class="border-y p-4 dark:border-gray-800">
                        <!-- Name -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('persian::app.registration.name')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                class="w-[300px] max-w-full"
                                id="name"
                                name="name"
                                :value="old('name')"
                                rules="required|max:100"
                                :label="trans('persian::app.registration.name')"
                                :placeholder="trans('persian::app.registration.name')"
                                autocomplete="name"
                            />

                            <x-admin::form.control-group.error control-name="name" />
                        </x-admin::form.control-group>

                        <!-- Email -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('persian::app.registration.email')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="email"
                                class="w-[300px] max-w-full"
                                id="email"
                                name="email"
                                dir="ltr"
                                :value="old('email')"
                                rules="required|email"
                                :label="trans('persian::app.registration.email')"
                                placeholder="name@example.com"
                                autocomplete="email"
                            />

                            <x-admin::form.control-group.error control-name="email" />
                        </x-admin::form.control-group>

                        <!-- Password -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('persian::app.registration.password')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                class="w-[300px] max-w-full"
                                id="password"
                                name="password"
                                rules="required|min:8"
                                ref="password"
                                :label="trans('persian::app.registration.password')"
                                :placeholder="trans('persian::app.registration.password-hint')"
                                autocomplete="new-password"
                            />

                            <x-admin::form.control-group.error control-name="password" />
                        </x-admin::form.control-group>

                        <!-- Confirm password -->
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label class="required">
                                @lang('persian::app.registration.password-confirmation')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                class="w-[300px] max-w-full"
                                id="password_confirmation"
                                name="password_confirmation"
                                rules="required|confirmed:@password"
                                :label="trans('persian::app.registration.password-confirmation')"
                                :placeholder="trans('persian::app.registration.password-confirmation')"
                                autocomplete="new-password"
                            />

                            <x-admin::form.control-group.error control-name="password_confirmation" />
                        </x-admin::form.control-group>

                        <!-- Honeypot (hidden from people, filled by bots) -->
                        <div
                            class="absolute h-0 w-0 overflow-hidden opacity-0"
                            aria-hidden="true"
                        >
                            <label for="website">Website</label>

                            <input
                                type="text"
                                id="website"
                                name="website"
                                tabindex="-1"
                                autocomplete="off"
                            >
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-4 p-4">
                        <a
                            class="cursor-pointer text-xs font-semibold leading-6 text-brandColor"
                            href="{{ route('admin.session.create') }}"
                        >
                            @lang('persian::app.registration.have-account')
                        </a>

                        <button class="primary-button">
                            @lang('persian::app.registration.submit')
                        </button>
                    </div>
                </x-admin::form>
            </div>
        </div>
    </div>
</x-admin::layouts.anonymous>
