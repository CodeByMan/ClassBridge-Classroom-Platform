<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo"><x-application-logo /></x-slot>

        <h1 class="text-2xl font-medium text-center">Confirm your password</h1>
        <p class="mt-2 mb-7 text-center text-gray-600">
            This is a secure area. Confirm your password to continue.
        </p>

        <form method="POST" action="{{ route('password.confirm') }}" class="grid gap-5">
            @csrf

            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-password-input
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="flex justify-end">
                <x-primary-button>Confirm</x-primary-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
