<?php

namespace App\Providers;

use App\Services\Assessment\AssessmentClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AssessmentClient::class, fn () => new AssessmentClient(config('services.assessment')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
