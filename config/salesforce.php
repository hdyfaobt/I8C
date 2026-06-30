<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Salesforce API Configuration
    |--------------------------------------------------------------------------
    | All values come from .env — never hardcode credentials here.
    | Uses OAuth2 Client Credentials flow (Connected App).
    */

    // Salesforce login endpoint (production or sandbox)
    'login_url'     => env('SALESFORCE_LOGIN_URL', 'https://login.salesforce.com'),

    // Connected App credentials
    'client_id'     => env('SALESFORCE_CLIENT_ID'),
    'client_secret' => env('SALESFORCE_CLIENT_SECRET'),

    // Salesforce REST API version
    'api_version'   => env('SALESFORCE_API_VERSION', 'v60.0'),
];
