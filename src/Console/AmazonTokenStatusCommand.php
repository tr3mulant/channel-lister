<?php

namespace IGE\ChannelLister\Console;

use IGE\ChannelLister\Services\AmazonSpApiService;
use IGE\ChannelLister\Services\AmazonTokenManager;
use Illuminate\Console\Command;

class AmazonTokenStatusCommand extends Command
{
    protected $signature = 'channel-lister:amazon-token-status {--refresh : Force refresh the token}';

    protected $description = 'Check Amazon SP-API token status';

    public function __construct(
        protected AmazonTokenManager $tokenManager,
        protected AmazonSpApiService $amazonService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Amazon SP-API Token Status');
        $this->info('===========================');

        // Check configuration
        $configErrors = $this->amazonService->validateConfiguration();
        if ($configErrors !== []) {
            $this->error('Configuration errors:');
            foreach ($configErrors as $error) {
                $this->error("  - {$error}");
            }

            return self::FAILURE;
        }

        $this->info('✓ Configuration is valid');

        // Force refresh if requested
        if ($this->option('refresh')) {
            $this->info('Forcing token refresh...');
            $this->tokenManager->invalidateToken();

            $newToken = $this->tokenManager->refreshAccessToken();
            if ($newToken !== null && $newToken !== []) {
                $this->info('✓ Token refreshed successfully');
            } else {
                $this->error('✗ Failed to refresh token');

                return self::FAILURE;
            }
        }

        // Get token info
        $tokenInfo = $this->tokenManager->getTokenInfo();

        if ($tokenInfo === null || $tokenInfo === []) {
            $this->warn('No cached token found. Attempting to get new token...');

            try {
                $this->tokenManager->getAccessToken();
                $tokenInfo = $this->tokenManager->getTokenInfo();
            } catch (\Exception $e) {
                $this->error('Failed to obtain token: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        if ($tokenInfo !== null && $tokenInfo !== []) {
            $this->info('Token Information:');
            $this->info("  Obtained at: {$tokenInfo['obtained_at']}");
            $this->info("  Expires at: {$tokenInfo['expires_at']}");
            $this->info("  Expires in: {$tokenInfo['expires_in_seconds']} seconds");
            $this->info('  Valid: '.($tokenInfo['is_valid'] ? 'Yes' : 'No'));
            $this->info("  Type: {$tokenInfo['token_type']}");

            if (! $tokenInfo['is_valid']) {
                $this->warn('⚠ Token is expired or expiring soon');
            } else {
                $this->info('✓ Token is valid');
            }
        }

        // Test API connection
        $this->info('Testing API connection...');
        try {
            $productTypes = $this->amazonService->searchProductTypes('test');
            $this->info('✓ API connection successful');
            $this->info('  Found {'.count($productTypes)."} product types for 'test' query");
        } catch (\Exception $e) {
            $this->error('✗ API connection failed: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
