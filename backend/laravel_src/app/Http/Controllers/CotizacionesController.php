<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use DateTime;

use App\Models\Parameter;
use App\Models\Sucursal;
use App\Models\Solicitud;
use App\Models\Parte;
use App\Models\Cotizacion;
use App\Models\Motivorechazo;
use App\Models\Filedata;
use App\Models\Oc;

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
                $cotizaciones = null;

                switch($user->role->name)
                {
                    // Administrador
                    case 'admin': {

                        $cotizaciones = Cotizacion::select('cotizaciones.*')
                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                    ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                    ->where('sucursales.country_id', '=', $user->stationable->country->id) // For Solicitudes in the same Country
                                    ->get();

                        break;
                    }

                    // Vendedor
                    case 'seller': {

                        $cotizaciones = Cotizacion::select('cotizaciones.*')
                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                    ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                    ->where('sucursales.id', '=', $user->stationable->id) // For Solicitudes in its Sucursal
                                    ->where('solicitudes.user_id', '=', $user->id) // Only belonging data
                                    ->get();

                        break;
                    }

                    // Agente de compra
                    case 'agtcom': {

                        $cotizaciones = Cotizacion::select('cotizaciones.*')
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

                if($cotizaciones !== null)
                {
                    foreach($cotizaciones as $cotizacion)
                    {
                        $cotizacion->partes_total;
                        $cotizacion->dias;

                        $cotizacion->makeHidden([
                            'solicitud_id', 
                            'estadocotizacion_id', 
                            'motivorechazo_id', 
                            'updated_at'
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
                        $cotizacion->solicitud->makeHidden([
                            'partes',
                            'sucursal_id',
                            'faena_id',
                            'marca_id',
                            'user_id',
                            'estadosolicitud_id',
                            'marca_id',
                            'created_at',
                            'updated_at'
                        ]);
                        $cotizacion->solicitud->faena;
                        $cotizacion->solicitud->faena->makeHidden([
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
                        $cotizacion->solicitud->faena->cliente;
                        $cotizacion->solicitud->faena->cliente->makeHidden([
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);
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
                'Error al obtener la lista de cotizaciones [!]' . $e,
                null
            );
        }

        return $response;
    }

    public function indexMotivosRechazoFull()
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('cotizaciones reject'))
            {
                if($motivosRechazo = Motivorechazo::all())
                {
                    foreach($motivosRechazo as $motivoRechazo)
                    {
                        $motivoRechazo->makeHidden([ 
                            'created_at', 
                            'updated_at'
                        ]);
                    }

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $motivosRechazo
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de motivos de rechazo',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar motivos de rechazo',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de motivos de rechazo [!]',
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
            if($user->role->hasRoutepermission('cotizaciones report'))
            {
                $validatorInput = $request->only(
                    'cotizaciones'
                );
                
                $validatorRules = [
                    'cotizaciones' => 'required|array|min:1',
                    'cotizaciones.*'  => 'required|exists:cotizaciones,id',
                ];
        
                $validatorMessages = [
                    'cotizaciones.required' => 'Debes seleccionar las cotizaciones',
                    'cotizaciones.array' => 'Lista de cotizaciones es invalida',
                    'cotizaciones.min' => 'El reporte debe contener al menos 1 cotizacion',
                    'cotizaciones.*.exists' => 'La lista de cotizaciones es invalida',
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
                    $cotizaciones = null;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            $cotizaciones = Cotizacion::select('cotizaciones.*')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                        ->where('sucursales.country_id', '=', $user->stationable->country->id) // For Solicitudes in the same Country
                                        ->whereIn('cotizaciones.id', $request->cotizaciones) // For the requested Cotizaciones
                                        ->get();

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            $cotizaciones = Cotizacion::select('cotizaciones.*')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                        ->where('sucursales.id', '=', $user->stationable->id) // For Solicitudes in its Sucursal
                                        ->where('solicitudes.user_id', '=', $user->id) // Only belonging data
                                        ->whereIn('cotizaciones.id', $request->cotizaciones) // For the requested Cotizaciones
                                        ->get();

                            break;
                        }

                        // Agente de compra
                        case 'agtcom': {

                            $cotizaciones = Cotizacion::select('cotizaciones.*')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->where('solicitudes.comprador_id', '=', $user->stationable->id) // For Solicitudes in its Comprador
                                        ->whereIn('cotizaciones.id', $request->cotizaciones) // For the requested Cotizaciones
                                        ->get();

                            break;
                        }

                        default:
                        {
                            break;
                        }
                    }

                    if($cotizaciones !== null)
                    {
                        foreach($cotizaciones as $cotizacion) 
                        {
                            $cotizacion->dias;
                            $cotizacion->makeHidden([
                                'solicitud_id',
                                'motivorechazo_id',
                                'estadocotizacion_id',
                                'updated_at',
                            ]);

                            $cotizacion->solicitud;
                            $cotizacion->solicitud->makeHidden([
                                'partes',
                                'sucursal_id',
                                'comprador_id',
                                'faena_id',
                                'marca_id',
                                'user_id',
                                'estadosolicitud_id',
                                'created_at', 
                                'updated_at'
                            ]);
        
                            $cotizacion->solicitud->faena;
                            $cotizacion->solicitud->faena->makeHidden(['cliente_id', 'sucursal_id', 'created_at', 'updated_at']);
        
                            $cotizacion->solicitud->faena->cliente;
                            $cotizacion->solicitud->faena->cliente->makeHidden([
                                'sucursal_id', 
                                'country_id', 
                                'created_at', 
                                'updated_at'
                            ]);

                            $cotizacion->solicitud->sucursal;
                            $cotizacion->solicitud->sucursal->makeHidden([
                                'country_id', 
                                'created_at',
                                'updated_at'
                            ]);
                            
                            $cotizacion->solicitud->marca;
                            $cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);

                            $cotizacion->solicitud->comprador;
                            $cotizacion->solicitud->comprador->makeHidden(['country_id', 'created_at', 'updated_at']);

                            $cotizacion->estadocotizacion;
                            $cotizacion->estadocotizacion->makeHidden(['created_at', 'updated_at']);

                            $cotizacion->motivorechazo;
                            if($cotizacion->motivorechazo !== null)
                            {
                                $cotizacion->motivorechazo->makeHidden(['created_at', 'updated_at']);
                            }

                            $cotizacion->solicitud->user;
                            $cotizacion->solicitud->user->makeHidden(['email_verified_at', 'role_id', 'country_id', 'created_at', 'updated_at']);
        
                            $cotizacion->partes;
                            foreach($cotizacion->partes as $parte)
                            {
                                $parte->makeHidden([
                                    'marca',
                                    'marca_id', 
                                    'created_at', 
                                    'updated_at'
                                ]);
                                
                                $parte->pivot->makeHidden([
                                    'costo',
                                    'margen',
                                    'peso',
                                    'flete',
                                    'backorder',
                                    'cotizacion_id',
                                    'parte_id',
                                    'marca_id', 
                                    'created_at', 
                                    'updated_at'
                                ]);
                            }
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
                            'Error al obtener el reporte de cotizacion',
                            null
                        );
                    }                     
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar cotizaciones',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener el reporte de cotizacion [!]',
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
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('cotizaciones update'))
            {
                $validatorInput = $request->only(
                    'partes'
                );
                
                $validatorRules = [
                    'partes' => 'required|array|min:1',
                    'partes.*.nparte'  => 'required',
                    'partes.*.cantidad'  => 'required|numeric|min:1',
                ];
        
                $validatorMessages = [
                    'partes.required' => 'Debes seleccionar las partes',
                    'partes.array' => 'Lista de partes invalida',
                    'partes.min' => 'La solicitud debe contener al menos 1 parte',
                    'partes.*.nparte.required' => 'La lista de partes es invalida',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte debe ser mayor a 0',
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
                    if($cotizacion = Cotizacion::find($id))
                    {
                        // Administrador
                        if(
                            ($user->role->name === 'admin') && 
                            ($cotizacion->solicitud->sucursal->country->id !== $user->stationable->country->id)
                        )
                        {
                            //If Administrator and solicitud doesn't belong to its country
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a actualizar esta cotizacion',
                                null
                            );
                        }
                        // Vendedor
                        else if(
                            ($user->role->name === 'seller') &&
                            (
                                ($cotizacion->solicitud->sucursal->id !== $user->stationable->id) ||
                                ($cotizacion->solicitud->user->id !== $user->id)
                            ) 
                        )
                        {
                            //If Vendedor and solicitud doesn't belong or not in its Sucursal
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a actualizar esta cotizacion',
                                null
                            );
                        }
                        else if(in_array($cotizacion->estadocotizacion_id, [3, 4])) // Estadocotizacion = 'Aprobada' or 'Rechazada'
                        {
                            // If cotizacion's estado comercial already defined
                            $response = HelpController::buildResponse(
                                409,
                                'No puedes editar una cotizacion con estado comercial ya definido',
                                null
                            );
                        }
                        else if(!($paramUsdToClp = Parameter::where('name', 'usd_to_clp')->first()))
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al obtener el valor USD para conversion',
                                null
                            );
                        }
                        else if(!($paramLbInUsd = Parameter::where('name', 'lb_in_usd')->first()))
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al obtener el valor LB en USD para calculo de flete',
                                null
                            );
                        }
                        else
                        {
                            // Clean partes list in request and store on parteList for sync
                            $parteList = array();

                            foreach($request->partes as $parte)
                            {
                                if(in_array($parte['nparte'], array_keys($parteList)))
                                {
                                    $parteList[$parte['nparte']] += $parte['cantidad'];
                                }
                                else
                                {
                                    $parteList[$parte['nparte']] = $parte['cantidad'];
                                }
                            }
                            
                            $success = true;
                            DB::beginTransaction();

                            $updateFlete = false;

                            // If Cotizacion was updated last time before than 15 days ago
                            if(new DateTime($cotizacion->lastupdate) < new DateTime('-15 days'))
                            {
                                // Update USD value in Cotizacion
                                $cotizacion->usdvalue = $paramUsdToClp->value;
                                // Update lastupdate field to now
                                $cotizacion->lastupdate = new DateTime();

                                // Set flag for updating flete value on each Parte
                                $updateFLete = true;
                            }

                            if($cotizacion->save())
                            {
                                foreach($cotizacion->partes as $parte)
                                {
                                    // If parte is still in the requested list
                                    if(in_array($parte->nparte, array_keys($parteList)))
                                    {
                                        $cantidad = $parteList[$parte->nparte];
                                        if($cantidad > 0)
                                        {
                                            // Update cantidad
                                            $parte->pivot->cantidad = $cantidad;
                                            
                                            // If Cotizacion was updated last time before than 15 days ago 
                                            if($updateFlete === true)
                                            {
                                                // Update flete with current LB in USD value
                                                $parte->pivot->flete = $parte->pivot->peso * $paramLbInUsd->value;
                                            }

                                            // If parte is updated
                                            if($parte->pivot->save())
                                            {
                                                // Removes parte from parteList
                                                unset($parteList[$parte->nparte]);
                                            }
                                            else
                                            {
                                                $response = HelpController::buildResponse(
                                                    409,
                                                    'Error al actualizar la parte "' . $parte->nparte . '"',
                                                    null
                                                );
            
                                                $success = false;
            
                                                break;
                                            }
                                        }
                                        else
                                        {
                                            $response = HelpController::buildResponse(
                                                409,
                                                'La cantidad ingresada para la parte "' . $parte->nparte . '" debe ser mayor a 0',
                                                null
                                            );
        
                                            $success = false;
        
                                            break;
                                        }
                                    }
                                    // Not in Cotizacion anymore, so detach
                                    else
                                    {
                                        // If detach parte
                                        if($cotizacion->partes()->detach($parte->id))
                                        {
                                            // Removes parte from parteList
                                            unset($parteList[$parte->nparte]);
                                        }
                                        else
                                        {
                                            $response = HelpController::buildResponse(
                                                409,
                                                'Error al eliminar la parte "' . $parte->nparte . '"',
                                                null
                                            );
        
                                            $success = false;
        
                                            break;
                                        }
                                    }
                                }
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al eliminar actualizar la cotizacion',
                                    null
                                );

                                $success = false;
                            }
                            
                            if($success === true)
                            {
                                // If all parte in request matched partes in Cotizacion
                                if(count($parteList) === 0)
                                {
                                    DB::commit();
                            
                                    $response = HelpController::buildResponse(
                                        200,
                                        'Cotizacion actualizada',
                                        null
                                    );    
                                }
                                else
                                {
                                    DB::rollback();

                                    $response = HelpController::buildResponse(
                                        409,
                                        'La lista de partes contiene partes que no exiten en la cotizacion',
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
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'La cotizacion no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar cotizaciones',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
        
            $response = HelpController::buildResponse(
                500,
                'Error al editar la cotizacion [!]' .$e,
                null
            );
        }
           
        return $response;
    }

    public function approve(Request $request, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('cotizaciones approve'))
            {
                // Reconstruct partes lists from a multipart/form-data request
                $partes = json_decode($request->partes, true);

                $validatorInput = [
                    'noccliente' => $request->noccliente,
                    'dococcliente' => $request->dococcliente,
                    'partes' => $partes
                ];
                
                $validatorRules = [
                    'noccliente' => 'required|min:1',
                    'dococcliente' => 'nullable|file|mimes:jpg,jpeg,png,bmp,pdf|max:5000', //Max size: 5mb (in kb)
                    'partes' => 'required|array|min:1',
                    'partes.*.id'  => 'required|exists:cotizacion_parte,parte_id,cotizacion_id,' . $id,
                    'partes.*.cantidad'  => 'required|numeric|min:1'
                ];
        
                $validatorMessages = [
                    'noccliente.required' => 'Debes ingresar el numero de OC cliente',
                    'noccliente.min' => 'El numero de OC cliente debe tener al menos un digito',
                    'dococcliente.file' => 'El archivo OC cliente es invalido',
                    'dococcliente.mimes' => 'El archivo OC cliente debe ser una imagen o un documento PDF',
                    'dococcliente.max' => 'El tamaño maximo para el archivo OC cliente es de 5 megabytes',
                    'partes.required' => 'Debes seleccionar las partes aprobadas',
                    'partes.array' => 'Lista de partes aprobadas invalida',
                    'partes.min' => 'La cotizacion debe contener al menos 1 parte aprobada',
                    'partes.*.id.required' => 'La lista de partes aprobadas es invalida',
                    'partes.*.id.exists' => 'La parte aprobada ingresada no existe',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte aprobada',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte aprobada debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte aprobada debe ser mayor a 0'
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
                    if($cotizacion = Cotizacion::find($id))
                    {
                        // Administrador
                        if(
                            ($user->role->name === 'admin') && 
                            ($cotizacion->solicitud->sucursal->country->id !== $user->stationable->country->id)
                        )
                        {
                            //If Administrator and solicitud doesn't belong to its country
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a aprobar esta cotizacion',
                                null
                            );
                        }
                        // Vendedor
                        else if(
                            ($user->role->name === 'seller') &&
                            (
                                ($cotizacion->solicitud->sucursal->id !== $user->stationable->id) ||
                                ($cotizacion->solicitud->user->id !== $user->id)
                            ) 
                        )
                        {
                            //If Vendedor and solicitud doesn't belong or not in its Sucursal
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a aprobar esta cotizacion',
                                null
                            );
                        }
                        else if(in_array($cotizacion->estadocotizacion_id, [3, 4]))  // If Estadocotizacion = 'Aprobada' or 'Rechazada'
                        {
                            $response = HelpController::buildResponse(
                                409,
                                'No puedes aprobar una cotizacion con estado comercial ya definido',
                                null
                            );
                        }
                        else
                        {
                            // If Estadocotizacion = 'Pendiente' or 'Vencida'
                            DB::beginTransaction();

                            $cotizacion->estadocotizacion_id = 3; // Aprobada
                            $cotizacion->motivorechazo_id = null; // Removes Motivorechazo if it had

                            if($cotizacion->save())
                            {
                                $success = true;
                                $path = null;

                                $filedata = null;
                                if($request->file('dococcliente'))
                                {
                                    if($path = $request->file('dococcliente')->store('occlientes', 'public'))
                                    {
                                        $filedata = new Filedata();
                                        $filedata->path = $path;
                                        $filedata->filename = $request->file('dococcliente')->getClientOriginalName();
                                        $filedata->size = $request->file('dococcliente')->getSize();

                                        if(!$filedata->save())
                                        {
                                            Storage::disk('public')->delete($path);

                                            $response = HelpController::buildResponse(
                                                500,
                                                'Error al adjuntar el archivo de OC cliente a la cotizacion',
                                                null
                                            );

                                            $success = false;
                                        }
                                    }
                                    else
                                    {
                                        $response = HelpController::buildResponse(
                                            500,
                                            'Error al guardar el archivo de OC cliente',
                                            null
                                        );

                                        $success = false;
                                    }
                                }

                                if($success === true)
                                {
                                    $oc = new OC();
                                    $oc->cotizacion_id = $cotizacion->id;
                                    $oc->estadooc_id = 1; //Initial Estadooc
                                    $oc->noccliente = $request->noccliente;
                                    $oc->usdvalue = $cotizacion->usdvalue;
                                    if($filedata !== null)
                                    {
                                        $oc->filedata_id = $filedata->id;
                                    }

                                    if($oc->save())
                                    {
                                        //Attaching each Parte to the Cotizacion
                                        $syncData = [];

                                        foreach($partes as $parte)
                                        {
                                            if($cparte = $cotizacion->partes->find($parte['id']))
                                            {
                                                $syncData[$cparte->id] =  array(
                                                    'estadoocparte_id' => 1, // Pendiente
                                                    'descripcion' => $cparte->pivot->descripcion,
                                                    'cantidad' => $parte['cantidad'],
                                                    'tiempoentrega' => $cparte->pivot->tiempoentrega,
                                                    'backorder' => $cparte->pivot->backorder
                                                );
                                            }
                                            else
                                            {
                                                $success = false;
                                                $response = HelpController::buildResponse(
                                                    500,
                                                    'Error al aprobar la cotizacion',
                                                    null
                                                );

                                                break;
                                            }
                                        }


                                        if($success === true)
                                        {
                                            if($oc->partes()->sync($syncData))
                                            {
                                                DB::commit();

                                                $response = HelpController::buildResponse(
                                                    201,
                                                    'Cotizacion aprobada',
                                                    null
                                                );
                                            }
                                            else
                                            {
                                                DB::rollback();

                                                $response = HelpController::buildResponse(
                                                    500,
                                                    'Error al aprobar la cotizacion',
                                                    null
                                                );
                                            }
                                        }
                                        else
                                        {
                                            //Error message already set
                                        }
                                        
                                    }
                                    else
                                    {
                                        DB::rollback();

                                        $response = HelpController::buildResponse(
                                            500,
                                            'Error al aprobar la cotizacion',
                                            null
                                        );
                                    }
                                }
                                else
                                {
                                    //Error message already set
                                }
                            }
                            else
                            {
                                DB::rollback();

                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al aprobar la cotizacion',
                                    null
                                );
                            }
                        }
                        
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'La cotizacion no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a aprobar cotizaciones',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al aprobar la cotizacion [!]',
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
            if($user->role->hasRoutepermission('cotizaciones reject'))
            {
                $validatorInput = $request->only(
                    'motivorechazo_id'
                );
                
                $validatorRules = [
                    'motivorechazo_id' => 'required|exists:motivosrechazo,id',
                ];
        
                $validatorMessages = [
                    'motivorechazo_id.required' => 'Debes seleccionar el motivo de rechazo',
                    'motivorechazo_id.exists' => 'El motivo de rechazo no existe',
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
                    if($cotizacion = Cotizacion::find($id))
                    {
                        // Administrador
                        if(
                            ($user->role->name === 'admin') && 
                            ($cotizacion->solicitud->sucursal->country->id !== $user->stationable->country->id)
                        )
                        {
                            //If Administrator and solicitud doesn't belong to its country
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a rechazar esta cotizacion',
                                null
                            );
                        }
                        // Vendedor
                        else if(
                            ($user->role->name === 'seller') &&
                            (
                                ($cotizacion->solicitud->sucursal->id !== $user->stationable->id) ||
                                ($cotizacion->solicitud->user->id !== $user->id)
                            ) 
                        )
                        {
                            //If Vendedor and solicitud doesn't belong or not in its Sucursal
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a rechazar esta cotizacion',
                                null
                            );
                        }
                        else if(in_array($cotizacion->estadocotizacion_id, [3, 4]))  // If Estadocotizacion = 'Aprobada' or 'Rechazada'
                        {
                            $response = HelpController::buildResponse(
                                409,
                                'No puedes rechazar una cotizacion con estado comercial ya definido',
                                null
                            );
                        }
                        else
                        {
                            $cotizacion->estadocotizacion_id = 4; // Rechazada
                            $cotizacion->motivorechazo_id = $request->motivorechazo_id;
                            
                            if($cotizacion->save())
                            {
                                $response = HelpController::buildResponse(
                                    200,
                                    'Cotizacion rechazada',
                                    null
                                );
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al rechazar la cotizacion',
                                    null
                                );   
                            }
                        }
                        
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'La cotizacion no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a rechazar cotizaciones',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al rechazar la cotizacion [!]',
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
        $response = HelpController::buildResponse(
            500,
            'Error al eliminar la cotizacion',
            null
        );
    }
}
