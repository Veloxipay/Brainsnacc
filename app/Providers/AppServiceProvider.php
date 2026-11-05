<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The AI provider. To change provider, bind another class that implements AiModel.
        $this->app->singleton(\App\Support\Ai\AiModel::class, \App\Support\Ai\AnthropicModel::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
