<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\Cotizacion;
use App\Models\Oc;
use App\Models\Proveedor;
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
                $ocs = null;

                switch($user->role->name)
                {
                    // Administrador
                    case 'admin': {

                        $ocs = Oc::select('ocs.*')
                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // For Solicitudes in the same Country
                            ->get();

                        break;
                    }

                    // Vendedor
                    case 'seller': {

                        $ocs = Oc::select('ocs.*')
                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                            ->where('sucursales.id', '=', $user->stationable->id) // For Solicitudes in its Sucursal
                            ->where('solicitudes.user_id', '=', $user->id) // Only belonging data
                            ->get();

                        break;
                    }

                    // Agente de compra
                    case 'agtcom': {

                        $ocs = Oc::select('ocs.*')
                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                            ->where('solicitudes.comprador_id', '=', $user->stationable->id) // For Solicitudes in its Comprador
                            ->get();

                        break;
                    }

                    default:
                    {
                        break;
                    }
                }

                if($ocs !== null)
                {
                    foreach($ocs as $oc)
                    {
                        $oc->partes_total;
                        $oc->dias;
                        
                        $oc->makeHidden([
                            'cotizacion_id',
                            'proveedor_id',
                            'filedata_id',
                            'motivobaja_id',
                            'estadooc_id', 
                            'created_at', 
                            //'updated_at'
                        ]);

                        foreach($oc->partes as $parte)
                        {   
                            $parte->makeHidden(['marca_id', 'created_at', 'updated_at']);
                            
                            $parte->pivot;
                            $parte->pivot->makeHidden(['oc_id', 'parte_id', 'estadoocparte_id']);

                            $parte->marca;
                            $parte->marca->makeHidden(['created_at', 'updated_at']);
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
                            'updated_at',
                            'partes',
                        ]);
                        
                        $oc->cotizacion->solicitud;
                        $oc->cotizacion->solicitud->makeHidden([
                            'partes_total',
                            'comentario',
                            'sucursal_id',
                            'comprador_id',
                            'user_id',
                            'faena_id',
                            'marca_id',
                            'estadosolicitud_id',
                            'created_at', 
                            'updated_at'
                        ]);
                                    
                        $oc->cotizacion->solicitud->sucursal;
                        $oc->cotizacion->solicitud->sucursal->makeHidden([
                            'type',
                            'rut',
                            'address',
                            'city',
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $oc->cotizacion->solicitud->faena;
                        $oc->cotizacion->solicitud->faena->makeHidden([
                            'sucursal_id',
                            'rut',
                            'address',
                            'city',
                            'contact',
                            'phone',
                            'cliente_id', 
                            'created_at', 
                            'updated_at'
                        ]);

                        $oc->cotizacion->solicitud->faena->cliente;
                        $oc->cotizacion->solicitud->faena->cliente->makeHidden([
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $oc->cotizacion->solicitud->marca;
                        $oc->cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);
                        
                        $oc->cotizacion->solicitud->user;
                        $oc->cotizacion->solicitud->user->makeHidden(['email', 'phone', 'country_id', 'role_id', 'email_verified_at', 'created_at', 'updated_at']);
                        
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
            if($user->role->hasRoutepermission('ocs reject'))
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
                'Error al obtener la lista de motivos de baja [!]',
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

    public function report(Request $request)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('ocs report'))
            {
                $validatorInput = $request->only(
                    'ocs'
                );
                
                $validatorRules = [
                    'ocs' => 'required|array|min:1',
                    'ocs.*'  => 'required|exists:ocs,id',
                ];
        
                $validatorMessages = [
                    'ocs.required' => 'Debes seleccionar las OCs',
                    'ocs.array' => 'Lista de OCs es invalida',
                    'ocs.min' => 'El reporte debe contener al menos 1 OC',
                    'ocs.*.exists' => 'La lista de OCs es invalida',
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
                    $ocs = null;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            $ocs = Oc::select('ocs.*')
                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                ->where('sucursales.country_id', '=', $user->stationable->country->id) // For Solicitudes in the same Country
                                ->whereIn('ocs.id', $request->ocs) // For the requested OCs
                                ->get();

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            $ocs = Oc::select('ocs.*')
                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                ->where('sucursales.id', '=', $user->stationable->id) // For Solicitudes in its Sucursal
                                ->where('solicitudes.user_id', '=', $user->id) // Only belonging data
                                ->whereIn('ocs.id', $request->ocs) // For the requested OCs
                                ->get();

                            break;
                        }

                        // Agente de compra
                        case 'agtcom': {

                            $ocs = Oc::select('ocs.*')
                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                ->where('solicitudes.comprador_id', '=', $user->stationable->id) // For Solicitudes in its Comprador
                                ->whereIn('ocs.id', $request->ocs) // For the requested OCs
                                ->get();

                            break;
                        }

                        default:
                        {
                            break;
                        }
                    }

                    if($ocs !== null)
                    {
                        foreach($ocs as $oc) 
                        {
                            $oc->dias;
                            $oc->makeHidden([
                                'cotizacion_id',
                                'filedata_id',
                                'proveedor_id',
                                'motivobaja_id',
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
                                'updated_at',
                                'partes',
                            ]);
                            
                            $oc->cotizacion->solicitud;
                            $oc->cotizacion->solicitud->makeHidden([
                                'partes_total',
                                'comentario',
                                'sucursal_id',
                                'comprador_id',
                                'user_id',
                                'faena_id',
                                'marca_id',
                                'estadosolicitud_id',
                                'created_at', 
                                'updated_at'
                            ]);
                                        
                            $oc->cotizacion->solicitud->sucursal;
                            $oc->cotizacion->solicitud->sucursal->makeHidden([
                                'type',
                                'rut',
                                'address',
                                'city',
                                'country_id',
                                'created_at', 
                                'updated_at'
                            ]);
                            
                            $oc->cotizacion->solicitud->faena;
                            $oc->cotizacion->solicitud->faena->makeHidden([
                                'sucursal_id',
                                'rut',
                                'address',
                                'city',
                                'contact',
                                'phone',
                                'cliente_id', 
                                'created_at', 
                                'updated_at'
                            ]);

                            $oc->cotizacion->solicitud->faena->cliente;
                            $oc->cotizacion->solicitud->faena->cliente->makeHidden([
                                'country_id',
                                'created_at', 
                                'updated_at'
                            ]);

                            $oc->cotizacion->solicitud->marca;
                            $oc->cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);
                            
                            $oc->cotizacion->solicitud->user;
                            $oc->cotizacion->solicitud->user->makeHidden([
                                'stationable_id',
                                'stationable_type',
                                'email', 
                                'phone', 
                                'country_id', 
                                'role_id', 
                                'email_verified_at', 
                                'created_at', 
                                'updated_at'
                            ]);
                            
                            $oc->cotizacion->solicitud->comprador;
                            $oc->cotizacion->solicitud->comprador->makeHidden([
                                'rut',
                                'address',
                                'city',
                                'contact',
                                'phone',
                                'country_id',
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

                                $parte->pivot->makeHidden([
                                    'oc',
                                    'oc_id',
                                    'parte_id',
                                    'estadoocparte_id', 
                                    'created_at', 
                                    //'updated_at'
                                ]);                        
        
                                $parte->pivot->estadoocparte;
                                $parte->pivot->estadoocparte->makeHidden([
                                    'created_at',
                                    'updated_at'
                                ]);
                            }
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
                            'Error al obtener el reporte de OC',
                            null
                        );
                    }                     
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
                'Error al obtener el reporte de OC [!]',
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
                    'cantidad' => 'required|numeric|min:1',
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
                // Administrador
                else if(
                    ($user->role->name === 'admin') && 
                    ($oc->cotizacion->solicitud->sucursal->country->id !== $user->stationable->country->id)
                )
                {
                    //If Administrator and solicitud doesn't belong to its country
                    $response = HelpController::buildResponse(
                        405,
                        'No tienes acceso a editar esta OC',
                        null
                    );
                }
                // Vendedor
                else if(
                    ($user->role->name === 'seller') &&
                    (
                        ($oc->cotizacion->solicitud->sucursal->id !== $user->stationable->id) ||
                        ($oc->cotizacion->solicitud->user->id !== $user->id)
                    ) 
                )
                {
                    //If Vendedor and solicitud doesn't belong or not in its Sucursal
                    $response = HelpController::buildResponse(
                        405,
                        'No tienes acceso a editar esta OC',
                        null
                    );
                }
                // Agente de compra
                else if(
                    ($user->role->name === 'agtcom') &&
                    ($oc->cotizacion->solicitud->comprador->id !== $user->stationable->id)
                )
                {
                    //If Agente de compra and solicitud isn't to its Comprador
                    $response = HelpController::buildResponse(
                        405,
                        'No tienes acceso a editar esta OC',
                        null
                    );
                }
                else if(in_array($oc->estadooc_id, [3, 4]))
                {
                    //If not Pendiente or En proceso
                    $response = HelpController::buildResponse(
                        409,
                        'No puedes editar una OC que ya esta cerrada o de baja',
                        null
                    );
                }
                else     
                {
                    if($parte = $oc->partes->where('nparte', $request->nparte)->first())
                    {
                        $success = true;

                        DB::beginTransaction();

                        $parte->pivot->cantidad = $request->cantidad;
                        $parte->pivot->tiempoentrega = $request->tiempoentrega;
                        $parte->pivot->backorder = $request->backorder;

                        // If Oc is Estadooc = 'En proceso'
                        if($oc->estadooc_id === 2)
                        {
                            $cantidadRecepcionadoAtComprador = $parte->pivot->getCantidadRecepcionado($oc->cotizacion->solicitud->comprador);

                            //If new cantidad is less than cantidad already received at Comprador
                            if($request->cantidad < $cantidadRecepcionadoAtComprador)
                            {
                                $response = HelpController::buildResponse(
                                    400,
                                    [
                                        "cantidad" => [
                                            "La cantidad debe ser mayor o igual a la ya recepcionada por el comprador"
                                        ]
                                    ],
                                    null
                                );

                                $success = false;
                            }
                            // If OcParte was full delivered to Faena at Sucursal (or Centro)
                            else if($request->cantidad === $parte->pivot->getCantidadTotalEntregado())
                            {
                                // Update OcParte status
                                $parte->pivot->estadoocparte_id = 3; // Estadoocparte = 'Entregado'
                            }
                            // If OcParte was full received at Comprador
                            else if($request->cantidad === $cantidadRecepcionadoAtComprador)
                            {
                                // Update OcParte status
                                $parte->pivot->estadoocparte_id = 2; //Estadoocparte = 'En transito'
                            }
                            else
                            {
                                // Update OcParte status
                                $parte->pivot->estadoocparte_id = 1; // Estadoocparte = 'Pendiente'
                            }

                            // Eval if all the OcPartes in Oc were fully delivered in Entregas
                            $ocFullDelivered = $oc->partes->reduce(function($carry, $parte)
                                {
                                    // Eval condition only if carry is still true
                                    if($carry === true)
                                    {
                                        // It will break whenever the condition (cantidad total in Entregas === cantidad total in Oc) is false
                                        if($parte->pivot->getCantidadTotalEntregado() < $parte->pivot->cantidad)
                                        {
                                            $carry = false;
                                        }
                                    }

                                    return $carry;       
                                },
                                true // Initialize in true
                            );

                            // If Oc is full delivered
                            if($ocFullDelivered === true)
                            {
                                $oc->estadooc_id = 3; // Estadooc = 'Cerrada'
                                if(!$oc->save())
                                {
                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al actualizar el estado de la OC',
                                        null
                                    );

                                    $success = false;
                                }
                            }
                        }

                        
                        if($success === true)
                        {
                            if($parte->pivot->save())
                            {
                                DB::commit();

                                $response = HelpController::buildResponse(
                                    200,
                                    'Parte actualizada',
                                    null
                                );
                            }
                            else
                            {
                                DB::rollback();

                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al actualizar la parte en la OC',
                                    null
                                );
                            }
                        }
                        else
                        {
                            DB::rollback();

                            // Error message was already given
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

    public function destroyParte($id, $parte_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('ocs update'))
            {
                $validatorInput = [
                    'parte_id' => $parte_id
                ];
            
                $validatorRules = [
                    'parte_id' => 'required|exists:partes,id'
                ];

                $validatorMessages = [
                    'parte_id.required' => 'Debes inresar la parte',
                    'parte_id.exists' => 'La parte seleccionada no existe en la OC'
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
                // Administrador
                else if(
                    ($user->role->name === 'admin') && 
                    ($oc->cotizacion->solicitud->sucursal->country->id !== $user->stationable->country->id)
                )
                {
                    //If Administrator and solicitud doesn't belong to its country
                    $response = HelpController::buildResponse(
                        405,
                        'No tienes acceso a editar esta OC',
                        null
                    );
                }
                // Vendedor
                else if(
                    ($user->role->name === 'seller') &&
                    (
                        ($oc->cotizacion->solicitud->sucursal->id !== $user->stationable->id) ||
                        ($oc->cotizacion->solicitud->user->id !== $user->id)
                    ) 
                )
                {
                    //If Vendedor and solicitud doesn't belong or not in its Sucursal
                    $response = HelpController::buildResponse(
                        405,
                        'No tienes acceso a editar esta OC',
                        null
                    );
                }
                // Agente de compra
                else if(
                    ($user->role->name === 'agtcom') &&
                    ($oc->cotizacion->solicitud->comprador->id !== $user->stationable->id)
                )
                {
                    //If Agente de compra and solicitud isn't to its Comprador
                    $response = HelpController::buildResponse(
                        405,
                        'No tienes acceso a editar esta OC',
                        null
                    );
                }
                else if(in_array($oc->estadooc_id, [3, 4]))
                {
                    //If not Pendiente or En proceso
                    $response = HelpController::buildResponse(
                        409,
                        'No puedes editar una OC que ya esta cerrada o de baja',
                        null
                    );
                }
                else     
                {
                    if($parte = $oc->partes->find($parte_id))
                    {                
                        $success = true;

                        // If Oc is Estadooc = 'En proceso'
                        if($oc->estadooc_id === 2)
                        {
                            //If new cantidad is less than cantidad already received at Comprador
                            if($request->cantidad < $parte->pivot->getCantidadRecepcionado($oc->cotizacion->solicitud->comprador))
                            {
                                $response = HelpController::buildResponse(
                                    400,
                                    [
                                        "cantidad" => [
                                            "La parte tiene cantidades ya recepcionadas por el comprador"
                                        ]
                                    ],
                                    null
                                );

                                $success = false;
                            }
                            // If this is the only one parte in Oc
                            else if($oc->partes->count() < 2)
                            {
                                $response = HelpController::buildResponse(
                                    400,
                                    [
                                        "cantidad" => [
                                            "La OC debe tener al menos 1 parte"
                                        ]
                                    ],
                                    null
                                );

                                $success = false;
                            }
                        }

                        if($success === true)
                        {
                            $parte->pivot->cantidad = $request->cantidad;
                            $parte->pivot->tiempoentrega = $request->tiempoentrega;
                            $parte->pivot->backorder = $request->backorder;

                            if($oc->partes()->detach($parte->id))
                            {
                                $response = HelpController::buildResponse(
                                    200,
                                    'Parte eliminada',
                                    null
                                );
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al eliminar la parte en la OC',
                                    null
                                );
                            }
                        }
                        else
                        {
                            // Error message was already given
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
                        // Administrador
                        if(
                            ($user->role->name === 'admin') && 
                            ($oc->cotizacion->solicitud->sucursal->country->id !== $user->stationable->country->id)
                        )
                        {
                            //If Administrator and solicitud doesn't belong to its country
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a dar de baja esta OC',
                                null
                            );
                        }
                        // Vendedor
                        else if(
                            ($user->role->name === 'seller') &&
                            (
                                ($oc->cotizacion->solicitud->sucursal->id !== $user->stationable->id) ||
                                ($oc->cotizacion->solicitud->user->id !== $user->id)
                            ) 
                        )
                        {
                            //If Vendedor and solicitud doesn't belong or not in its Sucursal
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a dar de baja esta OC',
                                null
                            );
                        }
                        // Agente de compra
                        else if(
                            ($user->role->name === 'agtcom') &&
                            ($oc->cotizacion->solicitud->comprador->id !== $user->stationable->id)
                        )
                        {
                            //If Agente de compra and solicitud isn't to its Comprador
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a dar de baja esta OC',
                                null
                            );
                        }
                        else if(in_array($oc->estadooc_id, [2, 3, 4]))
                        {
                            //If not Pendiente
                            $response = HelpController::buildResponse(
                                409,
                                'No puedes dar de baja una OC que ya esta en proceso, cerrada o de baja',
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
                            412,
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
                        // Administrador
                        if(
                            ($user->role->name === 'admin') && 
                            ($oc->cotizacion->solicitud->sucursal->country->id !== $user->stationable->country->id)
                        )
                        {
                            //If Administrator and solicitud doesn't belong to its country
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a activar esta OC',
                                null
                            );
                        }
                        // Agente de compra
                        else if(
                            ($user->role->name === 'agtcom') &&
                            ($oc->cotizacion->solicitud->comprador->id !== $user->stationable->id)
                        )
                        {
                            //If Agente de compra and solicitud isn't to its Comprador
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a activar esta OC',
                                null
                            );
                        }
                        else if(in_array($oc->estadooc_id, [2, 3, 4]))
                        {
                            //If not Pendiente
                            $response = HelpController::buildResponse(
                                409,
                                'No puedes activar una OC que ya esta en proceso, cerrada o de baja',
                                null
                            );
                        }                      
                        else
                        {
                            if(
                                $proveedor = Proveedor::where('id', '=', $request->proveedor_id)
                                            ->where('comprador_id', '=', $oc->cotizacion->solicitud->comprador->id)
                                            ->first()
                            )
                            {
                                $oc->estadooc_id = 2; // En proceso
                                $oc->proveedor_id = $request->proveedor_id;
                                
                                if($oc->save())
                                {
                                    $response = HelpController::buildResponse(
                                        200,
                                        'Proceso de compra activado',
                                        null
                                    );
                                }
                                else
                                {
                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al activar el proceso de compra de la OC',
                                        null
                                    );   
                                }
                            }
                            else
                            {
                                //If Proveedor is not in Comprador
                                $response = HelpController::buildResponse(
                                    400,
                                    [
                                        'proveedor_id' => [
                                            'El proveedor ingresado no esta asociado al comprador'
                                        ]
                                    ],
                                    null
                                );
                            }
                        }
                        
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            412,
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
                    'No tienes acceso a activar procesos de compra de OCs',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al activar el proceso de compra de la OC [!]',
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
