<?php

namespace App\Providers;

use App\Repositories\Contracts\LearningResourceRepositoryInterface;
use App\Repositories\Eloquent\LearningResourceRepository;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LearningResourceRepositoryInterface::class, LearningResourceRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS URLs in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
