<?php

namespace Crmtwenty\Persian\Http\Controllers;

use Crmtwenty\Persian\Mail\SmtpTestMail;
use Crmtwenty\Persian\Support\MailSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends a test message with the SMTP values currently typed on the
 * configuration page — before they are saved — so a wrong password or port
 * is caught immediately.
 */
class SmtpTestController extends Controller
{
    public function store(): JsonResponse
    {
        $data = request()->validate([
            'to' => 'required|email',
            'host' => 'nullable|string|max:255',
            'port' => 'nullable|integer|between:1,65535',
            'encryption' => 'nullable|in:auto,tls,ssl,none',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:1024',
            'verify_peer' => 'nullable|boolean',
            'from_address' => 'nullable|email',
            'from_name' => 'nullable|string|max:255',
        ]);

        $settings = array_merge(MailSettings::saved(), array_filter(
            $data,
            fn ($value, $key) => $key !== 'to' && $value !== null,
            ARRAY_FILTER_USE_BOTH
        ));

        if (blank($settings['host'] ?? null) && ! MailSettings::canSend()) {
            return new JsonResponse([
                'message' => trans('persian::app.configuration.smtp.test.no-host'),
            ], 422);
        }

        try {
            $config = MailSettings::toConfig($settings);

            /**
             * A dedicated mailer, so the test never reuses a transport already
             * built from older settings during this request.
             */
            config([
                'mail.mailers.crmtwenty_test' => array_merge(
                    $config['mail.mailers.smtp'] ?? config('mail.mailers.smtp'),
                    ['timeout' => 15]
                ),
            ]);

            $mail = new SmtpTestMail(
                $config['mail.from.address'] ?? config('mail.from.address'),
                $config['mail.from.name'] ?? config('mail.from.name')
            );

            Mail::mailer(blank($settings['host'] ?? null) ? null : 'crmtwenty_test')
                ->to($data['to'])
                ->send($mail);
        } catch (Throwable $exception) {
            report($exception);

            return new JsonResponse([
                'message' => trans('persian::app.configuration.smtp.test.failed', [
                    'error' => Str::limit($exception->getMessage(), 400),
                ]),
            ], 422);
        }

        return new JsonResponse([
            'message' => trans('persian::app.configuration.smtp.test.success', ['email' => $data['to']]),
        ]);
    }
}
