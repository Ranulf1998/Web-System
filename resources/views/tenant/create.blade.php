<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Register Your Shop</h1>
        <p class="mt-1 text-sm text-slate-600">Create your BrewCloud workspace and choose your subscription plan.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
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
        $plans = config('plans');
        $defaultPlan = old('plan', 'starter');
        $defaultMonths = max((int) old('subscription_months', 1), 1);
        $planMetadata = [];
        foreach ($plans as $planKey => $planConfig) {
            $planMetadata[(string) $planKey] = [
                'name' => (string) data_get($planConfig, 'name', 'Plan'),
                'max_users' => data_get($planConfig, 'max_users'),
                'features' => array_values(is_array(data_get($planConfig, 'features')) ? data_get($planConfig, 'features') : []),
            ];
        }
        $warningMessage = session('warning') ?? ($warning ?? null);
    @endphp

    @if ($successMessage)
        <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            <div class="font-semibold">{{ $successMessage }}</div>
            @if ($warningMessage)
                <div class="mt-1">{{ $warningMessage }}</div>
            @endif
        </div>
    @endif

    @if (($paymentCancelled ?? false) === true)
        <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Stripe checkout was cancelled. Your shop was not created yet.
        </div>
    @endif

    <form id="tenant-registration-form" method="POST" action="{{ route('tenant.register') }}" data-stripe-session-url="{{ route('tenant.register.payment.session') }}" class="space-y-4">
        @csrf

        <!-- Shop Name -->
        <div>
            <x-input-label for="shop_name" :value="__('Coffee Shop Name')" class="text-slate-700" />
            <x-text-input id="shop_name" class="block mt-1 w-full border-slate-300 focus:border-[color:var(--brand-primary)] focus:ring-[color:var(--brand-primary)]" type="text" name="shop_name" :value="old('shop_name')" required autofocus />
            <x-input-error :messages="$errors->get('shop_name')" class="mt-2" />
        </div>

        <!-- Shop Address -->
        <div>
            <x-input-label for="address" :value="__('Shop Address')" class="text-slate-700" />
            <x-text-input id="address" class="block mt-1 w-full border-slate-300 focus:border-[color:var(--brand-primary)] focus:ring-[color:var(--brand-primary)]" type="text" name="address" :value="old('address')" required />
            <x-input-error :messages="$errors->get('address')" class="mt-2" />
        </div>

        <!-- Subdomain -->
        <div>
            <x-input-label for="subdomain" :value="__('Subdomain')" class="text-slate-700" />
            <div class="flex items-center">
                <x-text-input id="subdomain" class="block mt-1 w-full border-slate-300 focus:border-[color:var(--brand-primary)] focus:ring-[color:var(--brand-primary)]" type="text" name="subdomain" :value="old('subdomain')" required placeholder="yourshop" />
                <span class="ml-2 text-slate-600">.{{ config('app.domain') }}</span>
            </div>
            <x-input-error :messages="$errors->get('subdomain')" class="mt-2" />
        </div>

        <!-- Plan -->
        <div class="pt-2">
            <x-input-label for="plan" :value="__('Choose a Plan')" class="text-slate-700" />
            <select id="plan" name="plan" class="border-slate-300 focus:border-[color:var(--brand-primary)] focus:ring-[color:var(--brand-primary)] rounded-md shadow-sm block mt-1 w-full">
                @foreach ($plans as $planKey => $planConfig)
                    <option
                        value="{{ $planKey }}"
                        data-plan-price="{{ data_get($planConfig, 'price', 0) }}"
                        {{ $defaultPlan === $planKey ? 'selected' : '' }}
                    >
                        {{ data_get($planConfig, 'name', ucfirst((string) $planKey) . ' Plan') }} - ₱{{ number_format((float) data_get($planConfig, 'price', 0)) }} / month
                    </option>
                @endforeach
            </select>

            <div id="plan-details" class="mt-2 rounded-md border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
                <div id="plan-details-title" class="font-semibold">Plan details</div>
                <div id="plan-details-text" class="mt-1 text-slate-600">Select a plan to view details.</div>
            </div>

            <x-input-error :messages="$errors->get('plan')" class="mt-2" />
        </div>

        <!-- Payment Method -->
        <div class="pt-2">
            <x-input-label :value="__('Payment Method')" class="text-slate-700" />
            <div class="mt-3 space-y-3">
                <label class="block border border-slate-200 rounded-lg p-4 cursor-pointer bg-white hover:bg-slate-50">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold text-slate-800">Gcash</div>
                        </div>
                        <input type="radio" name="payment_method" value="gcash" class="text-[color:var(--brand-primary)] focus:ring-[color:var(--brand-primary)]" {{ old('payment_method', 'gcash') === 'gcash' ? 'checked' : '' }}>
                    </div>
                </label>

                <label class="block border border-slate-200 rounded-lg p-4 cursor-pointer bg-white hover:bg-slate-50">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold text-slate-800">Bank (Card)</div>
                        </div>
                        <input type="radio" name="payment_method" value="stripe" class="text-[color:var(--brand-primary)] focus:ring-[color:var(--brand-primary)]" {{ old('payment_method') === 'stripe' ? 'checked' : '' }}>
                    </div>
                </label>
            </div>
            <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
        </div>

        <!-- Subscription Months -->
        <div>
            <x-input-label for="subscription_months" :value="__('Number of Months')" class="text-slate-700" />
            <select id="subscription_months" name="subscription_months" class="border-slate-300 focus:border-[color:var(--brand-primary)] focus:ring-[color:var(--brand-primary)] rounded-md shadow-sm block mt-1 w-full">
                @for ($month = 1; $month <= 24; $month++)
                    <option value="{{ $month }}" {{ $defaultMonths === $month ? 'selected' : '' }}>{{ $month }} {{ $month === 1 ? 'month' : 'months' }}</option>
                @endfor
            </select>
            <x-input-error :messages="$errors->get('subscription_months')" class="mt-2" />
        </div>

        <div class="rounded-md border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            <div><span class="font-semibold">Monthly Price:</span> <span id="monthly-price">₱0</span></div>
            <div class="mt-1"><span class="font-semibold">Total Due:</span> <span id="total-price">₱0</span></div>
        </div>

        <!-- Owner Name -->
        <div>
            <x-input-label for="name" :value="__('Your Name')" class="text-slate-700" />
            <x-text-input id="name" class="block mt-1 w-full border-slate-300 focus:border-[color:var(--brand-primary)] focus:ring-[color:var(--brand-primary)]" type="text" name="name" :value="old('name')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="text-slate-700" />
            <x-text-input id="email" class="block mt-1 w-full border-slate-300 focus:border-[color:var(--brand-primary)] focus:ring-[color:var(--brand-primary)]" type="email" name="email" :value="old('email')" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="terms" class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input id="terms" type="checkbox" name="terms" value="1" class="rounded border-slate-300 text-[color:var(--brand-primary)] shadow-sm focus:ring-[color:var(--brand-primary)]" {{ old('terms') ? 'checked' : '' }} required>
                <span>I agree to the Terms of Service (ToS).</span>
            </label>
            <x-input-error :messages="$errors->get('terms')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end pt-2">
            <div id="stripe-modal-error" class="mr-4 hidden rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>
            <x-primary-button id="register-shop-submit" class="ml-4">
                {{ __('Register Shop') }}
            </x-primary-button>
        </div>
    </form>

    <div id="stripe-checkout-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-3xl rounded-xl bg-white p-4 shadow-xl border border-slate-200">
            <div class="mb-3 flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-700">Complete Stripe Payment</div>
                <button id="stripe-modal-close" type="button" class="rounded-md px-2 py-1 text-sm text-slate-500 hover:bg-slate-100">Close</button>
            </div>
            <div id="stripe-checkout-container" class="max-h-[80vh] overflow-auto"></div>
        </div>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        (() => {
            const planInput = document.getElementById('plan');
            const planDetailsTitle = document.getElementById('plan-details-title');
            const planDetailsText = document.getElementById('plan-details-text');
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
            const planMetadata = @json($planMetadata);

            let embeddedCheckout = null;

            if (!planInput || !monthsInput || !monthlyPriceTarget || !totalPriceTarget || !registrationForm || !submitButton) {
                return;
            }

            const formatCurrency = (amount) => {
                return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 }).format(amount);
            };

            const getSelectedPlanPrice = () => {
                const selectedOption = planInput.options[planInput.selectedIndex];
                return selectedOption ? Number(selectedOption.getAttribute('data-plan-price') || 0) : 0;
            };

            const updatePricing = () => {
                const monthlyPrice = getSelectedPlanPrice();
                const months = Number(monthsInput.value || 1);
                const total = monthlyPrice * Math.max(months, 1);

                monthlyPriceTarget.textContent = formatCurrency(monthlyPrice);
                totalPriceTarget.textContent = formatCurrency(total);
            };

            const humanizeFeature = (feature) => {
                return String(feature || '')
                    .replace(/_/g, ' ')
                    .replace(/\b\w/g, (char) => char.toUpperCase());
            };

            const updatePlanDetails = () => {
                const selectedPlanKey = planInput.value;
                const selectedPlan = planMetadata[selectedPlanKey] || null;

                if (!selectedPlan || !planDetailsTitle || !planDetailsText) {
                    return;
                }

                const usersText = selectedPlan.max_users === null
                    ? 'Unlimited staff accounts'
                    : `Up to ${selectedPlan.max_users} staff account${selectedPlan.max_users === 1 ? '' : 's'}`;

                const featureText = Array.isArray(selectedPlan.features) && selectedPlan.features.length
                    ? selectedPlan.features.map(humanizeFeature).join(', ')
                    : 'No feature list available';

                planDetailsTitle.textContent = `${selectedPlan.name} Plan Details`;
                planDetailsText.textContent = `${usersText}. Features: ${featureText}.`;
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

            planInput.addEventListener('change', updatePricing);
            planInput.addEventListener('change', updatePlanDetails);
            monthsInput.addEventListener('change', updatePricing);
            updatePricing();
            updatePlanDetails();
        })();
    </script>
</x-guest-layout>
