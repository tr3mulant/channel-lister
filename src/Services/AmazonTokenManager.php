<?php

namespace IGE\ChannelLister\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmazonTokenManager
{
    protected string $clientId;

    protected string $clientSecret;

    protected string $refreshToken;

    protected string $tokenEndpoint = 'https://api.amazon.com/auth/o2/token';

    protected string $cachePrefix;

    public function __construct()
    {
        //
    }

    /**
     * Get a valid access token, refreshing if necessary.
     */
    public function getAccessToken(): string
    {
        // Try to get cached token first
        $cachedToken = $this->getCachedToken();

        if ($cachedToken && ! $this->isTokenExpiringSoon($cachedToken)) {
            return $cachedToken['access_token'];
        }

        // Token is missing or expiring soon, refresh it
        $newToken = $this->refreshAccessToken();

        if ($newToken === null || $newToken === []) {
            throw new \RuntimeException('Failed to obtain valid Amazon SP-API access token');
        }

        return $newToken['access_token'];
    }

    /**
     * Refresh the access token using the refresh token.
     */
    /**
     * @return array<string, mixed>|null
     */
    public function refreshAccessToken(): ?array
    {
        try {
            $response = Http::asForm()
                ->withBasicAuth($this->clientId(), $this->clientSecret())
                ->post($this->tokenEndpoint, [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->refreshToken(),
                ]);

            if (! $response->successful()) {
                Log::error('Amazon SP-API token refresh failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return null;
            }

            $responseData = $response->json();
            if (! is_array($responseData)) {
                Log::error('Amazon SP-API token refresh returned invalid data');

                return null;
            }

            $tokenData = $responseData;

            // Add timestamp for expiration tracking
            $tokenData['obtained_at'] = Carbon::now()->timestamp;
            $expiresIn = is_numeric($tokenData['expires_in'] ?? null) ? (int) $tokenData['expires_in'] : 3600;
            $tokenData['expires_at'] = Carbon::now()->addSeconds($expiresIn)->timestamp;

            // Cache the new token
            $this->cacheToken($tokenData);

            Log::info('Amazon SP-API token refreshed successfully', [
                'expires_in' => $tokenData['expires_in'] ?? 'unknown',
            ]);

            return $tokenData;

        } catch (\Exception $e) {
            Log::error('Amazon SP-API token refresh error', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if the current token is valid and not expiring soon.
     */
    public function isTokenValid(): bool
    {
        $cachedToken = $this->getCachedToken();

        return $cachedToken && ! $this->isTokenExpiringSoon($cachedToken);
    }

    /**
     * Invalidate the cached token (useful for testing or forced refresh).
     */
    public function invalidateToken(): void
    {
        Cache::forget($this->getCacheKey());
        Log::info('Amazon SP-API token cache invalidated');
    }

    /**
     * Get token information for debugging/monitoring.
     *
     * @return array<string, mixed>|null
     */
    public function getTokenInfo(): ?array
    {
        $cachedToken = $this->getCachedToken();

        if ($cachedToken === null || $cachedToken === []) {
            return null;
        }

        return [
            'has_token' => true,
            'obtained_at' => Carbon::createFromTimestamp($cachedToken['obtained_at'] ?? 0)->toDateTimeString(),
            'expires_at' => Carbon::createFromTimestamp($cachedToken['expires_at'] ?? 0)->toDateTimeString(),
            'expires_in_seconds' => ($cachedToken['expires_at'] ?? 0) - Carbon::now()->timestamp,
            'is_valid' => $this->isTokenValid(),
            'token_type' => $cachedToken['token_type'] ?? 'bearer',
        ];
    }

    /**
     * Get cached token data.
     *
     * @return array<string, mixed>|null
     */
    protected function getCachedToken(): ?array
    {
        $cached = Cache::get($this->getCacheKey());

        return is_array($cached) ? $cached : null;
    }

    /**
     * Cache the token data.
     *
     * @param  array<string, mixed>  $tokenData
     */
    protected function cacheToken(array $tokenData): void
    {
        // Cache for slightly less than the actual expiry to ensure we refresh before expiration
        $cacheSeconds = ($tokenData['expires_in'] ?? 3600) - 300; // 5 minutes buffer
        $cacheSeconds = max($cacheSeconds, 60); // Minimum 1 minute cache

        Cache::put($this->getCacheKey(), $tokenData, $cacheSeconds);
    }

    /**
     * Check if token is expiring soon (within 10 minutes).
     *
     * @param  array<string, mixed>  $tokenData
     */
    protected function isTokenExpiringSoon(array $tokenData): bool
    {
        if (! isset($tokenData['expires_at'])) {
            return true; // Consider invalid if no expiry info
        }

        $expiresAt = Carbon::createFromTimestamp($tokenData['expires_at']);
        $bufferTime = Carbon::now()->addMinutes(10);

        return $expiresAt->lte($bufferTime);
    }

    /**
     * Get the cache key for storing tokens.
     */
    protected function getCacheKey(): string
    {
        return $this->cachePrefix().':access_token';
    }

    /**
     * Validate configuration.
     *
     * @return array<int, string>
     */
    public function validateConfiguration(): array
    {
        $errors = [];

        if ($this->clientId() === '' || $this->clientId() === '0') {
            $errors[] = 'AMAZON_SP_API_CLIENT_ID is required';
        }

        if ($this->clientSecret() === '' || $this->clientSecret() === '0') {
            $errors[] = 'AMAZON_SP_API_CLIENT_SECRET is required';
        }

        if ($this->refreshToken() === '' || $this->refreshToken() === '0') {
            $errors[] = 'AMAZON_SP_API_REFRESH_TOKEN is required';
        }

        return $errors;
    }

    protected function clientId(): string
    {
        $configValue = config('channel-lister.amazon.client_id');

        return $this->clientId ??= is_string($configValue) ? $configValue : '';
    }

    protected function clientSecret(): string
    {
        $configValue = config('channel-lister.amazon.client_secret');

        return $this->clientSecret ??= is_string($configValue) ? $configValue : '';
    }

    protected function refreshToken(): string
    {
        $configValue = config('channel-lister.amazon.refresh_token');

        return $this->refreshToken ??= is_string($configValue) ? $configValue : '';
    }

    protected function cachePrefix(): string
    {
        return $this->cachePrefix ??= config('channel-lister.cache_prefix', 'channel-lister').':amazon:token';
    }
}
