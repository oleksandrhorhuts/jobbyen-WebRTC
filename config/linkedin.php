<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LinkedIn Client ID, Secret
    |--------------------------------------------------------------------------
    |
    | This value  is used for Signin with LinkedIn
    |
     */

    'client_id' => env('LINKEDIN_CLIENT_ID', ''),
    'client_secret' => env('LINKEDIN_CLIENT_SECRET', ''),
    'redirect_uri' => env('LINKEDIN_REDIRECT_URI', ''),
];