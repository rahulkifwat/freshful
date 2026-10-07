<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\RedirectIfAuthenticatedMiddleware;
use App\Http\Middleware\AccountManagerMiddleware;
use App\Http\Middleware\AccountManagerRedirectMiddleware;
use App\Http\Middleware\AreaManagerMiddleware;
use App\Http\Middleware\AreaManagerRedirectMiddleware;
use App\Http\Middleware\CountryManagerMiddleware;
use App\Http\Middleware\CountryManagerRedirectMiddleware;
use App\Http\Middleware\CustomerCareManagerMiddleware;
use App\Http\Middleware\CustomerCareManagerRedirectMiddleware;
use App\Http\Middleware\HrManagerMiddleware;
use App\Http\Middleware\HrManagerRedirectMiddleware;
use App\Http\Middleware\HubMiddleware;
use App\Http\Middleware\HubRedirectMiddleware;
use App\Http\Middleware\MarketingManagerMiddleware;
use App\Http\Middleware\MarketingManagerRedirectMiddleware;
use App\Http\Middleware\OperationManagerMiddleware;
use App\Http\Middleware\OperationManagerRedirectMiddleware;
use App\Http\Middleware\PlanningManagerMiddleware;
use App\Http\Middleware\PlanningManagerRedirectMiddleware;
use App\Http\Middleware\PosMiddleware;
use App\Http\Middleware\PosRedirectMiddleware;
use App\Http\Middleware\ProductionMiddleware;
use App\Http\Middleware\ProductionRedirectMiddleware;
use App\Http\Middleware\DriverJwtMiddleware;
use App\Http\Middleware\AdminApiMiddleware;
use App\Http\Middleware\BuyerApiMiddleware;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'redirectMiddleware' => RedirectIfAuthenticatedMiddleware::class,
            'account_manager' => AccountManagerMiddleware::class,
            'account_manager_redirect' => AccountManagerRedirectMiddleware::class,
            'area_manager' => AreaManagerMiddleware::class,
            'area_manager_redirect' => AreaManagerRedirectMiddleware::class,
            'country_manager' => CountryManagerMiddleware::class,
            'country_manager_redirect' => CountryManagerRedirectMiddleware::class,
            'customer_care_manager' => CustomerCareManagerMiddleware::class,
            'customer_care_manager_redirect' => CustomerCareManagerRedirectMiddleware::class,
            'hr_manager' => HrManagerMiddleware::class,
            'hr_manager_redirect' => HrManagerRedirectMiddleware::class,
            'hub' => HubMiddleware::class,
            'hub_redirect' => HubRedirectMiddleware::class,
            'marketing_manager' => MarketingManagerMiddleware::class,
            'marketing_manager_redirect' => MarketingManagerRedirectMiddleware::class,
            'operation_manager' => OperationManagerMiddleware::class,
            'operation_manager_redirect' => OperationManagerRedirectMiddleware::class,
            'planning_manager' => PlanningManagerMiddleware::class,
            'planning_manager_redirect' => PlanningManagerRedirectMiddleware::class,
            'pos' => PosMiddleware::class,
            'pos_redirect' => PosRedirectMiddleware::class,
            'production' => ProductionMiddleware::class,
            'production_redirect' => ProductionRedirectMiddleware::class,
            'driver.jwt' => DriverJwtMiddleware::class,
            'admin.api' => AdminApiMiddleware::class,
            'buyer.api' => BuyerApiMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API errors keep the legacy shape and HTTP 200 the mobile apps expect.
        $exceptions->render(function (ApiException $e) {
            return response()->json(['result' => false, 'message' => $e->getMessage()]);
        });
        // Legacy db_* helpers swallowed SQL errors into result=false; do the same for
        // unexpected API errors (still logged by the default reporter).
        $exceptions->render(function (Throwable $e, Request $request) {
            // HttpResponseException already carries its response (e.g. the rate-limit message).
            if (! $request->is('api/*') || $e instanceof HttpExceptionInterface || $e instanceof ValidationException
                || $e instanceof HttpResponseException) {
                return null;
            }

            $json = ['result' => false, 'message' => 'something went wrong'];
            if (config('app.debug')) {
                $json['error'] = $e->getMessage();
            }

            return response()->json($json);
        });
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());
    })->create();
