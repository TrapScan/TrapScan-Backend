<?php

use App\Http\Controllers\APIController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\QRController;
use App\Http\Controllers\ScanController;
use App\Models\Project;
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
    })->name('user.info');
    Route::get('/user/isCoordinator/{project}',  function (Request $request, Project $project) {
        $coord = $request->user()->isCoordinatorOf($project);
        if($coord) {
            return response()->json([true], 200);
        } else {
            return response()->json([false], 400);
        }
    })->name('user.is.coordinator');

    Route::prefix('inspection')->group(function () {
        Route::post('/create', [InspectionController::class, 'create'])
            ->name('inspection.create');
        Route::get('/show/{inspection}', [InspectionController::class, 'show'])
            ->name('inspection.show');
    });

    /*
     * Admin Protected Routes
     */
    Route::prefix('admin')->middleware('role:admin')->group(function() {
        Route::post('/qr/create', [QRController::class, 'create'])
            ->name('admin.qr.create');
        Route::post('/qr/create/{project}', [QRController::class, 'createInProject'])
            ->name('admin.qr.create.project');
        Route::get('/qr/unmapped', [QRController::class, 'unmapped'])
            ->name('admin.qr.unmapped');
        Route::get('/qr/unmapped/{project}', [QRController::class, 'unmappedInProject'])
            ->name('admin.qr.unmapped.project');
        Route::post('/qr/map', [QRController::class, 'mapQRCodeAdmin'])
            ->name('admin.qr.map');
    });

    /*
     * User Accessible QR routes
     */
    Route::post('/qr/map', [QRController::class, 'mapQRCode'])
        ->name('qr.map');

    /*
     * Field Scanning Code
     */
    Route::get('/scan/{qr_id}', [ScanController::class, 'scan'])
        ->name('scan.qr');
});

Route::prefix('anon')->group(function () {
    Route::get('/scan/{qr_id}', [ScanController::class, 'anonScan'])
        ->name('scan.qr');
});




