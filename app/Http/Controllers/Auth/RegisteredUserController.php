<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(fn (): User => User::create($validated));

        $mailDeliveryFailed = false;

        try {
            event(new Registered($user));
        } catch (Throwable $exception) {
            $mailDeliveryFailed = true;
            report($exception);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $redirect = redirect(RouteServiceProvider::HOME);

        if ($mailDeliveryFailed) {
            $redirect->with(
                'warning',
                'Your account was created, but the verification email could not be sent. Use the resend option on the verification page.'
            );
        }

        return $redirect;
    }
}
