<?php

namespace App\Providers;

use App\Services\MLServiceClient;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MLServiceClient::class, function () {
            return new MLServiceClient(
                baseUrl: (string) config('services.ml.url'),
                apiKey: (string) config('services.ml.key'),
                timeout: (int) config('services.ml.timeout', 120),
            );
        });
    }

    public function boot(): void
    {
        Paginator::useTailwind();
    }
}
