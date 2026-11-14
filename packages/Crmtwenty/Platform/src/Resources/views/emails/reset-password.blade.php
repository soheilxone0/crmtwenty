@component('admin::emails.layout')
    <div style="margin-bottom: 34px;">
        <p style="font-size: 16px; color: #5E5E5E; line-height: 28px;">
            @lang('platform::app.emails.reset.greeting', ['name' => $name])
        </p>

        <p style="font-size: 16px; color: #5E5E5E; line-height: 28px;">
            @lang('platform::app.emails.reset.body')
        </p>

        <p style="text-align: center; padding: 20px 0;">
            <a href="{{ $url }}" style="display: inline-block; padding: 12px 28px; background: #4F46E5; color: #ffffff; text-decoration: none; font-size: 16px; border-radius: 10px;">
                @lang('platform::app.emails.reset.button')
            </a>
        </p>

        <p style="font-size: 14px; color: #8A8A8A; line-height: 24px;">
            @lang('platform::app.emails.reset.expires', ['minutes' => $minutes])
        </p>

        <p style="font-size: 13px; color: #8A8A8A; line-height: 22px; direction: ltr; text-align: left; word-break: break-all;">
            {{ $url }}
        </p>
    </div>
@endcomponent
