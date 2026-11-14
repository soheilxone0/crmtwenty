@component('admin::emails.layout')
    <div style="margin-bottom: 34px;">
        <p style="font-size: 16px; color: #5E5E5E; line-height: 28px;">
            @lang('persian::app.emails.verify.greeting', ['name' => $userName])
        </p>

        <p style="font-size: 16px; color: #5E5E5E; line-height: 28px;">
            @lang('persian::app.emails.verify.body', ['app' => config('app.name')])
        </p>

        <p style="text-align: center; padding: 20px 0;">
            <a
                href="{{ $url }}"
                style="display: inline-block; padding: 12px 24px; background: #0E90D9; color: #ffffff; text-decoration: none; font-size: 16px; border-radius: 6px;"
            >
                @lang('persian::app.emails.verify.button')
            </a>
        </p>

        <p style="font-size: 14px; color: #8A8A8A; line-height: 24px;">
            @lang('persian::app.emails.verify.expires', ['hours' => $hours])
        </p>

        <p style="font-size: 13px; color: #8A8A8A; line-height: 22px; direction: ltr; text-align: left; word-break: break-all;">
            {{ $url }}
        </p>

        <p style="font-size: 14px; color: #8A8A8A; line-height: 24px;">
            @lang('persian::app.emails.verify.ignore')
        </p>
    </div>
@endcomponent
