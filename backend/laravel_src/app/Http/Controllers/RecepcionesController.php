<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Comprador;
use App\Models\Sucursal;
use App\Models\Proveedor;
use App\Models\Oc;
use App\Models\OcParte;
use App\Models\Recepcion;
use App\Models\OcParteRecepcion;


class RecepcionesController extends Controller
{

    /*
     *  Compradores 
     */

    public function index_comprador($id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores recepciones_index'))
            {
                if($comprador = Comprador::find($id))
                {
                    $recepciones = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {

                        // Administrador
                        case 'admin': {

                            // Get only Recepciones containing OcPartes from OCs generated from its same country
                            $recepciones = Recepcion::select('recepciones.*')
                                        ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                        ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                        ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                        ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                        ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                        ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->groupBy('recepciones.id')
                                        ->get();

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // Get only Recepciones containing OcPartes from belonging OCs generated from its same Sucursal
                            $recepciones = Recepcion::select('recepciones.*')
                                        ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                        ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                        ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                        ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                        ->where('solicitudes.sucursal_id', '=', $user->stationable->id) // Same Sucursal as user station
                                        ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                        ->groupBy('recepciones.id')
                                        ->get();

                            break;
                        }

                        // Agente de compra
                        case 'agtcom': {

                            // If user belongs to this Comprador
                            if(
                                (get_class($user->stationable) === get_class($comprador)) &&
                                ($user->stationable->id === $comprador->id)
                            )
                            {
                                // Get only Recepciones at its Comprador
                                $recepciones = Recepcion::select('recepciones.*')
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->get();
                            }
                            else
                            {
                                // Set as forbidden
                                $forbidden = true;
                            }

                            break;
                        }

                        // Coordinador logistico at Comprador
                        case 'colcom': {

                            // If user belongs to this Comprador
                            if(
                                (get_class($user->stationable) === get_class($comprador)) &&
                                ($user->stationable->id === $comprador->id)
                            )
                            {
                                // Get only Recepciones at its Comprador
                                $recepciones = Recepcion::select('recepciones.*')
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->get();
                            }
                            else
                            {
                                // Set as forbidden
                                $forbidden = true;
                            }

                            break;
                        }

                        // Coordinador logistico at Sucursal (or Centro)
                        case 'colsol': {

                            // If user belongs to Sucursal (centro)
                            if($user->stationable->type === 'centro')
                            {
                                // Get only Recepciones containing OcPartes from OCs generated from its same country
                                $recepciones = Recepcion::select('recepciones.*')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->groupBy('recepciones.id')
                                            ->get();
                            }
                            // If user belongs to Sucursal
                            else if($user->stationable->type === 'sucursal')
                            {
                                // Get only Recepciones containing OcPartes from OCs generated from its same Sucursal
                                $recepciones = Recepcion::select('recepciones.*')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->where('solicitudes.sucursal_id', '=', $user->stationable->id) // Same Sucursal as user station
                                            ->groupBy('recepciones.id')
                                            ->get();
                            }
                            

                            break;
                        }

                        default:
                        {
                            break;
                        }
                    }

                    if($recepciones !== null)
                    {
                        $data = $recepciones->reduce(function($carry, $recepcion)
                            {
                                if($carry !== null)
                                {
                                    if($ocParte = $recepcion->ocpartes->first())
                                    {   
                                        $recepcion->partes_total;
                                        
                                        $recepcion->makeHidden([
                                            'sourceable_id', 
                                            'sourceable_type',
                                            'recepcionable_id', 
                                            'recepcionable_type', 
                                            'created_at', 
                                            'updated_at'
                                        ]);
    
                                        $recepcion->ocpartes;
                                        $recepcion->ocpartes = $recepcion->ocpartes->filter(function($ocParte)
                                        {
                                            $ocParte->makeHidden([
                                                'oc_id',
                                                'parte_id',
                                                'estadoocparte_id',
                                                'tiempoentrega',
                                                'oc',
                                                'created_at',
                                                'updated_at'
                                            ]);
        
                                            $ocParte->pivot->makeHidden([
                                                'recepcion_id',
                                                'ocparte_id',
                                                'created_at',
                                                'updated_at'
                                            ]);
        
                                            return $ocParte;
                                        });
        
                                        $recepcion->sourceable;
                                        $recepcion->sourceable->makeHidden([
                                            'comprador_id',
                                            'rut',
                                            'address',
                                            'city',
                                            'contact',
                                            'phone',
                                            'country',
                                            'created_at', 
                                            'updated_at'
                                        ]);
    
                                        // Takes the OC from first OcParte as reference
                                        $oc = $ocParte->oc;
                                        $oc->makeHidden([
                                            'cotizacion_id',
                                            'proveedor_id',
                                            'filedata_id',
                                            'motivobaja_id',
                                            'estadooc_id', 
                                            'usdvalue',
                                            'partes_total',
                                            'dias',
                                            'monto',
                                            'partes',
                                            'created_at', 
                                            'updated_at'
                                        ]);
        
                                        $oc->cotizacion;
                                        $oc->cotizacion->makeHidden([
                                            'solicitud_id',
                                            'estadocotizacion_id',
                                            'motivorechazo_id',
                                            'usdvalue',
                                            'partes_total',
                                            'dias',
                                            'monto',
                                            'partes',
                                            'created_at', 
                                            'updated_at'
                                        ]);
        
                                        $oc->cotizacion->solicitud;
                                        $oc->cotizacion->solicitud->makeHidden([
                                            'sucursal_id',
                                            'faena_id',
                                            'marca_id',
                                            'comprador_id',
                                            'user_id',
                                            'estadosolicitud_id',
                                            'comentario',
                                            'partes_total',
                                            'partes',
                                            'created_at', 
                                            'updated_at'
                                        ]);
        
                                        $oc->cotizacion->solicitud->faena;
                                        $oc->cotizacion->solicitud->faena->makeHidden([
                                            'cliente_id',
                                            'sucursal_id',
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
                                            'country_id',
                                            'created_at', 
                                            'updated_at'
                                        ]);
                                        
                                        array_push(
                                            $carry, 
                                            array(
                                                'recepcion' => $recepcion,
                                                'oc' => $oc
                                            )
                                        );
                                    }
                                    // If there was no OcParte found in Recepcion
                                    else
                                    {
                                        // Set response as null to break the loop
                                        $carry = null;
                                    }
                                }

                                return $carry;
                            },
                            array()
                        );

                        if($data !== null)
                        {
                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $data
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al obtener la lista de recepciones',
                                null
                            );
                        }
                    }
                    else if($forbidden === true)
                    {
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a visualizar las recepciones',
                            null
                        );
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            500,
                            'Error al obtener la lista de recepciones',
                            null
                        );
                    }
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El comprador no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar recepciones de compradores',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener las recepciones del comprador [!]',
                null
            );
        }
            
        return $response;
    }

    public function queueOcs_comprador($comprador_id, $proveedor_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores recepciones_store'))
            {
                if($comprador = Comprador::find($comprador_id))
                {
                    if($proveedor = $comprador->proveedores->where('id', $proveedor_id)->first())
                    {
                        $ocList = null;
                        $forbidden = false;

                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {

                                // Get only OCs for the Comprador assigned to Proveedor and generated from its same country
                                $ocList = Oc::select('ocs.*')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                        ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                        ->where('ocs.proveedor_id', '=', $proveedor->id)
                                        ->where('solicitudes.comprador_id', '=', $comprador->id) // For this Comprador
                                        ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->get();

                                break;
                            }

                            // Agente de compra
                            case 'agtcom': {

                                // If user belongs to this Comprador
                                if(
                                    (get_class($user->stationable) === get_class($comprador)) &&
                                    ($user->stationable->id === $comprador->id)
                                )
                                {
                                    // Get list
                                    $ocList = Oc::select('ocs.*')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->where('solicitudes.comprador_id', '=', $comprador->id) // For this Comprador
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->where('ocs.proveedor_id', '=', $proveedor->id)
                                            ->get();
                                }
                                else
                                {
                                    // Set as forbidden
                                    $forbidden = true;
                                }

                                break;
                            }

                            // Coordinador logistico at Comprador
                            case 'colcom': {

                                // If user belongs to this Comprador
                                if(
                                    (get_class($user->stationable) === get_class($comprador)) &&
                                    ($user->stationable->id === $comprador->id)
                                )
                                {
                                    // Get list
                                    $ocList = Oc::select('ocs.*')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->where('solicitudes.comprador_id', '=', $comprador->id) // For this Comprador
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->where('ocs.proveedor_id', '=', $proveedor->id)
                                            ->get();
                                }
                                else
                                {
                                    // Set as forbidden
                                    $forbidden = true;
                                }

                                break;
                            }

                            default:
                            {
                                break;
                            }
                        }

                        if($ocList !== null)
                        {
                            $queueOcs = $ocList->reduce(function($carry, $oc) use ($comprador)
                                {
                                    $fullReceived = true;
                                    foreach($oc->partes as $parte)
                                    {
                                        $parte->makeHidden([
                                            'marca_id',
                                            'created_at', 
                                            'updated_at'
                                        ]);

                                        $parte->marca;
                                        $parte->marca->makeHidden([
                                            'created_at', 
                                            'updated_at'
                                        ]);

                                        $parte->pivot->cantidad_recepcionado = $parte->pivot->getCantidadRecepcionado($comprador);
                                        if($parte->pivot->cantidad_recepcionado < $parte->pivot->cantidad)
                                        {
                                            $fullReceived = false;
                                        }

                                        $parte->pivot->makeHidden([
                                            'oc_id',
                                            'parte_id',
                                            'estadoocparte_id',
                                            'created_at',
                                            'updated_at'
                                        ]);
                                    }

                                    // If the OC isn't full received yet, add to the filtered list
                                    if($fullReceived === false)
                                    {
                                        // Filter data to response
                                        $oc->makeHidden([
                                            'cotizacion_id',
                                            'proveedor_id',
                                            'filedata_id',
                                            'estadooc_id',
                                            'motivobaja_id',
                                            'usdvalue'
                                        ]);

                                        $oc->cotizacion->makeHidden([
                                            'solicitud_id',
                                            'estadocotizacion_id',
                                            'motivorechazo_id',
                                            'usdvalue',
                                            'created_at',
                                            'updated_at',
                                            'partes_total',
                                            'dias',
                                            'monto',
                                            'partes'
                                        ]);

                                        $oc->cotizacion->solicitud;
                                        $oc->cotizacion->solicitud->makeHidden([
                                            'sucursal_id',
                                            'faena_id',
                                            'marca_id',
                                            'comprador_id',
                                            'user_id',
                                            'estadosolicitud_id',
                                            'comentario',
                                            'created_at',
                                            'updated_at',
                                            'partes_total',
                                            'partes'
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

                                        $oc->cotizacion->solicitud->sucursal->country;
                                        $oc->cotizacion->solicitud->sucursal->country->makeHidden([
                                            'created_at',
                                            'updated_at'
                                        ]);

                                        $oc->cotizacion->solicitud->faena;
                                        $oc->cotizacion->solicitud->faena->makeHidden([
                                            'cliente_id',
                                            'sucursal_id',
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
                                            'country_id',
                                            'created_at',
                                            'updated_at'
                                        ]);

                                        $oc->cotizacion->solicitud->marca;
                                        $oc->cotizacion->solicitud->marca->makeHidden([
                                            'created_at',
                                            'updated_at'
                                        ]);

                                        array_push($carry, $oc);
                                    }

                                    return $carry;
                                },
                                array()
                            );

                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $queueOcs
                            );
                        }
                        else if($forbidden === true)
                        {
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a visualizar las OCs pendiente de recepcion',
                                null
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
                            412,
                            'El proveedor no existe para el comprador',
                            null
                        );
                    }
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El comprador no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar OCs pendiente de recepcion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener OCs pendiente de recepcion [!]',
                null
            );
        }
            
        return $response;
    }

    public function store_comprador(Request $request, $comprador_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores recepciones_store'))
            {
                $validatorInput = $request->only('proveedor_id', 'fecha', 'ndocumento', 'responsable', 'comentario', 'ocs');
            
                $validatorRules = [
                    'proveedor_id' => 'required|exists:proveedores,id',
                    'fecha' => 'required|date_format:Y-m-d|before:tomorrow', // it includes today
                    'ndocumento' => 'nullable|min:1',
                    'responsable' => 'required|min:1',
                    'comentario' => 'sometimes|nullable',
                    'ocs' => 'required|array|min:1',
                    'ocs.*.id'  => 'required',
                    'ocs.*.partes' => 'required|array|min:1',
                    'ocs.*.partes.*.id'  => 'required|exists:partes,id',
                    'ocs.*.partes.*.cantidad'  => 'required|numeric|min:1',
                ];
        
                $validatorMessages = [
                    'proveedor_id.required' => 'Debes seleccionar el proveedor',
                    'proveedor_id.exists' => 'El proveedor no existe',
                    'fecha.required' => 'Debes ingresar la fecha de recepcion',
                    'fecha.date_format' => 'El formato de fecha de recepcion es invalido',
                    'fecha.before' => 'La fecha debe ser igual o anterior a hoy',
                    'ndocumento.min' => 'El numero de documento debe tener al menos un digito',
                    'responsable.required' => 'Debes ingresar el nombre de la persona que recibe',
                    'responsable.min' => 'El nombre de la persona que recibe debe tener al menos un digito',
                    'ocs.required' => 'Debes seleccionar las partes recepcionadas',
                    'ocs.array' => 'Lista de partes recepcionadas invalida',
                    'ocs.min' => 'La recepcion debe contener al menos 1 parte recepcionada',
                    'ocs.*.id.required' => 'Debes seleccionar la OC a recepcionar',
                    'ocs.*.partes.required' => 'Debes seleccionar las partes recepcionadas',
                    'ocs.*.partes.array' => 'Lista de partes recepcionadas invalida',
                    'ocs.*.partes.min' => 'La recepcion debe contener al menos 1 parte recepcionada',
                    'ocs.*.partes.*.id.required' => 'La lista de partes recepcionadas es invalida',
                    'ocs.*.partes.*.id.exists' => 'La parte recepcionada ingresada no existe',
                    'ocs.*.partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte recepcionada',
                    'ocs.*.partes.*.cantidad.numeric' => 'La cantidad para la parte recepcionada debe ser numerica',
                    'ocs.*.partes.*.cantidad.min' => 'La cantidad para la parte recepcionada debe ser mayor a 0',
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
                else if(($comprador = Comprador::find($comprador_id)) === null)
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El comprador no existe',
                        null
                    );
                }
                else if(($proveedor = $comprador->proveedores->find($request->proveedor_id)) === null)
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El proveedor seleccionado no existe para el comprador',
                        null
                    );
                }
                else        
                {
                    // Clean OC and partes list in request
                    $ocList = array();
                    foreach($request->ocs as $oc)
                    {
                        if((in_array($oc['id'], array_keys($ocList))) === false)
                        {
                            $ocList[$oc['id']] = array();
                        }

                        foreach($oc['partes'] as $parte)
                        {
                            if(in_array($parte['id'], array_keys($ocList[$oc['id']])))
                            {
                                $ocList[$oc['id']][$parte['id']] += $parte['cantidad'];
                            }
                            else
                            {
                                $ocList[$oc['id']][$parte['id']] = $parte['cantidad'];
                            }
                        }
                    }

                    if(count($ocList) > 1)
                    {
                        // If there were sent more than 1 OC
                        $response = HelpController::buildResponse(
                            409,
                            'No puedes recepcionar mas de 1 OC por proveedor',
                            null
                        );
                    }
                    else
                    {
                        DB::beginTransaction();

                        $recepcion = new Recepcion();
                        // Set the morph source for Recepcion as Proveedor
                        $recepcion->sourceable_id = $proveedor->id;
                        $recepcion->sourceable_type = get_class($proveedor);
                        // Set the morph destination for Recepcion as Comprador
                        $recepcion->recepcionable_id = $comprador->id;
                        $recepcion->recepcionable_type = get_class($comprador);
                        // Fill the data
                        $recepcion->fecha = $request->fecha;
                        $recepcion->ndocumento = $request->ndocumento;
                        $recepcion->responsable = $request->responsable;
                        $recepcion->comentario = $request->comentario;

                        if($recepcion->save())
                        {
                            $success = true;

                            // This list has only 1 item
                            foreach(array_keys($ocList) as $ocId)
                            {      
                                // If success wasn't broken yet, continue
                                if($success === true)
                                {
                                    $oc = null;
            
                                    switch($user->role->name)
                                    {
                                        // Administrador
                                        case 'admin': {

                                            $oc = Oc::select('ocs.*')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.id', '=', $ocId) // For this Oc
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->where('ocs.proveedor_id', '=', $proveedor->id)
                                                ->where('solicitudes.comprador_id', '=', $comprador->id) // For this Comprador
                                                ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->first();
            
                                            break;
                                        }
            
                                        // Agente de compra
                                        case 'agtcom': {

                                            // If user belongs to this Comprador
                                            if(
                                                (get_class($user->stationable) === get_class($comprador)) &&
                                                ($user->stationable->id === $comprador->id)
                                            )
                                            {
                                                $oc = Oc::select('ocs.*')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->where('ocs.id', '=', $ocId)
                                                    ->where('solicitudes.comprador_id', '=', $comprador->id) // If solicitud belongs to this Comprador
                                                    ->first();
                                            }
            
                                            break;
                                        }
            
                                        // Coordinador logistico at Comprador
                                        case 'colcom': {
                                            
                                            // If user belongs to this Comprador
                                            if(
                                                (get_class($user->stationable) === get_class($comprador)) &&
                                                ($user->stationable->id === $comprador->id)
                                            )
                                            {
                                                $oc = Oc::select('ocs.*')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->where('ocs.id', '=', $ocId)
                                                    ->where('solicitudes.comprador_id', '=', $comprador->id) // If solicitud belongs to this Comprador
                                                    ->first();
                                            }
            
                                            break;
                                        }
            
                                        default: {
                                            break;
                                        }
                                    }
            
                                    if($oc !== null)
                                    {
                                        if($oc->proveedor->id === $proveedor->id)
                                        {
                                            foreach(array_keys($ocList[$oc->id]) as $parteId)
                                            {
                                                if($p = $oc->partes->find($parteId))
                                                {
                                                    $cantidadRecepcionado = $p->pivot->getCantidadRecepcionado($comprador);
                                                    if($cantidadRecepcionado < $p->pivot->cantidad)
                                                    {
                                                        if(($cantidadRecepcionado + $ocList[$oc->id][$parteId]) <= $p->pivot->cantidad)
                                                        {
                                                            $recepcion->ocpartes()->attach(
                                                                array(
                                                                    $p->pivot->id => array(
                                                                        "cantidad" => $ocList[$oc->id][$parteId]
                                                                    )
                                                                )
                                                            );
                                                        }
                                                        else
                                                        {
                                                            // If the received parts are more than waiting in queue
                                                            $response = HelpController::buildResponse(
                                                                409,
                                                                'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor a la cantidad de pendiente de recepcion',
                                                                null
                                                            );
                        
                                                            $success = false;
                        
                                                            break;
                                                        }
                                                    }
                                                    else
                                                    {
                                                        // If the entered parte isn't in queue
                                                        $response = HelpController::buildResponse(
                                                            409,
                                                            'La parte "' . $p->nparte . '" no tiene partes pendiente de recepcion',
                                                            null
                                                        );
                    
                                                        $success = false;
                    
                                                        break;
                                                    }
                                                }
                                                else
                                                {
                                                    //If the entered parte isn't in the OC
                                                    $response = HelpController::buildResponse(
                                                        409,
                                                        'Una de las partes ingresadas no existe en la OC',
                                                        null
                                                    );
                
                                                    $success = false;
                
                                                    break;
                                                }
                                            }
                                        }
                                        else
                                        {
                                            $response = HelpController::buildResponse(
                                                409,
                                                'La OC no pertenece al proveedor ingresado',
                                                null
                                            );
        
                                            $success = false;
        
                                            break;
                                        }
                                    }
                                    else
                                    {
                                        if(Oc::find($ocId))
                                        {
                                            $response = HelpController::buildResponse(
                                                405,
                                                'No tienes acceso a registrar recepciones para la OC',
                                                null
                                            );
                                        }
                                        else
                                        {
                                            $response = HelpController::buildResponse(
                                                412,
                                                'La OC ingresada no existe',
                                                null
                                            );
                                        }
                                        
                                        $success = false;
        
                                        break;
                                    }
                                }
                                // If success was already broken
                                else
                                {
                                    // Break the higher loop
                                    break;
                                }                    
                            }

                            if($success === true)
                            {
                                DB::commit();
                                    
                                $response = HelpController::buildResponse(
                                    201,
                                    'Recepcion creada',
                                    null
                                );
                            }
                            else
                            {
                                DB::rollback();
                            }
                        }
                        else
                        {       
                            DB::rollback();

                            $response = HelpController::buildResponse(
                                500,
                                'Error al crear la recepcion',
                                null
                            );
                        }
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a registrar recepciones para comprador',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al crear la recepcion [!]',
                null
            );
        }
        
        return $response;
    }

    public function show_comprador($comprador_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores recepciones_show'))
            {
                $validatorInput = ['recepcion_id' => $id];
            
                $validatorRules = [
                    'recepcion_id' => 'required|exists:recepciones,id,recepcionable_id,' . $comprador_id . ',recepcionable_type,' . get_class(new Comprador()),
                ];
        
                $validatorMessages = [
                    'recepcion_id.required' => 'Debes ingresar la recepcion',
                    'recepcion_id.exists' => 'La recepcion ingresada no existe para el comprador',                    
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
                    if($comprador = Comprador::find($comprador_id))
                    {
                        $recepcion = null;

                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {
                                
                                // Only if Recepcion contains OcPartes from OCs generated from its same country
                                $recepcion = Recepcion::select('recepciones.*')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->first();

                                break;
                            }

                            // Vendedor
                            case 'seller': {

                                // Only if Recepcion contains OcPartes from belonging OCs generated from its same Sucursal
                                $recepcion = Recepcion::select('recepciones.*')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->where('solicitudes.sucursal_id', '=', $user->stationable->id) // Same Sucursal as user station
                                            ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                            ->first();

                                break;
                            }

                            // Agente de compra
                            case 'agtcom': {

                                // If user belongs to this Comprador
                                if(
                                    (get_class($user->stationable) === get_class($comprador)) &&
                                    ($user->stationable->id === $comprador->id)
                                )
                                {
                                    // Only if Recepcion was received at Comprador
                                    $recepcion = Recepcion::select('recepciones.*')
                                                ->where('recepciones.id', '=', $id) // For this Recepcion
                                                ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                                ->first();
                                }

                                break;
                            }

                            // Coordinador logistico at Comprador
                            case 'colcom': {

                                // If user belongs to this Comprador
                                if(
                                    (get_class($user->stationable) === get_class($comprador)) &&
                                    ($user->stationable->id === $comprador->id)
                                )
                                {
                                    // Only if Recepcion was received at Comprador
                                    $recepcion = Recepcion::select('recepciones.*')
                                                ->where('recepciones.id', '=', $id) // For this Recepcion
                                                ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                                ->first();
                                }

                                break;
                            }

                            // Coordinador logistico at Sucursal (or Centro)
                            case 'colsol': {

                                // If user belongs to Sucursal (centro)
                                if($user->stationable->type === 'centro')
                                {
                                    // Only if Recepcion contains OcPartes from OCs generated from its same country
                                    $recepcion = Recepcion::select('recepciones.*')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                                ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('recepciones.id', '=', $id) // For this Recepcion
                                                ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                                ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->first();
                                }
                                // If user belongs to Sucursal
                                else if($user->stationable->type === 'sucursal')
                                {
                                    // Only if Recepcion contains OcPartes from OCs generated from its same Sucursal
                                    $recepcion = Recepcion::select('recepciones.*')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                                ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->where('recepciones.id', '=', $id) // For this Recepcion
                                                ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                                ->where('solicitudes.sucursal_id', '=', $user->stationable->id) // Same Sucursal as user station
                                                ->first();
                                }
                                

                                break;
                            }
                            

                            default: {
                                break;
                            }
                        }
                        
                        if($recepcion !== null)
                        {
                            if($ocParte = $recepcion->ocpartes->first())
                            {   
                                $recepcion->makeHidden([
                                    'sourceable_id',
                                    'sourceable_type',
                                    'recepcionable_id',
                                    'recepcionable_type',
                                    'proveedor_id',
                                    'partes_total',
                                    'updated_at',
                                ]);
    
                                $recepcion->recepcionable;
                                $recepcion->recepcionable->makeHidden([
                                    'rut',
                                    'address',
                                    'city',
                                    'contact',
                                    'phone',
                                    'country_id',
                                    'created_at', 
                                    'updated_at'
                                ]);
    
                                $recepcion->sourceable;
                                $recepcion->sourceable->makeHidden([
                                    'comprador_id',
                                    'rut',
                                    'address',
                                    'city',
                                    'contact',
                                    'phone',
                                    'country',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $recepcion->ocpartes;
                                foreach($recepcion->ocpartes as $ocParte)
                                {                                
                                    $ocParte->makeHidden([
                                        'oc_id',
                                        'parte_id',
                                        'estadoocparte_id',
                                        'tiempoentrega',
                                        'oc',
                                        'created_at', 
                                        'updated_at'
                                    ]);
    
                                    $ocParte->pivot->makeHidden([
                                        'recepcion_id',
                                        'ocparte_id',
                                        'created_at',
                                        'updated_at',
                                    ]);
    
                                    $ocParte->parte->makeHidden([
                                        'marca_id',
                                        'created_at', 
                                        'updated_at'
                                    ]);
    
                                    $ocParte->parte->marca;
                                    $ocParte->parte->marca->makeHidden(['created_at', 'updated_at']);
                                }
    
                                // Takes the OC from first OcParte as reference
                                $oc = $ocParte->oc;
                                $oc->makeHidden([
                                    'cotizacion_id',
                                    'proveedor_id',
                                    'filedata_id',
                                    'motivobaja_id',
                                    'estadooc_id', 
                                    'usdvalue',
                                    'partes_total',
                                    'dias',
                                    'monto',
                                    'partes',
                                    'created_at', 
                                    'updated_at'
                                ]);
    
                                $oc->cotizacion;
                                $oc->cotizacion->makeHidden([
                                    'solicitud_id',
                                    'estadocotizacion_id',
                                    'motivorechazo_id',
                                    'usdvalue',
                                    'partes_total',
                                    'dias',
                                    'monto',
                                    'partes',
                                    'created_at', 
                                    'updated_at'
                                ]);
    
                                $oc->cotizacion->solicitud;
                                $oc->cotizacion->solicitud->makeHidden([
                                    'sucursal_id',
                                    'faena_id',
                                    'marca_id',
                                    'comprador_id',
                                    'user_id',
                                    'estadosolicitud_id',
                                    'comentario',
                                    'partes_total',
                                    'partes',
                                    'created_at', 
                                    'updated_at'
                                ]);
    
                                $oc->cotizacion->solicitud->faena;
                                $oc->cotizacion->solicitud->faena->makeHidden([
                                    'cliente_id',
                                    'sucursal_id',
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
                                    'country_id',
                                    'created_at', 
                                    'updated_at'
                                ]);
                                
                                $data = array(
                                    'recepcion' => $recepcion,
                                    'oc' => $oc
                                );
                                
                                $response = HelpController::buildResponse(
                                    200,
                                    null,
                                    $data
                                );   
                            }
                            // If there was no OcParte found in Recepcion
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al obtener la recepcion',
                                    null
                                );
                            }                  
                        }
                        // If wasn't catched
                        else
                        {
                            // If Recepcion exists
                            if(Recepcion::find($id))
                            {
                                // It was filtered, so it's forbidden
                                $response = HelpController::buildResponse(
                                    405,
                                    'No tienes acceso a visualizar la recepcion',
                                    null
                                );
                            }
                            // It doesn't exist
                            else
                            {
                                $response = HelpController::buildResponse(
                                    412,
                                    'La recepcion no existe',
                                    null
                                );
                            }
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'El comprador no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar recepciones de comprador',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la recepcion [!]',
                null
            );
        }
        
        return $response;
    }

    /**
     * It retrieves all the required info for
     * selecting data and updating a Recepcion for Comprador
     * 
     */
    public function update_prepare_comprador($comprador_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores recepciones_update'))
            {
                $validatorInput = ['recepcion_id' => $id];
            
                $validatorRules = [
                    'recepcion_id' => 'required|exists:recepciones,id,recepcionable_id,' . $comprador_id . ',recepcionable_type,' . get_class(new Comprador()),
                ];
        
                $validatorMessages = [
                    'recepcion_id.required' => 'Debes ingresar la recepcion',
                    'recepcion_id.exists' => 'La recepcion ingresada no existe para el comprador',                    
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
                    if($comprador = Comprador::find($comprador_id))
                    {
                        $recepcion = null;

                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {
                                
                                // Only if Recepcion contains OcPartes from OCs generated from its same country
                                $recepcion = Recepcion::select('recepciones.*')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->first();

                                break;
                            }

                            // Agente de compra
                            case 'agtcom': {

                                // If user belongs to this Comprador
                                if(
                                    (get_class($user->stationable) === get_class($comprador)) &&
                                    ($user->stationable->id === $comprador->id)
                                )
                                {
                                    // Only if Recepcion was received at Comprador
                                    $recepcion = Recepcion::select('recepciones.*')
                                                ->where('recepciones.id', '=', $id) // For this Recepcion
                                                ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                                ->first();
                                }

                                break;
                            }

                            // Coordinador logistico at Comprador
                            case 'colcom': {

                                // If user belongs to this Comprador
                                if(
                                    (get_class($user->stationable) === get_class($comprador)) &&
                                    ($user->stationable->id === $comprador->id)
                                )
                                {
                                    // Only if Recepcion was received at Comprador
                                    $recepcion = Recepcion::select('recepciones.*')
                                                ->where('recepciones.id', '=', $id) // For this Recepcion
                                                ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                                ->first();
                                }

                                break;
                            }
                            

                            default: {
                                break;
                            }
                        }

                        if($recepcion !== null)
                        {
                            if($ocParte = $recepcion->ocpartes->first())
                            { 
                                $recepcion->makeHidden([
                                    'sourceable_id',
                                    'sourceable_type',
                                    'recepcionable_id',
                                    'recepcionable_type',
                                    'proveedor_id',
                                    'partes_total',
                                    'updated_at',
                                ]);
    
                                $recepcion->recepcionable;
                                $recepcion->recepcionable->makeHidden([
                                    'rut',
                                    'address',
                                    'city',
                                    'contact',
                                    'phone',
                                    'country_id',
                                    'created_at', 
                                    'updated_at'
                                ]);
    
                                $recepcion->sourceable;
                                $recepcion->sourceable->makeHidden([
                                    'comprador_id',
                                    'rut',
                                    'address',
                                    'city',
                                    'contact',
                                    'phone',
                                    'country',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $recepcion->ocpartes;
                                foreach($recepcion->ocpartes as $ocParte)
                                {                                
                                    $ocParte->makeHidden([
                                        'oc_id',
                                        'parte_id',
                                        'estadoocparte_id',
                                        'descripcion',
                                        'cantidad',
                                        'tiempoentrega',
                                        'backorder',
                                        'oc',
                                        'created_at', 
                                        'updated_at'
                                    ]);
    
                                    $ocParte->pivot->makeHidden([
                                        'recepcion_id',
                                        'ocparte_id',
                                        'created_at',
                                        'updated_at',
                                    ]);
    
                                    $ocParte->parte->makeHidden([
                                        'nparte',
                                        'marca_id',
                                        'created_at', 
                                        'updated_at'
                                    ]);
                                }
    
                                // Takes the OC from first OcParte as reference
                                $oc = $ocParte->oc;
                                $oc->makeHidden([
                                    'cotizacion_id',
                                    'proveedor_id',
                                    'filedata_id',
                                    'motivobaja_id',
                                    'estadooc_id', 
                                    'usdvalue',
                                    'partes_total',
                                    'dias',
                                    'monto',
                                    'created_at', 
                                    'updated_at'
                                ]);
    
                                $oc->cotizacion;
                                $oc->cotizacion->makeHidden([
                                    'solicitud_id',
                                    'estadocotizacion_id',
                                    'motivorechazo_id',
                                    'usdvalue',
                                    'partes_total',
                                    'dias',
                                    'monto',
                                    'partes',
                                    'created_at', 
                                    'updated_at'
                                ]);
    
                                $oc->cotizacion->solicitud;
                                $oc->cotizacion->solicitud->makeHidden([
                                    'sucursal_id',
                                    'faena_id',
                                    'marca_id',
                                    'comprador_id',
                                    'user_id',
                                    'estadosolicitud_id',
                                    'comentario',
                                    'partes_total',
                                    'partes',
                                    'created_at', 
                                    'updated_at'
                                ]);
    
                                $oc->cotizacion->solicitud->faena;
                                $oc->cotizacion->solicitud->faena->makeHidden([
                                    'cliente_id',
                                    'sucursal_id',
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
                                    'country_id',
                                    'created_at', 
                                    'updated_at'
                                ]);
    
                                foreach($oc->partes as $parte)
                                {
                                    $parte->makeHidden([
                                        'marca_id',
                                        'created_at', 
                                        'updated_at'
                                    ]);
    
                                    $parte->marca;
                                    $parte->marca->makeHidden([
                                        'created_at', 
                                        'updated_at'
                                    ]);
    
                                    $parte->pivot->cantidad_recepcionado = $parte->pivot->getCantidadRecepcionado($comprador);
                                    $parte->pivot->cantidad_despachado = $parte->pivot->getCantidadDespachado($comprador);
    
                                    $parte->pivot->makeHidden([
                                        'oc_id',
                                        'parte_id',
                                        'estadoocparte_id',
                                        'created_at',
                                        'updated_at'
                                    ]);
                                }
    
                                $data = array(
                                    'recepcion' => $recepcion,
                                    'oc' => $oc
                                );
                                
                                $response = HelpController::buildResponse(
                                    200,
                                    null,
                                    $data
                                );   
                            }
                            // If there was no OcParte found in Recepcion
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al obtener la recepcion',
                                    null
                                );
                            }  
                        }        
                        // If wasn't catched
                        else
                        {
                            // If Recepcion exists
                            if(Recepcion::find($id))
                            {
                                // It was filtered, so it's forbidden
                                $response = HelpController::buildResponse(
                                    405,
                                    'No tienes acceso a actualizar la recepcion',
                                    null
                                );
                            }
                            // It doesn't exist
                            else
                            {
                                $response = HelpController::buildResponse(
                                    412,
                                    'La recepcion no existe',
                                    null
                                );
                            }
                        }                  
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'El comprador no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar recepciones de comprador',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la recepcion [!]',
                null
            );
        }
        
        return $response;
    }

    public function update_comprador(Request $request, $comprador_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores recepciones_update'))
            {
                $validatorInput = $request->only('fecha', 'ndocumento', 'responsable', 'comentario', 'ocs');
            
                $validatorRules = [
                    'fecha' => 'required|date_format:Y-m-d|before:tomorrow', // it includes today
                    'ndocumento' => 'nullable|min:1',
                    'responsable' => 'required|min:1',
                    'comentario' => 'sometimes|nullable',
                    'ocs' => 'required|array|min:1',
                    'ocs.*.id'  => 'required|exists:ocs,id',
                    'ocs.*.partes' => 'required|array|min:1',
                    'ocs.*.partes.*.id'  => 'required|exists:partes,id',
                    'ocs.*.partes.*.cantidad'  => 'required|numeric|min:1',
                ];
        
                $validatorMessages = [
                    'fecha.required' => 'Debes ingresar la fecha de recepcion',
                    'fecha.date_format' => 'El formato de fecha de recepcion es invalido',
                    'fecha.before' => 'La fecha debe ser igual o anterior a hoy',
                    'ndocumento.min' => 'El numero de documento debe tener al menos un digito',
                    'responsable.required' => 'Debes ingresar el nombre de la persona que recibe',
                    'responsable.min' => 'El nombre de la persona que recibe debe tener al menos un digito',
                    'ocs.required' => 'Debes seleccionar las partes recepcionadas',
                    'ocs.array' => 'Lista de partes recepcionadas invalida',
                    'ocs.min' => 'La recepcion debe contener al menos 1 parte recepcionada',
                    'ocs.*.id.required' => 'Debes seleccionar la OC a recepcionar',
                    'ocs.*.id.exists' => 'La OC ingresada no existe',
                    'ocs.*.partes.required' => 'Debes seleccionar las partes recepcionadas',
                    'ocs.*.partes.array' => 'Lista de partes recepcionadas invalida',
                    'ocs.*.partes.min' => 'La recepcion debe contener al menos 1 parte recepcionada',
                    'ocs.*.partes.*.id.required' => 'La lista de partes recepcionadas es invalida',
                    'ocs.*.partes.*.id.exists' => 'La parte recepcionada ingresada no existe',
                    'ocs.*.partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte recepcionada',
                    'ocs.*.partes.*.cantidad.numeric' => 'La cantidad para la parte recepcionada debe ser numerica',
                    'ocs.*.partes.*.cantidad.min' => 'La cantidad para la parte recepcionada debe ser mayor a 0',
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
                else if(($comprador = Comprador::find($comprador_id)) === null)
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El comprador no existe',
                        null
                    );
                }
                else        
                {
                    $recepcion = null;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {
                            
                            // Only if Recepcion contains OcPartes from OCs generated from its same country
                            $recepcion = Recepcion::select('recepciones.*')
                                        ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                        ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                        ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                        ->where('recepciones.id', '=', $id) // For this Recepcion
                                        ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                        ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                        ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->first();

                            break;
                        }

                        // Agente de compra
                        case 'agtcom': {

                            // If user belongs to this Comprador
                            if(
                                (get_class($user->stationable) === get_class($comprador)) &&
                                ($user->stationable->id === $comprador->id)
                            )
                            {
                                // Only if Recepcion was received at Comprador
                                $recepcion = Recepcion::select('recepciones.*')
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->first();
                            }

                            break;
                        }

                        // Coordinador logistico at Comprador
                        case 'colcom': {

                            // If user belongs to this Comprador
                            if(
                                (get_class($user->stationable) === get_class($comprador)) &&
                                ($user->stationable->id === $comprador->id)
                            )
                            {
                                // Only if Recepcion was received at Comprador
                                $recepcion = Recepcion::select('recepciones.*')
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->first();
                            }

                            break;
                        }

                        default: {
                            break;
                        }
                    }

                    if($recepcion !== null)
                    {
                        // Clean OC and partes list in request and store on diffList for validations and ocList for sync
                        $diffList = array();
                        $ocList = array();
                        foreach($request->ocs as $oc)
                        {
                            if((in_array($oc['id'], array_keys($diffList))) === false)
                            {
                                $diffList[$oc['id']] = array();
                                $ocList[$oc['id']] = array();
                            }

                            foreach($oc['partes'] as $parte)
                            {
                                if(in_array($parte['id'], array_keys($diffList[$oc['id']])))
                                {
                                    $diffList[$oc['id']][$parte['id']] += $parte['cantidad'];
                                    $ocList[$oc['id']][$parte['id']] += $parte['cantidad'];
                                }
                                else
                                {
                                    $diffList[$oc['id']][$parte['id']] = $parte['cantidad'];
                                    $ocList[$oc['id']][$parte['id']] = $parte['cantidad'];
                                }
                            }
                        }

                        // For each OcParte in Recepcion
                        foreach($recepcion->ocpartes as $ocParte)
                        {
                            // If OC isn't in the OC list yet
                            if((in_array($ocParte->oc->id, array_keys($diffList))) === false)
                            {
                                /*  Add the OC. Also the Parte wasn't there either
                                 *  so it's gonna be removed. Then add it with negative cantidad
                                 *  and don't add it on the ocList (for sync)
                                */

                                $diffList[$ocParte->oc->id] = array(
                                    $ocParte->parte->id => ($ocParte->pivot->cantidad * -1)
                                );
                            }
                            // If OC was already in the list
                            else
                            {
                                // If the Parte is already in list, it's kept in Recepcion
                                if((in_array($ocParte->parte->id, array_keys($diffList[$ocParte->oc->id]))) === true)
                                {
                                    // Add the diff cantidad with cantidad given in request - old cantidad
                                    $diffList[$ocParte->oc->id][$ocParte->parte->id] -= $ocParte->pivot->cantidad;
                                }
                                // If the OcParte isn't in the list, it's going to be removed and don't add it on the ocList (for sync)
                                else
                                {
                                    $diffList[$ocParte->oc->id][$ocParte->parte->id] = ($ocParte->pivot->cantidad * -1);
                                }
                            }
                        }

                        if(count($diffList) > 1)
                        {
                            // If there were sent more than 1 OC
                            $response = HelpController::buildResponse(
                                409,
                                'No puedes recepcionar mas de 1 OC por proveedor',
                                null
                            );
                        }
                        else
                        {
                            DB::beginTransaction();

                            // Fill the data
                            $recepcion->fill($request->all());

                            if($recepcion->save())
                            {
                                $success = true;

                                $syncData = [];
                                // This list has only 1 item
                                foreach(array_keys($diffList) as $ocId)
                                {      
                                    // If success wasn't broken yet, continue
                                    if($success === true)
                                    {
                                        $oc = null;
                
                                        switch($user->role->name)
                                        {
                                            // Administrador
                                            case 'admin': {

                                                // Only if Oc was generated from its same country
                                                $oc = Oc::select('ocs.*')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                    ->where('ocs.id', '=', $ocId)
                                                    ->where('solicitudes.comprador_id', '=', $comprador->id) // If solicitud belongs to this Comprador
                                                    ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                    ->first();
                
                                                break;
                                            }
                
                                            // Agente de compra
                                            case 'agtcom': {

                                                // If user belongs to this Comprador
                                                if(
                                                    (get_class($user->stationable) === get_class($comprador)) &&
                                                    ($user->stationable->id === $comprador->id)
                                                )
                                                {
                                                    $oc = Oc::select('ocs.*')
                                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                        ->where('ocs.id', '=', $ocId)
                                                        ->where('solicitudes.comprador_id', '=', $comprador->id) // If solicitud belongs to this Comprador
                                                        ->first();
                                                }
                
                                                break;
                                            }
                
                                            // Coordinador logistico en Comprador
                                            case 'colcom': {

                                                // If user belongs to this Comprador
                                                if(
                                                    (get_class($user->stationable) === get_class($comprador)) &&
                                                    ($user->stationable->id === $comprador->id)
                                                )
                                                {
                                                    $oc = Oc::select('ocs.*')
                                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                        ->where('ocs.id', '=', $ocId)
                                                        ->where('solicitudes.comprador_id', '=', $comprador->id) // If solicitud belongs to this Comprador
                                                        ->first();
                                                }
                
                                                break;
                                            }
                
                                            default: {
                                                break;
                                            }
                                        }
                
                                        if($oc !== null)
                                        {
                                            foreach(array_keys($diffList[$oc->id]) as $parteId)
                                            {
                                                if($p = $oc->partes->find($parteId))
                                                {
                                                    // Calc new cantidad with cantidad in Recepciones + diff (negative when removing)
                                                    $newCantidad = $p->pivot->getCantidadRecepcionado($comprador) + $diffList[$oc->id][$parteId];

                                                    // If new cantidad in Recepciones is equal or less than total in OC
                                                    if($newCantidad <= $p->pivot->cantidad)
                                                    {
                                                        // If new cantidad in Recepciones is equal or more than total in Despachos
                                                        if($newCantidad >= $p->pivot->getCantidadDespachado($comprador))
                                                        {
                                                            // If OC is in the request
                                                            if(in_array($oc->id, array_keys($ocList)) === true)
                                                            {
                                                                // If Parte is in the request for the OC
                                                                if(in_array($parteId, array_keys($ocList[$oc->id])) === true)
                                                                {
                                                                    // Add the OcParte to syunc using the ID which is unique
                                                                    $syncData[$p->pivot->id] = array(
                                                                        'cantidad' => $ocList[$oc->id][$parteId]
                                                                    );
                                                                }
                                                            }
                                                        }
                                                        else
                                                        {
                                                            // If the received parts are more than waiting in queue
                                                            $response = HelpController::buildResponse(
                                                                409,
                                                                'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor a la cantidad ya despachada',
                                                                null
                                                            );
                        
                                                            $success = false;
                        
                                                            break;
                                                        }
                                                    }
                                                    else
                                                    {
                                                        // If the received parts are more than waiting in queue
                                                        $response = HelpController::buildResponse(
                                                            409,
                                                            'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor a la cantidad de pendiente de recepcion en la OC: ' . $oc->id,
                                                            null
                                                        );
                    
                                                        $success = false;
                    
                                                        break;
                                                    }
                                                }
                                                else
                                                {
                                                    //If the entered parte isn't in the OC
                                                    $response = HelpController::buildResponse(
                                                        409,
                                                        'Una de las partes ingresadas no existe en la OC: ' . $oc->id,
                                                        null
                                                    );
                
                                                    $success = false;
                
                                                    break;
                                                }
                                            }
                                        }
                                        else
                                        {
                                            if(Oc::find($ocId))
                                            {
                                                $response = HelpController::buildResponse(
                                                    405,
                                                    'No tienes acceso a actualizar recepciones para la OC: ' . $ocId,
                                                    null
                                                );
                                            }
                                            else
                                            {
                                                $response = HelpController::buildResponse(
                                                    412,
                                                    'La OC: ' . $ocId . ' no existe',
                                                    null
                                                );
                                            }
                                            
                                            $success = false;
            
                                            break;
                                        }
                                    }
                                    // If success was already broken
                                    else
                                    {
                                        // Break the higher loop
                                        break;
                                    }                    
                                }

                                if(($success === true) && ($recepcion->ocpartes()->sync($syncData)))
                                {
                                    DB::commit();
                                        
                                    $response = HelpController::buildResponse(
                                        201,
                                        'Recepcion actualizada',
                                        null
                                    );
                                }
                                else
                                {
                                    DB::rollback();
                                }
                            }
                            else
                            {       
                                DB::rollback();

                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al actualizar la recepcion',
                                    null
                                );
                            }
                        }
                    }
                    // If wasn't catched
                    else
                    {
                        // If Recepcion exists
                        if(Recepcion::find($id))
                        {
                            // It was filtered, so it's forbidden
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a actualizar la recepcion',
                                null
                            );
                        }
                        // It doesn't exist
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La recepcion no existe',
                                null
                            );
                        }
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar recepciones para comprador',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al actualizar la recepcion [!]',
                null
            );
        }
        
        return $response;
    }

    public function destroy_comprador($comprador_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores recepciones_destroy'))
            {
                if($comprador = Comprador::find($comprador_id))
                {
                    $recepcion = null;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {
                            
                            // Only if Recepcion contains OcPartes from OCs generated from its same country
                            $recepcion = Recepcion::select('recepciones.*')
                                        ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                        ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                        ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                        ->where('recepciones.id', '=', $id) // For this Recepcion
                                        ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                        ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                        ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->first();

                            break;
                        }

                        // Agente de compra
                        case 'agtcom': {

                            // If user belongs to this Comprador
                            if(
                                (get_class($user->stationable) === get_class($comprador)) &&
                                ($user->stationable->id === $comprador->id)
                            )
                            {
                                // Only if Recepcion was received at Comprador
                                $recepcion = Recepcion::select('recepciones.*')
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->first();
                            }

                            break;
                        }

                        // Coordinador logistico at Comprador
                        case 'colcom': {

                            // If user belongs to this Comprador
                            if(
                                (get_class($user->stationable) === get_class($comprador)) &&
                                ($user->stationable->id === $comprador->id)
                            )
                            {
                                // Only if Recepcion was received at Comprador
                                $recepcion = Recepcion::select('recepciones.*')
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->first();
                            }

                            break;
                        }

                        default: {
                            break;
                        }
                    }

                    if($recepcion !== null)
                    {
                        $ocList = array();

                        // For each OcParte in Recepcion
                        foreach($recepcion->ocpartes as $ocParte)
                        {
                            // If OC isn't in the OC list yet
                            if((in_array($ocParte->oc->id, array_keys($ocList))) === false)
                            {
                                // Add the OC and add the Parte with negative cantidad for removing
                                $ocList[$ocParte->oc->id] = array(
                                    $ocParte->parte->id => ($ocParte->pivot->cantidad * -1)
                                );
                            }
                            // If OC was already in the list
                            else
                            {
                                // Add the Parte with negative cantidad for removing
                                $ocList[$ocParte->oc->id][$ocParte->parte->id] = ($ocParte->pivot->cantidad * -1);
                            }
                        }

                        $success = true;
                        foreach(array_keys($ocList) as $ocId)
                        {      
                            // If success wasn't broken yet, continue
                            if($success === true)
                            {
                                $oc = null;
        
                                switch($user->role->name)
                                {
                                    // Administrador
                                    case 'admin': {

                                        // Only if Oc was generated from its same country
                                        $oc = Oc::select('ocs.*')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('ocs.id', '=', $ocId)
                                            ->where('solicitudes.comprador_id', '=', $comprador->id) // If solicitud belongs to this Comprador
                                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->first();
        
                                        break;
                                    }
        
                                    // Agente de compra
                                    case 'agtcom': {

                                        // If user belongs to this Comprador
                                        if(
                                            (get_class($user->stationable) === get_class($comprador)) &&
                                            ($user->stationable->id === $comprador->id)
                                        )
                                        {
                                            $oc = Oc::select('ocs.*')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->where('ocs.id', '=', $ocId)
                                                ->where('solicitudes.comprador_id', '=', $comprador->id) // If solicitud belongs to this Comprador
                                                ->first();
                                        }
        
                                        break;
                                    }
        
                                    // Coordinador logistico en Comprador
                                    case 'colcom': {

                                        // If user belongs to this Comprador
                                        if(
                                            (get_class($user->stationable) === get_class($comprador)) &&
                                            ($user->stationable->id === $comprador->id)
                                        )
                                        {
                                            $oc = Oc::select('ocs.*')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->where('ocs.id', '=', $ocId)
                                                ->where('solicitudes.comprador_id', '=', $comprador->id) // If solicitud belongs to this Comprador
                                                ->first();
                                        }
        
                                        break;
                                    }
        
                                    default: {
                                        break;
                                    }
                                }
        
                                if($oc !== null)
                                {
                                    foreach(array_keys($ocList[$oc->id]) as $parteId)
                                    {
                                        if($p = $oc->partes->find($parteId))
                                        {
                                            // Calc new cantidad with cantidad in Recepciones + diff (negative when removing)
                                            $newCantidad = $p->pivot->getCantidadRecepcionado($comprador) + $ocList[$oc->id][$parteId];

                                            // If new cantidad in Recepciones is less than total in Despachos
                                            if($newCantidad < $p->pivot->getCantidadDespachado($comprador))
                                            {
                                                // If the received parts are more than waiting in queue
                                                $response = HelpController::buildResponse(
                                                    409,
                                                    'La parte "' . $p->nparte . '" ya tiene partes despachadas en la OC: ' . $oc->id,
                                                    null
                                                );
            
                                                $success = false;
            
                                                break;
                                            }
                                        }
                                        else
                                        {
                                            $response = HelpController::buildResponse(
                                                500,
                                                'Error al eliminar la recepcion',
                                                null
                                            );
        
                                            $success = false;
        
                                            break;
                                        }
                                    }
                                }
                                else
                                {
                                    if(Oc::find($ocId))
                                    {
                                        $response = HelpController::buildResponse(
                                            405,
                                            'No tienes acceso a eliminar recepciones para la OC: ' . $ocId,
                                            null
                                        );
                                    }
                                    else
                                    {
                                        $response = HelpController::buildResponse(
                                            412,
                                            'La OC: ' . $ocId . ' no existe',
                                            null
                                        );
                                    }
                                    
                                    $success = false;
    
                                    break;
                                }
                            }
                            // If success was already broken
                            else
                            {
                                // Break the higher loop
                                break;
                            }                    
                        }

                        if(($success === true) && ($recepcion->delete()))
                        {  
                            $response = HelpController::buildResponse(
                                200,
                                'Recepcion eliminada',
                                null
                            );
                        }
                    }
                    // If wasn't catched
                    else
                    {
                        // If Recepcion exists
                        if(Recepcion::find($id))
                        {
                            // It was filtered, so it's forbidden
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a eliminar la recepcion',
                                null
                            );
                        }
                        // It doesn't exist
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La recepcion no existe',
                                null
                            );
                        }
                    }                    
                }
                else
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El comprador no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a eliminar recepciones para comprador',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al eliminar la recepcion [!]',
                null
            );
        }
        
        return $response;
    }
}
