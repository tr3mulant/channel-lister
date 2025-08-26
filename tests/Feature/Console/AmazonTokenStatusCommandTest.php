<?php

declare(strict_types=1);

use IGE\ChannelLister\Console\AmazonTokenStatusCommand;
use IGE\ChannelLister\Services\AmazonSpApiService;
use IGE\ChannelLister\Services\AmazonTokenManager;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;

describe('AmazonTokenStatusCommand', function (): void {
    beforeEach(function (): void {
        // Mock the services
        $this->tokenManagerMock = Mockery::mock(AmazonTokenManager::class);
        $this->amazonServiceMock = Mockery::mock(AmazonSpApiService::class);

        // Bind mocks to container
        $this->app->bind(AmazonTokenManager::class, fn () => $this->tokenManagerMock);
        $this->app->bind(AmazonSpApiService::class, fn () => $this->amazonServiceMock);
    });

    afterEach(function (): void {
        Mockery::close();
    });

    it('is registered as console command', function (): void {
        expect(
            collect($this->app[Kernel::class]->all())
                ->has('channel-lister:amazon-token-status')
        )->toBeTrue();
    });

    it('has correct signature and description', function (): void {
        $command = new AmazonTokenStatusCommand($this->tokenManagerMock, $this->amazonServiceMock);

        expect($command->getName())->toBe('channel-lister:amazon-token-status');
        expect($command->getDescription())->toBe('Check Amazon SP-API token status');
    });

    describe('handle() method', function (): void {
        describe('configuration validation', function (): void {
            it('returns failure when configuration is invalid', function (): void {
                $this->amazonServiceMock
                    ->shouldReceive('validateConfiguration')
                    ->once()
                    ->andReturn(['Missing client_id', 'Invalid region']);

                $this->artisan('channel-lister:amazon-token-status')
                    ->expectsOutput('Amazon SP-API Token Status')
                    ->expectsOutput('===========================')
                    ->expectsOutput('Configuration errors:')
                    ->expectsOutput('  - Missing client_id')
                    ->expectsOutput('  - Invalid region')
                    ->assertExitCode(Command::FAILURE);
            });

            it('continues when configuration is valid', function (): void {
                $this->amazonServiceMock
                    ->shouldReceive('validateConfiguration')
                    ->once()
                    ->andReturn([]);

                // Mock the rest of the flow to complete successfully
                $this->tokenManagerMock
                    ->shouldReceive('getTokenInfo')
                    ->once()
                    ->andReturn([
                        'obtained_at' => '2025-01-01 12:00:00',
                        'expires_at' => '2025-01-01 13:00:00',
                        'expires_in_seconds' => 3600,
                        'is_valid' => true,
                        'token_type' => 'bearer',
                    ]);

                $this->amazonServiceMock
                    ->shouldReceive('searchProductTypes')
                    ->with('test')
                    ->once()
                    ->andReturn(['PRODUCT_TYPE_1', 'PRODUCT_TYPE_2']);

                $this->artisan('channel-lister:amazon-token-status')
                    ->expectsOutput('✓ Configuration is valid')
                    ->assertExitCode(Command::SUCCESS);
            });
        });

        describe('token refresh option', function (): void {
            it('refreshes token when --refresh option is provided', function (): void {
                $this->amazonServiceMock
                    ->shouldReceive('validateConfiguration')
                    ->once()
                    ->andReturn([]);

                $this->tokenManagerMock
                    ->shouldReceive('invalidateToken')
                    ->once();

                $this->tokenManagerMock
                    ->shouldReceive('refreshAccessToken')
                    ->once()
                    ->andReturn(['access_token' => 'new_token']);

                $this->tokenManagerMock
                    ->shouldReceive('getTokenInfo')
                    ->once()
                    ->andReturn([
                        'obtained_at' => '2025-01-01 12:00:00',
                        'expires_at' => '2025-01-01 13:00:00',
                        'expires_in_seconds' => 3600,
                        'is_valid' => true,
                        'token_type' => 'bearer',
                    ]);

                $this->amazonServiceMock
                    ->shouldReceive('searchProductTypes')
                    ->with('test')
                    ->once()
                    ->andReturn(['PRODUCT_TYPE_1']);

                $this->artisan('channel-lister:amazon-token-status --refresh')
                    ->expectsOutput('Forcing token refresh...')
                    ->expectsOutput('✓ Token refreshed successfully')
                    ->assertExitCode(Command::SUCCESS);
            });

            it('returns failure when token refresh fails', function (): void {
                $this->amazonServiceMock
                    ->shouldReceive('validateConfiguration')
                    ->once()
                    ->andReturn([]);

                $this->tokenManagerMock
                    ->shouldReceive('invalidateToken')
                    ->once();

                $this->tokenManagerMock
                    ->shouldReceive('refreshAccessToken')
                    ->once()
                    ->andReturn(null);

                $this->artisan('channel-lister:amazon-token-status --refresh')
                    ->expectsOutput('Forcing token refresh...')
                    ->expectsOutput('✗ Failed to refresh token')
                    ->assertExitCode(Command::FAILURE);
            });

            it('returns failure when token refresh returns empty array', function (): void {
                $this->amazonServiceMock
                    ->shouldReceive('validateConfiguration')
                    ->once()
                    ->andReturn([]);

                $this->tokenManagerMock
                    ->shouldReceive('invalidateToken')
                    ->once();

                $this->tokenManagerMock
                    ->shouldReceive('refreshAccessToken')
                    ->once()
                    ->andReturn([]);

                $this->artisan('channel-lister:amazon-token-status --refresh')
                    ->expectsOutput('Forcing token refresh...')
                    ->expectsOutput('✗ Failed to refresh token')
                    ->assertExitCode(Command::FAILURE);
            });
        });

        describe('token information display', function (): void {
            it('displays token information when token exists', function (): void {
                $this->amazonServiceMock
                    ->shouldReceive('validateConfiguration')
                    ->once()
                    ->andReturn([]);

                $tokenInfo = [
                    'obtained_at' => '2025-01-01 12:00:00',
                    'expires_at' => '2025-01-01 13:00:00',
                    'expires_in_seconds' => 3600,
                    'is_valid' => true,
                    'token_type' => 'bearer',
                ];

                $this->tokenManagerMock
                    ->shouldReceive('getTokenInfo')
                    ->once()
                    ->andReturn($tokenInfo);

                $this->amazonServiceMock
                    ->shouldReceive('searchProductTypes')
                    ->with('test')
                    ->once()
                    ->andReturn(['PRODUCT_TYPE_1', 'PRODUCT_TYPE_2']);

                $this->artisan('channel-lister:amazon-token-status')
                    ->expectsOutput('Token Information:')
                    ->expectsOutput('  Obtained at: 2025-01-01 12:00:00')
                    ->expectsOutput('  Expires at: 2025-01-01 13:00:00')
                    ->expectsOutput('  Expires in: 3600 seconds')
                    ->expectsOutput('  Valid: Yes')
                    ->expectsOutput('  Type: bearer')
                    ->expectsOutput('✓ Token is valid')
                    ->assertExitCode(Command::SUCCESS);
            });

            it('shows warning when token is expired', function (): void {
                $this->amazonServiceMock
                    ->shouldReceive('validateConfiguration')
                    ->once()
                    ->andReturn([]);

                $tokenInfo = [
                    'obtained_at' => '2025-01-01 10:00:00',
                    'expires_at' => '2025-01-01 11:00:00',
                    'expires_in_seconds' => -3600,
                    'is_valid' => false,
                    'token_type' => 'bearer',
                ];

                $this->tokenManagerMock
                    ->shouldReceive('getTokenInfo')
                    ->once()
                    ->andReturn($tokenInfo);

                $this->amazonServiceMock
                    ->shouldReceive('searchProductTypes')
                    ->with('test')
                    ->once()
                    ->andReturn([]);

                $this->artisan('channel-lister:amazon-token-status')
                    ->expectsOutput('Token Information:')
                    ->expectsOutput('  Valid: No')
                    ->expectsOutput('⚠ Token is expired or expiring soon')
                    ->assertExitCode(Command::SUCCESS);
            });

            it('attempts to get new token when none cached', function (): void {
                $this->amazonServiceMock
                    ->shouldReceive('validateConfiguration')
                    ->once()
                    ->andReturn([]);

                $this->tokenManagerMock
                    ->shouldReceive('getTokenInfo')
                    ->once()
                    ->andReturn(null);

                $this->tokenManagerMock
                    ->shouldReceive('getAccessToken')
                    ->once()
                    ->andReturn('new_access_token');

                $this->tokenManagerMock
                    ->shouldReceive('getTokenInfo')
                    ->once()
                    ->andReturn([
                        'obtained_at' => '2025-01-01 12:00:00',
                        'expires_at' => '2025-01-01 13:00:00',
                        'expires_in_seconds' => 3600,
                        'is_valid' => true,
                        'token_type' => 'bearer',
                    ]);

                $this->amazonServiceMock
                    ->shouldReceive('searchProductTypes')
                    ->with('test')
                    ->once()
                    ->andReturn(['PRODUCT_TYPE_1']);

                $this->artisan('channel-lister:amazon-token-status')
                    ->expectsOutput('No cached token found. Attempting to get new token...')
                    ->assertExitCode(Command::SUCCESS);
            });

            it('attempts to get new token when empty array cached', function (): void {
                $this->amazonServiceMock
                    ->shouldReceive('validateConfiguration')
                    ->once()
                    ->andReturn([]);

                $this->tokenManagerMock
                    ->shouldReceive('getTokenInfo')
                    ->once()
                    ->andReturn([]);

                $this->tokenManagerMock
                    ->shouldReceive('getAccessToken')
                    ->once()
                    ->andReturn('new_access_token');

                $this->tokenManagerMock
                    ->shouldReceive('getTokenInfo')
                    ->once()
                    ->andReturn([
                        'obtained_at' => '2025-01-01 12:00:00',
                        'expires_at' => '2025-01-01 13:00:00',
                        'expires_in_seconds' => 3600,
                        'is_valid' => true,
                        'token_type' => 'bearer',
                    ]);

                $this->amazonServiceMock
                    ->shouldReceive('searchProductTypes')
                    ->with('test')
                    ->once()
                    ->andReturn(['PRODUCT_TYPE_1']);

                $this->artisan('channel-lister:amazon-token-status')
                    ->expectsOutput('No cached token found. Attempting to get new token...')
                    ->assertExitCode(Command::SUCCESS);
            });

            it('returns failure when token acquisition fails', function (): void {
                $this->amazonServiceMock
                    ->shouldReceive('validateConfiguration')
                    ->once()
                    ->andReturn([]);

                $this->tokenManagerMock
                    ->shouldReceive('getTokenInfo')
                    ->once()
                    ->andReturn(null);

                $this->tokenManagerMock
                    ->shouldReceive('getAccessToken')
                    ->once()
                    ->andThrow(new Exception('Token acquisition failed'));

                $this->artisan('channel-lister:amazon-token-status')
                    ->expectsOutput('No cached token found. Attempting to get new token...')
                    ->expectsOutput('Failed to obtain token: Token acquisition failed')
                    ->assertExitCode(Command::FAILURE);
            });
        });

        describe('API connection testing', function (): void {
            it('tests API connection successfully', function (): void {
                $this->amazonServiceMock
                    ->shouldReceive('validateConfiguration')
                    ->once()
                    ->andReturn([]);

                $this->tokenManagerMock
                    ->shouldReceive('getTokenInfo')
                    ->once()
                    ->andReturn([
                        'obtained_at' => '2025-01-01 12:00:00',
                        'expires_at' => '2025-01-01 13:00:00',
                        'expires_in_seconds' => 3600,
                        'is_valid' => true,
                        'token_type' => 'bearer',
                    ]);

                $productTypes = ['LUGGAGE', 'ELECTRONICS', 'BOOKS'];
                $this->amazonServiceMock
                    ->shouldReceive('searchProductTypes')
                    ->with('test')
                    ->once()
                    ->andReturn($productTypes);

                $this->artisan('channel-lister:amazon-token-status')
                    ->expectsOutput('Testing API connection...')
                    ->expectsOutput('✓ API connection successful')
                    ->expectsOutput('  Found {3} product types for \'test\' query')
                    ->assertExitCode(Command::SUCCESS);
            });

            it('handles API connection failure', function (): void {
                $this->amazonServiceMock
                    ->shouldReceive('validateConfiguration')
                    ->once()
                    ->andReturn([]);

                $this->tokenManagerMock
                    ->shouldReceive('getTokenInfo')
                    ->once()
                    ->andReturn([
                        'obtained_at' => '2025-01-01 12:00:00',
                        'expires_at' => '2025-01-01 13:00:00',
                        'expires_in_seconds' => 3600,
                        'is_valid' => true,
                        'token_type' => 'bearer',
                    ]);

                $this->amazonServiceMock
                    ->shouldReceive('searchProductTypes')
                    ->with('test')
                    ->once()
                    ->andThrow(new Exception('API connection failed'));

                $this->artisan('channel-lister:amazon-token-status')
                    ->expectsOutput('Testing API connection...')
                    ->expectsOutput('✗ API connection failed: API connection failed')
                    ->assertExitCode(Command::FAILURE);
            });

            it('reports zero product types found', function (): void {
                $this->amazonServiceMock
                    ->shouldReceive('validateConfiguration')
                    ->once()
                    ->andReturn([]);

                $this->tokenManagerMock
                    ->shouldReceive('getTokenInfo')
                    ->once()
                    ->andReturn([
                        'obtained_at' => '2025-01-01 12:00:00',
                        'expires_at' => '2025-01-01 13:00:00',
                        'expires_in_seconds' => 3600,
                        'is_valid' => true,
                        'token_type' => 'bearer',
                    ]);

                $this->amazonServiceMock
                    ->shouldReceive('searchProductTypes')
                    ->with('test')
                    ->once()
                    ->andReturn([]);

                $this->artisan('channel-lister:amazon-token-status')
                    ->expectsOutput('Testing API connection...')
                    ->expectsOutput('✓ API connection successful')
                    ->expectsOutput('  Found {0} product types for \'test\' query')
                    ->assertExitCode(Command::SUCCESS);
            });
        });

        describe('complete flows', function (): void {
            it('completes full successful flow without refresh', function (): void {
                $this->amazonServiceMock
                    ->shouldReceive('validateConfiguration')
                    ->once()
                    ->andReturn([]);

                $this->tokenManagerMock
                    ->shouldReceive('getTokenInfo')
                    ->once()
                    ->andReturn([
                        'obtained_at' => '2025-01-01 12:00:00',
                        'expires_at' => '2025-01-01 13:00:00',
                        'expires_in_seconds' => 3600,
                        'is_valid' => true,
                        'token_type' => 'bearer',
                    ]);

                $this->amazonServiceMock
                    ->shouldReceive('searchProductTypes')
                    ->with('test')
                    ->once()
                    ->andReturn(['PRODUCT_TYPE_1']);

                $this->artisan('channel-lister:amazon-token-status')
                    ->expectsOutput('Amazon SP-API Token Status')
                    ->expectsOutput('===========================')
                    ->expectsOutput('✓ Configuration is valid')
                    ->expectsOutput('Token Information:')
                    ->expectsOutput('✓ Token is valid')
                    ->expectsOutput('Testing API connection...')
                    ->expectsOutput('✓ API connection successful')
                    ->assertExitCode(Command::SUCCESS);
            });

            it('completes full successful flow with refresh', function (): void {
                $this->amazonServiceMock
                    ->shouldReceive('validateConfiguration')
                    ->once()
                    ->andReturn([]);

                $this->tokenManagerMock
                    ->shouldReceive('invalidateToken')
                    ->once();

                $this->tokenManagerMock
                    ->shouldReceive('refreshAccessToken')
                    ->once()
                    ->andReturn(['access_token' => 'refreshed_token']);

                $this->tokenManagerMock
                    ->shouldReceive('getTokenInfo')
                    ->once()
                    ->andReturn([
                        'obtained_at' => '2025-01-01 12:00:00',
                        'expires_at' => '2025-01-01 13:00:00',
                        'expires_in_seconds' => 3600,
                        'is_valid' => true,
                        'token_type' => 'bearer',
                    ]);

                $this->amazonServiceMock
                    ->shouldReceive('searchProductTypes')
                    ->with('test')
                    ->once()
                    ->andReturn(['PRODUCT_TYPE_1']);

                $this->artisan('channel-lister:amazon-token-status --refresh')
                    ->expectsOutput('Amazon SP-API Token Status')
                    ->expectsOutput('===========================')
                    ->expectsOutput('✓ Configuration is valid')
                    ->expectsOutput('Forcing token refresh...')
                    ->expectsOutput('✓ Token refreshed successfully')
                    ->expectsOutput('Token Information:')
                    ->expectsOutput('✓ Token is valid')
                    ->expectsOutput('Testing API connection...')
                    ->expectsOutput('✓ API connection successful')
                    ->assertExitCode(Command::SUCCESS);
            });
        });
    });
});
