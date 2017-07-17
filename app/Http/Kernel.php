<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'admin.auth' => \App\Http\Middleware\AdminAuthenticate::class,
        'salesman.auth' => \App\Http\Middleware\SalesmanAuthenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'admin.guest' => \App\Http\Middleware\RedirectIfAdminAuthenticated::class,
        'child.guest' => \App\Http\Middleware\RedirectIfChildUserAuthenticated::class,
        'retailer' => \App\Http\Middleware\ForbidRetailer::class,
        'supplier' => \App\Http\Middleware\ForbidSupplier::class,
        'deposit' => \App\Http\Middleware\VerifyDeposit::class,
        'child.auth' =>  \App\Http\Middleware\ChildUserAuthenticate::class,
        'maker_salesman' =>  \App\Http\Middleware\MakerSalesmanAuthenticate::class,
    ];
}
