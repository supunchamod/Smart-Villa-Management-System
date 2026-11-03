<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Landing Page / Public Booking Website UI
    |--------------------------------------------------------------------------
    |
    | Temporarily hides every admin UI entry point to the Landing Page CMS and
    | the public direct-booking page (sidebar link, dashboard "Public Booking
    | Website" widget, and the Settings page shortcuts). Routes and
    | functionality are untouched - the pages still work if opened by URL -
    | only the links are hidden. Set FEATURE_LANDING_PAGE_UI=true in .env to
    | show them again.
    |
    */

    'landing_page_ui' => (bool) env('FEATURE_LANDING_PAGE_UI', false),

];
