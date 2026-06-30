<?php

use App\Services\SalesforceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

// ---------------------------------------------------------------------------
// SalesforceService — Unit-level feature tests
// All HTTP calls are faked: no real Salesforce connection is made.
// ---------------------------------------------------------------------------

beforeEach(function () {
    // Clear the cached token before every test
    Cache::forget('salesforce_access_token');
});

it('creates an opportunity and returns the salesforce id', function () {
    // Arrange: fake the OAuth token request + the Opportunity POST
    Http::fake([
        'login.salesforce.com/services/oauth2/token' => Http::response([
            'access_token' => 'fake-token-123',
            'instance_url' => 'https://test.salesforce.com',
        ], 200),

        'test.salesforce.com/services/data/*/sobjects/Opportunity' => Http::response([
            'id'      => '006TEST000001',
            'success' => true,
        ], 201),
    ]);

    $data = [
        'order_id' => 1,
        'product'  => 'Cloud Consulting',
        'total'    => 5000.00,
        'notes'    => 'First engagement',
    ];

    // Act
    $salesforceId = app(SalesforceService::class)->createOpportunity($data);

    // Assert
    expect($salesforceId)->toBe('006TEST000001');
});

it('caches the access token after the first request', function () {
    Http::fake([
        'login.salesforce.com/services/oauth2/token' => Http::response([
            'access_token' => 'cached-token',
            'instance_url' => 'https://test.salesforce.com',
        ], 200),

        'test.salesforce.com/services/data/*/sobjects/Opportunity' => Http::response([
            'id'      => '006CACHE000001',
            'success' => true,
        ], 201),
    ]);

    $data = ['order_id' => 2, 'product' => 'Support', 'total' => 1000.00, 'notes' => null];

    // Call twice — token endpoint should only be hit once
    app(SalesforceService::class)->createOpportunity($data);
    app(SalesforceService::class)->createOpportunity($data);

    Http::assertSentCount(3); // 1 token + 2 opportunity requests
});

it('throws a RuntimeException when salesforce returns an error', function () {
    Http::fake([
        'login.salesforce.com/services/oauth2/token' => Http::response([
            'access_token' => 'fake-token',
            'instance_url' => 'https://test.salesforce.com',
        ], 200),

        'test.salesforce.com/services/data/*/sobjects/Opportunity' => Http::response([
            'errorCode' => 'REQUIRED_FIELD_MISSING',
            'message'   => 'Required fields are missing',
        ], 400),
    ]);

    $data = ['order_id' => 3, 'product' => 'Bad Order', 'total' => 0, 'notes' => null];

    expect(fn () => app(SalesforceService::class)->createOpportunity($data))
        ->toThrow(\RuntimeException::class);
});

it('throws a RuntimeException when oauth authentication fails', function () {
    Http::fake([
        'login.salesforce.com/services/oauth2/token' => Http::response([
            'error' => 'invalid_client',
        ], 401),
    ]);

    $data = ['order_id' => 4, 'product' => 'Test', 'total' => 100, 'notes' => null];

    expect(fn () => app(SalesforceService::class)->createOpportunity($data))
        ->toThrow(\RuntimeException::class, '[Salesforce] Authentication failed');
});
