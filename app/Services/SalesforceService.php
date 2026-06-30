<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SalesforceService
{
    /**
     * Base URL of the Salesforce instance — set after OAuth token exchange.
     * Example: https://mycompany.my.salesforce.com
     */
    private string $instanceUrl;

    /**
     * Create a Salesforce Opportunity from an order payload.
     * Called by ConsumeOrders after receiving a message from RabbitMQ.
     *
     * @param array $data  Decoded order payload from RabbitMQ
     * @return string      The Salesforce Opportunity ID (e.g. "006...")
     *
     * @throws \RuntimeException  If the Salesforce API call fails
     */
    public function createOpportunity(array $data): string
    {
        $token = $this->getAccessToken();

        // Map order data to Salesforce Opportunity fields
        $payload = [
            'Name'        => "Order #{$data['order_id']} — {$data['product']}",
            'StageName'   => 'Prospecting',
            'CloseDate'   => now()->addDays(30)->toDateString(), // Required by Salesforce
            'Amount'      => $data['total'],
            'Description' => $data['notes'] ?? '',
        ];

        $response = Http::withToken($token)
            ->post("{$this->instanceUrl}/services/data/" . config('salesforce.api_version') . '/sobjects/Opportunity', $payload);

        if (! $response->successful()) {
            Log::error('[Salesforce] Failed to create Opportunity', [
                'order_id' => $data['order_id'],
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);

            throw new \RuntimeException(
                "[Salesforce] Opportunity creation failed for order #{$data['order_id']}: " . $response->body()
            );
        }

        $salesforceId = $response->json('id');

        Log::info("[Salesforce] Opportunity created for order #{$data['order_id']}: {$salesforceId}");

        return $salesforceId;
    }

    /**
     * Get a valid OAuth2 access token.
     * Token is cached for 1 hour to avoid repeated auth requests.
     */
    private function getAccessToken(): string
    {
        return Cache::remember('salesforce_access_token', now()->addHour(), function () {
            return $this->fetchNewToken();
        });
    }

    /**
     * Request a new OAuth2 token from Salesforce using Client Credentials flow.
     * Stores the instance URL as a side effect.
     *
     * @return string  The access token
     * @throws \RuntimeException  If authentication fails
     */
    private function fetchNewToken(): string
    {
        $response = Http::asForm()->post(config('salesforce.login_url') . '/services/oauth2/token', [
            'grant_type'    => 'client_credentials',
            'client_id'     => config('salesforce.client_id'),
            'client_secret' => config('salesforce.client_secret'),
        ]);

        if (! $response->successful()) {
            Log::error('[Salesforce] OAuth2 token request failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new \RuntimeException('[Salesforce] Authentication failed: ' . $response->body());
        }

        // Store the instance URL returned by Salesforce (needed for all API calls)
        $this->instanceUrl = $response->json('instance_url');

        Log::info('[Salesforce] New access token obtained. Instance: ' . $this->instanceUrl);

        return $response->json('access_token');
    }
}
