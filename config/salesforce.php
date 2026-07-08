<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Salesforce REST API Configuration
    |--------------------------------------------------------------------------
    |
    | Uses OAuth 2.0 Username-Password flow for server-to-server integration.
    | Create a Connected App in Salesforce to get your Consumer Key & Secret.
    |
    | Settings → App Manager → New Connected App
    |   - Enable OAuth Settings
    |   - Enable "Use Digital Signatures" (optional)
    |   - Selected OAuth Scopes: "Access and manage your data (api)"
    |
    */

    'client_id' => env('SALESFORCE_CLIENT_ID'),
    'client_secret' => env('SALESFORCE_CLIENT_SECRET'),
    'username' => env('SALESFORCE_USERNAME'),
    'password' => env('SALESFORCE_PASSWORD'),

    // Login URL: login.salesforce.com (production) or test.salesforce.com (sandbox)
    'login_url' => env('SALESFORCE_LOGIN_URL', 'https://login.salesforce.com'),

    // API version
    'api_version' => env('SALESFORCE_API_VERSION', '62.0'),
];
