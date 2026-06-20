<?php

namespace App\Providers;

use App\Repositories\Contracts\LearningResourceRepositoryInterface;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use App\Repositories\Eloquent\LearningResourceRepository;
use App\Repositories\Eloquent\ResidentRepository;
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
        $this->app->bind(ResidentRepositoryInterface::class, ResidentRepository::class);
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
