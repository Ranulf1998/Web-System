<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Super Admin Login - BrewCloud</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    @php
        $recaptchaSiteKey = (string) config('services.recaptcha.site_key');
    @endphp

    <div class="mx-auto flex min-h-screen w-full max-w-7xl items-center justify-center px-6 py-12">
        <div class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900/80 p-8 shadow-2xl">
            <div class="mb-6">
                <p class="text-xs uppercase tracking-[0.25em] text-indigo-300">BrewCloud</p>
                <h1 class="mt-2 text-2xl font-semibold">Super Admin Login</h1>
                <p class="mt-2 text-sm text-slate-400">Sign in to access the BrewCloud owner dashboard.</p>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-lg border border-indigo-400/40 bg-indigo-500/10 px-4 py-3 text-sm text-indigo-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('super-admin.login.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-200">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           class="mt-1 block w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 placeholder:text-slate-500 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-200">Password</label>
                    <input id="password" name="password" type="password" required
                           class="mt-1 block w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 placeholder:text-slate-500 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-700 bg-slate-950 text-indigo-500 focus:ring-indigo-500/30">
                    Remember me
                </label>

                <div>
                    @if ($recaptchaSiteKey !== '')
                        <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
                    @else
                        <div class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
                            reCAPTCHA is not configured. Set RECAPTCHA_SITE_KEY and RECAPTCHA_SECRET_KEY first.
                        </div>
                    @endif

                    @if ($errors->has('g-recaptcha-response'))
                        <div class="mt-2 text-sm text-red-300">{{ $errors->first('g-recaptcha-response') }}</div>
                    @endif
                </div>

                <button type="submit" @disabled($recaptchaSiteKey === '') class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60">
                    Sign in
                </button>
            </form>

            <div class="mt-6 text-center text-xs text-slate-500">
                <a href="{{ route('home') }}" class="hover:text-slate-300">Back to home</a>
            </div>
        </div>
    </div>

    @if ($recaptchaSiteKey !== '')
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
</body>
</html>
