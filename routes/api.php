<?php

use App\Http\Controllers\APIController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\QRController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});


Route::get('/login/{provider}', [LoginController::class, 'redirectToProvider']);
Route::get('login/{provider}/callback', [LoginController::class, 'handleProviderCallback']);

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

/*
 * Login Protected Routes
 */
Route::middleware('auth:sanctum')->group(function() {
    Route::get('/user',  function (Request $request) {
        return $request->user()->load('roles');
    });

    Route::prefix('inspection')->group(function () {
        Route::post('/create', [InspectionController::class, 'create']);
        Route::get('/show/{inspection}', [InspectionController::class, 'show']);
    });

    /*
     * Admin Protected Routes
     */
    Route::prefix('admin')->middleware('role:admin')->group(function() {
        Route::post('/qr/create', [QRController::class, 'create']);
        Route::post('/qr/create/{project}', [QRController::class, 'createInProject']);
        Route::get('/qr/unmapped', [QRController::class, 'unmapped']);
        Route::get('qr/unmapped/{project}', [QRController::class, 'unmappedInProject']);
    });
});


