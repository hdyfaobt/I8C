<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Salesforce REST API Configuration
    |--------------------------------------------------------------------------
    |
    | Uses OAuth 2.0 Client Credentials flow (server-to-server, no user
    | username/password involved — Salesforce's replacement for the
    | Username-Password flow, which is blocked by default on orgs created
    | Summer '23+ and unsupported on External Client Apps entirely).
    |
    | Setup → App Manager → External Client Apps → your app → Settings:
    |   - Enable OAuth Settings
    |   - Selected OAuth Scopes: "Access and manage your data (api)"
    |   - Enable Client Credentials Flow, set a "Run As" user
    |
    */

    'client_id' => env('SALESFORCE_CLIENT_ID'),
    'client_secret' => env('SALESFORCE_CLIENT_SECRET'),

    // Login URL: login.salesforce.com (production) or test.salesforce.com (sandbox)
    'login_url' => env('SALESFORCE_LOGIN_URL', 'https://login.salesforce.com'),

    // API version
    'api_version' => env('SALESFORCE_API_VERSION', '62.0'),
];
