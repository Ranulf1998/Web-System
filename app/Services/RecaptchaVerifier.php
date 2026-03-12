<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RecaptchaVerifier
{
    protected function shouldAllowInsecureLocalSsl(): bool
    {
        if (! app()->environment('local')) {
            return false;
        }

        $raw = config('services.recaptcha.allow_insecure_local_ssl', false);

        return filter_var($raw, FILTER_VALIDATE_BOOL);
    }

    protected function isCurlSslCaError(\Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'curl error 60')
            || str_contains($message, 'ssl certificate')
            || str_contains($message, 'local issuer certificate');
    }

    public function siteKey(): string
    {
        return trim((string) config('services.recaptcha.site_key'));
    }

    public function secretKey(): string
    {
        return trim((string) config('services.recaptcha.secret_key'));
    }

    public function enabled(): bool
    {
        return $this->siteKey() !== '' && $this->secretKey() !== '';
    }

    public function ensureValid(?string $responseToken, ?string $ipAddress = null): void
    {
        if (! $this->enabled()) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'reCAPTCHA is not configured. Set RECAPTCHA_SITE_KEY and RECAPTCHA_SECRET_KEY first.',
            ]);
        }

        if (! is_string($responseToken) || trim($responseToken) === '') {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'Please complete the reCAPTCHA challenge.',
            ]);
        }

        $payload = [
            'secret' => $this->secretKey(),
            'response' => trim($responseToken),
        ];

        if (is_string($ipAddress) && trim($ipAddress) !== '') {
            $payload['remoteip'] = trim($ipAddress);
        }

        $verifyUrls = array_values(array_unique(array_filter([
            trim((string) config('services.recaptcha.verify_url')),
            trim((string) config('services.recaptcha.fallback_verify_url')),
        ])));

        $verificationData = null;
        $lastException = null;
        $encounteredSslCaError = false;
        $allowInsecureLocalSsl = $this->shouldAllowInsecureLocalSsl();

        foreach ($verifyUrls as $verifyUrl) {
            try {
                $response = Http::asForm()
                    ->timeout(10)
                    ->post($verifyUrl, $payload);

                if (! $response->successful()) {
                    throw new \RuntimeException('reCAPTCHA verification endpoint returned HTTP ' . $response->status());
                }

                $verificationData = $response->json();
                break;
            } catch (\Throwable $exception) {
                $lastException = $exception;
                $isSslCaError = $this->isCurlSslCaError($exception);
                $encounteredSslCaError = $encounteredSslCaError || $isSslCaError;

                Log::warning('reCAPTCHA endpoint check failed.', [
                    'verify_url' => $verifyUrl,
                    'error' => $exception->getMessage(),
                ]);

                if ($isSslCaError && $allowInsecureLocalSsl) {
                    try {
                        $insecureResponse = Http::asForm()
                            ->timeout(10)
                            ->withOptions(['verify' => false])
                            ->post($verifyUrl, $payload);

                        if (! $insecureResponse->successful()) {
                            throw new \RuntimeException('reCAPTCHA insecure local retry returned HTTP ' . $insecureResponse->status());
                        }

                        $verificationData = $insecureResponse->json();

                        Log::warning('reCAPTCHA verification succeeded using insecure SSL retry (local only).', [
                            'verify_url' => $verifyUrl,
                        ]);

                        break;
                    } catch (\Throwable $insecureException) {
                        $lastException = $insecureException;

                        Log::warning('reCAPTCHA insecure local retry failed.', [
                            'verify_url' => $verifyUrl,
                            'error' => $insecureException->getMessage(),
                        ]);
                    }
                }
            }
        }

        if (! is_array($verificationData)) {
            Log::warning('reCAPTCHA verification could not be completed on all configured endpoints.', [
                'has_last_exception' => $lastException !== null,
                'last_error' => $lastException?->getMessage(),
            ]);

            if ($encounteredSslCaError) {
                throw ValidationException::withMessages([
                    'g-recaptcha-response' => 'Server SSL CA bundle is missing. Set PHP curl.cainfo/openssl.cafile, or enable RECAPTCHA_ALLOW_INSECURE_LOCAL_SSL=true for local only.',
                ]);
            }

            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'Unable to verify reCAPTCHA right now. Please try again.',
            ]);
        }

        if (($verificationData['success'] ?? false) === true) {
            return;
        }

        $errorCodes = is_array($verificationData['error-codes'] ?? null)
            ? $verificationData['error-codes']
            : [];

        if (in_array('invalid-input-secret', $errorCodes, true) || in_array('missing-input-secret', $errorCodes, true)) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'Invalid reCAPTCHA secret key. Please check RECAPTCHA_SECRET_KEY.',
            ]);
        }

        if (in_array('invalid-input-response', $errorCodes, true) || in_array('missing-input-response', $errorCodes, true)) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'Please complete the reCAPTCHA challenge and try again.',
            ]);
        }

        throw ValidationException::withMessages([
            'g-recaptcha-response' => 'reCAPTCHA verification failed. Please try again.',
        ]);
    }
}
