<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClinicsController;
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
    Route::middleware('role:admin')->group(function () {

        Route::get('admin/categories', [CategoryController::class, 'index']);
        Route::post('admin/add-category', [CategoryController::class, 'store']);
        Route::post('admin/clinic', [ClinicsController::class, 'addClinic']);
        Route::post('admin/clinic/queque', [ClinicsController::class, 'createScedhule']);

    });
});


Route::middleware('auth:api')->group(function () {
    Route::middleware('role:user')->group(function () {

        Route::get('user/verify', [AuthController::class, 'me']);
        Route::get('user/categories', [CategoryController::class, 'index']);

        Route::get('user/services', [ClinicsController::class, 'getClinic']);
        Route::get('user/service/{name}', [ClinicsController::class, 'getClinicByName']);
        Route::get('user/clinic/queque/{clinic_id}', [ClinicsController::class, 'getByClinicId']);

        Route::post('user/transaction', [TransactionController::class, 'create']);


    });
});