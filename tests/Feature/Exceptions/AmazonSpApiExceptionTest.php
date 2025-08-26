<?php

use IGE\ChannelLister\Exceptions\AmazonSpApiException;

describe('AmazonSpApiException', function (): void {
    it('extends Exception', function (): void {
        $exception = new AmazonSpApiException;
        expect($exception)->toBeInstanceOf(Exception::class);
    });

    it('can be created with basic parameters', function (): void {
        $exception = new AmazonSpApiException('Test message', 500);

        expect($exception->getMessage())->toBe('Test message');
        expect($exception->getCode())->toBe(500);
        expect($exception->getContext())->toBeArray();
        expect($exception->getContext())->toBeEmpty();
        expect($exception->getAmazonErrorCode())->toBeNull();
        expect($exception->getAmazonErrorType())->toBeNull();
    });

    it('can be created with all parameters', function (): void {
        $context = ['request_id' => '123', 'url' => 'https://example.com'];
        $previousException = new Exception('Previous error');

        $exception = new AmazonSpApiException(
            message: 'API Error',
            code: 400,
            previous: $previousException,
            context: $context,
            amazonErrorCode: 'InvalidRequest',
            amazonErrorType: 'Sender'
        );

        expect($exception->getMessage())->toBe('API Error');
        expect($exception->getCode())->toBe(400);
        expect($exception->getPrevious())->toBe($previousException);
        expect($exception->getContext())->toBe($context);
        expect($exception->getAmazonErrorCode())->toBe('InvalidRequest');
        expect($exception->getAmazonErrorType())->toBe('Sender');
    });
});

describe('fromApiResponse factory method', function (): void {
    it('creates exception from Amazon errors array format', function (): void {
        $response = [
            'errors' => [
                [
                    'message' => 'Invalid marketplace ID',
                    'code' => 'InvalidParameterValue',
                    'type' => 'Sender',
                ],
            ],
        ];

        $exception = AmazonSpApiException::fromApiResponse($response, 400);

        expect($exception->getMessage())->toBe('Invalid marketplace ID');
        expect($exception->getCode())->toBe(400);
        expect($exception->getContext())->toBe($response);
        expect($exception->getAmazonErrorCode())->toBe('InvalidParameterValue');
        expect($exception->getAmazonErrorType())->toBe('Sender');
    });

    it('creates exception from OAuth error format', function (): void {
        $response = [
            'error' => 'invalid_grant',
            'error_description' => 'The provided authorization code is invalid',
        ];

        $exception = AmazonSpApiException::fromApiResponse($response, 401);

        expect($exception->getMessage())->toBe('The provided authorization code is invalid');
        expect($exception->getCode())->toBe(401);
        expect($exception->getContext())->toBe($response);
        expect($exception->getAmazonErrorCode())->toBe('invalid_grant');
        expect($exception->getAmazonErrorType())->toBeNull();
    });

    it('handles OAuth error without description', function (): void {
        $response = [
            'error' => 'invalid_client',
        ];

        $exception = AmazonSpApiException::fromApiResponse($response);

        expect($exception->getMessage())->toBe('invalid_client');
        expect($exception->getAmazonErrorCode())->toBe('invalid_client');
    });

    it('handles empty errors array', function (): void {
        $response = [
            'errors' => [],
        ];

        $exception = AmazonSpApiException::fromApiResponse($response, 500);

        expect($exception->getMessage())->toBe('Amazon SP-API request failed');
        expect($exception->getCode())->toBe(500);
        expect($exception->getAmazonErrorCode())->toBeNull();
        expect($exception->getAmazonErrorType())->toBeNull();
    });

    it('handles response without recognized error format', function (): void {
        $response = [
            'some_other_field' => 'value',
        ];

        $exception = AmazonSpApiException::fromApiResponse($response);

        expect($exception->getMessage())->toBe('Amazon SP-API request failed');
        expect($exception->getCode())->toBe(0);
        expect($exception->getContext())->toBe($response);
    });

    it('handles non-string error values safely', function (): void {
        $response = [
            'errors' => [
                [
                    'message' => ['not_a_string'],
                    'code' => 123,
                    'type' => true,
                ],
            ],
        ];

        $exception = AmazonSpApiException::fromApiResponse($response);

        expect($exception->getMessage())->toBe('Amazon SP-API request failed');
        expect($exception->getAmazonErrorCode())->toBeNull();
        expect($exception->getAmazonErrorType())->toBeNull();
    });

    it('handles non-string OAuth error values safely', function (): void {
        $response = [
            'error' => 123,
            'error_description' => ['not_a_string'],
        ];

        $exception = AmazonSpApiException::fromApiResponse($response);

        expect($exception->getMessage())->toBe('Amazon SP-API request failed');
        expect($exception->getAmazonErrorCode())->toBeNull();
    });
});

describe('Rate limit detection', function (): void {
    it('detects rate limit from HTTP status code', function (): void {
        $exception = new AmazonSpApiException('Rate limited', 429);
        expect($exception->isRateLimitError())->toBeTrue();
    });

    it('detects rate limit from Amazon error code', function (): void {
        $exception = new AmazonSpApiException(
            message: 'Quota exceeded',
            amazonErrorCode: 'QuotaExceeded'
        );
        expect($exception->isRateLimitError())->toBeTrue();
    });

    it('detects rate limit from message content', function (): void {
        $exception = new AmazonSpApiException('Request rate limit exceeded');
        expect($exception->isRateLimitError())->toBeTrue();

        $exception2 = new AmazonSpApiException('RATE LIMIT reached');
        expect($exception2->isRateLimitError())->toBeTrue();
    });

    it('returns false for non-rate-limit errors', function (): void {
        $exception = new AmazonSpApiException('Invalid parameter', 400);
        expect($exception->isRateLimitError())->toBeFalse();
    });
});

describe('Authentication error detection', function (): void {
    it('detects auth errors from HTTP status codes', function (): void {
        $exception401 = new AmazonSpApiException('Unauthorized', 401);
        expect($exception401->isAuthError())->toBeTrue();

        $exception403 = new AmazonSpApiException('Forbidden', 403);
        expect($exception403->isAuthError())->toBeTrue();
    });

    it('detects auth errors from Amazon error codes', function (): void {
        $unauthorized = new AmazonSpApiException(
            amazonErrorCode: 'Unauthorized'
        );
        expect($unauthorized->isAuthError())->toBeTrue();

        $accessDenied = new AmazonSpApiException(
            amazonErrorCode: 'AccessDenied'
        );
        expect($accessDenied->isAuthError())->toBeTrue();

        $invalidKey = new AmazonSpApiException(
            amazonErrorCode: 'InvalidAccessKeyId'
        );
        expect($invalidKey->isAuthError())->toBeTrue();
    });

    it('detects auth errors from message content', function (): void {
        $exception = new AmazonSpApiException('Request is unauthorized');
        expect($exception->isAuthError())->toBeTrue();

        $exception2 = new AmazonSpApiException('UNAUTHORIZED access');
        expect($exception2->isAuthError())->toBeTrue();
    });

    it('returns false for non-auth errors', function (): void {
        $exception = new AmazonSpApiException('Invalid parameter', 400);
        expect($exception->isAuthError())->toBeFalse();
    });
});

describe('Retryable error detection', function (): void {
    it('detects retryable errors from HTTP status codes', function (): void {
        $codes = [429, 500, 502, 503, 504];

        foreach ($codes as $code) {
            $exception = new AmazonSpApiException('Server error', $code);
            expect($exception->isRetryableError())->toBeTrue();
        }
    });

    it('detects retryable errors from rate limiting', function (): void {
        $exception = new AmazonSpApiException('Rate limited', 429);
        expect($exception->isRetryableError())->toBeTrue();
    });

    it('detects retryable errors from Amazon error types', function (): void {
        $internalFailure = new AmazonSpApiException(
            amazonErrorType: 'InternalFailure'
        );
        expect($internalFailure->isRetryableError())->toBeTrue();

        $serviceUnavailable = new AmazonSpApiException(
            amazonErrorType: 'ServiceUnavailable'
        );
        expect($serviceUnavailable->isRetryableError())->toBeTrue();
    });

    it('returns false for non-retryable errors', function (): void {
        $exception = new AmazonSpApiException('Invalid parameter', 400);
        expect($exception->isRetryableError())->toBeFalse();
    });
});

describe('User-friendly messages', function (): void {
    it('provides rate limit message', function (): void {
        $exception = new AmazonSpApiException('Rate limited', 429);
        expect($exception->getUserMessage())->toBe('Amazon API rate limit exceeded. Please try again later.');
    });

    it('provides authentication error message', function (): void {
        $exception = new AmazonSpApiException('Unauthorized', 401);
        expect($exception->getUserMessage())->toBe('Authentication with Amazon failed. Please check your API credentials.');
    });

    it('provides invalid parameter message', function (): void {
        $exception = new AmazonSpApiException(
            amazonErrorCode: 'InvalidParameterValue'
        );
        expect($exception->getUserMessage())->toBe('Invalid parameter provided to Amazon API.');
    });

    it('provides resource not found message', function (): void {
        $exception = new AmazonSpApiException(
            amazonErrorCode: 'ResourceNotFound'
        );
        expect($exception->getUserMessage())->toBe('The requested resource was not found on Amazon.');
    });

    it('provides generic message for unknown errors', function (): void {
        $exception = new AmazonSpApiException('Unknown error', 500);
        expect($exception->getUserMessage())->toBe('An error occurred while communicating with Amazon. Please try again.');
    });

    it('prioritizes rate limit message over other conditions', function (): void {
        $exception = new AmazonSpApiException(
            message: 'Rate limit exceeded',
            amazonErrorCode: 'InvalidParameterValue'
        );
        expect($exception->getUserMessage())->toBe('Amazon API rate limit exceeded. Please try again later.');
    });

    it('prioritizes auth message over parameter errors', function (): void {
        $exception = new AmazonSpApiException(
            message: 'Unauthorized access',
            code: 401,
            amazonErrorCode: 'InvalidParameterValue'
        );
        expect($exception->getUserMessage())->toBe('Authentication with Amazon failed. Please check your API credentials.');
    });
});
