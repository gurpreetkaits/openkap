<?php

/**
 * Webhook Diagnostic Script
 * Run this on production: php scripts/diagnose-webhooks.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Polar Webhook Diagnostics ===\n\n";

// 1. Check queue configuration
echo "1. Queue Configuration:\n";
echo '   QUEUE_CONNECTION: '.config('queue.default')."\n";
echo "   Expected: 'database' or 'redis'\n\n";

// 2. Check pending jobs
echo "2. Pending Jobs:\n";
$pendingJobs = DB::table('jobs')->count();
echo "   Count: {$pendingJobs}\n";
if ($pendingJobs > 0) {
    echo "   WARNING: Jobs are stuck in queue! Queue worker may not be running.\n";
    $oldestJob = DB::table('jobs')->orderBy('created_at')->first();
    if ($oldestJob) {
        echo '   Oldest job created at: '.$oldestJob->created_at."\n";
    }
}
echo "\n";

// 3. Check failed jobs
echo "3. Failed Jobs:\n";
$failedJobs = DB::table('failed_jobs')->count();
echo "   Count: {$failedJobs}\n";
if ($failedJobs > 0) {
    $lastFailed = DB::table('failed_jobs')->orderBy('failed_at', 'desc')->first();
    echo '   Last failure: '.$lastFailed->failed_at."\n";
    echo '   Exception: '.substr($lastFailed->exception, 0, 200)."...\n";
}
echo "\n";

// 4. Check webhook_calls
echo "4. Webhook Calls:\n";
$webhookCalls = DB::table('webhook_calls')->count();
echo "   Total: {$webhookCalls}\n";
$lastWebhook = DB::table('webhook_calls')->orderBy('id', 'desc')->first();
if ($lastWebhook) {
    echo "   Last webhook ID: {$lastWebhook->id}\n";
    echo "   Created at: {$lastWebhook->created_at}\n";
    $payload = json_decode($lastWebhook->payload, true);
    echo '   Type: '.($payload['type'] ?? 'unknown')."\n";
    echo '   Has billable_id: '.(isset($payload['data']['customer']['metadata']['billable_id']) ? 'YES' : 'NO')."\n";
    if (isset($payload['data']['customer']['metadata'])) {
        echo '   Customer metadata: '.json_encode($payload['data']['customer']['metadata'])."\n";
    }
}
echo "\n";

// 5. Check polar tables
echo "5. Polar Tables:\n";
echo '   polar_customers: '.DB::table('polar_customers')->count()."\n";
echo '   polar_subscriptions: '.DB::table('polar_subscriptions')->count()."\n";
echo '   polar_orders: '.DB::table('polar_orders')->count()."\n";
echo "\n";

// 6. Check if ProcessWebhook class is loadable
echo "6. Class Autoloading:\n";
try {
    $class = new \Danestves\LaravelPolar\Handlers\ProcessWebhook(new \Spatie\WebhookClient\Models\WebhookCall);
    echo "   ProcessWebhook class: LOADABLE\n";
} catch (\Throwable $e) {
    echo '   ProcessWebhook class: FAILED - '.$e->getMessage()."\n";
}
echo "\n";

// 7. Check webhook secret
echo "7. Webhook Secret:\n";
$secret = config('webhook-client.configs.0.signing_secret');
echo '   Configured: '.($secret ? 'YES ('.strlen($secret).' chars)' : 'NO - MISSING!')."\n";
echo "\n";

// 8. Users with subscriptions
echo "8. Users with Active Subscriptions:\n";
$activeUsers = DB::table('users')->where('subscription_status', 'active')->count();
echo "   Count: {$activeUsers}\n";
echo "\n";

echo "=== Recommendations ===\n";
if ($pendingJobs > 0) {
    echo "- Queue worker is not running! Start it with: php artisan queue:work\n";
}
if ($failedJobs > 0) {
    echo "- You have failed jobs. Check them with: php artisan queue:failed\n";
    echo "- Retry with: php artisan queue:retry all\n";
}
if (! $secret) {
    echo "- POLAR_WEBHOOK_SECRET is not configured!\n";
}
echo "- After any fix, run: php artisan queue:restart && composer dump-autoload\n";
