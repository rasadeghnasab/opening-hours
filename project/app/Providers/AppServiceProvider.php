<?php

namespace App\Providers;

use App\Interfaces\HasOpenHoursInterface;
use App\Interfaces\OpenHourInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            OpenHourInterface::class,
            function ($app, $params) {
                $type = $params[0] ?? 'open';
                $classes = config('open_hours_classes');

                if (!$classes[$type] ?? null) {
                    throw new \Exception('Open hour type is not valid');
                }

                return new $classes[$type];
            }
        );
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
