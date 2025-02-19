<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClinicsController;
use App\Http\Controllers\ServiceInfoController;
use App\Http\Controllers\TransactionController;
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

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
});


Route::middleware('auth:api')->group(function () {

        Route::middleware('role:admin')->group(function () {
        Route::get('admin/categories', [CategoryController::class, 'index']);
        Route::post('admin/add-category', [CategoryController::class, 'store']);
        Route::post('admin/clinic', [ClinicsController::class, 'addClinic']);
        Route::post('admin/clinic/queque', [ClinicsController::class, 'createScedhule']);
        Route::get('admin/services', [ClinicsController::class, 'getClinic']);
        Route::post('admin/service-info', [ServiceInfoController::class, 'store']);
        Route::get('admin/active-queque/{clinicId}', [TransactionController::class, 'listQueue']);
        Route::get('admin/current-queque/{clinicId}', [TransactionController::class, 'currentQueue']);
        Route::post('admin/transaction-update',[TransactionController::class,'updateStatus']);
        
    });
});


Route::middleware('auth:api')->group(function () {
    Route::middleware('role:user')->group(function () {

        Route::get('user/categories', [CategoryController::class, 'index']);

        Route::get('user/services', [ClinicsController::class, 'getClinic']);
        Route::get('user/service/{name}', [ClinicsController::class, 'getClinicByName']);
        Route::get('user/clinic/queque/{clinic_id}', [ClinicsController::class, 'getByClinicId']);

        Route::post('user/transaction', [TransactionController::class, 'create']);
        Route::get(uri: 'user/transaction-all', action:  [TransactionController::class, 'getAllByUserId']);
        Route::get(uri: 'user/transaction/{id}', action:  [TransactionController::class, 'getDetailById']);
        Route::post(uri: 'user/transaction-cek', action:  [TransactionController::class, 'getCekTransaction']);

        Route::get('user/service-info/{clinic_id}', [ServiceInfoController::class, 'getByClinicId']);
        

    });
});