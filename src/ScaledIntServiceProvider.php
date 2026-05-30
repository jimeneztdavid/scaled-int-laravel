<?php

namespace Jimeneztdavid\ScaledIntLaravel;

use Illuminate\Support\ServiceProvider;
use Jimeneztdavid\ScaledIntLaravel\Console\Commands\MakeScaledIntCastCommand;

final class ScaledIntServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/scaled-int.php', 'scaled-int');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeScaledIntCastCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/scaled-int.php' => config_path('scaled-int.php'),
            ], 'scaled-int-config');
        }
    }
}
