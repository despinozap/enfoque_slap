<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Parameter;
use App\Models\Solicitud;
use App\Models\Parte;
use App\Models\Cotizacion;

class CotizacionesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('cotizaciones index'))
            {
                switch($user->role->id)
                {
                    /*
                    case 2: //Vendedor
                    {
                        if($solicitudes = Solicitud::where('user_id', $user->id)->get()) //Only belonging data
                        {
                            foreach($solicitudes as $solicitud)
                            {
                                $solicitud->makeHidden(['cliente_id', 'marca_id', 'user_id', 'estadosolicitud_id']);

                                $totalPartes = 0;
                                foreach($solicitud->partes as $parte)
                                {   
                                    $parte->makeHidden(['marca_id', 'created_at', 'updated_at']);
                                    
                                    $parte->pivot;
                                    $totalPartes += $parte->pivot->cantidad;
                                    $parte->pivot->makeHidden(['solicitud_id', 'parte_id']);

                                    $parte->marca;
                                    $parte->marca->makeHidden(['created_at', 'updated_at']);
                                }

                                $solicitud->partes_total;
                                $solicitud->faena;
                                $solicitud->faena->makeHidden(['created_at', 'updated_at']);
                                $solicitud->faena->cliente;
                                $solicitud->faena->cliente->makeHidden(['created_at', 'updated_at']);
                                $solicitud->marca;
                                $solicitud->marca->makeHidden(['created_at', 'updated_at']);
                                $solicitud->user;
                                $solicitud->user->makeHidden(['email', 'phone', 'role_id', 'email_verified_at', 'created_at', 'updated_at']);
                                $solicitud->estadosolicitud;
                                $solicitud->estadosolicitud->makeHidden(['created_at', 'updated_at']);
                            }

                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $solicitudes
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al obtener la lista de solicitudes',
                                null
                            );
                        }

                        break;
                    }
                    */

                    default: //All others
                    {
                        if($cotizaciones = Cotizacion::all())
                        {
                            foreach($cotizaciones as $cotizacion)
                            {
                                $cotizacion->partes_total;
                                $cotizacion->dias;
                                $cotizacion->monto;

                                $cotizacion->makeHidden([
                                    'solicitud_id', 
                                    'estadocotizacion_id', 
                                    'created_at', 
                                    //'updated_at'
                                ]);

                                foreach($cotizacion->partes as $parte)
                                {   
                                    $parte->makeHidden(['marca_id', 'created_at', 'updated_at']);
                                    
                                    $parte->pivot;
                                    $parte->pivot->makeHidden(['cotizacion_id', 'parte_id']);

                                    $parte->marca;
                                    $parte->marca->makeHidden(['created_at', 'updated_at']);
                                }

                                $cotizacion->solicitud;
                                $cotizacion->solicitud->makeHidden(['partes', 'faena_id', 'marca_id', 'user_id', 'estadosolicitud_id', 'marca_id', 'created_at', 'updated_at']);
                                $cotizacion->solicitud->faena;
                                $cotizacion->solicitud->faena->makeHidden(['cliente_id', 'created_at', 'updated_at']);
                                $cotizacion->solicitud->faena->cliente;
                                $cotizacion->solicitud->faena->cliente->makeHidden(['created_at', 'updated_at']);
                                $cotizacion->solicitud->marca;
                                $cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);
                                $cotizacion->solicitud->user;
                                $cotizacion->solicitud->user->makeHidden(['email', 'phone', 'role_id', 'email_verified_at', 'created_at', 'updated_at']);

                                $cotizacion->estadocotizacion;
                                $cotizacion->estadocotizacion->makeHidden(['created_at', 'updated_at']);
                            }

                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $cotizaciones
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al obtener la lista de cotizaciones',
                                null
                            );
                        }

                        break;
                    }
                }
                
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar cotizaciones',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de cotizaciones [!]' .$e,
                null
            );
        }

        return $response;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
