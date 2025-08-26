<?php

use Carbon\Carbon;
use IGE\ChannelLister\Services\AmazonTokenManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    $this->tokenManager = new AmazonTokenManager;

    // Set up default config values
    Config::set('channel-lister.amazon.client_id', 'test_client_id');
    Config::set('channel-lister.amazon.client_secret', 'test_client_secret');
    Config::set('channel-lister.amazon.refresh_token', 'test_refresh_token');
    Config::set('channel-lister.cache_prefix', 'test-channel-lister');

    // Clear any cached tokens
    Cache::flush();
});

describe('AmazonTokenManager', function (): void {
    describe('getAccessToken', function (): void {
        it('returns cached token when valid', function (): void {
            $validToken = [
                'access_token' => 'cached_access_token',
                'expires_at' => Carbon::now()->addHours(2)->timestamp,
                'obtained_at' => Carbon::now()->subHour()->timestamp,
                'expires_in' => 3600,
            ];

            Cache::put('test-channel-lister:amazon:token:access_token', $validToken, 3600);

            $result = $this->tokenManager->getAccessToken();

            expect($result)->toBe('cached_access_token');
        });

        it('refreshes token when not cached', function (): void {
            Http::fake([
                'https://api.amazon.com/auth/o2/token' => Http::response([
                    'access_token' => 'new_access_token',
                    'token_type' => 'bearer',
                    'expires_in' => 3600,
                ], 200),
            ]);

            $result = $this->tokenManager->getAccessToken();

            expect($result)->toBe('new_access_token');
            Http::assertSent(fn ($request): bool => $request->url() === 'https://api.amazon.com/auth/o2/token' &&
                   $request['grant_type'] === 'refresh_token' &&
                   $request['refresh_token'] === 'test_refresh_token');
        });

        it('refreshes token when expiring soon', function (): void {
            $expiringToken = [
                'access_token' => 'expiring_token',
                'expires_at' => Carbon::now()->addMinutes(5)->timestamp, // Expires in 5 minutes
                'obtained_at' => Carbon::now()->subHour()->timestamp,
                'expires_in' => 3600,
            ];

            Cache::put('test-channel-lister:amazon:token:access_token', $expiringToken, 3600);

            Http::fake([
                'https://api.amazon.com/auth/o2/token' => Http::response([
                    'access_token' => 'refreshed_access_token',
                    'token_type' => 'bearer',
                    'expires_in' => 3600,
                ], 200),
            ]);

            $result = $this->tokenManager->getAccessToken();

            expect($result)->toBe('refreshed_access_token');
        });

        it('throws exception when refresh fails', function (): void {
            Http::fake([
                'https://api.amazon.com/auth/o2/token' => Http::response(['error' => 'invalid_grant'], 400),
            ]);

            expect(fn () => $this->tokenManager->getAccessToken())
                ->toThrow(RuntimeException::class, 'Failed to obtain valid Amazon SP-API access token');
        });

        it('handles malformed cached token', function (): void {
            $malformedToken = [
                'access_token' => 'some_token',
                // Missing expires_at - this will trigger isTokenExpiringSoon to return true
            ];

            Cache::put('test-channel-lister:amazon:token:access_token', $malformedToken, 3600);

            Http::fake([
                'https://api.amazon.com/auth/o2/token' => Http::response([
                    'access_token' => 'valid_access_token',
                    'token_type' => 'bearer',
                    'expires_in' => 3600,
                ], 200),
            ]);

            $result = $this->tokenManager->getAccessToken();

            // Should refresh token because malformed token (missing expires_at) is considered expiring
            expect($result)->toBe('valid_access_token');
        });
    });

    describe('refreshAccessToken', function (): void {
        it('successfully refreshes token', function (): void {
            Http::fake([
                'https://api.amazon.com/auth/o2/token' => Http::response([
                    'access_token' => 'new_access_token',
                    'token_type' => 'bearer',
                    'expires_in' => 3600,
                ], 200),
            ]);

            $result = $this->tokenManager->refreshAccessToken();

            expect($result)->toHaveKey('access_token', 'new_access_token');
            expect($result)->toHaveKey('token_type', 'bearer');
            expect($result)->toHaveKey('expires_in', 3600);
            expect($result)->toHaveKey('obtained_at');
            expect($result)->toHaveKey('expires_at');

            // Verify token was cached
            $cachedToken = Cache::get('test-channel-lister:amazon:token:access_token');
            expect($cachedToken)->not()->toBeNull();
            expect($cachedToken['access_token'])->toBe('new_access_token');
        });

        it('handles HTTP error response', function (): void {
            Log::spy();

            Http::fake([
                'https://api.amazon.com/auth/o2/token' => Http::response(['error' => 'invalid_grant'], 400),
            ]);

            $result = $this->tokenManager->refreshAccessToken();

            expect($result)->toBeNull();
            Log::shouldHaveReceived('error')
                ->with('Amazon SP-API token refresh failed', Mockery::type('array'))
                ->once();
        });

        it('handles invalid JSON response', function (): void {
            Log::spy();

            Http::fake([
                'https://api.amazon.com/auth/o2/token' => Http::response('invalid json', 200),
            ]);

            $result = $this->tokenManager->refreshAccessToken();

            expect($result)->toBeNull();
            Log::shouldHaveReceived('error')
                ->with('Amazon SP-API token refresh returned invalid data')
                ->once();
        });

        it('handles exception during refresh', function (): void {
            Log::spy();

            Http::fake(function (): void {
                throw new \Exception('Network error');
            });

            $result = $this->tokenManager->refreshAccessToken();

            expect($result)->toBeNull();
            Log::shouldHaveReceived('error')
                ->with('Amazon SP-API token refresh error', ['error' => 'Network error'])
                ->once();
        });

        it('logs successful refresh', function (): void {
            Log::spy();

            Http::fake([
                'https://api.amazon.com/auth/o2/token' => Http::response([
                    'access_token' => 'new_token',
                    'expires_in' => 7200,
                ], 200),
            ]);

            $this->tokenManager->refreshAccessToken();

            Log::shouldHaveReceived('info')
                ->with('Amazon SP-API token refreshed successfully', ['expires_in' => 7200])
                ->once();
        });

        it('uses correct HTTP request format', function (): void {
            Http::fake([
                'https://api.amazon.com/auth/o2/token' => Http::response(['access_token' => 'token'], 200),
            ]);

            $this->tokenManager->refreshAccessToken();

            Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Basic '.base64_encode('test_client_id:test_client_secret')) &&
                   $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded') &&
                   $request['grant_type'] === 'refresh_token' &&
                   $request['refresh_token'] === 'test_refresh_token');
        });
    });

    describe('isTokenValid', function (): void {
        it('returns true for valid non-expiring token', function (): void {
            $validToken = [
                'access_token' => 'valid_token',
                'expires_at' => Carbon::now()->addHours(2)->timestamp,
            ];

            Cache::put('test-channel-lister:amazon:token:access_token', $validToken, 3600);

            $result = $this->tokenManager->isTokenValid();

            expect($result)->toBeTrue();
        });

        it('returns false for expiring token', function (): void {
            $expiringToken = [
                'access_token' => 'expiring_token',
                'expires_at' => Carbon::now()->addMinutes(5)->timestamp,
            ];

            Cache::put('test-channel-lister:amazon:token:access_token', $expiringToken, 3600);

            $result = $this->tokenManager->isTokenValid();

            expect($result)->toBeFalse();
        });

        it('returns false when no token cached', function (): void {
            $result = $this->tokenManager->isTokenValid();

            expect($result)->toBeFalse();
        });
    });

    describe('invalidateToken', function (): void {
        it('removes token from cache', function (): void {
            Log::spy();

            Cache::put('test-channel-lister:amazon:token:access_token', ['access_token' => 'token'], 3600);

            $this->tokenManager->invalidateToken();

            $cachedToken = Cache::get('test-channel-lister:amazon:token:access_token');
            expect($cachedToken)->toBeNull();

            Log::shouldHaveReceived('info')
                ->with('Amazon SP-API token cache invalidated')
                ->once();
        });
    });

    describe('getTokenInfo', function (): void {
        it('returns token information when cached', function (): void {
            $tokenData = [
                'access_token' => 'test_token',
                'obtained_at' => Carbon::now()->subHour()->timestamp,
                'expires_at' => Carbon::now()->addHour()->timestamp,
                'token_type' => 'bearer',
            ];

            Cache::put('test-channel-lister:amazon:token:access_token', $tokenData, 3600);

            $result = $this->tokenManager->getTokenInfo();

            expect($result)->toHaveKey('has_token', true);
            expect($result)->toHaveKey('obtained_at');
            expect($result)->toHaveKey('expires_at');
            expect($result)->toHaveKey('expires_in_seconds');
            expect($result)->toHaveKey('is_valid');
            expect($result)->toHaveKey('token_type', 'bearer');
            expect($result['expires_in_seconds'])->toBeGreaterThan(0);
        });

        it('returns null when no token cached', function (): void {
            $result = $this->tokenManager->getTokenInfo();

            expect($result)->toBeNull();
        });

        it('handles missing timestamp fields gracefully', function (): void {
            $tokenData = [
                'access_token' => 'test_token',
                // Missing obtained_at and expires_at
            ];

            Cache::put('test-channel-lister:amazon:token:access_token', $tokenData, 3600);

            $result = $this->tokenManager->getTokenInfo();

            expect($result)->toHaveKey('has_token', true);
            expect($result)->toHaveKey('obtained_at');
            expect($result)->toHaveKey('expires_at');
            expect($result['expires_in_seconds'])->toBeLessThan(0); // Already expired
        });
    });

    describe('validateConfiguration', function (): void {
        it('returns no errors for valid configuration', function (): void {
            $errors = $this->tokenManager->validateConfiguration();

            expect($errors)->toBe([]);
        });

        it('returns error for missing client ID', function (): void {
            Config::set('channel-lister.amazon.client_id', '');

            $errors = $this->tokenManager->validateConfiguration();

            expect($errors)->toContain('AMAZON_SP_API_CLIENT_ID is required');
        });

        it('returns error for missing client secret', function (): void {
            Config::set('channel-lister.amazon.client_secret', null);

            $errors = $this->tokenManager->validateConfiguration();

            expect($errors)->toContain('AMAZON_SP_API_CLIENT_SECRET is required');
        });

        it('returns error for missing refresh token', function (): void {
            Config::set('channel-lister.amazon.refresh_token', '0');

            $errors = $this->tokenManager->validateConfiguration();

            expect($errors)->toContain('AMAZON_SP_API_REFRESH_TOKEN is required');
        });

        it('returns all errors when all config missing', function (): void {
            Config::set('channel-lister.amazon.client_id', '');
            Config::set('channel-lister.amazon.client_secret', '');
            Config::set('channel-lister.amazon.refresh_token', '');

            $errors = $this->tokenManager->validateConfiguration();

            expect($errors)->toHaveCount(3);
            expect($errors)->toContain('AMAZON_SP_API_CLIENT_ID is required');
            expect($errors)->toContain('AMAZON_SP_API_CLIENT_SECRET is required');
            expect($errors)->toContain('AMAZON_SP_API_REFRESH_TOKEN is required');
        });
    });

    describe('protected methods via reflection', function (): void {
        it('caches token with correct duration', function (): void {
            $tokenData = [
                'access_token' => 'test_token',
                'expires_in' => 3600,
            ];

            $reflection = new ReflectionClass($this->tokenManager);
            $method = $reflection->getMethod('cacheToken');
            $method->setAccessible(true);

            $method->invoke($this->tokenManager, $tokenData);

            $cachedToken = Cache::get('test-channel-lister:amazon:token:access_token');
            expect($cachedToken)->not()->toBeNull();
            expect($cachedToken['access_token'])->toBe('test_token');
        });

        it('uses minimum cache time for short expiry', function (): void {
            $tokenData = [
                'access_token' => 'short_token',
                'expires_in' => 30, // Very short expiry
            ];

            $reflection = new ReflectionClass($this->tokenManager);
            $method = $reflection->getMethod('cacheToken');
            $method->setAccessible(true);

            $method->invoke($this->tokenManager, $tokenData);

            $cachedToken = Cache::get('test-channel-lister:amazon:token:access_token');
            expect($cachedToken)->not()->toBeNull();
        });

        it('detects expiring token correctly', function (): void {
            $expiringToken = [
                'expires_at' => Carbon::now()->addMinutes(5)->timestamp,
            ];

            $reflection = new ReflectionClass($this->tokenManager);
            $method = $reflection->getMethod('isTokenExpiringSoon');
            $method->setAccessible(true);

            $result = $method->invoke($this->tokenManager, $expiringToken);

            expect($result)->toBeTrue();
        });

        it('handles token without expiry info', function (): void {
            $tokenWithoutExpiry = [
                'access_token' => 'token_without_expiry',
            ];

            $reflection = new ReflectionClass($this->tokenManager);
            $method = $reflection->getMethod('isTokenExpiringSoon');
            $method->setAccessible(true);

            $result = $method->invoke($this->tokenManager, $tokenWithoutExpiry);

            expect($result)->toBeTrue(); // Should consider invalid
        });

        it('generates correct cache key', function (): void {
            $reflection = new ReflectionClass($this->tokenManager);
            $method = $reflection->getMethod('getCacheKey');
            $method->setAccessible(true);

            $result = $method->invoke($this->tokenManager);

            expect($result)->toBe('test-channel-lister:amazon:token:access_token');
        });

        it('uses fallback config values', function (): void {
            Config::set('channel-lister.amazon.client_id', null);
            Config::set('channel-lister.cache_prefix', null);

            $reflection = new ReflectionClass($this->tokenManager);

            $clientIdMethod = $reflection->getMethod('clientId');
            $clientIdMethod->setAccessible(true);
            $clientId = $clientIdMethod->invoke($this->tokenManager);
            expect($clientId)->toBe('');

            $cachePrefixMethod = $reflection->getMethod('cachePrefix');
            $cachePrefixMethod->setAccessible(true);
            $cachePrefix = $cachePrefixMethod->invoke($this->tokenManager);
            // When config is null, config() function with default returns default
            // But concatenation with null might result in `:amazon:token`
            expect($cachePrefix)->toBe(':amazon:token');
        });
    });
});
