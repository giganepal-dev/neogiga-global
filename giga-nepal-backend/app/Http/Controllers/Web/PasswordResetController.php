<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Marketing\AccountCommunicationService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Server-rendered password reset pages. The reset EMAIL links to the named
 * `password.reset` route, so these pages are required for the API
 * forgot-password flow to function (the notification URL-generation throws
 * without it). Same Password broker as Api\Auth\ResetPasswordController.
 */
class PasswordResetController extends Controller
{
    public function showLinkRequest(): View
    {
        return view('frontend.auth.forgot-password');
    }

    public function sendLink(Request $request, AccountCommunicationService $communications): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $email = mb_strtolower($request->string('email')->toString());
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($user) {
            $token = Password::broker()->createToken($user);
            $url = route('password.reset', ['token' => $token, 'email' => $user->email]);
            $communications->passwordReset($user, $token, $url);
        }

        // Same response whether or not the account exists (no enumeration).
        return back()->with('status', 'If an account exists for that email, a reset link has been sent.');
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('frontend.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request, AccountCommunicationService $communications): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) use ($communications) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                $communications->passwordChanged($user);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('password.request')->with('status', 'Your password has been reset. You can now sign in with your new password.')
            : back()->withErrors(['email' => __($status)])->withInput($request->only('email'));
    }
}
