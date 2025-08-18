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
        $this->clientId = config('channel-lister.amazon.client_id');
        $this->clientSecret = config('channel-lister.amazon.client_secret');
        $this->refreshToken = config('channel-lister.amazon.refresh_token');
        $this->cachePrefix = config('channel-lister.cache_prefix', 'channel-lister').':amazon:token';
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
    public function refreshAccessToken(): ?array
    {
        try {
            $response = Http::asForm()
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->post($this->tokenEndpoint, [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->refreshToken,
                ]);

            if (! $response->successful()) {
                Log::error('Amazon SP-API token refresh failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return null;
            }

            $tokenData = $response->json();

            // Add timestamp for expiration tracking
            $tokenData['obtained_at'] = Carbon::now()->timestamp;
            $tokenData['expires_at'] = Carbon::now()->addSeconds($tokenData['expires_in'] ?? 3600)->timestamp;

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
     */
    protected function getCachedToken(): ?array
    {
        return Cache::get($this->getCacheKey());
    }

    /**
     * Cache the token data.
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
        return $this->cachePrefix.':access_token';
    }

    /**
     * Validate configuration.
     */
    public function validateConfiguration(): array
    {
        $errors = [];

        if ($this->clientId === '' || $this->clientId === '0') {
            $errors[] = 'AMAZON_SP_API_CLIENT_ID is required';
        }

        if ($this->clientSecret === '' || $this->clientSecret === '0') {
            $errors[] = 'AMAZON_SP_API_CLIENT_SECRET is required';
        }

        if ($this->refreshToken === '' || $this->refreshToken === '0') {
            $errors[] = 'AMAZON_SP_API_REFRESH_TOKEN is required';
        }

        return $errors;
    }
}
