<?php declare(strict_types=1);

namespace Fschirinzi\TranslationManager;

use Fschirinzi\TranslationManager\Commands\ValidateTranslations;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap the application services.
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ValidateTranslations::class,
            ]);
        }
    }
}
