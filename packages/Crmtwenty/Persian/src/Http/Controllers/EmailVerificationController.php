<?php

namespace Crmtwenty\Persian\Http\Controllers;

use Crmtwenty\Persian\Mail\VerifyEmailMail;
use Crmtwenty\Persian\Support\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;
use Webkul\User\Models\User;

class EmailVerificationController extends Controller
{
    /**
     * "Resend verification e-mail" form.
     */
    public function create(): View
    {
        return view('persian::auth.resend-verification');
    }

    /**
     * Send a new link. The answer is the same whether or not the address
     * exists, so the form cannot be used to discover accounts.
     */
    public function store(): RedirectResponse
    {
        $data = request()->validate(['email' => 'required|email']);

        $user = User::query()
            ->where('email', mb_strtolower(trim($data['email'])))
            ->where('self_registered', true)
            ->whereNull('email_verified_at')
            ->first();

        if ($user) {
            try {
                Mail::to($user->getAttribute('email'))->send(new VerifyEmailMail($user));
            } catch (Throwable $exception) {
                report($exception);

                return back()->with('warning', trans('persian::app.registration.mail-failed'));
            }
        }

        return redirect()->route('admin.session.create')
            ->with('success', trans('persian::app.verification.resent'));
    }

    /**
     * Confirm the address from the signed link in the e-mail.
     */
    public function verify(int $id, string $hash): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        abort_unless(hash_equals(sha1((string) $user->getAttribute('email')), $hash), 403);

        if ($user->getAttribute('email_verified_at')) {
            return redirect()->route('admin.session.create')
                ->with('success', trans('persian::app.verification.already-verified'));
        }

        $selfRegistered = (bool) $user->getAttribute('self_registered');

        $requiresApproval = $selfRegistered && Registration::requiresApproval();

        $user->forceFill([
            'email_verified_at' => now(),
            'status' => $selfRegistered && ! $requiresApproval ? 1 : $user->getAttribute('status'),
        ])->save();

        if ($requiresApproval) {
            RegistrationController::notifyAdministrators($user);

            return redirect()->route('admin.session.create')
                ->with('success', trans('persian::app.verification.verified-awaiting-approval'));
        }

        return redirect()->route('admin.session.create')
            ->with('success', trans('persian::app.verification.verified'));
    }
}
