<?php

use Aura\Base\Http\Controllers\Auth\AuthenticatedSessionController;
use Aura\Base\Http\Controllers\Auth\ConfirmablePasswordController;
use Aura\Base\Http\Controllers\Auth\EmailVerificationNotificationController;
use Aura\Base\Http\Controllers\Auth\EmailVerificationPromptController;
use Aura\Base\Http\Controllers\Auth\InvitationRegisterUserController;
use Aura\Base\Http\Controllers\Auth\NewPasswordController;
use Aura\Base\Http\Controllers\Auth\PasswordController;
use Aura\Base\Http\Controllers\Auth\PasswordResetLinkController;
use Aura\Base\Http\Controllers\Auth\RegisteredUserController;
use Aura\Base\Http\Controllers\Auth\TeamInvitationController;
use Aura\Base\Http\Controllers\Auth\VerifyEmailController;
use Aura\Base\Http\Controllers\SwitchTeamController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController;
use Laravel\Fortify\RoutePath;

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('aura.logout');

Route::middleware('guest')->name('aura.')->group(function () {
    Route::get('/login-as/{id}', function ($id) {
        if (! app()->environment('local')) {
            abort(404);
        }

        // Only allow .test domains
        if (! Str::endsWith(request()->getHost(), '.test')) {
            abort(404);
        }

        // Bypass TeamScope: guests have no current team, and fail-closed
        // scoping would hide every user (404) before Auth::login can run.
        $user = app(config('aura.resources.user'))
            ->newQueryWithoutScopes()
            ->findOrFail($id);

        Auth::login($user);

        return redirect()->route('aura.dashboard');
    })->name('login-as');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    if (config('aura.auth.registration')) {
        Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
        Route::post('register', [RegisteredUserController::class, 'store'])->name('register.post');

    }

    if (config('aura.teams')) {
        Route::get('register/{team}/{teamInvitation}', [InvitationRegisterUserController::class, 'create'])->name('invitation.register')->middleware(['signed']);
        Route::post('register/{team}/{teamInvitation}', [InvitationRegisterUserController::class, 'store'])->middleware(['signed'])->name('invitation.register.post');
    }

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->name('aura.')->group(function () {
    $limiter = config('fortify.limiters.login');
    $twoFactorLimiter = config('fortify.limiters.two-factor');
    $verificationLimiter = config('fortify.limiters.verification', '6,1');

    Route::get('email/verify', [EmailVerificationPromptController::class, '__invoke'])->name('verification.notice');

    Route::get('email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // If has Team Features
    Route::put('/current-team', [SwitchTeamController::class, 'update'])->name('current-team.update');

    Route::get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])->middleware(['signed'])->name('team-invitations.accept');

    if (config('aura.teams')) {
        Route::delete('/teams/{team}/team-invitations/{invitation}', [TeamInvitationController::class, 'destroy'])->middleware('can:invite-users,team')->name('team-invitations.destroy');
        Route::post('/teams/{team}/team-invitations/{invitation}/resend', [TeamInvitationController::class, 'resend'])->middleware('can:invite-users,team')->name('team-invitations.resend');
    }

    if (config('aura.auth.2fa')) {
        $twoFactorMiddleware = ['auth:web', 'password.confirm'];

        Route::get(RoutePath::for('two-factor.login', '/two-factor-challenge'), [TwoFactorAuthenticatedSessionController::class, 'create'])
            ->middleware(['guest:'.config('fortify.guard')])
            ->name('two-factor.login');

        Route::post(RoutePath::for('two-factor.login', '/two-factor-challenge'), [TwoFactorAuthenticatedSessionController::class, 'store'])
            ->middleware(array_filter([
                'guest:'.config('fortify.guard'),
                $twoFactorLimiter ? 'throttle:'.$twoFactorLimiter : null,
            ]));

        Route::post(RoutePath::for('two-factor.enable', '/user/two-factor-authentication'), [TwoFactorAuthenticationController::class, 'store'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.enable');

        Route::post(RoutePath::for('two-factor.confirm', '/user/confirmed-two-factor-authentication'), [ConfirmedTwoFactorAuthenticationController::class, 'store'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.confirm');

        Route::delete(RoutePath::for('two-factor.disable', '/user/two-factor-authentication'), [TwoFactorAuthenticationController::class, 'destroy'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.disable');

        Route::get(RoutePath::for('two-factor.qr-code', '/user/two-factor-qr-code'), [TwoFactorQrCodeController::class, 'show'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.qr-code');

        Route::get(RoutePath::for('two-factor.secret-key', '/user/two-factor-secret-key'), [TwoFactorSecretKeyController::class, 'show'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.secret-key');

        Route::get(RoutePath::for('two-factor.recovery-codes', '/user/two-factor-recovery-codes'), [RecoveryCodeController::class, 'index'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.recovery-codes');

        Route::post(RoutePath::for('two-factor.recovery-codes', '/user/two-factor-recovery-codes'), [RecoveryCodeController::class, 'store'])
            ->middleware($twoFactorMiddleware);
    }
});

Route::get('/aura-login', function () {
    return redirect()->route('aura.login');
})->name('login');
