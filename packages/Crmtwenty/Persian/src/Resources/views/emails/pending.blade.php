@component('admin::emails.layout')
    <div style="margin-bottom: 34px;">
        <p style="font-size: 16px; color: #5E5E5E; line-height: 28px;">
            @lang('persian::app.emails.pending.body', ['name' => $userName, 'email' => $userEmail])
        </p>

        <p style="text-align: center; padding: 20px 0;">
            <a
                href="{{ $url }}"
                style="display: inline-block; padding: 12px 24px; background: #0E90D9; color: #ffffff; text-decoration: none; font-size: 16px; border-radius: 6px;"
            >
                @lang('persian::app.emails.pending.button')
            </a>
        </p>
    </div>
@endcomponent
