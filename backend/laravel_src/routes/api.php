<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\LoggedactionsController;
use App\Http\Controllers\ParametersController;
use App\Http\Controllers\SucursalesController;
use App\Http\Controllers\PartesController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\FaenasController;
use App\Http\Controllers\MarcasController;
use App\Http\Controllers\SolicitudesController;
use App\Http\Controllers\CotizacionesController;
use App\Http\Controllers\OcsController;
use App\Http\Controllers\CompradoresController;
use App\Http\Controllers\ProveedoresController;
use App\Http\Controllers\CentrosdistribucionController;
use App\Http\Controllers\RecepcionesController;
use App\Http\Controllers\DespachosController;
use App\Http\Controllers\EntregasController;

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
    Route::post('login', [AuthController::class, 'logIn']);
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

    // Sucursales
    Route::get('/centrosdistribucion/countries/{country_id}', [SucursalesController::class, 'index_centrodistribucion']);
    Route::get('/sucursales/countries/{country_id}', [SucursalesController::class, 'index_sucursal']);

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
    Route::get('/solicitudes/sucursales/prepare', [SolicitudesController::class, 'store_prepare']);
    Route::post('/solicitudes', [SolicitudesController::class, 'store']);
    Route::get('/solicitudes/{id}', [SolicitudesController::class, 'show']);
    Route::put('/solicitudes/{id}', [SolicitudesController::class, 'update']);
    Route::post('/solicitudes/complete/{id}', [SolicitudesController::class, 'complete']);
    Route::post('/solicitudes/close/{id}', [SolicitudesController::class, 'close']);
    Route::delete('/solicitudes/{id}', [SolicitudesController::class, 'destroy']);

    // Cotizaciones
    Route::get('/cotizaciones', [CotizacionesController::class, 'index']);
    Route::get('/cotizaciones/motivosrechazo/all', [CotizacionesController::class, 'indexMotivosRechazoFull']);
    Route::post('/cotizaciones/report', [CotizacionesController::class, 'report']);
    Route::put('/cotizaciones/{id}', [CotizacionesController::class, 'update']);
    Route::post('/cotizaciones/approve/{id}', [CotizacionesController::class, 'approve']);
    Route::post('/cotizaciones/reject/{id}', [CotizacionesController::class, 'reject']);
    Route::post('/cotizaciones/close/{id}', [CotizacionesController::class, 'close']);
    Route::delete('/cotizaciones/{id}', [CotizacionesController::class, 'destroy']);

    // Compradores
    Route::get('/compradores', [CompradoresController::class, 'index']);
    Route::get('/compradores/{id}', [CompradoresController::class, 'show']);
    
    // Proveedores
    Route::get('/compradores/{comprador_id}/proveedores', [ProveedoresController::class, 'index']);
    Route::post('/compradores/{comprador_id}/proveedores', [ProveedoresController::class, 'store']);
    Route::get('/compradores/{comprador_id}/proveedores/{id}', [ProveedoresController::class, 'show']);
    Route::put('/compradores/{comprador_id}/proveedores/{id}', [ProveedoresController::class, 'update']);
    Route::delete('/compradores/{comprador_id}/proveedores/{id}', [ProveedoresController::class, 'destroy']);

    // OCs
    Route::get('/ocs', [OcsController::class, 'index']);
    Route::get('/ocs/motivosbaja/all', [OcsController::class, 'indexMotivosBajaFull']);
    Route::post('/ocs/report', [OcsController::class, 'report']);
    Route::post('/ocs/reject/{id}', [OcsController::class, 'reject']);
    Route::put('/ocs/{id}/partes', [OcsController::class, 'updateParte']);
    Route::delete('/ocs/{id}/partes/{parte_id}', [OcsController::class, 'destroyParte']);
    Route::post('/ocs/start/{id}', [OcsController::class, 'start']);

    // Recepciones (Comprador)
    Route::get('/compradores/{id}/recepciones', [RecepcionesController::class, 'index_comprador']);
    Route::get('/compradores/{comprador_id}/recepciones/prepare', [RecepcionesController::class, 'store_prepare_comprador']);
    Route::get('/compradores/{comprador_id}/recepciones/queueocs/proveedores/{proveedor_id}', [RecepcionesController::class, 'queueOcs_comprador']);
    Route::post('/compradores/{comprador_id}/recepciones', [RecepcionesController::class, 'store_comprador']);
    Route::get('/compradores/{comprador_id}/recepciones/{id}', [RecepcionesController::class, 'show_comprador']);
    Route::get('/compradores/{comprador_id}/recepciones/{id}/prepare', [RecepcionesController::class, 'update_prepare_comprador']);
    Route::put('/compradores/{comprador_id}/recepciones/{id}', [RecepcionesController::class, 'update_comprador']);
    Route::delete('/compradores/{comprador_id}/recepciones/{id}', [RecepcionesController::class, 'destroy_comprador']);
    
    // Despachos (Comprador)
    Route::get('/compradores/{id}/despachos', [DespachosController::class, 'index_comprador']);
    Route::get('/compradores/{comprador_id}/despachos/prepare', [DespachosController::class, 'store_prepare_comprador']);
    Route::get('/compradores/{comprador_id}/despachos/queueocpartes/centrosdistribucion/{centrodistribucion_id}', [DespachosController::class, 'queueOcPartes_comprador']);
    Route::post('/compradores/{comprador_id}/despachos', [DespachosController::class, 'store_comprador']);
    Route::get('/compradores/{comprador_id}/despachos/{id}', [DespachosController::class, 'show_comprador']);
    Route::get('/compradores/{comprador_id}/despachos/{id}/prepare', [DespachosController::class, 'update_prepare_comprador']);
    Route::put('/compradores/{comprador_id}/despachos/{id}', [DespachosController::class, 'update_comprador']);
    Route::delete('/compradores/{comprador_id}/despachos/{id}', [DespachosController::class, 'destroy_comprador']);

    // Recepciones (Sucursal [centro])
    Route::get('/centrosdistribucion/{id}/recepciones', [RecepcionesController::class, 'index_centrodistribucion']);
    Route::get('/centrosdistribucion/{centrodistribucion_id}/recepciones/prepare', [RecepcionesController::class, 'store_prepare_centrodistribucion']);
    Route::get('/centrosdistribucion/{centrodistribucion_id}/recepciones/queueocpartes/compradores/{comprador_id}', [RecepcionesController::class, 'queueOcPartes_centrodistribucion']);
    Route::post('/centrosdistribucion/{centrodistribucion_id}/recepciones', [RecepcionesController::class, 'store_centrodistribucion']);
    Route::get('/centrosdistribucion/{centrodistribucion_id}/recepciones/{id}', [RecepcionesController::class, 'show_centrodistribucion']);
    Route::get('/centrosdistribucion/{centrodistribucion_id}/recepciones/{id}/prepare', [RecepcionesController::class, 'update_prepare_centrodistribucion']);
    Route::put('/centrosdistribucion/{centrodistribucion_id}/recepciones/{id}', [RecepcionesController::class, 'update_centrodistribucion']);
    Route::delete('/centrosdistribucion/{centrodistribucion_id}/recepciones/{id}', [RecepcionesController::class, 'destroy_centrodistribucion']);
    
    // // Despachos (Sucursal [centro])
    Route::get('/centrosdistribucion/{id}/despachos', [DespachosController::class, 'index_centrodistribucion']);
    Route::get('/centrosdistribucion/{centrodistribucion_id}/despachos/prepare', [DespachosController::class, 'store_prepare_centrodistribucion']);
    Route::get('/centrosdistribucion/{centrodistribucion_id}/despachos/queueocpartes/sucursales/{sucursal_id}', [DespachosController::class, 'queueOcPartes_centrodistribucion']);
    Route::post('/centrosdistribucion/{id}/despachos', [DespachosController::class, 'store_centrodistribucion']);
    Route::get('/centrosdistribucion/{centrodistribucion_id}/despachos/{id}', [DespachosController::class, 'show_centrodistribucion']);
    Route::get('/centrosdistribucion/{centrodistribucion_id}/despachos/{id}/prepare', [DespachosController::class, 'update_prepare_centrodistribucion']);
    Route::put('/centrosdistribucion/{centrodistribucion_id}/despachos/{id}', [DespachosController::class, 'update_centrodistribucion']);
    Route::delete('/centrosdistribucion/{centrodistribucion_id}/despachos/{id}', [DespachosController::class, 'destroy_centrodistribucion']);

    // // Entregas (Sucursal [centro])
    // Route::get('/centrosdistribucion/{id}/entregas', [EntregasController::class, 'index_centrodistribucion']);
    // Route::get('/centrosdistribucion/{centrodistribucion_id}/entregas/queueocs', [EntregasController::class, 'queueOcs_centrodistribucion']);
    // Route::get('/centrosdistribucion/{centrodistribucion_id}/entregas/prepare/ocs/{oc_id}', [EntregasController::class, 'store_prepare_centrodistribucion']);
    // Route::post('/centrosdistribucion/{centrodistribucion_id}/entregas/ocs/{oc_id}', [EntregasController::class, 'store_centrodistribucion']);
    // Route::get('/centrosdistribucion/{centrodistribucion_id}/entregas/{id}', [EntregasController::class, 'show_centrodistribucion']);
    // Route::get('/centrosdistribucion/{centrodistribucion_id}/entregas/{id}/prepare', [EntregasController::class, 'update_prepare_centrodistribucion']);
    // Route::put('/centrosdistribucion/{centrodistribucion_id}/entregas/{id}', [EntregasController::class, 'update_centrodistribucion']);
    // Route::delete('/centrosdistribucion/{centrodistribucion_id}/entregas/{id}', [EntregasController::class, 'destroy_centrodistribucion']);

    // // Recepciones (Sucursal)
    // Route::get('/sucursales/{id}/recepciones', [RecepcionesController::class, 'index_sucursal']);
    // Route::get('/sucursales/{sucursal_id}/centrosdistribucion/{centrodistribucion_id}/queuepartes', [RecepcionesController::class, 'queuePartes_sucursal']);
    // Route::post('/sucursales/{sucursal_id}/recepciones', [RecepcionesController::class, 'store_sucursal']);
    // Route::get('/sucursales/{sucursal_id}/recepciones/{id}', [RecepcionesController::class, 'show_sucursal']);
    // Route::get('/sucursales/{sucursal_id}/recepciones/{id}/prepare', [RecepcionesController::class, 'update_prepare_sucursal']);
    // Route::put('/sucursales/{sucursal_id}/recepciones/{id}', [RecepcionesController::class, 'update_sucursal']);
    // Route::delete('/sucursales/{sucursal_id}/recepciones/{id}', [RecepcionesController::class, 'destroy_sucursal']);

    // // Entregas (Sucursal)
    // Route::get('/sucursales/{id}/entregas', [EntregasController::class, 'index_sucursal']);
    // Route::get('/sucursales/{sucursal_id}/entregas/queueocs', [EntregasController::class, 'queueOcs_sucursal']);
    // Route::get('/sucursales/{sucursal_id}/entregas/prepare/ocs/{oc_id}', [EntregasController::class, 'store_prepare_sucursal']);
    // Route::post('/sucursales/{sucursal_id}/entregas/ocs/{oc_id}', [EntregasController::class, 'store_sucursal']);
    // Route::get('/sucursales/{sucursal_id}/entregas/{id}', [EntregasController::class, 'show_sucursal']);
    // Route::get('/sucursales/{sucursal_id}/entregas/{id}/prepare', [EntregasController::class, 'update_prepare_sucursal']);
    // Route::put('/sucursales/{sucursal_id}/entregas/{id}', [EntregasController::class, 'update_sucursal']);
    // Route::delete('/sucursales/{sucursal_id}/entregas/{id}', [EntregasController::class, 'destroy_sucursal']);
});