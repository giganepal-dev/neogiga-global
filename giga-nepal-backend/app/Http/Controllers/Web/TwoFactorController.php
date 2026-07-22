<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $tfa) {}

    /** Show 2FA setup page with QR code. */
    public function setup()
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $secret = $this->tfa->generateSecret();
        session(['2fa.pending_secret' => $secret]);

        $qrUri = $this->tfa->qrCodeUri($user, $secret);

        return view('frontend.auth.two-factor.setup', [
            'secret' => $secret,
            'qrUri' => $qrUri,
        ]);
    }

    /** Verify the setup code and enable 2FA. */
    public function enable(Request $request)
    {
        $user = Auth::user();
        $secret = session('2fa.pending_secret');

        if (! $user || ! $secret) {
            return redirect()->route('login');
        }

        $request->validate(['code' => 'required|string|size:6']);

        if (! $this->tfa->verify($secret, $request->code)) {
            return back()->withErrors(['code' => 'Invalid code. Try again.']);
        }

        $this->tfa->enable($user, $secret);
        session()->forget('2fa.pending_secret');

        $recoveryCodes = $user->two_factor_recovery_codes;

        return view('frontend.auth.two-factor.recovery-codes', [
            'codes' => $recoveryCodes,
        ]);
    }

    /** Show the 2FA challenge page (after password login). */
    public function challenge()
    {
        if (! session('2fa.needs_challenge')) {
            return redirect()->intended('/dashboard');
        }

        return view('frontend.auth.two-factor.challenge');
    }

    /** Verify the 2FA code during login. */
    public function verify(Request $request)
    {
        $user = Auth::user();

        if (! $user || ! $user->two_factor_enabled) {
            return redirect()->intended('/dashboard');
        }

        $request->validate(['code' => 'required|string']);

        $code = str_replace(' ', '', $request->code);

        // Try recovery code first
        if (strlen($code) === 10 && ctype_xdigit($code)) {
            if ($this->tfa->verifyRecoveryCode($user, $code)) {
                $this->tfa->markConfirmed();
                session()->forget('2fa.needs_challenge');

                return redirect()->intended('/dashboard')->with('success', 'Logged in with recovery code. Generate new ones.');
            }
            return back()->withErrors(['code' => 'Invalid recovery code.']);
        }

        // Try TOTP
        if ($this->tfa->verify($user->two_factor_secret, $code)) {
            $this->tfa->markConfirmed();
            session()->forget('2fa.needs_challenge');

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors(['code' => 'Invalid code. Check your authenticator app.']);
    }

    /** Show 2FA management page (disable, new recovery codes). */
    public function manage()
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        return view('frontend.auth.two-factor.manage', [
            'enabled' => $user->two_factor_enabled,
            'confirmedAt' => $user->two_factor_confirmed_at,
        ]);
    }

    /** Generate new recovery codes. */
    public function newRecoveryCodes()
    {
        $user = Auth::user();
        if (! $user || ! $user->two_factor_enabled) {
            return back()->withErrors(['2fa' => '2FA is not enabled.']);
        }

        $codes = $this->tfa->generateRecoveryCodes()->toArray();
        $user->update(['two_factor_recovery_codes' => $codes]);

        return view('frontend.auth.two-factor.recovery-codes', ['codes' => $codes]);
    }

    /** Disable 2FA. */
    public function disable(Request $request)
    {
        $user = Auth::user();
        $request->validate(['code' => 'required|string|size:6']);

        if (! $this->tfa->verify($user->two_factor_secret, $request->code)) {
            return back()->withErrors(['code' => 'Invalid code. 2FA not disabled.']);
        }

        $this->tfa->disable($user);
        session()->forget('2fa.confirmed');

        return redirect()->route('account.security')->with('success', 'Two-factor authentication disabled.');
    }
}
