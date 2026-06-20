<?php

namespace App\Providers;

use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use App\Repositories\Contracts\LearningResourceRepositoryInterface;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Eloquent\AnnouncementRepository;
use App\Repositories\Eloquent\LearningResourceRepository;
use App\Repositories\Eloquent\OrganizationRepository;
use App\Repositories\Eloquent\ResidentRepository;
use App\Repositories\Eloquent\StaffRepository;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AnnouncementRepositoryInterface::class, AnnouncementRepository::class);
        $this->app->bind(LearningResourceRepositoryInterface::class, LearningResourceRepository::class);
        $this->app->bind(OrganizationRepositoryInterface::class, OrganizationRepository::class);
        $this->app->bind(ResidentRepositoryInterface::class, ResidentRepository::class);
        $this->app->bind(StaffRepositoryInterface::class, StaffRepository::class);
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
