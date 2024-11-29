<?php

namespace App\Providers;

use App\Http\Resource\AuthResource;
use App\Http\Resource\Validator\Auth\RegisterValidator;
use App\Http\Resource\Validator\Auth\LoginValidator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuthResource::class, function () {
            return new AuthResource(
                $this->app->get(LoginValidator::class),
                $this->app->get(RegisterValidator::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
