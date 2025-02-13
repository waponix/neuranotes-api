<?php

namespace App\Providers;

use App\Http\Api\BasicOutputBuilder;
use App\Http\Resource\AssistantResource;
use App\Http\Resource\AuthResource;
use App\Http\Resource\NoteResource;
use App\Http\Resource\Validator\AssistantValidator;
use App\Http\Resource\Validator\Auth\RegisterValidator;
use App\Http\Resource\Validator\Auth\LoginValidator;
use App\Http\Resource\Validator\CreateNoteValidator;
use App\LLM\Assistant;
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
                $this->app->get(BasicOutputBuilder::class),
                $this->app->get(LoginValidator::class),
                $this->app->get(RegisterValidator::class),
            );
        });

        $this->app->singleton(NoteResource::class, function () {
            return new NoteResource(
                $this->app->get(Assistant::class),
                $this->app->get(BasicOutputBuilder::class),
                $this->app->get(CreateNoteValidator::class),
            );
        });

        $this->app->singleton(AssistantResource::class, function () {
            return new AssistantResource(
                $this->app->get(Assistant::class),
                $this->app->get(BasicOutputBuilder::class),
                $this->app->get(AssistantValidator::class),
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
