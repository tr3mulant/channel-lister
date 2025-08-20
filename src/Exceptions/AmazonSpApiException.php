<?php

namespace IGE\ChannelLister\Exceptions;

use Exception;

class AmazonSpApiException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        /** @var array<string, mixed> */
        protected array $context = [],
        protected ?string $amazonErrorCode = null,
        protected ?string $amazonErrorType = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function getAmazonErrorCode(): ?string
    {
        return $this->amazonErrorCode;
    }

    public function getAmazonErrorType(): ?string
    {
        return $this->amazonErrorType;
    }

    /**
     * Create exception from Amazon SP-API error response.
     *
     * @param  array<string, mixed>  $response
     */
    public static function fromApiResponse(array $response, int $statusCode = 0): self
    {
        $message = 'Amazon SP-API request failed';
        $amazonErrorCode = null;
        $amazonErrorType = null;

        // Parse Amazon error format
        if (isset($response['errors']) && is_array($response['errors']) && $response['errors'] !== []) {
            $firstError = $response['errors'][0];
            $message = is_string($firstError['message'] ?? null) ? $firstError['message'] : $message;
            $amazonErrorCode = is_string($firstError['code'] ?? null) ? $firstError['code'] : null;
            $amazonErrorType = is_string($firstError['type'] ?? null) ? $firstError['type'] : null;
        } elseif (isset($response['error'])) {
            $errorDescription = $response['error_description'] ?? null;
            $error = $response['error'];
            $message = is_string($errorDescription) ? $errorDescription : (is_string($error) ? $error : $message);
            $amazonErrorCode = is_string($error) ? $error : null;
        }

        return new self(
            message: $message,
            code: $statusCode,
            context: $response,
            amazonErrorCode: $amazonErrorCode,
            amazonErrorType: $amazonErrorType
        );
    }

    /**
     * Check if this is a rate limiting error.
     */
    public function isRateLimitError(): bool
    {
        if ($this->getCode() === 429) {
            return true;
        }
        if ($this->amazonErrorCode === 'QuotaExceeded') {
            return true;
        }

        return str_contains(strtolower($this->getMessage()), 'rate limit');
    }

    /**
     * Check if this is an authentication error.
     */
    public function isAuthError(): bool
    {
        return in_array($this->getCode(), [401, 403]) ||
               in_array($this->amazonErrorCode, ['Unauthorized', 'AccessDenied', 'InvalidAccessKeyId']) ||
               str_contains(strtolower($this->getMessage()), 'unauthorized');
    }

    /**
     * Check if this is a temporary error that might be retried.
     */
    public function isRetryableError(): bool
    {
        if (in_array($this->getCode(), [429, 500, 502, 503, 504])) {
            return true;
        }
        if ($this->isRateLimitError()) {
            return true;
        }

        return in_array($this->amazonErrorType, ['InternalFailure', 'ServiceUnavailable']);
    }

    /**
     * Get user-friendly error message.
     */
    public function getUserMessage(): string
    {
        if ($this->isRateLimitError()) {
            return 'Amazon API rate limit exceeded. Please try again later.';
        }

        if ($this->isAuthError()) {
            return 'Authentication with Amazon failed. Please check your API credentials.';
        }

        if ($this->amazonErrorCode === 'InvalidParameterValue') {
            return 'Invalid parameter provided to Amazon API.';
        }

        if ($this->amazonErrorCode === 'ResourceNotFound') {
            return 'The requested resource was not found on Amazon.';
        }

        return 'An error occurred while communicating with Amazon. Please try again.';
    }
}
