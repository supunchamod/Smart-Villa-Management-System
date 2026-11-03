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

    /*
    |--------------------------------------------------------------------------
    | Automated WhatsApp Sending
    |--------------------------------------------------------------------------
    |
    | Master switch for every *automated* WhatsApp trigger (booking
    | confirmations, check-in reminders, checkout invoices, post-checkout
    | review requests, owner enquiry alerts). Defaults to false so a local
    | environment without a running WAHA instance never fails a booking
    | operation just because it tried to reach WhatsApp - flip it on in
    | .env once WAHA is actually configured and reachable.
    |
    | This does not affect the manual "Resend via WhatsApp" button or the
    | WhatsApp settings/QR-code page, which stay usable either way; those
    | already fail gracefully (see WhatsAppService) rather than erroring
    | out if WAHA is unreachable.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Country Code
    |--------------------------------------------------------------------------
    |
    | Applied only to local-format numbers (a leading 0, or a bare short
    | number). Numbers entered with a + or 00 prefix keep their own
    | country code.
    |
    */

    'default_country_code' => env('WAHA_DEFAULT_COUNTRY_CODE', '94'),

    'auto_send' => env('WHATSAPP_AUTO_SEND', false),

];
