<?php

use Illuminate\Support\Facades\Route;
use Modules\OfflineTracking\App\Http\Controllers\Api\OfflineTrackingApiController;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/
Route::middleware([
  'api',
])->group(function () {
  Route::group(['prefix' => 'V1'], function () {
    Route::group([
      'middleware' => 'api',
      'as' => 'api.',
    ], function () {
      Route::middleware('auth:api')->group(function () {
        Route::prefix('offlineTracking')->name('offlineTracking.')->group(function () {
          Route::post('bulkDeviceStatusUpdate', [OfflineTrackingApiController::class, 'bulkUpdateDeviceStatus']);
          Route::post('bulkActivityStatusUpdate', [OfflineTrackingApiController::class, 'bulkActivityStatusUpdate']);
        });
      });
    });
  });
});
