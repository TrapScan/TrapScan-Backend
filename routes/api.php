<?php

use App\Http\Controllers\APIController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\QRController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\StatsController;
use App\Http\Resources\CoordinatorSettingsResource;
use App\Http\Resources\UserResource;
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

/*
 * Login Protected Routes
 */
Route::middleware('auth:sanctum')->group(function() {
    Route::get('/user',  function (Request $request) {
        return UserResource::make($request->user()->load('roles'));
    })->name('user.info');

    Route::prefix('my')->group(function() {
        Route::get('/inspectionsPerProject', function(Request $request) {
            return $request->user()->inspectionCountPerProject();
        });
        Route::post('/settings', function(Request $request) {
           $validated_data = $request->validate([
               'settings' => 'required|array'
           ]);
           return UserResource::make($request->user()->setSetting($validated_data['settings']));
        });
        Route::get('/coordinator/settings', function(Request $request) {
           $projects = $request->user()->isCoordinator();
           return CoordinatorSettingsResource::make($projects);
        });
        Route::post('/coordinator/settings', function(Request $request) {
           $validated_data = $request->validate([
                'key' => 'required',
               'label' => 'required',
               'value' => 'required',
               'project_id' => 'required|exists:projects,id'
           ]);
            if($request->user()->setCoordinatorSettings($validated_data)) {
                return response()->json(['message' => 'Coordinator settings updated!'], 200);
            } else {
                return response()->json(['mesaage' => 'Error: Could not update coordinator settings'], 400);
            }
        });
    });

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

/*
 * Guest / Unprotected Routes
 */
Route::prefix('anon')->group(function () {
    Route::get('/scan/{qr_id}', [ScanController::class, 'anonScan'])
        ->name('scan.qr');
});

Route::prefix('stats')->group(function () {
    Route::get('/kpi', [StatsController::class, 'kpis'])
        ->name('stats.kpi');
});


