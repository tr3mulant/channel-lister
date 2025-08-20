<?php

namespace IGE\ChannelLister\Http\Middleware;

use Closure;
use IGE\ChannelLister\Services\AmazonTokenManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AmazonSpApiAuth
{
    public function __construct(
        protected AmazonTokenManager $tokenManager
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Validate Amazon SP-API configuration
        $configErrors = $this->tokenManager->validateConfiguration();
        if ($configErrors !== []) {
            Log::error('Amazon SP-API configuration errors', $configErrors);

            return response()->json([
                'error' => 'Amazon SP-API not properly configured',
                'details' => $configErrors,
            ], 500);
        }

        // Ensure we have a valid token
        try {
            $accessToken = $this->tokenManager->getAccessToken();

            // Add token to request for use in controllers
            $request->merge(['amazon_access_token' => $accessToken]);

        } catch (\Exception $e) {
            Log::error('Failed to obtain Amazon SP-API access token', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to authenticate with Amazon SP-API',
                'message' => 'Unable to obtain valid access token',
            ], 503);
        }

        return $next($request);
    }
}
