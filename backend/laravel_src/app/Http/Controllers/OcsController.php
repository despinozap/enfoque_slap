<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

use App\Models\Cotizacion;
use App\Models\Oc;

class OcsController extends Controller
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
            if($user->role->hasRoutepermission('ocs index'))
            {
                
                if($ocs = ($user->role->id === 2) ? // By role
                    // If Vendedor filters only the belonging data
                    //Oc::select('cotizaciones.*')->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')->where('solicitudes.user_id', '=', $user->id)->get() :
                    Oc::all() :
                    // For any other role
                    Oc::all()
                )
                {
                    foreach($ocs as $oc)
                    {
                        $oc->partes_total;
                        $oc->dias;
                        $oc->monto;

                        $oc->makeHidden([
                            'cotizacion_id', 
                            'estadooc_id', 
                            'created_at', 
                            //'updated_at'
                        ]);

                        // foreach($oc->partes as $parte)
                        // {   
                        //     $parte->makeHidden(['marca_id', 'created_at', 'updated_at']);
                            
                        //     $parte->pivot;
                        //     $parte->pivot->makeHidden(['cotizacion_id', 'parte_id']);

                        //     $parte->marca;
                        //     $parte->marca->makeHidden(['created_at', 'updated_at']);
                        // }

                        // $oc->solicitud;
                        // $oc->solicitud->makeHidden(['partes', 'faena_id', 'marca_id', 'user_id', 'estadosolicitud_id', 'marca_id', 'created_at', 'updated_at']);
                        // $oc->solicitud->faena;
                        // $oc->solicitud->faena->makeHidden(['cliente_id', 'created_at', 'updated_at']);
                        // $oc->solicitud->faena->cliente;
                        // $oc->solicitud->faena->cliente->makeHidden(['created_at', 'updated_at']);
                        // $oc->solicitud->marca;
                        // $oc->solicitud->marca->makeHidden(['created_at', 'updated_at']);
                        // $oc->solicitud->user;
                        // $oc->solicitud->user->makeHidden(['email', 'phone', 'role_id', 'email_verified_at', 'created_at', 'updated_at']);

                        // $oc->estadocotizacion;
                        // $oc->estadocotizacion->makeHidden(['created_at', 'updated_at']);
                    }

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $ocs
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de OCs',
                        null
                    );
                }

            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar OCs',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de OCs [!]',
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
