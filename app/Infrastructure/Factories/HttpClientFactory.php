<?php

namespace App\Infrastructure\Factories;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Factory for creating configured HTTP clients.
 *
 * Centralizes HTTP client creation with retry logic, timeouts,
 * and common configuration to eliminate code duplication.
 */
class HttpClientFactory
{
    private int $timeout;
    private int $retryAttempts;
    private int $retryDelay;

    /**
     * Initialize factory with configuration.
     */
    public function __construct()
    {
        $this->timeout = (int) (config('API_TIMEOUT', 5000) / 1000); // Convert ms to seconds
        $this->retryAttempts = 3;
        $this->retryDelay = (int) config('RATE_LIMIT_DELAY', 200); // milliseconds
    }

    /**
     * Create a configured HTTP client with retry logic.
     *
     * @param array $headers Additional headers to include
     * @return PendingRequest
     */
    public function create(array $headers = []): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->retry($this->retryAttempts, $this->retryDelay)
            ->withHeaders(array_merge([
                'Accept' => 'application/json',
            ], $headers));
    }

    /**
     * Create HTTP client for JBEX API.
     *
     * @param string $apiKey JBEX API key (optional)
     * @return PendingRequest
     */
    public function createForJbex(string $apiKey = ''): PendingRequest
    {
        $headers = [];

        if (!empty($apiKey)) {
            $headers['X-BH-APIKEY'] = $apiKey;
        }

        return $this->create($headers);
    }
}
