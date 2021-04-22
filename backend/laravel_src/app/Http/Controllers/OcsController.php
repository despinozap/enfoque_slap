<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Validator;

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
                        
                        // If user has role Vendedor retrieves monto converted to CLP
                        if($user->role->id === 2)
                        {
                            // CLP conversion
                            $oc->monto = $oc->usd_monto * $oc->usdvalue;
                        }
                        else
                        {
                            $oc->monto = $oc->usd_monto;
                        }
                        

                        $oc->makeHidden([
                            'cotizacion_id', 
                            'estadooc_id', 
                            'created_at', 
                            //'updated_at'
                        ]);

                        foreach($oc->partes as $parte)
                        {   
                            $parte->makeHidden(['marca_id', 'created_at', 'updated_at']);
                            
                            $parte->pivot;
                            $parte->pivot->makeHidden(['oc_id', 'parte_id']);

                            $parte->marca;
                            $parte->marca->makeHidden(['created_at', 'updated_at']);
                        }
                        

                        $oc->cotizacion;
                        $oc->cotizacion->makeHidden(['partes', 'estadocotizacion_id', 'created_at', 'updated_at']);
                        $oc->cotizacion->solicitud;
                        $oc->cotizacion->solicitud->makeHidden(['partes', 'faena_id', 'marca_id', 'user_id', 'estadosolicitud_id', 'marca_id', 'created_at', 'updated_at']);
                        $oc->cotizacion->solicitud->faena;
                        $oc->cotizacion->solicitud->faena->makeHidden(['cliente_id', 'created_at', 'updated_at']);
                        $oc->cotizacion->solicitud->faena->cliente;
                        $oc->cotizacion->solicitud->faena->cliente->makeHidden(['created_at', 'updated_at']);
                        $oc->cotizacion->solicitud->marca;
                        $oc->cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);
                        $oc->cotizacion->solicitud->user;
                        $oc->cotizacion->solicitud->user->makeHidden(['email', 'phone', 'role_id', 'email_verified_at', 'created_at', 'updated_at']);

                        $oc->estadooc;
                        $oc->estadooc->makeHidden(['created_at', 'updated_at']);
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
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('ocs show'))
            {
                if($oc = Oc::find($id))
                {
                    if(($user->role_id === 2) && ($oc->cotizacion->solicitud->user_id !== $user->id))
                    {
                        //If Vendedor and solicitud doesn't belong
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a visualizar esta OC',
                            null
                        );
                    }
                    else
                    {
                        $oc->dias;
                        $oc->makeHidden([
                            'cotizacion_id',
                            'filedata_id',
                            'proveedor_id',
                            'estadooc_id',
                            'partes_total',
                            'created_at', 
                            'updated_at'
                        ]);

                        if($oc->proveedor)
                        {
                            $oc->proveedor->makeHidden([
                                'comprador_id',
                                'rut',
                                'address',
                                'city',
                                'contact',
                                'phone',
                                'created_at', 
                                'updated_at'
                            ]);
                        }
    
                        $oc->cotizacion;
                        $oc->cotizacion->makeHidden([
                            'solicitud_id',
                            'motivorechazo_id',
                            'estadocotizacion_id',
                            'usdvalue',
                            'partes_total',
                            'dias',
                            'created_at', 
                            'updated_at'
                        ]);

                        $oc->cotizacion->solicitud;
                        $oc->cotizacion->solicitud->makeHidden([
                            'faena_id',
                            'marca_id',
                            'comprador_id',
                            'estadosolicitud_id',
                            'comentario',
                            'partes_total',
                            'user_id',
                            'created_at', 
                            'updated_at'
                        ]);

                        $oc->cotizacion->solicitud->faena;
                        $oc->cotizacion->solicitud->faena->makeHidden([
                            'cliente_id',
                            'rut',
                            'address',
                            'city',
                            'contact',
                            'phone',
                            'created_at',
                            'updated_at'
                        ]);
    
                        $oc->cotizacion->solicitud->faena->cliente;
                        $oc->cotizacion->solicitud->faena->cliente->makeHidden([
                            'sucursal_id', 
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $oc->cotizacion->solicitud->marca;
                        $oc->cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);

                        $oc->cotizacion->solicitud->comprador;
                        $oc->cotizacion->solicitud->comprador->makeHidden([
                            'rut',
                            'address',
                            'city',
                            'contact',
                            'phone',
                            'created_at', 
                            'updated_at'
                        ]);

                        $oc->estadooc;
                        $oc->estadooc->makeHidden(['created_at', 'updated_at']);
    
                        $oc->partes;
                        foreach($oc->partes as $parte)
                        {
                            $parte->makeHidden([
                                'marca_id', 
                                'created_at', 
                                'updated_at'
                            ]);
    
                            $parte->pivot->estadoocparte;
                            $parte->pivot->estadoocparte->makeHidden([
                                'created_at',
                                'updated_at'
                            ]);

                            switch($user->role_id)
                            {
                                case 1: { // Administrador
    
                                    $parte->pivot->makeHidden([
                                        'oc_id',
                                        'parte_id',
                                        'estadoocparte_id', 
                                        'created_at', 
                                        //'updated_at'
                                    ]);
    
                                    break;
                                }
    
                                case 2: { // Vendedor
    
                                    if($parte->pivot->monto !== null)
                                    {
                                        $parte->pivot->monto = $parte->pivot->monto * $oc->usdvalue;
                                    }
                                    
                                    $parte->pivot->makeHidden([
                                        'oc_id',
                                        'parte_id',
                                        'estadoocparte_id', 
                                        'created_at', 
                                        //'updated_at'
                                    ]);
    
                                    break;
                                }
    
                                default: {
    
                                    break;
                                }
                            }
                        }
                        
                        $response = HelpController::buildResponse(
                            200,
                            null,
                            $oc
                        );
                    }
                    
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        400,
                        'La OC no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar OCs',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la OC [!]',
                null
            );
        }
        

        return $response;
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

    public function start(Request $request, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('ocs update'))
            {
                $validatorInput = $request->only(
                    'proveedor_id'
                );
                
                $validatorRules = [
                    'proveedor_id' => 'required|exists:proveedores,id',
                ];
        
                $validatorMessages = [
                    'proveedor_id.required' => 'Debes seleccionar el proveedor',
                    'proveedor_id.exists' => 'El proveedor no existe',
                ];
        
                $validator = Validator::make(
                    $validatorInput,
                    $validatorRules,
                    $validatorMessages
                );
        
                if ($validator->fails()) 
                {
                    $response = HelpController::buildResponse(
                        400,
                        $validator->errors(),
                        null
                    );
                }
                else        
                {
                    if($oc = Oc::find($id))
                    {
                        if(($user->role_id === 2) && ($oc->cotizacion->solicitud->user_id !== $user->id))
                        {
                            //If Vendedor and solicitud doesn't belong
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a procesar esta OC',
                                null
                            );
                        }
                        else
                        {
                            $oc->estadooc_id = 2; // En proceso
                            $oc->proveedor_id = $request->proveedor_id;
                            
                            if($oc->save())
                            {
                                $response = HelpController::buildResponse(
                                    200,
                                    'Proceso de OC iniciado',
                                    null
                                );
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al iniciar el proceso de la OC',
                                    null
                                );   
                            }
                        }
                        
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            400,
                            'La OC no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a iniciar procesos de OCs',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al iniciar el proceso de la OC [!]',
                null
            );
        }
        
        return $response;
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
