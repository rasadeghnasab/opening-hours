<?php
declare(strict_types=1);

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->constraints();
        $this->configureRateLimiting();

        $this->routes(
            function () {
                Route::prefix('api/v1')
                    ->middleware('api')
                    ->namespace($this->namespace)
                    ->group(base_path('routes/api/api-v1.php'));

                Route::middleware('web')
                    ->namespace($this->namespace)
                    ->group(base_path('routes/web.php'));
            }
        );

        Route::bind('timeable', function ($timeable_id) {
            $timeables = config('timeables');
            $timeable_type = $this->app->request->route('timeable_type');

            if(!isset($timeables[$timeable_type])) {
                abort(405, 'not found');
            }

            $timeable_class = $timeables[$timeable_type];

            return $timeable_class::findOrFail($timeable_id);
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for(
            'api',
            function (Request $request) {
                return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
            }
        );
    }

    protected function constraints()
    {
        $timeables = array_keys(config('timeables'));
        Route::pattern('timeable_type', implode('|', $timeables));
    }
}
