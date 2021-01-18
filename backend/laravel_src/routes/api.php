<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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

/*  Authenticated  */
Route::middleware('auth:api')->group(function()
{
    Route::get('/auth/user', [AuthController::class, 'getUser']);
});