@component('admin::emails.layout')
    <div style="margin-bottom: 34px;">
        <p style="font-size: 18px; font-weight: 700; color: #202B3C; line-height: 28px;">
            @lang('persian::app.emails.test.heading') ✅
        </p>

        <p style="font-size: 16px; color: #5E5E5E; line-height: 28px;">
            @lang('persian::app.emails.test.body', ['app' => config('app.name')])
        </p>

        <p style="font-size: 14px; color: #8A8A8A; line-height: 24px;">
            @lang('persian::app.emails.test.sent-at', ['date' => $sentAt])
        </p>
    </div>
@endcomponent
