@component('admin::emails.layout')
    <div style="margin-bottom: 34px;">
        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            @lang('admin::app.emails.common.user.forget-password.dear', ['username' => $user_name]), 👋
        </p>

        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            @lang('admin::app.emails.common.user.forget-password.info')
        </p>

        <p style="text-align: center;padding: 20px 0;">
            <a 
                href="{{ route('admin.reset_password.create', array_filter(['token' => $token, 'email' => $email ?? null])) }}"
                style="display: inline-block; padding: 12px 28px; background: #4F46E5; color: #ffffff; text-decoration: none; font-size: 16px; border-radius: 10px;"
            >
                @lang('admin::app.emails.common.user.forget-password.reset-password')
            </a>
        </p>

        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            @lang('admin::app.emails.common.user.forget-password.final-summary')
        </p>

        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            @lang('admin::app.emails.common.user.forget-password.thanks')
        </p>
    </div>
@endcomponent
