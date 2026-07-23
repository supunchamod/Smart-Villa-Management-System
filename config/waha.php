<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WAHA Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL of your self-hosted WAHA (WhatsApp HTTP API) instance.
    |
    */

    'base_url' => env('WAHA_BASE_URL', 'http://localhost:3000'),

    /*
    |--------------------------------------------------------------------------
    | WAHA API Key
    |--------------------------------------------------------------------------
    |
    | Optional API key used to authenticate against the WAHA instance via
    | the `X-Api-Key` header. Leave unset if your instance does not
    | require authentication.
    |
    */

    'api_key' => env('WAHA_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | WAHA Session Name
    |--------------------------------------------------------------------------
    |
    | The name of the WhatsApp session to operate against.
    |
    */

    'session' => env('WAHA_SESSION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Number of seconds to wait for a response before the HTTP client
    | gives up on a request to the WAHA instance.
    |
    */

    'timeout' => env('WAHA_TIMEOUT', 30),

];
