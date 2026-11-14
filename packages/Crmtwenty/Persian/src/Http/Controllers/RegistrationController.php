<?php

namespace Crmtwenty\Persian\Http\Controllers;

use Crmtwenty\Persian\Mail\AccountPendingMail;
use Crmtwenty\Persian\Mail\VerifyEmailMail;
use Crmtwenty\Persian\Support\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class RegistrationController extends Controller
{
    /**
     * Sign-up form.
     */
    public function create(): RedirectResponse|View
    {
        abort_unless(Registration::isEnabled(), 404);

        if (auth()->guard('user')->check()) {
            return redirect()->route('admin.dashboard.index');
        }

        return view('persian::auth.register');
    }

    /**
     * Create the account and send the verification e-mail.
     */
    public function store(): RedirectResponse
    {
        abort_unless(Registration::isEnabled(), 404);

        /**
         * Honeypot: real visitors never see or fill this field.
         */
        if (filled(request('website'))) {
            return redirect()->route('admin.session.create')
                ->with('success', trans('persian::app.registration.check-inbox'));
        }

        request()->merge(['email' => mb_strtolower(trim((string) request('email')))]);

        $data = request()->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email:rfc|max:191|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], [
            'name' => trans('persian::app.registration.name'),
            'email' => trans('persian::app.registration.email'),
            'password' => trans('persian::app.registration.password'),
        ]);

        $requiresVerification = Registration::requiresVerification();

        $requiresApproval = Registration::requiresApproval();

        $user = DB::transaction(function () use ($data, $requiresVerification, $requiresApproval) {
            $user = new User;

            $user->forceFill([
                'name' => strip_tags($data['name']),
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role_id' => Registration::defaultRoleId(),
                'view_permission' => 'individual',
                'self_registered' => true,
                'email_verified_at' => $requiresVerification ? null : now(),
                'status' => ($requiresVerification || $requiresApproval) ? 0 : 1,
            ])->save();

            return $user;
        });

        if ($requiresVerification) {
            try {
                Mail::to($user->getAttribute('email'))->send(new VerifyEmailMail($user));
            } catch (Throwable $exception) {
                report($exception);

                return redirect()->route('admin.session.create')
                    ->with('warning', trans('persian::app.registration.mail-failed'));
            }

            return redirect()->route('admin.session.create')
                ->with('success', trans('persian::app.registration.check-inbox'));
        }

        if ($requiresApproval) {
            static::notifyAdministrators($user);

            return redirect()->route('admin.session.create')
                ->with('success', trans('persian::app.registration.awaiting-approval'));
        }

        return redirect()->route('admin.session.create')
            ->with('success', trans('persian::app.registration.created'));
    }

    /**
     * E-mail every active administrator about an account awaiting activation.
     */
    public static function notifyAdministrators(User $user): void
    {
        try {
            $admins = User::query()
                ->where('status', 1)
                ->whereIn('role_id', Role::query()->where('permission_type', 'all')->pluck('id'))
                ->pluck('email');

            foreach ($admins as $email) {
                Mail::to($email)->send(new AccountPendingMail($user));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
