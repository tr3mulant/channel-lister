<?php

use IGE\ChannelLister\Http\Middleware\AmazonSpApiAuth;
use IGE\ChannelLister\Services\AmazonTokenManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    $this->tokenManager = \Mockery::mock(AmazonTokenManager::class);
    $this->middleware = new AmazonSpApiAuth($this->tokenManager);
    $this->request = new Request;

    // Mock the next closure
    $this->next = fn (Request $request) => response()->json(['success' => true]);
});

afterEach(function (): void {
    \Mockery::close();
});

describe('AmazonSpApiAuth Middleware', function (): void {
    it('can be instantiated with token manager', function (): void {
        expect($this->middleware)->toBeInstanceOf(AmazonSpApiAuth::class);
    });

    it('passes request through when configuration is valid and token is obtained', function (): void {
        $this->tokenManager->shouldReceive('validateConfiguration')
            ->once()
            ->andReturn([]);

        $this->tokenManager->shouldReceive('getAccessToken')
            ->once()
            ->andReturn('valid-access-token');

        $response = $this->middleware->handle($this->request, $this->next);

        expect($response->getStatusCode())->toBe(200);
        expect(json_decode((string) $response->getContent(), true))->toBe(['success' => true]);
        expect($this->request->get('amazon_access_token'))->toBe('valid-access-token');
    });

    it('returns error response when configuration validation fails', function (): void {
        $configErrors = [
            'client_id' => 'Missing Amazon SP-API Client ID',
            'client_secret' => 'Missing Amazon SP-API Client Secret',
        ];

        $this->tokenManager->shouldReceive('validateConfiguration')
            ->once()
            ->andReturn($configErrors);

        Log::shouldReceive('error')
            ->once()
            ->with('Amazon SP-API configuration errors', $configErrors);

        $response = $this->middleware->handle($this->request, $this->next);

        expect($response)->toBeInstanceOf(JsonResponse::class);
        expect($response->getStatusCode())->toBe(500);

        $content = json_decode((string) $response->getContent(), true);
        expect($content['error'])->toBe('Amazon SP-API not properly configured');
        expect($content['details'])->toBe($configErrors);
    });

    it('returns error response when token acquisition fails', function (): void {
        $this->tokenManager->shouldReceive('validateConfiguration')
            ->once()
            ->andReturn([]);

        $exception = new Exception('Token refresh failed');
        $this->tokenManager->shouldReceive('getAccessToken')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to obtain Amazon SP-API access token', [
                'error' => 'Token refresh failed',
            ]);

        $response = $this->middleware->handle($this->request, $this->next);

        expect($response)->toBeInstanceOf(JsonResponse::class);
        expect($response->getStatusCode())->toBe(503);

        $content = json_decode((string) $response->getContent(), true);
        expect($content['error'])->toBe('Failed to authenticate with Amazon SP-API');
        expect($content['message'])->toBe('Unable to obtain valid access token');
    });

    it('adds access token to request when successful', function (): void {
        $accessToken = 'test-access-token-12345';

        $this->tokenManager->shouldReceive('validateConfiguration')
            ->once()
            ->andReturn([]);

        $this->tokenManager->shouldReceive('getAccessToken')
            ->once()
            ->andReturn($accessToken);

        $this->middleware->handle($this->request, $this->next);

        expect($this->request->get('amazon_access_token'))->toBe($accessToken);
    });

    it('handles multiple configuration errors', function (): void {
        $configErrors = [
            'client_id' => 'Missing Amazon SP-API Client ID',
            'client_secret' => 'Missing Amazon SP-API Client Secret',
            'refresh_token' => 'Missing Amazon SP-API Refresh Token',
        ];

        $this->tokenManager->shouldReceive('validateConfiguration')
            ->once()
            ->andReturn($configErrors);

        Log::shouldReceive('error')
            ->once()
            ->with('Amazon SP-API configuration errors', $configErrors);

        $response = $this->middleware->handle($this->request, $this->next);

        expect($response->getStatusCode())->toBe(500);
        $content = json_decode((string) $response->getContent(), true);
        expect($content['details'])->toHaveCount(3);
    });

    it('handles different types of exceptions during token acquisition', function (): void {
        $this->tokenManager->shouldReceive('validateConfiguration')
            ->once()
            ->andReturn([]);

        $exception = new RuntimeException('Network timeout');
        $this->tokenManager->shouldReceive('getAccessToken')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to obtain Amazon SP-API access token', [
                'error' => 'Network timeout',
            ]);

        $response = $this->middleware->handle($this->request, $this->next);

        expect($response->getStatusCode())->toBe(503);
    });

    it('preserves existing request data when adding token', function (): void {
        $this->request->merge(['existing_param' => 'existing_value']);

        $this->tokenManager->shouldReceive('validateConfiguration')
            ->once()
            ->andReturn([]);

        $this->tokenManager->shouldReceive('getAccessToken')
            ->once()
            ->andReturn('token-123');

        $this->middleware->handle($this->request, $this->next);

        expect($this->request->get('existing_param'))->toBe('existing_value');
        expect($this->request->get('amazon_access_token'))->toBe('token-123');
    });

    it('does not call getAccessToken if configuration validation fails', function (): void {
        $this->tokenManager->shouldReceive('validateConfiguration')
            ->once()
            ->andReturn(['error' => 'Configuration invalid']);

        $this->tokenManager->shouldNotReceive('getAccessToken');

        Log::shouldReceive('error')->once();

        $response = $this->middleware->handle($this->request, $this->next);

        expect($response->getStatusCode())->toBe(500);
    });

    it('logs configuration errors with proper context', function (): void {
        $configErrors = ['client_id' => 'Missing client ID'];

        $this->tokenManager->shouldReceive('validateConfiguration')
            ->once()
            ->andReturn($configErrors);

        Log::shouldReceive('error')
            ->once()
            ->with('Amazon SP-API configuration errors', $configErrors);

        $this->middleware->handle($this->request, $this->next);
    });

    it('logs token acquisition errors with proper context', function (): void {
        $this->tokenManager->shouldReceive('validateConfiguration')
            ->once()
            ->andReturn([]);

        $exception = new Exception('Specific error message');
        $this->tokenManager->shouldReceive('getAccessToken')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to obtain Amazon SP-API access token', [
                'error' => 'Specific error message',
            ]);

        $this->middleware->handle($this->request, $this->next);
    });
});
