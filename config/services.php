<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
        'base_url' => 'https://api.resend.com',
    ],

    'unosend' => [
        'api_key' => env('UNOSEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/api/auth/google/callback'),
    ],

    'frontend' => [
        'url' => env('FRONTEND_URL', 'http://localhost:5173'),
    ],

    'polar' => [
        'api_key' => env('POLAR_ACCESS_TOKEN', env('POLAR_API_KEY')), // For backward compatibility
        'organization_id' => env('POLAR_ORGANIZATION_ID'),
        // Pro plan product IDs
        'product_id_monthly' => env('POLAR_PRODUCT_ID_MONTHLY'),
        'product_id_yearly' => env('POLAR_PRODUCT_ID_YEARLY'),
        // Teams plan product ID (monthly only for now)
        'product_id_teams_monthly' => env('POLAR_PRODUCT_ID_TEAMS_MONTHLY'),
        'webhook_secret' => env('POLAR_WEBHOOK_SECRET'),
        'environment' => env('POLAR_SERVER', env('POLAR_ENVIRONMENT', 'sandbox')),
        'api_url' => env('POLAR_SERVER', env('POLAR_ENVIRONMENT', 'sandbox')) === 'production'
            ? 'https://api.polar.sh'
            : 'https://sandbox-api.polar.sh',
    ],

    'posthog' => [
        'key' => env('POSTHOG_KEY'),
        'host' => env('POSTHOG_HOST', 'https://us.i.posthog.com'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'whisper_model' => env('OPENAI_WHISPER_MODEL', 'whisper-1'),
        'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
    ],

    'integrations' => [
        'slack' => [
            'client_id' => env('SLACK_CLIENT_ID'),
            'client_secret' => env('SLACK_CLIENT_SECRET'),
            'redirect_uri' => env('SLACK_REDIRECT_URI', env('APP_URL').'/api/integrations/slack/callback'),
        ],
        'jira' => [
            'client_id' => env('JIRA_CLIENT_ID'),
            'client_secret' => env('JIRA_CLIENT_SECRET'),
            'redirect_uri' => env('JIRA_REDIRECT_URI', env('APP_URL').'/api/integrations/jira/callback'),
        ],
    ],

    'bunny' => [
        'library_id' => env('BUNNY_STREAM_LIBRARY_ID'),
        'api_key' => env('BUNNY_STREAM_API_KEY'),
        'cdn_hostname' => env('BUNNY_STREAM_CDN_HOSTNAME'),
        'security_key' => env('BUNNY_STREAM_SECURITY_KEY'),
        'playback_expiry' => (int) env('BUNNY_STREAM_PLAYBACK_EXPIRY', 3600),
        'upload_expiry' => (int) env('BUNNY_STREAM_UPLOAD_EXPIRY', 7200),
        'base_url' => 'https://video.bunnycdn.com',
        'tus_endpoint' => 'https://video.bunnycdn.com/tusupload',
    ],

];
