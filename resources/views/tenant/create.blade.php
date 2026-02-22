<x-guest-layout>
    @if ($errors->any())
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <div class="font-semibold">Please fix the following:</div>
            <ul class="list-disc ml-5 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $successMessage = session('success') ?? ($success ?? null);
        $tenantLoginUrl = session('tenant_login_url') ?? ($tenant_login_url ?? null);
        $warningMessage = session('warning') ?? ($warning ?? null);
    @endphp

    @if ($successMessage && $tenantLoginUrl)
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            <div class="font-semibold">{{ $successMessage }}</div>
            @if ($warningMessage)
                <div class="mt-1">{{ $warningMessage }}</div>
            @endif
            <div class="mt-1">
                Your shop login is ready:
                <a class="underline" href="{{ $tenantLoginUrl }}">{{ $tenantLoginUrl }}</a>
            </div>
            <div class="mt-1 text-xs text-green-700">Use the link above to log in to your shop.</div>
        </div>
    @endif

    <form method="POST" action="{{ route('tenant.register') }}">
        @csrf

        <!-- Shop Name -->
        <div>
            <x-input-label for="shop_name" :value="__('Coffee Shop Name')" />
            <x-text-input id="shop_name" class="block mt-1 w-full" type="text" name="shop_name" :value="old('shop_name')" required autofocus />
            <x-input-error :messages="$errors->get('shop_name')" class="mt-2" />
        </div>

        <!-- Subdomain -->
        <div class="mt-4">
            <x-input-label for="subdomain" :value="__('Subdomain')" />
            <div class="flex items-center">
                <x-text-input id="subdomain" class="block mt-1 w-full" type="text" name="subdomain" :value="old('subdomain')" required placeholder="yourshop" />
                <span class="ml-2 text-gray-600">.{{ config('app.domain') }}</span>
            </div>
            <x-input-error :messages="$errors->get('subdomain')" class="mt-2" />
        </div>

        <!-- Plan -->
        <div class="mt-6">
            <x-input-label :value="__('Choose a Plan')" />

            <div class="mt-3 space-y-3">
                <label class="block border rounded-lg p-4 cursor-pointer">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="font-semibold">Starter Plan</div>
                            <ul class="list-disc ml-5 mt-2 text-sm text-gray-700">
                                <li>POS system</li>
                                <li>Product management</li>
                                <li>Basic sales tracking</li>
                                <li>1 user account</li>
                                <li>Basic reports</li>
                            </ul>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold">₱500 / month</div>
                            <input class="mt-3" type="radio" name="plan" value="starter" {{ old('plan', 'starter') === 'starter' ? 'checked' : '' }}>
                        </div>
                    </div>
                </label>

                <label class="block border rounded-lg p-4 cursor-pointer">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="font-semibold">Standard Plan</div>
                            <ul class="list-disc ml-5 mt-2 text-sm text-gray-700">
                                <li>POS system</li>
                                <li>Product &amp; inventory management</li>
                                <li>Sales reports &amp; dashboard</li>
                                <li>Up to 5 staff accounts</li>
                                <li>Inventory alerts</li>
                            </ul>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold">₱1500 / month</div>
                            <input class="mt-3" type="radio" name="plan" value="standard" {{ old('plan') === 'standard' ? 'checked' : '' }}>
                        </div>
                    </div>
                </label>

                <label class="block border rounded-lg p-4 cursor-pointer">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="font-semibold">Business Plan</div>
                            <ul class="list-disc ml-5 mt-2 text-sm text-gray-700">
                                <li>All Standard features</li>
                                <li>Unlimited staff accounts</li>
                                <li>Advanced analytics</li>
                                <li>Multi-branch support</li>
                                <li>Priority support</li>
                            </ul>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold">₱2000 / month</div>
                            <input class="mt-3" type="radio" name="plan" value="business" {{ old('plan') === 'business' ? 'checked' : '' }}>
                        </div>
                    </div>
                </label>
            </div>

            <x-input-error :messages="$errors->get('plan')" class="mt-2" />
        </div>

        <!-- Owner Name -->
        <div class="mt-4">
            <x-input-label for="name" :value="__('Your Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ml-4">
                {{ __('Register Shop') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
