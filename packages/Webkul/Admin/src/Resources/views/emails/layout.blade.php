<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html
    xmlns="http://www.w3.org/1999/xhtml"
    lang="{{ app()->getLocale() }}"
    dir="{{ in_array(app()->getLocale(), ['fa', 'ar']) ? 'rtl' : 'ltr' }}"
>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

    </head>

    <body
        dir="{{ in_array(app()->getLocale(), ['fa', 'ar']) ? 'rtl' : 'ltr' }}"
        style="font-family: Vazirmatn, Tahoma, 'Segoe UI', Arial, sans-serif; text-align: {{ in_array(app()->getLocale(), ['fa', 'ar']) ? 'right' : 'left' }};"
    >
        <div style="max-width: 640px; margin-left: auto; margin-right: auto;">
            <div style="padding: 30px;">
                <!-- Email Header -->
                <div style="margin-bottom: 45px;">
                    <a href="{{ config('app.url') }}" style="text-decoration: none;">
                        <span style="display: inline-block; padding: 10px 18px; border-radius: 12px; background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #ffffff; font-size: 20px; font-weight: 700;">
                            {{ config('app.name') }}
                        </span>
                    </a>
                </div>

                <!-- Email Content -->
                {{ $slot }}

                <!-- Email Footer -->
                <p style="font-size: 16px;color: #202B3C;line-height: 24px;">
                    @lang('admin::app.emails.common.cheers', ['app_name' => config('app.name')])
                </p>
            </div>
        </div>
    </body>
</html>
