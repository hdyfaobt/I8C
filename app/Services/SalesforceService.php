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

    // OAuth client credentials auth
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

    // Sync order to Salesforce
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

    // Sync customer as Account
    public function syncCustomer(Customer $customer): ?string
    {
        try {
            $this->authenticate();

            $accountId = $customer->salesforce_id
                ?? $this->findAccountByEmail($customer->email)
                ?? $this->createAccount($customer);

            if ($customer->salesforce_id !== $accountId) {
                $customer->update(['salesforce_id' => $accountId]);
            }

            Log::info("[Salesforce] Customer #{$customer->id} synced as Account {$accountId}");

            return $accountId;

        } catch (\Exception $e) {
            Log::error("[Salesforce] Failed to sync customer #{$customer->id}: ".$e->getMessage());

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
        // Recognizable Opportunity name
        $firstProduct = $order->items->first()?->product ?? 'Bestelling';
        $extraItemsCount = max($order->items->count() - 1, 0);
        $productLabel = $firstProduct.($extraItemsCount > 0 ? " (+{$extraItemsCount})" : '');

        $opportunityName = "Bestelling #{$order->id} — {$order->customer->name} — {$productLabel}";

        // One line per product
        $itemLines = $order->items->map(
            fn ($item) => "{$item->quantity}x {$item->product} — € ".number_format($item->unit_price, 2)
        )->implode("\n");

        $description = trim($itemLines."\n\n".($order->notes ?? ''));

        $response = $this->request('POST', '/services/data/v'.config('salesforce.api_version').'/sobjects/Opportunity', [
            'Name' => $opportunityName,
            'StageName' => 'Prospecting',
            'CloseDate' => now()->addDays(30)->format('Y-m-d'),
            'Amount' => $order->totalPrice(),
            'AccountId' => $accountId,
            'Description' => $description,
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
