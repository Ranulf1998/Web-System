<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Create Account</h1>
        <p class="mt-1 text-sm text-slate-600">Register a user account for your BrewCloud tenant.</p>
    </div>

    <form method="POST" action="{{ route('register', ['subdomain' => request()->route('subdomain')]) }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" class="text-slate-700" />
            <x-text-input id="name" class="block mt-1 w-full border-slate-300 focus:border-[color:var(--brand-primary)] focus:ring-[color:var(--brand-primary)]" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" class="text-slate-700" />
            <x-text-input id="email" class="block mt-1 w-full border-slate-300 focus:border-[color:var(--brand-primary)] focus:ring-[color:var(--brand-primary)]" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" class="text-slate-700" />

            <x-text-input id="password" class="block mt-1 w-full border-slate-300 focus:border-[color:var(--brand-primary)] focus:ring-[color:var(--brand-primary)]"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-slate-700" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full border-slate-300 focus:border-[color:var(--brand-primary)] focus:ring-[color:var(--brand-primary)]"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-slate-600 hover:text-[color:var(--brand-primary)] rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[color:var(--brand-primary)]" href="{{ route('tenant.login', ['subdomain' => request()->route('subdomain')]) }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
