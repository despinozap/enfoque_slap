<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;

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

/*  Login  */
Route::prefix('auth')->group(function ()
{
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::post('reset', [AuthController::class, 'resetPassword'])->name('password.reset');
});

/* ATTENTION: Only put routes here for testing. Then move them into the auth middleware */
// Rols
Route::get('/roles/all', [RolesController::class, 'indexFull']);

// Users
Route::get('/users', [UsersController::class, 'index']);
Route::post('/users', [UsersController::class, 'store']);
Route::get('/users/{id}', [UsersController::class, 'show']);
Route::put('/users/{id}', [UsersController::class, 'update']);
Route::delete('/users/{id}', [UsersController::class, 'destroy']);



/*  Authenticated  */
Route::middleware('auth:api')->group(function()
{
    Route::get('/auth/user', [AuthController::class, 'getUser']);

    // Profile (user)
    Route::post('/profile', [UsersController::class, 'updateProfile']);
});