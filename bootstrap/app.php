<?php

use App\Enums\UserRole;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/status',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // Fortify's guest middleware normally sends every authenticated user
        // to one static home page. Route authenticated login/register requests
        // through the requested module entry instead, where a role mismatch can
        // be handled without changing the user's stored role or returning 403.
        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request): string {
            $currentRole = $request->user()?->role();
            $requestedRole = UserRole::tryFrom((string) $request->query('type'));

            if ($requestedRole && $currentRole && $requestedRole !== $currentRole) {
                return route(
                    $requestedRole === UserRole::Employer ? 'hrflow' : 'jobflow',
                    ['intent' => $request->routeIs('register') ? 'register' : 'login'],
                );
            }

            return route($currentRole === UserRole::Employer ? 'hrflow' : 'jobflow');
        });

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
