<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles login & logout.
 *
 * Implements the "rehash-on-first-login" strategy:
 *   1. If `backend_user.password_hash` is set → verify with Hash::check().
 *   2. Otherwise → compare plain-text vs legacy `hr_employee.employee_password`,
 *      and on success bcrypt-hash + persist into `password_hash`.
 *
 * Over time every active user's password gets migrated to bcrypt without
 * requiring a manual reset.
 */
class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status'    => session('status'),
            'canReset'  => false, // wired in a later sprint
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $employee = Employee::active()
            ->where('employee_username', $credentials['username'])
            ->with('backendUser')
            ->first();

        $user = $employee?->backendUser;

        if (! $employee || ! $user || (int) $user->status !== 1) {
            $this->fail();
        }

        $passwordOk = $user->password_hash
            ? Hash::check($credentials['password'], $user->password_hash)
            : hash_equals((string) $employee->employee_password, $credentials['password']);

        if (! $passwordOk) {
            $this->fail();
        }

        // Rehash-on-success: migrate plain-text → bcrypt transparently.
        if (! $user->password_hash) {
            $user->password_hash = Hash::make($credentials['password']);
            $user->save();
        }

        // Log the login event onto the legacy columns (preserves audit parity).
        $user->forceFill([
            'last_login' => now(),
            'last_ip'    => $request->ip(),
            'token'      => hash('sha256', now() . $user->id),
        ])->save();

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function fail(): never
    {
        throw ValidationException::withMessages([
            'username' => __('auth.failed'),
        ]);
    }
}
