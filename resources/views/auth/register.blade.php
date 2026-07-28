<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo"><x-application-logo /></x-slot>

        <h1 class="text-2xl font-medium text-center">Create your account</h1>
        <p class="mt-2 mb-7 text-center text-gray-600">Start organizing your classroom work</p>

        <form method="POST" action="{{ route('register') }}" class="grid gap-5">
            @csrf

            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input
                    id="name"
                    type="text"
                    name="name"
                    :value="old('name')"
                    required
                    autofocus
                    autocomplete="name"
                />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input
                    id="email"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
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
                    autocomplete="new-password"
                />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm password')" />
                <x-password-input
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                />
            </div>

            <div class="flex items-center justify-between gap-3">
                <a class="text-sm font-medium text-blue-700 hover:underline" href="{{ route('login') }}">
                    Already registered?
                </a>
                <x-primary-button>Register</x-primary-button>
            </div>

            @if (config('services.google.enabled'))
                <a href="{{ route('auth.google') }}" class="gc-button gc-button--outline w-full">
                    <i class="fa-brands fa-google"></i>Register with Google
                </a>
            @else
                <button type="button" class="gc-button gc-button--disabled w-full" disabled aria-disabled="true">
                    <i class="fa-brands fa-google"></i>Register with Google
                    <span class="gc-coming-soon">Coming soon</span>
                </button>
            @endif
        </form>
    </x-auth-card>
</x-guest-layout>
