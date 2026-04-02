<?php

namespace hexa_package_copyleaks\Services;

use hexa_core\Models\Setting;
use hexa_core\Services\GenericService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * CopyleaksService — AI content detection via Copyleaks API.
 *
 * Auth flow: POST email+key to id.copyleaks.com → get bearer token (48h) → use on api.copyleaks.com.
 * Docs: https://docs.copyleaks.com
 */
class CopyleaksService
{
    protected GenericService $generic;

    /** @var string Login endpoint */
    const LOGIN_URL = 'https://id.copyleaks.com/v3/account/login/api';

    /** @var string AI detection endpoint (scanId is a unique ID per request) */
    const DETECT_URL = 'https://api.copyleaks.com/v2/writer-detector/{scanId}/check';

    /**
     * @param GenericService $generic
     */
    public function __construct(GenericService $generic)
    {
        $this->generic = $generic;
    }

    /**
     * Check if Copyleaks is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return Setting::getValue('copyleaks_enabled', config('copyleaks.enabled', true));
    }

    /**
     * Check if debug mode is on (sends only first 3 sentences).
     *
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return (bool) Setting::getValue('copyleaks_debug_mode', false);
    }

    /**
     * Get the API key (from CredentialService or legacy Setting).
     *
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        if (class_exists(\hexa_core\Services\CredentialService::class)) {
            $cred = app(\hexa_core\Services\CredentialService::class);
            $val = $cred->get('copyleaks', 'api_key');
            if ($val) return $val;
        }
        return Setting::getValue('copyleaks_api_key');
    }

    /**
     * Get the account email for API login (from CredentialService or legacy Setting).
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        if (class_exists(\hexa_core\Services\CredentialService::class)) {
            $cred = app(\hexa_core\Services\CredentialService::class);
            $val = $cred->get('copyleaks', 'email');
            if ($val) return $val;
        }
        return Setting::getValue('copyleaks_email');
    }

    /**
     * Authenticate with Copyleaks and get a bearer token.
     * Token is cached for 47 hours (valid for 48h).
     *
     * @return array{success: bool, token?: string, message?: string}
     */
    public function authenticate(): array
    {
        $email = $this->getEmail();
        $apiKey = $this->getApiKey();

        if (empty($email) || empty($apiKey)) {
            return ['success' => false, 'message' => 'Copyleaks email and API key are both required.'];
        }

        // Check cached token
        $cached = Setting::getValue('copyleaks_bearer_token');
        $cachedAt = Setting::getValue('copyleaks_token_time');
        if ($cached && $cachedAt && (time() - (int) $cachedAt) < 169200) { // 47 hours
            return ['success' => true, 'token' => $cached];
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(15)->post(self::LOGIN_URL, [
                'email' => $email,
                'key' => $apiKey,
            ]);

            if (!$response->successful()) {
                $error = $response->json('message') ?? $response->body();
                return ['success' => false, 'message' => 'Copyleaks login failed: ' . (is_string($error) ? $error : json_encode($error))];
            }

            $data = $response->json();
            $token = $data['access_token'] ?? $data['.accessToken'] ?? null;

            if (!$token) {
                return ['success' => false, 'message' => 'Copyleaks login returned no token. Response: ' . json_encode($data)];
            }

            // Cache the token
            Setting::setValue('copyleaks_bearer_token', $token);
            Setting::setValue('copyleaks_token_time', (string) time());

            return ['success' => true, 'token' => $token];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Copyleaks login error: ' . $e->getMessage()];
        }
    }

    /**
     * Detect AI-generated content.
     *
     * @param string $text Plain text to analyze
     * @return array{success: bool, message: string, data?: array}
     */
    public function detect(string $text): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Copyleaks is disabled.'];
        }

        // Authenticate first
        $auth = $this->authenticate();
        if (!$auth['success']) {
            return $auth;
        }

        // Debug mode: only send first 3 sentences
        if ($this->isDebugMode()) {
            $sentences = preg_split('/(?<=[.!?])\s+/', $text, 4);
            $text = implode(' ', array_slice($sentences, 0, 3));
        }

        $scanId = Str::uuid()->toString();

        try {
            $url = str_replace('{scanId}', $scanId, self::DETECT_URL);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $auth['token'],
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($url, [
                'text' => $text,
                'sandbox' => $this->isDebugMode(),
            ]);

            if (!$response->successful()) {
                // If 401, clear cached token and retry once
                if ($response->status() === 401) {
                    Setting::setValue('copyleaks_bearer_token', '');
                    Setting::setValue('copyleaks_token_time', '');
                    $auth = $this->authenticate();
                    if ($auth['success']) {
                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $auth['token'],
                            'Content-Type' => 'application/json',
                        ])->timeout(30)->post($url, [
                            'text' => $text,
                            'sandbox' => $this->isDebugMode(),
                        ]);
                    }
                }

                if (!$response->successful()) {
                    $error = $response->json('message') ?? $response->body();
                    return ['success' => false, 'message' => 'Copyleaks API error (' . $response->status() . '): ' . (is_string($error) ? $error : json_encode($error))];
                }
            }

            $data = $response->json();
            $summary = $data['summary'] ?? $data;
            $aiScore = $summary['ai'] ?? $summary['aiScore'] ?? null;
            $humanScore = $summary['human'] ?? $summary['humanScore'] ?? null;

            return [
                'success' => true,
                'message' => 'Detection complete.',
                'data' => [
                    'ai_score' => $aiScore !== null ? round((float) $aiScore * 100, 1) : null,
                    'human_score' => $humanScore !== null ? round((float) $humanScore * 100, 1) : null,
                    'classification' => $data['summary']['classification'] ?? null,
                    'sentences' => collect($data['results'] ?? [])->where('classification', 'ai')->pluck('text')->values()->all(),
                    'raw' => $data,
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Copyleaks request failed: ' . $e->getMessage()];
        }
    }

    /**
     * Test the API connection.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        $auth = $this->authenticate();
        if (!$auth['success']) {
            return $auth;
        }
        return ['success' => true, 'message' => 'Copyleaks authenticated. Token valid for 48h. Credits remaining on dashboard.'];
    }
}
