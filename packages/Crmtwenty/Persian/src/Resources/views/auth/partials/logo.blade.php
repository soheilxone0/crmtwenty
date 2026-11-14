@if ($logo = core()->getConfigData('general.general.admin_logo.logo_image'))
    <img
        class="h-10 w-[110px]"
        src="{{ Storage::url($logo) }}"
        alt="{{ config('app.name') }}"
    />
@else
    <img
        class="w-max"
        src="{{ vite()->asset('images/logo.svg') }}"
        alt="{{ config('app.name') }}"
    />
@endif
