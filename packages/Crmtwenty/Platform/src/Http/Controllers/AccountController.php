<?php

namespace Crmtwenty\Platform\Http\Controllers;

use Crmtwenty\Platform\Models\PlatformAdmin;
use Crmtwenty\Platform\Support\Logins;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Profile of the signed-in platform administrator, and the list of administrators.
 */
class AccountController extends Controller
{
    public function edit(): View
    {
        return view('platform::account', [
            'admin' => auth()->guard('platform')->user(),
            'admins' => PlatformAdmin::query()->orderBy('id')->get(),
        ]);
    }

    public function update(): RedirectResponse
    {
        /** @var PlatformAdmin $admin */
        $admin = auth()->guard('platform')->user();

        $data = $this->validate(request(), [
            'name' => 'required|string|max:120',
            'username' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('platform.platform_admins', 'username')->ignore($admin->id)],
            'email' => ['required', 'email', 'max:190', Rule::unique('platform.platform_admins', 'email')->ignore($admin->id)],
            'current_password' => 'required_with:password|nullable|string',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if (filled($data['password'] ?? null) && ! Hash::check((string) $data['current_password'], $admin->password)) {
            return back()->withErrors(['current_password' => trans('platform::app.account.wrong-password')]);
        }

        $admin->fill([
            'name' => $data['name'],
            'username' => Logins::normalize($data['username']),
            'email' => Logins::normalize($data['email']),
        ]);

        if (filled($data['password'] ?? null)) {
            $admin->password = Hash::make($data['password']);
        }

        $admin->save();

        return back()->with('success', trans('platform::app.account.saved'));
    }

    public function storeAdmin(): RedirectResponse
    {
        $data = $this->validate(request(), [
            'name' => 'required|string|max:120',
            'username' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('platform.platform_admins', 'username')],
            'email' => ['required', 'email', 'max:190', Rule::unique('platform.platform_admins', 'email')],
            'password' => 'required|string|min:8|confirmed',
        ]);

        PlatformAdmin::query()->create([
            'name' => $data['name'],
            'username' => Logins::normalize($data['username']),
            'email' => Logins::normalize($data['email']),
            'password' => Hash::make($data['password']),
            'status' => true,
        ]);

        return back()->with('success', trans('platform::app.account.admin-created'));
    }

    public function destroyAdmin(int $id): RedirectResponse
    {
        if ($id === (int) auth()->guard('platform')->id()) {
            return back()->with('error', trans('platform::app.account.cannot-delete-self'));
        }

        PlatformAdmin::query()->whereKey($id)->delete();

        return back()->with('success', trans('platform::app.account.admin-deleted'));
    }
}
