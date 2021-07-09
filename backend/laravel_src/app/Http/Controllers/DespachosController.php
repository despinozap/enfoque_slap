<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Comprador;
use App\Models\Country;
use App\Models\Sucursal;
use App\Models\Oc;
use App\Models\OcParte;
use App\Models\Despacho;

class DespachosController extends Controller
{

    /*
     *  Compradores 
     */
    
    public function store_prepare_comprador($comprador_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores despachos_store'))
            {
                if($comprador = Comprador::find($comprador_id))
                {
                    $countryList = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // Get only Countries from where Ocs were generated and OcPartes were received at Comprador
                            $countryList = Country::select('countries.*')
                                        ->join('sucursales', 'sucursales.country_id', '=', 'countries.id')
                                        ->join('solicitudes', 'solicitudes.sucursal_id', '=', 'sucursales.id')
                                        ->join('cotizaciones', 'cotizaciones.solicitud_id', '=', 'solicitudes.id')
                                        ->join('ocs', 'ocs.cotizacion_id', '=', 'cotizaciones.id')
                                        ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                        ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id') // Only for OcPartes in Recepciones
                                        ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                        ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                        ->where('recepciones.recepcionable_id', '=', $comprador->id) // Recepciones received at Comprador
                                        ->where('ocs.estadooc_id', '=', 2)  // Oc with estadooc = 'En proceso'
                                        ->where('sucursales.country_id', '=', $user->stationable->country->id) // Solicitud from same Country as user station
                                        ->groupBy('countries.id')
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
                                // Get only Countries from where Ocs were generated and OcPartes were received at Comprador
                                $countryList = Country::select('countries.*')
                                            ->join('sucursales', 'sucursales.country_id', '=', 'countries.id')
                                            ->join('solicitudes', 'solicitudes.sucursal_id', '=', 'sucursales.id')
                                            ->join('cotizaciones', 'cotizaciones.solicitud_id', '=', 'solicitudes.id')
                                            ->join('ocs', 'ocs.cotizacion_id', '=', 'cotizaciones.id')
                                            ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id') // Only for OcPartes in Recepciones
                                            ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Recepciones received at Comprador
                                            ->where('ocs.estadooc_id', '=', 2)  // Oc with estadooc = 'En proceso'
                                            ->groupBy('countries.id')
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
                                // Get only Countries from where Ocs were generated and OcPartes were received at Comprador
                                $countryList = Country::select('countries.*')
                                            ->join('sucursales', 'sucursales.country_id', '=', 'countries.id')
                                            ->join('solicitudes', 'solicitudes.sucursal_id', '=', 'sucursales.id')
                                            ->join('cotizaciones', 'cotizaciones.solicitud_id', '=', 'solicitudes.id')
                                            ->join('ocs', 'ocs.cotizacion_id', '=', 'cotizaciones.id')
                                            ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id') // Only for OcPartes in Recepciones
                                            ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Recepciones received at Comprador
                                            ->where('ocs.estadooc_id', '=', 2)  // Oc with estadooc = 'En proceso'
                                            ->groupBy('countries.id')
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

                    if($countryList !== null)
                    {
                        $countryList = $countryList->reduce(function($carry, $country)
                            {
                                if(in_array($country->id, $carry) === false)
                                {
                                    array_push($carry, $country->id);
                                }

                                return $carry;
                            },
                            []
                        );
                        
                        $centrodistribucionList = Sucursal::select('sucursales.*')
                                    ->whereIn('sucursales.country_id', $countryList)
                                    ->where('sucursales.type', '=', 'centro')
                                    ->get();

                        $centrodistribucionList = $centrodistribucionList->map(function($centrodistribucion)
                            {
                                $centrodistribucion->makeHidden([
                                    'type',
                                    'rut',
                                    'address',
                                    'city',
                                    'country_id',
                                    'created_at',
                                    'updated_at'
                                ]);

                                $centrodistribucion->country;
                                $centrodistribucion->country->makeHidden(['created_at', 'updated_at']);

                                return $centrodistribucion;
                            }
                        );

                    
                        $data = [
                            "centrosdistribucion" => $centrodistribucionList
                        ];

                        $response = HelpController::buildResponse(
                            200,
                            null,
                            $data
                        );
                    }
                    else if($forbidden === true)
                    {
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso registrar despachos para el comprador',
                            null
                        );
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            500,
                            'Error al preparar el despacho',
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
                    'No tienes acceso a registrar despachos para comprador',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al preparar el despacho [!]',
                null
            );
        }
            
        return $response;
    }

    public function queueOcPartes_comprador($comprador_id, $centrodistribucion_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores despachos_store'))
            {
                if($comprador = Comprador::find($comprador_id))
                {
                    if($centrodistribucion = Sucursal::where('id', '=', $centrodistribucion_id)->where('type', '=', 'centro')->first())
                    {
                        $ocParteList = null;
                        $forbidden = false;
    
                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {
    
                                // Get only OcPartes on OCs generated from its country and received at Comprador
                                $ocParteList = OcParte::select('oc_parte.*')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                            ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->where('sucursales.country_id', '=', $centrodistribucion->country->id) // Same Country as Sucursal (centro)
                                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->groupBy('oc_parte.id')
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
                                    // Get only OcPartes on OCs generated from its country and received at Comprador
                                    $ocParteList = OcParte::select('oc_parte.*')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                                ->where('sucursales.country_id', '=', $centrodistribucion->country->id) // Same Country as Sucursal (centro)
                                                ->groupBy('oc_parte.id')
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
                                    // Get only OcPartes on OCs generated from its country and received at Comprador
                                    $ocParteList = OcParte::select('oc_parte.*')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                                ->where('sucursales.country_id', '=', $centrodistribucion->country->id) // Same Country as Sucursal (centro)
                                                ->groupBy('oc_parte.id')
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
    
                        if($ocParteList !== null)
                        {
                            $queueOcPartes = $ocParteList->reduce(function($carry, $ocParte) use ($comprador)
                                {
                                    $cantidadRecepcionado = $ocParte->getCantidadRecepcionado($comprador);
                                    $cantidadDespachado = $ocParte->getCantidadDespachado($comprador);

                                    // Add to list only if has stock at Comprador
                                    if($cantidadDespachado < $cantidadRecepcionado)
                                    {
                                        // Filter data to response
                                        $ocParte->makeHidden([
                                            'oc_id',
                                            'parte_id',
                                            'estadoocparte_id',
                                            'tiempoentrega',
                                            'created_at',
                                        ]);

                                        $ocParte->cantidad_recepcionado = $cantidadRecepcionado;
                                        $ocParte->cantidad_despachado = $cantidadDespachado;

                                        $ocParte->parte->makeHidden([
                                            'marca_id',
                                            'created_at', 
                                            'updated_at'
                                        ]);
    
                                        $ocParte->parte->marca;
                                        $ocParte->parte->marca->makeHidden([
                                            'created_at', 
                                            'updated_at'
                                        ]);

                                        $ocParte->oc;
                                        $ocParte->oc->makeHidden([
                                            'cotizacion_id',
                                            'proveedor_id',
                                            'filedata_id',
                                            'estadooc_id',
                                            'motivobaja_id',
                                            'usdvalue',
                                            'partes_total',
                                            'partes',
                                            'created_at',
                                            'updated_at',
                                        ]);
    
                                        $ocParte->oc->cotizacion->makeHidden([
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
    
                                        $ocParte->oc->cotizacion->solicitud;
                                        $ocParte->oc->cotizacion->solicitud->makeHidden([
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
    
                                        $ocParte->oc->cotizacion->solicitud->sucursal;
                                        $ocParte->oc->cotizacion->solicitud->sucursal->makeHidden([
                                            'type',
                                            'rut',
                                            'address',
                                            'city',
                                            'country_id',
                                            'created_at',
                                            'updated_at'
                                        ]);
    
                                        $ocParte->oc->cotizacion->solicitud->faena;
                                        $ocParte->oc->cotizacion->solicitud->faena->makeHidden([
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
    
                                        $ocParte->oc->cotizacion->solicitud->faena->cliente;
                                        $ocParte->oc->cotizacion->solicitud->faena->cliente->makeHidden([
                                            'country_id',
                                            'created_at',
                                            'updated_at'
                                        ]);
    
                                        $ocParte->oc->cotizacion->solicitud->marca;
                                        $ocParte->oc->cotizacion->solicitud->marca->makeHidden([
                                            'created_at',
                                            'updated_at'
                                        ]);

                                        array_push($carry, $ocParte);
                                    }

                                    return $carry;
                                },
                                []
                            );
    
                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $queueOcPartes
                            );
                        }
                        else if($forbidden === true)
                        {
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a visualizar las OCs pendiente de despacho',
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
                            'El centro de distribucion no existe',
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
                    'No tienes acceso a visualizar OCs pendiente de despacho',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener OCs pendiente de despacho [!]' . $e,
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
            if($user->role->hasRoutepermission('compradores despachos_store'))
            {
                $validatorInput = $request->only('centrodistribucion_id', 'fecha', 'ndocumento', 'responsable', 'comentario', 'ocs');
            
                $validatorRules = [
                    'centrodistribucion_id' => 'required|exists:sucursales,id,type,"centro"',
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
                    'centrodistribucion_id.required' => 'Debes seleccionar el centro de distribucion',
                    'centrodistribucion_id.exists' => 'El centro de distribucion no existe',
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
                else if(($centrodistribucion = Sucursal::where('type', '=', 'centro')->where('id', '=', $request->centrodistribucion_id)->first()) === null)
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El centro de distribucion seleccionado no existe',
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


                    DB::beginTransaction();

                    $despacho = new Despacho();
                    // Set the morph source for Despacho as Comprador
                    $despacho->despachable_id = $comprador->id;
                    $despacho->despachable_type = get_class($comprador);
                    // Set the morph destination for Despacho as Sucursal (centro)
                    $despacho->destinable_id = $centrodistribucion->id;
                    $despacho->destinable_type = get_class($centrodistribucion);
                    // Fill the data
                    $despacho->fecha = $request->fecha;
                    $despacho->ndocumento = $request->ndocumento;
                    $despacho->responsable = $request->responsable;
                    $despacho->comentario = $request->comentario;

                    if($despacho->save())
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
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->where('solicitudes.comprador_id', '=', $comprador->id) // Solicitudes for this Comprador
                                            ->where('sucursales.country_id', '=', $centrodistribucion->country->id) // Same Country as Sucursal (centro)
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
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->where('solicitudes.comprador_id', '=', $comprador->id) // Solicitudes for this Comprador
                                                ->where('sucursales.country_id', '=', $centrodistribucion->country->id) // Same Country as Sucursal (centro)
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
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->where('solicitudes.comprador_id', '=', $comprador->id) // Solicitudes for this Comprador
                                                ->where('sucursales.country_id', '=', $centrodistribucion->country->id) // Same Country as Sucursal (centro)
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
                                            $cantidadRecepcionado = $p->pivot->getCantidadRecepcionado($comprador);
                                            $cantidadDespachado = $p->pivot->getCantidadDespachado($comprador);

                                            if($cantidadDespachado < $cantidadRecepcionado)
                                            {
                                                if(($cantidadDespachado + $ocList[$oc->id][$parteId]) <= $cantidadRecepcionado)
                                                {
                                                    $despacho->ocpartes()->attach(
                                                        array(
                                                            $p->pivot->id => array(
                                                                "cantidad" => $ocList[$oc->id][$parteId]
                                                            )
                                                        )
                                                    );
                                                }
                                                else
                                                {
                                                    // If the dispatched parts are more than waiting in queue
                                                    $response = HelpController::buildResponse(
                                                        409,
                                                        'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor a la cantidad de pendiente de despacho',
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
                                                    'La parte "' . $p->nparte . '" no tiene partes pendiente de despacho',
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
                                    if(Oc::find($ocId))
                                    {
                                        $response = HelpController::buildResponse(
                                            405,
                                            'No tienes acceso a registrar despachos para la OC',
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
                                'Despacho creado',
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
                            'Error al crear el despacho',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a registrar despachos para comprador',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al crear el despacho [!]' . $e,
                null
            );
        }
        
        return $response;
    }
}
