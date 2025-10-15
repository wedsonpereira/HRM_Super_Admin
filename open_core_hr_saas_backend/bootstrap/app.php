<?php

use App\Http\Middleware\CheckWebAccess;
use App\Http\Middleware\LicenseChecker;
use App\Http\Middleware\LoadSettings;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\SetHrLayoutMiddleware;
use App\Http\Middleware\TransformApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    api: __DIR__ . '/../routes/api.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
  )
  ->withMiddleware(function (Middleware $middleware) {
    $middleware->web(LicenseChecker::class);
    $middleware->web(LocaleMiddleware::class);
    $middleware->web(LoadSettings::class);
    $middleware->web(SetHrLayoutMiddleware::class);
    $middleware->web(CheckWebAccess::class);  // Add web access check to all web routes
    $middleware->appendToGroup('api', [
      TransformApiResponse::class,
    ]);
    $middleware->alias([
      'role' => RoleMiddleware::class,
      'permission' => PermissionMiddleware::class,
      'role_or_permission' => RoleOrPermissionMiddleware::class,
      'web_access' => CheckWebAccess::class,
    ]);
  })
  ->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (AuthenticationException $e, Request $request) {
      if ($request->is('api/*')) {
        return response()->json([
          'statusCode' => 401,
          'status' => 'failed',
          'data' => 'Unauthorized'
        ], 401);
      } else {
        return redirect()->guest('auth/login');
      }
    });

    //Validation exception of api response
    $exceptions->render(function (ValidationException $e, Request $request) {
      if ($request->is('api/*')) {
        return response()->json([
          'status' => 'failed',
          'message' => $e->getMessage(),
          'data' => $e->errors()
        ], 422);
      }
    });

/*    $exceptions->render(function (Throwable $e, Request $request) {
      if ($e instanceof UnauthorizedException) {
        return response()->view("errors.403", [
          "exception" => $e
        ], 403);
      }
      return parent::render($request, $e);
    });*/
  })->create();
