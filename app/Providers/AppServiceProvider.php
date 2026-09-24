<?php

namespace App\Providers;

use App\Services\ChunkStorageService;
use Danestves\LaravelPolar\Events\SubscriptionActive;
use Danestves\LaravelPolar\Events\SubscriptionCanceled;
use Danestves\LaravelPolar\Events\SubscriptionCreated;
use Danestves\LaravelPolar\Events\SubscriptionRevoked;
use Danestves\LaravelPolar\Events\WebhookReceived;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Chunked-upload storage is rooted at one directory and has no other
        // framework dependency, which keeps it directly testable.
        $this->app->singleton(ChunkStorageService::class, fn () => new ChunkStorageService(
            storage_path('app/temp/stream-uploads')
        ));
    }

    /**
     * Named rate limiters.
     *
     * These must be named rather than inline `throttle:120,1` middleware.
     * A numeric throttle keys its counter on sha1(user id) alone, so the
     * global API limiter and every per-route numeric throttle increment the
     * SAME counter — which made a handful of ordinary API calls enough to
     * lock a user out of starting or uploading a recording. Giving each
     * limiter its own `by()` prefix keeps the buckets separate.
     */
    protected function configureRateLimiting(): void
    {
        // Everything except recording traffic. Recording endpoints opt out
        // here because each has its own dedicated limiter below — otherwise
        // the global ceiling would cap chunk uploads no matter what the
        // route-level limit says.
        RateLimiter::for('api', function (Request $request) {
            if ($request->is('api/stream/*')) {
                return Limit::none();
            }

            return Limit::perMinute(120)->by('api|'.$this->limiterKey($request));
        });

        // Starting a recording is a rare, deliberate action.
        RateLimiter::for('stream-start', fn (Request $request) => Limit::perMinute(20)->by('stream-start|'.$this->limiterKey($request)));

        // Chunk uploads run at ~20/min steady state for a 3s chunk interval,
        // and burst well above that when the client re-sends a gap after a
        // network blip. The ceiling only exists to stop genuine abuse.
        RateLimiter::for('stream-chunk', fn (Request $request) => Limit::perMinute(600)->by('stream-chunk|'.$this->limiterKey($request)));

        // Status is polled during reconciliation before completing.
        RateLimiter::for('stream-status', fn (Request $request) => Limit::perMinute(120)->by('stream-status|'.$this->limiterKey($request)));

        RateLimiter::for('stream-cancel', fn (Request $request) => Limit::perMinute(20)->by('stream-cancel|'.$this->limiterKey($request)));

        // Telemetry is accepted unauthenticated, so it is keyed by IP.
        RateLimiter::for('extension-errors', fn (Request $request) => Limit::perMinute(30)->by('extension-errors|'.$this->limiterKey($request)));
    }

    private function limiterKey(Request $request): string
    {
        return (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // Force HTTPS in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Log incoming Polar webhooks
        Event::listen(WebhookReceived::class, function ($event) {
            Log::channel('daily')->info('Polar webhook received', [
                'type' => $event->payload['type'] ?? 'unknown',
                'payload' => $event->payload,
            ]);
        });

        // Sync User model when subscription is created
        Event::listen(SubscriptionCreated::class, function ($event) {
            Log::channel('daily')->info('Polar subscription created', [
                'billable_id' => $event->billable->id ?? null,
                'subscription_id' => $event->subscription->polar_id ?? null,
                'status' => $event->subscription->status->value ?? null,
            ]);

            // Update User model with subscription data
            $user = $event->billable;
            $subscription = $event->subscription;

            $user->forceFill([
                'subscription_status' => $subscription->status->value,
                'polar_subscription_id' => $subscription->polar_id,
                'polar_product_id' => $subscription->product_id,
                'subscription_started_at' => now(),
                'subscription_expires_at' => $subscription->current_period_end,
            ])->save();

            Log::channel('daily')->info('User subscription status updated', [
                'user_id' => $user->id,
                'status' => $subscription->status->value,
            ]);
        });

        // Sync User model when subscription becomes active
        Event::listen(SubscriptionActive::class, function ($event) {
            Log::channel('daily')->info('Polar subscription activated', [
                'billable_id' => $event->billable->id ?? null,
                'subscription_id' => $event->subscription->polar_id ?? null,
            ]);

            $user = $event->billable;
            $subscription = $event->subscription;

            $user->forceFill([
                'subscription_status' => 'active',
                'subscription_expires_at' => $subscription->current_period_end,
            ])->save();
        });

        // Sync User model when subscription is canceled
        Event::listen(SubscriptionCanceled::class, function ($event) {
            Log::channel('daily')->info('Polar subscription canceled', [
                'billable_id' => $event->billable->id ?? null,
                'subscription_id' => $event->subscription->polar_id ?? null,
            ]);

            $user = $event->billable;
            $subscription = $event->subscription;

            $user->forceFill([
                'subscription_status' => 'canceled',
                'subscription_canceled_at' => now(),
                'subscription_expires_at' => $subscription->ends_at ?? $subscription->current_period_end,
            ])->save();
        });

        // Sync User model when subscription is revoked (ended)
        Event::listen(SubscriptionRevoked::class, function ($event) {
            Log::channel('daily')->info('Polar subscription revoked', [
                'billable_id' => $event->billable->id ?? null,
                'subscription_id' => $event->subscription->polar_id ?? null,
            ]);

            $user = $event->billable;

            $user->forceFill([
                'subscription_status' => 'expired',
                'subscription_expires_at' => now(),
            ])->save();
        });
    }
}
