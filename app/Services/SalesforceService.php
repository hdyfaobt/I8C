<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SalesforceService
{
    private ?string $accessToken = null;

    private ?string $instanceUrl = null;

    /**
     * Authenticate with Salesforce using the OAuth 2.0 Client Credentials flow.
     * This is a server-to-server flow: only the Connected/External Client App's
     * Client ID and Secret are needed — no user username/password/security
     * token. The API acts as the "Run As" user configured on the app in
     * Salesforce (Setup → App → OAuth Settings → Client Credentials Flow).
     */
    public function authenticate(): void
    {
        $response = Http::asForm()->post(config('salesforce.login_url').'/services/oauth2/token', [
            'grant_type' => 'client_credentials',
            'client_id' => config('salesforce.client_id'),
            'client_secret' => config('salesforce.client_secret'),
        ]);

        if ($response->failed()) {
            Log::error('[Salesforce] Authentication failed: '.$response->body());
            throw new \RuntimeException('Salesforce authentication failed: '.$response->body());
        }

        $data = $response->json();

        $this->accessToken = $data['access_token'];
        $this->instanceUrl = $data['instance_url'];
    }

    /**
     * Sync an order to Salesforce: create/update Account, create Opportunity.
     * Returns the Salesforce Opportunity ID on success, null on failure.
     */
    public function syncOrder(Order $order): ?string
    {
        try {
            $this->authenticate();

            $customer = $order->customer;

            $accountId = $customer->salesforce_id
                ?? $this->findAccountByEmail($customer->email);

            if (! $accountId) {
                $accountId = $this->createAccount($customer);
            }

            if ($customer->salesforce_id !== $accountId) {
                $customer->update(['salesforce_id' => $accountId]);
            }

            $opportunityId = $this->createOpportunity($order, $accountId);

            $order->update(['salesforce_id' => $opportunityId]);

            Log::info("[Salesforce] Order #{$order->id} synced as Opportunity {$opportunityId}");

            return $opportunityId;

        } catch (\Exception $e) {
            Log::error("[Salesforce] Failed to sync order #{$order->id}: ".$e->getMessage());

            return null;
        }
    }

    private function findAccountByEmail(string $email): ?string
    {
        $response = $this->request('GET', '/services/data/v'.config('salesforce.api_version').'/query', [
            'q' => "SELECT Id FROM Account WHERE Email__c = '".str_replace("'", "\\'", $email)."' LIMIT 1",
        ]);

        $records = $response['records'] ?? [];

        return ! empty($records) ? $records[0]['Id'] : null;
    }

    private function createAccount(Customer $customer): string
    {
        $response = $this->request('POST', '/services/data/v'.config('salesforce.api_version').'/sobjects/Account', [
            'Name' => $customer->name,
            'Email__c' => $customer->email,
            'Phone' => $customer->phone,
            'Description' => 'Klant via I8C app — '.($customer->company ?? ''),
        ]);

        Log::info("[Salesforce] Account created: {$response['id']} for {$customer->name}");

        return $response['id'];
    }

    private function createOpportunity(Order $order, string $accountId): string
    {
        // Include the order number and customer name in the Opportunity name so it's
        // recognizable in Salesforce list views, without having to open the record.
        $opportunityName = "Bestelling #{$order->id} — {$order->customer->name} — {$order->product}";

        $response = $this->request('POST', '/services/data/v'.config('salesforce.api_version').'/sobjects/Opportunity', [
            'Name' => $opportunityName,
            'StageName' => 'Prospecting',
            'CloseDate' => now()->addDays(30)->format('Y-m-d'),
            'Amount' => $order->totalPrice(),
            'AccountId' => $accountId,
            'Description' => $order->notes ?? '',
        ]);

        Log::info("[Salesforce] Opportunity created: {$response['id']} for order #{$order->id}");

        return $response['id'];
    }

    private function request(string $method, string $path, ?array $data = null): array
    {
        $url = $this->instanceUrl.$path;

        $http = Http::withToken($this->accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->acceptJson();

        $response = $method === 'POST'
            ? $http->post($url, $data)
            : $http->get($url, $data);

        if ($response->failed()) {
            Log::error("[Salesforce] API error ({$method} {$path}): ".$response->body());
            throw new \RuntimeException('Salesforce API error: '.$response->body());
        }

        return $response->json();
    }
}
