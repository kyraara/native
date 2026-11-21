<?php

namespace App\Providers;

use App\View\Composers\AdminLayoutComposer;
use App\View\Composers\PublicPageComposer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // @rupiah($amount) → "Rp 175.000" (cents only when present).
        Blade::directive('rupiah', fn (string $expression) => "<?php echo e(\App\Support\Money::rupiah({$expression})); ?>");

        View::composer('layouts.admin', AdminLayoutComposer::class);
        View::composer(['pages.home', 'pages.order.*'], PublicPageComposer::class);

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
