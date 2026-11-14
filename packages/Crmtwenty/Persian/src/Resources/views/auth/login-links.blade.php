{{-- crmtwenty: links under the login form. --}}
@php($registrationEnabled = \Crmtwenty\Persian\Support\Registration::isEnabled())

@if ($registrationEnabled || session('warning'))
<div class="flex flex-col gap-1 border-t px-4 py-3 text-xs dark:border-gray-800">
    @if ($registrationEnabled)
        <p class="text-gray-600 dark:text-gray-300">
            @lang('persian::app.registration.no-account')

            <a
                class="font-semibold text-brandColor"
                href="{{ route('admin.register.create') }}"
            >
                @lang('persian::app.registration.create-account')
            </a>
        </p>
    @endif

    <a
        class="font-semibold text-gray-500 hover:text-brandColor dark:text-gray-400"
        href="{{ route('admin.verification.create', array_filter(['email' => old('email')])) }}"
    >
        @lang('persian::app.verification.resend-link')
    </a>
</div>
@endif
