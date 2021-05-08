<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\Cotizacion;
use App\Models\Oc;
use App\Models\Motivobaja;

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

    public function indexMotivosBajaFull()
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('ocs show'))
            {
                if($motivosBaja = Motivobaja::all())
                {
                    foreach($motivosBaja as $motivoBaja)
                    {
                        $motivoBaja->makeHidden([ 
                            'created_at', 
                            'updated_at'
                        ]);
                    }

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $motivosBaja
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de motivos de baja',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar motivos de baja',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de motivos de baja [!]' .$e,
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

                        if($oc->filedata)
                        {
                            $oc->filedata->url;
                            $oc->filedata->name;
                            $oc->filedata->makeHidden([
                                'size',
                                'path',
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
                            
                            // Get quantities
                            $parte->pivot->cantidad_pendiente;
                            $parte->pivot->cantidad_compradorrecepcionado;
                            $parte->pivot->cantidad_compradordespachado;
                            $parte->pivot->cantidad_centrodistribucionrecepcionado;
                            $parte->pivot->cantidad_centrodistribuciondespachado;
                            $parte->pivot->cantidad_sucursalrecepcionado;
                            $parte->pivot->cantidad_sucursaldespachado;
    
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

    public function updateParte(Request $request, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('ocs update'))
            {
                $validatorInput = $request->only('nparte', 'cantidad', 'tiempoentrega', 'backorder');
            
                $validatorRules = [
                    'nparte' => 'required|exists:partes,nparte',
                    'cantidad.required' => 'Debes ingresar la cantidad para la parte',
                    'backorder'  => 'required|boolean',
                ];

                $validatorMessages = [
                    'nparte.required' => 'La lista de partes es invalida',
                    'nparte.exists' => 'La parte seleccionada no existe en la OC',
                    'cantidad.required' => 'Debes ingresar la cantidad para la parte',
                    'cantidad.numeric' => 'La cantidad para la parte debe ser numerica',
                    'cantidad.min' => 'La cantidad para la parte debe ser mayor a 0',
                    'tiempoentrega.required' => 'Debes ingresar el tiempo de entrega para la parte',
                    'tiempoentrega.numeric' => 'El tiempo de entrega para la parte debe ser numerico',
                    'tiempoentrega.min' => 'El tiempo de entrega para la parte debe ser mayor o igual a 0',
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
                else if(($oc = Oc::find($id)) === null)
                {
                    $response = HelpController::buildResponse(
                        412,
                        'La OC no existe',
                        null
                    );
                }
                else if(($oc->estadooc_id === 3) || ($oc->estadooc_id === 4))
                {
                    //If Cerrada or Baja
                    $response = HelpController::buildResponse(
                        400,
                        'No puedes editar una OC que ya esta cerrada o de baja',
                        null
                    );
                }
                else     
                {
                    if($parte = $oc->partes->where('nparte', $request->nparte)->first())
                    {
                        if($request->cantidad >= $parte->pivot->cantidad_recepcionadocomprador)
                        {
                            $parte->pivot->cantidad = $request->cantidad;
                            $parte->pivot->tiempoentrega = $request->tiempoentrega;
                            $parte->pivot->backorder = $request->backorder;

                            //If all of them (cantidad) were delivered
                            if($parte->pivot->cantidad_sucursaldespachado === $request->cantidad)
                            {
                                $parte->pivot->estadoocparte_id = 3; //Entregado
                            }
                        
                            if($parte->pivot->save())
                            {
                                $response = HelpController::buildResponse(
                                    200,
                                    'Parte actualizada',
                                    null
                                );
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al actualizar la parte en la OC',
                                    null
                                );
                            }
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                409,
                                [
                                    'cantidad' => [
                                        'La cantidad debe ser mayor o igual a la de partes ya asignadas'
                                    ]
                                ],
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'La parte no existe en la OC',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar OC',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al actualizar la parte en la OC [!]',
                null
            );
        }
        
        return $response;
    }

    public function reject(Request $request, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('ocs reject'))
            {
                $validatorInput = $request->only(
                    'motivobaja_id'
                );
                
                $validatorRules = [
                    'motivobaja_id' => 'required|exists:motivosbaja,id',
                ];
        
                $validatorMessages = [
                    'motivobaja_id.required' => 'Debes seleccionar el motivo de baja',
                    'motivobaja_id.exists' => 'El motivo de baja no existe',
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
                                'No tienes acceso a dar de baja esta OC',
                                null
                            );
                        }
                        else if(($oc->estadooc_id === 3) || ($oc->estadooc_id === 4))
                        {
                            //If Cerrada or Baja
                            $response = HelpController::buildResponse(
                                400,
                                'No puedes dar de baja una OC que ya esta cerrada o de baja',
                                null
                            );
                        }
                        else
                        {
                            $oc->estadooc_id = 4; // Baja
                            $oc->motivobaja_id = $request->motivobaja_id;
                            
                            if($oc->save())
                            {
                                $response = HelpController::buildResponse(
                                    200,
                                    'OC dada de baja',
                                    null
                                );
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al dar de baja la OC',
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
                    'No tienes acceso a dar de baja OCs',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al dar de baja la OC [!]',
                null
            );
        }
        
        return $response;
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
                        else if($oc->estadooc_id !== 1)
                        {
                            //If not Pendiente
                            $response = HelpController::buildResponse(
                                400,
                                'Solo puedes procesar OCs que estan pendiente',
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
