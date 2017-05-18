<?php

    /**
     * OIDCAccount configuration file.
     */

    return [
        'debug' => [
            'auth' => false
        ],
        'reserved_user_ids' => [
            'guest'  => -1,
            'master' => 1
        ],
        'session' => [
            // The keys used in the session to store info about authenticated users
            'keys' => [
                'current_user_id'  => 'account.current_user_id',    // the key to use for storing the authenticated user's id
                'captcha'          => 'account.captcha'     // Key used to store a captcha hash during captcha verification
            ]
        ],
        // "Site" settings that are automatically passed to Twig
        'site' => [
            'login' => [
                'enable_email' => true
            ],
            'registration' => [
                'enabled' => true,
                'user_defaults' => [
                    'locale' => 'en_US',
                    // Default roles for newly registered users
                    'roles' => [
                        'user' => true
                    ]
                ]
            ]
        ],
        'throttles' => [
            'registration_attempt' => null,
            'sign_in_attempt' => null
        ],
        'client_id' => env('CLIENT_ID')
    ];
