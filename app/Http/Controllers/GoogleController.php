<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use RuntimeException;
use Throwable;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $providerId = trim((string) $googleUser->getId());
            $email = Str::lower(trim((string) $googleUser->getEmail()));
            $name = trim((string) ($googleUser->getName() ?: $googleUser->getNickname() ?: 'Google user'));
            $rawUser = method_exists($googleUser, 'getRaw') ? $googleUser->getRaw() : [];
            $emailVerified = (bool) ($rawUser['email_verified'] ?? $rawUser['verified_email'] ?? false);

            if ($providerId === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $emailVerified) {
                throw new RuntimeException('Google did not return a verified account.');
            }

            $user = DB::transaction(function () use ($providerId, $email, $name): User {
                $user = User::where('google_id', $providerId)->first();

                if ($user) {
                    if (! $user->email_verified_at) {
                        $user->forceFill(['email_verified_at' => now()])->save();
                    }

                    return $user;
                }

                $user = User::where('email', $email)->first();

                if ($user && $user->google_id && $user->google_id !== $providerId) {
                    throw new RuntimeException('This email is already linked to another Google account.');
                }

                if (! $user) {
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Str::random(64),
                    ]);
                }

                $user->forceFill([
                    'google_id' => $providerId,
                    'email_verified_at' => $user->email_verified_at ?: now(),
                ])->save();

                return $user;
            });

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('home');
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')
                ->with('error', 'Google sign-in could not be completed. Please try again.');
        }
    }
}
