<?php

namespace App\Providers;

use App\Mail\Transport\UnosendTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class UnosendServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Mail::extend('unosend', function (array $config) {
            return new UnosendTransport(
                $config['api_key'] ?? config('services.unosend.api_key')
            );
        });
    }
}
