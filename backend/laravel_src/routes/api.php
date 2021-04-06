<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\ParametersController;
use App\Http\Controllers\PartesController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\FaenasController;
use App\Http\Controllers\MarcasController;
use App\Http\Controllers\SolicitudesController;
use App\Http\Controllers\CotizacionesController;

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
Route::prefix('auth')->middleware('cors')->group(function ()
{
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::post('reset', [AuthController::class, 'resetPassword'])->name('password.reset');
});

/* ATTENTION: Only put routes here for testing. Then move them into the auth middleware */
/*
*
*/

/*  Authenticated  */
Route::middleware(['auth:api', 'cors'])->group(function()
{
    Route::get('/auth/user', [AuthController::class, 'getUser']);

    // Profile (user)
    Route::post('/profile', [UsersController::class, 'updateProfile']);

    // Roles
    Route::get('/roles/all', [RolesController::class, 'indexFull']);

    // Users
    Route::get('/users', [UsersController::class, 'index']);
    Route::post('/users', [UsersController::class, 'store']);
    Route::get('/users/{id}', [UsersController::class, 'show']);
    Route::put('/users/{id}', [UsersController::class, 'update']);
    Route::delete('/users/{id}', [UsersController::class, 'destroy']);

    // Parameters
    Route::get('/parameters', [ParametersController::class, 'index']);
    Route::get('/parameters/{id}', [ParametersController::class, 'show']);
    Route::put('/parameters/{id}', [ParametersController::class, 'update']);

    // Partes
    Route::get('/partes', [PartesController::class, 'index']);
    Route::get('/partes/{id}', [PartesController::class, 'show']);
    Route::put('/partes/{id}', [PartesController::class, 'update']);
    Route::delete('/partes/{id}', [PartesController::class, 'destroy']);

    // Clientes
    Route::get('/clientes', [ClientesController::class, 'index']);
    Route::post('/clientes', [ClientesController::class, 'store']);
    Route::get('/clientes/{id}', [ClientesController::class, 'show']);
    Route::put('/clientes/{id}', [ClientesController::class, 'update']);
    Route::delete('/clientes/{id}', [ClientesController::class, 'destroy']);
    
    // Faenas
    Route::get('/faenas/all', [FaenasController::class, 'indexFull']);
    Route::get('/clientes/{cliente_id}/faenas', [FaenasController::class, 'index']);
    Route::post('/clientes/{cliente_id}/faenas', [FaenasController::class, 'store']);
    Route::get('/clientes/{cliente_id}/faenas/{id}', [FaenasController::class, 'show']);
    Route::put('/clientes/{cliente_id}/faenas/{id}', [FaenasController::class, 'update']);
    Route::delete('/clientes/{cliente_id}/faenas/{id}', [FaenasController::class, 'destroy']);

    // Marcas
    Route::get('/marcas/all', [MarcasController::class, 'indexFull']);

    // Solicitudes
    Route::get('/solicitudes', [SolicitudesController::class, 'index']);
    Route::post('/solicitudes', [SolicitudesController::class, 'store']);
    Route::get('/solicitudes/{id}', [SolicitudesController::class, 'show']);
    Route::put('/solicitudes/{id}', [SolicitudesController::class, 'update']);
    Route::post('/solicitudes/complete/{id}', [SolicitudesController::class, 'complete']);
    Route::post('/solicitudes/close/{id}', [SolicitudesController::class, 'close']);
    Route::delete('/solicitudes/{id}', [SolicitudesController::class, 'destroy']);

    // Cotizaciones
    Route::get('/cotizaciones', [CotizacionesController::class, 'index']);
    Route::get('/motivosrechazo/all', [CotizacionesController::class, 'indexMotivosRechazoFull']);
    Route::get('/cotizaciones/{id}', [CotizacionesController::class, 'show']);
    Route::put('/cotizaciones/{id}', [CotizacionesController::class, 'update']);
    Route::post('/cotizaciones/approve/{id}', [CotizacionesController::class, 'approve']);
    Route::post('/cotizaciones/reject/{id}', [CotizacionesController::class, 'reject']);
    Route::post('/cotizaciones/close/{id}', [CotizacionesController::class, 'close']);
    Route::delete('/cotizaciones/{id}', [CotizacionesController::class, 'destroy']);
});