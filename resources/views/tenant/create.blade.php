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
        $tenantSubdomain = session('tenant_subdomain') ?? ($tenant_subdomain ?? null);
        $tenantLoginUrl = session('tenant_login_url') ?? ($tenant_login_url ?? null);
        $plans = config('plans');
        $defaultPlan = old('plan', 'starter');
        $defaultMonths = max((int) old('subscription_months', 1), 1);
        if (! $tenantLoginUrl && $tenantSubdomain && config('app.domain') !== 'localhost') {
            $tenantLoginUrl = url("http://{$tenantSubdomain}." . config('app.domain') . '/login');
        }
        $warningMessage = session('warning') ?? ($warning ?? null);
    @endphp

    @if ($tenantLoginUrl)
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            <div class="font-semibold">{{ $successMessage ?? 'Shop created successfully.' }}</div>
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

    @if (($paymentCancelled ?? false) === true)
        <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Stripe checkout was cancelled. Your shop was not created yet.
        </div>
    @endif

    <form id="tenant-registration-form" method="POST" action="{{ route('tenant.register') }}" data-stripe-session-url="{{ route('tenant.register.payment.session') }}">
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
                            <div class="font-semibold">₱{{ number_format($plans['starter']['price']) }} / month</div>
                            <input class="mt-3 plan-option" type="radio" name="plan" value="starter" data-plan-price="{{ $plans['starter']['price'] }}" {{ $defaultPlan === 'starter' ? 'checked' : '' }}>
                        </div>
                    </div>
                </label>

                <label class="block border rounded-lg p-4 cursor-pointer">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="font-semibold">Standard Plan</div>
                            <ul class="list-disc ml-5 mt-2 text-sm text-gray-700">
                                <li>POS system</li>
                                <li>Product management</li>
                                <li>Order queue</li>
                                <li>Brewing guides</li>
                                <li>Inventory management</li>
                                <li>Sales reports</li>
                                <li>Branding customization</li>
                                <li>Up to 3 staff accounts</li>
                            </ul>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold">₱{{ number_format($plans['standard']['price']) }} / month</div>
                            <input class="mt-3 plan-option" type="radio" name="plan" value="standard" data-plan-price="{{ $plans['standard']['price'] }}" {{ $defaultPlan === 'standard' ? 'checked' : '' }}>
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
                            <div class="font-semibold">₱{{ number_format($plans['business']['price']) }} / month</div>
                            <input class="mt-3 plan-option" type="radio" name="plan" value="business" data-plan-price="{{ $plans['business']['price'] }}" {{ $defaultPlan === 'business' ? 'checked' : '' }}>
                        </div>
                    </div>
                </label>
            </div>

            <x-input-error :messages="$errors->get('plan')" class="mt-2" />
        </div>

        <!-- Payment Method -->
        <div class="mt-6">
            <x-input-label :value="__('Payment Method')" />
            <div class="mt-3 space-y-3">
                <label class="block border rounded-lg p-4 cursor-pointer">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Gcash</div>
                        </div>
                        <input type="radio" name="payment_method" value="gcash" {{ old('payment_method', 'gcash') === 'gcash' ? 'checked' : '' }}>
                    </div>
                </label>

                <label class="block border rounded-lg p-4 cursor-pointer">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Bank (Card)</div>
                        </div>
                        <input type="radio" name="payment_method" value="stripe" {{ old('payment_method') === 'stripe' ? 'checked' : '' }}>
                    </div>
                </label>
            </div>
            <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
        </div>

        <!-- Subscription Months -->
        <div class="mt-4">
            <x-input-label for="subscription_months" :value="__('Number of Months')" />
            <select id="subscription_months" name="subscription_months" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                @for ($month = 1; $month <= 24; $month++)
                    <option value="{{ $month }}" {{ $defaultMonths === $month ? 'selected' : '' }}>{{ $month }} {{ $month === 1 ? 'month' : 'months' }}</option>
                @endfor
            </select>
            <x-input-error :messages="$errors->get('subscription_months')" class="mt-2" />
        </div>

        <div class="mt-4 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
            <div><span class="font-semibold">Monthly Price:</span> <span id="monthly-price">₱0</span></div>
            <div class="mt-1"><span class="font-semibold">Total Due:</span> <span id="total-price">₱0</span></div>
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
            <div id="stripe-modal-error" class="mr-4 hidden rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>
            <x-primary-button id="register-shop-submit" class="ml-4">
                {{ __('Register Shop') }}
            </x-primary-button>
        </div>
    </form>

    <div id="stripe-checkout-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-3xl rounded-xl bg-white p-4 shadow-xl">
            <div class="mb-3 flex items-center justify-between">
                <div class="text-sm font-semibold text-gray-700">Complete Stripe Payment</div>
                <button id="stripe-modal-close" type="button" class="rounded-md px-2 py-1 text-sm text-gray-500 hover:bg-gray-100">Close</button>
            </div>
            <div id="stripe-checkout-container" class="max-h-[80vh] overflow-auto"></div>
        </div>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        (() => {
            const planInputs = document.querySelectorAll('.plan-option');
            const monthsInput = document.getElementById('subscription_months');
            const monthlyPriceTarget = document.getElementById('monthly-price');
            const totalPriceTarget = document.getElementById('total-price');
            const registrationForm = document.getElementById('tenant-registration-form');
            const submitButton = document.getElementById('register-shop-submit');
            const stripePaymentInput = document.querySelector('input[name="payment_method"][value="stripe"]');
            const stripeError = document.getElementById('stripe-modal-error');
            const stripeModal = document.getElementById('stripe-checkout-modal');
            const stripeModalClose = document.getElementById('stripe-modal-close');
            const stripeContainer = document.getElementById('stripe-checkout-container');

            let embeddedCheckout = null;

            if (!planInputs.length || !monthsInput || !monthlyPriceTarget || !totalPriceTarget || !registrationForm || !submitButton) {
                return;
            }

            const formatCurrency = (amount) => {
                return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 }).format(amount);
            };

            const getSelectedPlanPrice = () => {
                const selected = document.querySelector('.plan-option:checked');
                return selected ? Number(selected.getAttribute('data-plan-price') || 0) : 0;
            };

            const updatePricing = () => {
                const monthlyPrice = getSelectedPlanPrice();
                const months = Number(monthsInput.value || 1);
                const total = monthlyPrice * Math.max(months, 1);

                monthlyPriceTarget.textContent = formatCurrency(monthlyPrice);
                totalPriceTarget.textContent = formatCurrency(total);
            };

            const showStripeError = (message) => {
                if (!stripeError) {
                    return;
                }

                stripeError.textContent = message;
                stripeError.classList.remove('hidden');
            };

            const clearStripeError = () => {
                if (!stripeError) {
                    return;
                }

                stripeError.textContent = '';
                stripeError.classList.add('hidden');
            };

            const closeStripeModal = async () => {
                if (!stripeModal || !stripeContainer) {
                    return;
                }

                try {
                    if (embeddedCheckout && typeof embeddedCheckout.destroy === 'function') {
                        embeddedCheckout.destroy();
                    }
                } catch (error) {
                }

                embeddedCheckout = null;
                stripeContainer.innerHTML = '';
                stripeModal.classList.add('hidden');
                stripeModal.classList.remove('flex');
            };

            const openStripeModal = () => {
                if (!stripeModal) {
                    return;
                }

                stripeModal.classList.remove('hidden');
                stripeModal.classList.add('flex');
            };

            const getStripeErrorFromPayload = (payload) => {
                if (!payload || typeof payload !== 'object') {
                    return 'Unable to start Stripe checkout. Please try again.';
                }

                if (typeof payload.message === 'string' && payload.message.trim() !== '') {
                    return payload.message;
                }

                const errors = payload.errors;
                if (errors && typeof errors === 'object') {
                    const firstField = Object.keys(errors)[0];
                    const firstMessage = firstField ? errors[firstField]?.[0] : null;
                    if (typeof firstMessage === 'string' && firstMessage.trim() !== '') {
                        return firstMessage;
                    }
                }

                return 'Unable to start Stripe checkout. Please try again.';
            };

            const setSubmittingState = (isSubmitting) => {
                submitButton.disabled = isSubmitting;
                submitButton.style.opacity = isSubmitting ? '0.7' : '1';
            };

            registrationForm.addEventListener('submit', async (event) => {
                const stripeSelected = Boolean(stripePaymentInput && stripePaymentInput.checked);
                if (!stripeSelected) {
                    return;
                }

                event.preventDefault();
                clearStripeError();
                setSubmittingState(true);

                try {
                    const response = await fetch(registrationForm.dataset.stripeSessionUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': registrationForm.querySelector('input[name="_token"]')?.value || '',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(registrationForm),
                    });

                    const payload = await response.json();

                    if (!response.ok) {
                        showStripeError(getStripeErrorFromPayload(payload));
                        setSubmittingState(false);
                        return;
                    }

                    if (!window.Stripe || !payload.publishableKey || !payload.clientSecret) {
                        showStripeError('Stripe could not be initialized. Please check configuration.');
                        setSubmittingState(false);
                        return;
                    }

                    const stripe = window.Stripe(payload.publishableKey);
                    embeddedCheckout = await stripe.initEmbeddedCheckout({
                        clientSecret: payload.clientSecret,
                    });

                    openStripeModal();
                    embeddedCheckout.mount('#stripe-checkout-container');
                } catch (error) {
                    showStripeError('Unable to start Stripe checkout. Please try again.');
                } finally {
                    setSubmittingState(false);
                }
            });

            if (stripeModalClose) {
                stripeModalClose.addEventListener('click', () => {
                    closeStripeModal();
                });
            }

            if (stripeModal) {
                stripeModal.addEventListener('click', (event) => {
                    if (event.target === stripeModal) {
                        closeStripeModal();
                    }
                });
            }

            planInputs.forEach((input) => input.addEventListener('change', updatePricing));
            monthsInput.addEventListener('change', updatePricing);
            updatePricing();
        })();
    </script>
</x-guest-layout>
