<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo"><x-application-logo /></x-slot>

        <h1 class="text-2xl font-medium text-center">Sign in</h1>
        <p class="mt-2 mb-7 text-center text-gray-600">Use your ClassBridge account</p>

        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="grid gap-5">
            @csrf

            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input
                    id="email"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
                    autofocus
                    autocomplete="username"
                />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-password-input
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <label class="flex items-center gap-2">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600"
                    name="remember"
                >
                <span class="text-sm text-gray-600">Remember me</span>
            </label>

            <div class="flex items-center justify-between gap-3">
                @if (Route::has('password.request'))
                    <a
                        class="text-sm font-medium text-blue-700 hover:underline"
                        href="{{ route('password.request') }}"
                    >
                        Forgot password?
                    </a>
                @endif

                <x-primary-button>Sign in</x-primary-button>
            </div>

            <p class="text-center text-sm text-gray-600">
                New here?
                <a href="{{ route('register') }}" class="font-medium text-blue-700 hover:underline">
                    Create an account
                </a>
            </p>

            @if (config('services.google.enabled'))
                <a href="{{ route('auth.google') }}" class="gc-button gc-button--outline w-full">
                    <i class="fa-brands fa-google"></i>Continue with Google
                </a>
            @else
                <button type="button" class="gc-button gc-button--disabled w-full" disabled aria-disabled="true">
                    <i class="fa-brands fa-google"></i>Continue with Google
                    <span class="gc-coming-soon">Coming soon</span>
                </button>
            @endif
        </form>
    </x-auth-card>
</x-guest-layout>
