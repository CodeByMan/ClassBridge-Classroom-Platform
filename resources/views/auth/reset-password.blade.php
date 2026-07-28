<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo"><x-application-logo /></x-slot>

        <h1 class="text-2xl font-medium text-center">Choose a new password</h1>
        <p class="mt-2 mb-7 text-center text-gray-600">Create a strong password for your account.</p>

        <form method="POST" action="{{ route('password.update') }}" class="grid gap-5">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input
                    id="email"
                    type="email"
                    name="email"
                    :value="old('email', $request->email)"
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

            <div class="flex justify-end">
                <x-primary-button>Reset password</x-primary-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
