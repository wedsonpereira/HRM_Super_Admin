<?php

use App\Http\Middleware\AddonCheckMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\FaceAttendance\app\Http\Controllers\FaceAttendanceController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => function ($request, $next) {
  $request->headers->set('addon', ModuleConstants::FACE_ATTENDANCE);
  return $next($request);
}], function () {
  Route::middleware([
    'api',
    'auth',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    AddonCheckMiddleware::class
  ])->group(function () {

    Route::prefix('faceAttendance')->name('faceAttendance.')->group(function () {
      Route::get('getFaceDataAjax/{userId}', [FaceAttendanceController::class, 'getFaceDataAjax'])->name('getFaceDataAjax');
      Route::post('addOrUpdateFaceData', [FaceAttendanceController::class, 'addOrUpdateFaceData'])->name('addOrUpdateFaceData');
      Route::delete('deleteFaceData/{userId}', [FaceAttendanceController::class, 'removeFaceData'])->name('deleteFaceData');
    });
  });
});
