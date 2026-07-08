<?php

use App\Models\Customer;
use App\Models\Order;
use App\Services\SalesforceService;
use Illuminate\Support\Facades\Http;

// ---------------------------------------------------------------------------
// SalesforceService — tests with mocked HTTP responses
// ---------------------------------------------------------------------------

beforeEach(function () {
    Http::preventStrayRequests();
});

it('authenticates and returns a token', function () {
    Http::fake([
        'login.salesforce.com/services/oauth2/token' => Http::response([
            'access_token' => '00DXX0000000000!abc',
            'instance_url' => 'https://your-instance.salesforce.com',
        ]),
    ]);

    $service = new SalesforceService;

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('authenticate');
    $method->setAccessible(true);
    $method->invoke($service);

    expect($reflection->getProperty('accessToken')->getValue($service))->toBe('00DXX0000000000!abc');
    expect($reflection->getProperty('instanceUrl')->getValue($service))->toBe('https://your-instance.salesforce.com');
});

it('syncs an order by creating account and opportunity', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'pending',
    ]);

    Http::fake([
        'login.salesforce.com/services/oauth2/token' => Http::response([
            'access_token' => 'token123',
            'instance_url' => 'https://your-instance.salesforce.com',
        ]),
        'your-instance.salesforce.com/services/data/v*/query*' => Http::response([
            'totalSize' => 0,
            'records' => [],
        ]),
        'your-instance.salesforce.com/services/data/v*/sobjects/Account' => Http::response([
            'id' => '001XX0000000001AAA',
            'success' => true,
        ]),
        'your-instance.salesforce.com/services/data/v*/sobjects/Opportunity' => Http::response([
            'id' => '006XX0000000001AAA',
            'success' => true,
        ]),
    ]);

    $service = app(SalesforceService::class);

    $opportunityId = $service->syncOrder($order);

    expect($opportunityId)->toBe('006XX0000000001AAA');

    $customer->refresh();
    expect($customer->salesforce_id)->toBe('001XX0000000001AAA');

    $order->refresh();
    expect($order->salesforce_id)->toBe('006XX0000000001AAA');
});

it('returns null when authentication fails', function () {
    Http::fake([
        'login.salesforce.com/services/oauth2/token' => Http::response([
            'error' => 'invalid_grant',
        ], 400),
    ]);

    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);

    $service = app(SalesforceService::class);

    $result = $service->syncOrder($order);

    expect($result)->toBeNull();
});

it('reuses existing salesforce_id on customer when already synced', function () {
    $customer = Customer::factory()->create([
        'salesforce_id' => '001XX0000000002AAA',
    ]);
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'pending',
    ]);

    Http::fake([
        'login.salesforce.com/services/oauth2/token' => Http::response([
            'access_token' => 'token123',
            'instance_url' => 'https://your-instance.salesforce.com',
        ]),
        'your-instance.salesforce.com/services/data/v*/sobjects/Opportunity' => Http::response([
            'id' => '006XX0000000002AAA',
            'success' => true,
        ]),
    ]);

    $service = app(SalesforceService::class);

    $result = $service->syncOrder($order);

    expect($result)->toBe('006XX0000000002AAA');
    expect($order->fresh()->salesforce_id)->toBe('006XX0000000002AAA');
});

it('finds existing account by email before creating a new one', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'pending',
    ]);

    Http::fake([
        'login.salesforce.com/services/oauth2/token' => Http::response([
            'access_token' => 'token123',
            'instance_url' => 'https://your-instance.salesforce.com',
        ]),
        'your-instance.salesforce.com/services/data/v*/query*' => Http::response([
            'totalSize' => 1,
            'records' => [
                ['Id' => '001XX0000000003AAA'],
            ],
        ]),
        'your-instance.salesforce.com/services/data/v*/sobjects/Opportunity' => Http::response([
            'id' => '006XX0000000003AAA',
            'success' => true,
        ]),
    ]);

    $service = app(SalesforceService::class);

    $result = $service->syncOrder($order);

    expect($result)->toBe('006XX0000000003AAA');
    expect($customer->fresh()->salesforce_id)->toBe('001XX0000000003AAA');
});
