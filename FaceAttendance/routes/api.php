<?php

use Illuminate\Support\Facades\Route;
use Modules\FaceAttendance\app\Http\Controllers\Api\FaceAttendanceApiController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::middleware([
  'api',
  InitializeTenancyByDomain::class,
  PreventAccessFromCentralDomains::class,
])->group(function () {
  Route::group([
    'middleware' => 'api',
    'as' => 'api.',
  ], function () {
    Route::middleware('auth:api')->group(function () {
    Route::group(['prefix' => 'V1'], function () {
      Route::prefix('faceAttendance')->name('faceAttendance')->group(function () {
        Route::get('getFaceData', [FaceAttendanceApiController::class, 'getFaceDataAsImage'])->name('getFaceDataAsImage');
        Route::get('isFaceDataAdded', [FaceAttendanceApiController::class, 'isFaceDataAdded'])->name('isFaceDataAdded');
        Route::post('addOrUpdateFaceData', [FaceAttendanceApiController::class, 'addOrUpdateFaceData'])->name('addOrUpdateFaceData');
      });
    });
    });
  });
});
