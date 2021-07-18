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
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                        ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                        ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.id', '=', $user->stationable->id) // Same Sucursal as user station
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
                                            'ocpartes',
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

    public function store_prepare_comprador($comprador_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores recepciones_store'))
            {
                if($comprador = Comprador::find($comprador_id))
                {
                    $proveedores = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // Get only Proveedores assigned for Ocs which were generated at its country for Comprador
                            $proveedores = Proveedor::select('proveedores.*')
                                        ->join('ocs', 'ocs.proveedor_id', '=', 'proveedores.id')
                                        ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('proveedores.comprador_id', '=', $comprador->id) // Proveedores for this Comprador
                                        ->where('ocs.estadooc_id', '=', 2)  // Oc with estadooc = 'En proceso'
                                        ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->groupBy('proveedores.id')
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
                                // Get only Proveedores assigned for Ocs for Comprador
                                $proveedores = Proveedor::select('proveedores.*')
                                            ->join('ocs', 'ocs.proveedor_id', '=', 'proveedores.id')
                                            ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                            ->where('proveedores.comprador_id', '=', $comprador->id) // Proveedores for this Comprador
                                            ->where('ocs.estadooc_id', '=', 2)  // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->groupBy('proveedores.id')
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
                                // Get only Proveedores assigned for Ocs for Comprador
                                $proveedores = Proveedor::select('proveedores.*')
                                            ->join('ocs', 'ocs.proveedor_id', '=', 'proveedores.id')
                                            ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                            ->where('proveedores.comprador_id', '=', $comprador->id) // Proveedores for this Comprador
                                            ->where('ocs.estadooc_id', '=', 2)  // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->groupBy('proveedores.id')
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

                    if($proveedores !== null)
                    {
                        $proveedores = $proveedores->map(function($proveedor)
                            {
                                $proveedor->makeHidden([
                                    'comprador_id',
                                    'rut',
                                    'address',
                                    'contact',
                                    'phone',
                                    'created_at',
                                    'updated_at'
                                ]);

                                return $proveedor;
                            }
                        );

                    
                        $data = [
                            "proveedores" => $proveedores
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
                            'No tienes acceso registrar recepciones para el comprador',
                            null
                        );
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            500,
                            'Error al preparar la recepcion',
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
                    'No tienes acceso a registrar recepciones para comprador',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al preparar la recepcion [!]',
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
                                        ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                        ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                        ->where('ocs.proveedor_id', '=', $proveedor->id)
                                        ->where('solicitudes.comprador_id', '=', $comprador->id) // For this Comprador
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->groupBy('ocs.id')
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
                                            ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('solicitudes.comprador_id', '=', $comprador->id) // For this Comprador
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->where('ocs.proveedor_id', '=', $proveedor->id)
                                            ->groupBy('ocs.id')
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
                                            ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('solicitudes.comprador_id', '=', $comprador->id) // For this Comprador
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->where('ocs.proveedor_id', '=', $proveedor->id)
                                            ->groupBy('ocs.id')
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
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('ocs.id', '=', $ocId) // For this Oc
                                                ->where('ocs.proveedor_id', '=', $proveedor->id)
                                                ->where('solicitudes.comprador_id', '=', $comprador->id) // For this Comprador
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                                    ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('ocs.id', '=', $ocId)
                                                    ->where('ocs.proveedor_id', '=', $proveedor->id)
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
                                                    ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('ocs.id', '=', $ocId)
                                                    ->where('ocs.proveedor_id', '=', $proveedor->id)
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
                                                        $newCantidad = $cantidadRecepcionado + $ocList[$oc->id][$parteId];
                                                        if($newCantidad <= $p->pivot->cantidad)
                                                        {
                                                            // If new cantidad in Recepciones is equal to cantidad in Oc
                                                            if($newCantidad === $p->pivot->cantidad)
                                                            {
                                                                // All partes were received at Comprador
                                                                $p->pivot->estadoocparte_id = 2; // Estadoocparte = 'En transito'

                                                                // If fail on saving the new status for OcParte
                                                                if(!($p->pivot->save()))
                                                                {
                                                                    $response = HelpController::buildResponse(
                                                                        500,
                                                                        'Error al cambiar el estado de la parte "' . $p->nparte . '"',
                                                                        null
                                                                    );
                                
                                                                    $success = false;
                                
                                                                    break;
                                                                }
                                                            }

                                                            // If didn't break the loop, then add the OcParte to Recepcion
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
                                                                'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor a la cantidad pendiente de recepcion en la OC: ' . $oc->id,
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
                                                            'La parte "' . $p->nparte . '" no tiene partes pendiente de recepcion en la OC: ' . $oc->id,
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
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('recepciones.id', '=', $id) // For this Recepcion
                                                ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('recepciones.id', '=', $id) // For this Recepcion
                                                ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.id', '=', $user->stationable->id) // Same Sucursal as user station
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
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('ocs.estadooc_id', '=', 2)  // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                                ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->where('ocs.estadooc_id', '=', 2)  // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
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
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                                ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->where('ocs.estadooc_id', '=', 2)  // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
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
                                    // Set minimum cantidad as cantidad in Despachos - (cantidad in Recepciones - cantidad in Recepcion) at Comprador
                                    $ocParte->cantidad_min = $ocParte->getCantidadDespachado($comprador) - ($ocParte->getCantidadRecepcionado($comprador) - $ocParte->pivot->cantidad);
                   
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
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('ocs.estadooc_id', '=', 2)  // Oc with estadooc = 'En proceso'
                                        ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                        ->where('recepciones.id', '=', $id) // For this Recepcion
                                        ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                        ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->where('ocs.estadooc_id', '=', 2)  // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
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
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->where('ocs.estadooc_id', '=', 2)  // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
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
                                                    ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                    ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                    ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('ocs.id', '=', $ocId)
                                                    ->where('solicitudes.comprador_id', '=', $comprador->id) // If solicitud belongs to this Comprador
                                                    ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                    ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                                        ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                        ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                        ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
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
                                                        ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                        ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                        ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
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
                                                                    // If new cantidad in Recepciones is equal to cantidad in Oc
                                                                    if($newCantidad === $p->pivot->cantidad)
                                                                    {
                                                                        // All partes were received at Comprador
                                                                        $p->pivot->estadoocparte_id = 2; // Estadoocparte = 'En transito'
                                                                    }
                                                                    else
                                                                    {
                                                                        $p->pivot->estadoocparte_id = 1; // Estadoocparte = 'Pendiente'
                                                                    }

                                                                    // Add the OcParte to sync using the ID which is unique
                                                                    $syncData[$p->pivot->id] = array(
                                                                        'cantidad' => $ocList[$oc->id][$parteId]
                                                                    );
                                                                }
                                                                // If Oc is in the list but not the Parte, so it's gonna be removed from Recepcion
                                                                else
                                                                {
                                                                    $p->pivot->estadoocparte_id = 1; // Estadoocparte = 'Pendiente'
                                                                }
                                                            }
                                                            // If the Oc isn't in the list, so the Parte it's gonna be removed from Recepcion
                                                            else
                                                            {
                                                                $p->pivot->estadoocparte_id = 1; // Estadoocparte = 'Pendiente'
                                                            }

                                                            // If fails on saving the new status for OcParte
                                                            if(!($p->pivot->save()))
                                                            {
                                                                $response = HelpController::buildResponse(
                                                                    500,
                                                                    'Error al cambiar el estado de la parte "' . $p->nparte . '"',
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
                                                                'La cantidad ingresada para la parte "' . $p->nparte . '" es menor a la cantidad ya despachada',
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
                                                            'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor a la cantidad pendiente de recepcion en la OC: ' . $oc->id,
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
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('ocs.estadooc_id', '=', 2)  // Oc with estadooc = 'En proceso'
                                        ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                        ->where('recepciones.id', '=', $id) // For this Recepcion
                                        ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                        ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->where('ocs.estadooc_id', '=', 2)  // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
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
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->where('ocs.estadooc_id', '=', 2)  // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
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

                        DB::beginTransaction();

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
                                            ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('ocs.id', '=', $ocId)
                                            ->where('solicitudes.comprador_id', '=', $comprador->id) // If solicitud belongs to this Comprador
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
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
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
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

                                            // If new cantidad in Recepciones is more or equal than total in Despachos
                                            if($newCantidad >= $p->pivot->getCantidadDespachado($comprador))
                                            {
                                                // OcParte estadoocparte goes back to 'Pendiente'
                                                $p->pivot->estadoocparte_id = 1; // Estadoocparte = 'Pendiente'

                                                // If fail on saving the new status for OcParte
                                                if(!($p->pivot->save()))
                                                {
                                                    $response = HelpController::buildResponse(
                                                        500,
                                                        'Error al cambiar el estado de la parte "' . $p->nparte . '"',
                                                        null
                                                    );
                
                                                    $success = false;
                
                                                    break;
                                                }
                                            }
                                            // If new cantidad in Recepciones is less than total in Despachos
                                            else
                                            {
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
                            DB::commit();

                            $response = HelpController::buildResponse(
                                200,
                                'Recepcion eliminada',
                                null
                            );
                        }
                        else
                        {
                            DB::rollback();

                            // Error message was already given
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


    /*
     *  Sucursales (centro)
     */

    public function index_centrodistribucion($id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales recepciones_index'))
            {
                if($centrodistribucion = Sucursal::where('type', '=', 'centro')->where('id', '=', $id)->first())
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
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                        ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->groupBy('recepciones.id')
                                        ->get();

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // Get only Recepciones containing OcPartes from OCs generated from its same Sucursal
                            $recepciones = Recepcion::select('recepciones.*')
                                        ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                        ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                        ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                        ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
										->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                        ->groupBy('recepciones.id')
                                        ->get();

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
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                            ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                            ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.id', '=', $user->stationable->id) // Same Sucursal as user station
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
                        $recepciones = $recepciones->map(function($recepcion)
                            {
                                $recepcion->partes_total;
                                        
                                $recepcion->makeHidden([
                                    'sourceable_id', 
                                    'sourceable_type',
                                    'recepcionable_id', 
                                    'recepcionable_type', 
                                    'ocpartes',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $recepcion->sourceable;
                                $recepcion->sourceable->makeHidden([
                                    'rut',
                                    'address',
                                    'contact',
                                    'phone',
                                    'country_id',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $recepcion->sourceable->country;
                                $recepcion->sourceable->country->makeHidden(['created_at', 'updated_at']);

                                return $recepcion;
                            }
                        );

                        $response = HelpController::buildResponse(
                            200,
                            null,
                            $recepciones
                        );
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
                        'El centro de distribucion no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar recepciones de centros de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener las recepciones del centro de distribucion [!]',
                null
            );
        }
            
        return $response;
    }

    public function store_prepare_centrodistribucion($centrodistribucion_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales recepciones_store'))
            {
                if($centrodistribucion = Sucursal::where('id', '=', $centrodistribucion_id)->where('type', '=', 'centro')->first())
                {
                    $compradores = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // If user belongs to this Sucursal's (centro) country
                            if($user->stationable->country->id === $centrodistribucion->country->id)
                            {
                                // Get only assigned Compradores with OcPartes dispatched to Sucursal (centro) on Ocs generated from its country
                                $compradores = Comprador::select('compradores.*')
                                            ->join('despachos', 'despachos.despachable_id', '=', 'compradores.id')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('despachos.despachable_type', '=', get_class(new Comprador())) // Dispatched by Comprador
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('despachos.destinable_type', '=', get_class($centrodistribucion))
                                            ->where('despachos.destinable_id', '=', $centrodistribucion->id) // Despachos dispatched to Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->groupBy('compradores.id')
                                            ->get();
                            }
                            else
                            {
                                // Set as forbidden
                                $forbidden = true;
                            }

                            break;
                        }

                        // Coordinador logistico at Sucursal (centro)
                        case 'colsol': {
    
                            // If user belongs to this Sucursal (centro)
                            if(
                                (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                ($user->stationable->id === $centrodistribucion->id)
                            )
                            {
                                // Get only assigned Compradores with OcPartes dispatched to Sucursal (centro) on Ocs generated from its country
                                $compradores = Comprador::select('compradores.*')
                                            ->join('despachos', 'despachos.despachable_id', '=', 'compradores.id')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('despachos.despachable_type', '=', get_class(new Comprador())) // Dispatched by Comprador
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('despachos.destinable_type', '=', get_class($centrodistribucion))
                                            ->where('despachos.destinable_id', '=', $centrodistribucion->id) // Despachos dispatched to Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->groupBy('compradores.id')
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

                    if($compradores !== null)
                    {
                        $compradores = $compradores->map(function($comprador)
                            {
                                $comprador->makeHidden([
                                    'rut',
                                    'address',
                                    'contact',
                                    'phone',
                                    'country_id',
                                    'created_at',
                                    'updated_at'
                                ]);

                                $comprador->country;
                                $comprador->country->makeHidden(['created_at', 'updated_at']);
                                
                                return $comprador;
                            }
                        );

                    
                        $data = [
                            "compradores" => $compradores
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
                            'No tienes acceso registrar recepciones para el centro de distribucion',
                            null
                        );
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            500,
                            'Error al preparar la recepcion',
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
                    405,
                    'No tienes acceso a registrar recepciones para centro de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al preparar la recepcion [!]',
                null
            );
        }
            
        return $response;
    }

    public function queueOcPartes_centrodistribucion($centrodistribucion_id, $comprador_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales despachos_store'))
            {
                if($centrodistribucion = Sucursal::where('id', '=', $centrodistribucion_id)->where('type', '=', 'centro')->first())
                {
                    if($comprador = Comprador::find($comprador_id))
                    {
                        $ocParteList = null;
                        $forbidden = false;
    
                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {
    
                                // If user belongs to this Sucursal's (centro) country
                                if($user->stationable->country->id === $centrodistribucion->country->id)
                                {
                                    // Get only OcPartes on OCs generated from its country and dispatched from Comprador to Sucursal (centro)
                                    $ocParteList = OcParte::select('oc_parte.*')
                                                ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('despachos.destinable_type', '=', get_class($centrodistribucion))
                                                ->where('despachos.destinable_id', '=', $centrodistribucion->id) // Dispatched to Sucursal (centro)
                                                ->where('despachos.despachable_type', '=', get_class($comprador))
                                                ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
    
                            // Coordinador logistico at Sucursal (centro)
                            case 'colsol': {
    
                                // If user belongs to this Sucursal (centro)
                                if(
                                    (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                    ($user->stationable->id === $centrodistribucion->id)
                                )
                                {
                                    // Get only OcPartes on OCs generated from its country and dispatched from Comprador to Sucursal (centro)
                                    $ocParteList = OcParte::select('oc_parte.*')
                                                ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('despachos.destinable_type', '=', get_class($centrodistribucion))
                                                ->where('despachos.destinable_id', '=', $centrodistribucion->id) // Dispatched to Sucursal (centro)
                                                ->where('despachos.despachable_type', '=', get_class($comprador))
                                                ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                            $queueOcPartes = $ocParteList->reduce(function($carry, $ocParte) use ($centrodistribucion, $comprador)
                                {
                                    $cantidadRecepcionado = $ocParte->getCantidadRecepcionado($centrodistribucion);
                                    $cantidadDespachado = $ocParte->getCantidadDespachado($comprador);

                                    // Add to list only if has cantidad in transit
                                    if($cantidadRecepcionado < $cantidadDespachado)
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
                                            'monto',
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
                            'El comprador no existe',
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
   
    public function store_centrodistribucion(Request $request, $centrodistribucion_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales recepciones_store'))
            {
                $validatorInput = $request->only('comprador_id', 'fecha', 'ndocumento', 'responsable', 'comentario', 'ocs');
            
                $validatorRules = [
                    'comprador_id' => 'required|exists:compradores,id',
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
                    'comprador_id.required' => 'Debes seleccionar el comprador',
                    'comprador_id.exists' => 'El comprador no existe',
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
                else if(($centrodistribucion = Sucursal::where('type', '=', 'centro')->where('id', '=', $centrodistribucion_id)->first()) === null)
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El centro de distribucion no existe',
                        null
                    );
                }
                else if(($comprador = Comprador::find($request->comprador_id)) === null)
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El comprador seleccionado no existe',
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

                    $recepcion = new Recepcion();
                    // Set the morph source for Recepcion as Comprador
                    $recepcion->sourceable_id = $comprador->id;
                    $recepcion->sourceable_type = get_class($comprador);
                    // Set the morph destination for Recepcion as Sucursal (centro)
                    $recepcion->recepcionable_id = $centrodistribucion->id;
                    $recepcion->recepcionable_type = get_class($centrodistribucion);
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

                                        // If user belongs to this Sucursal's (centro) country
                                        if($user->stationable->country->id === $centrodistribucion->country->id)
                                        {
                                            $oc = Oc::select('ocs.*')
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('despachos.destinable_type', '=', get_class($centrodistribucion))
                                                ->where('despachos.destinable_id', '=', $centrodistribucion->id) // Dispatched to Sucursal (centro)
                                                ->where('despachos.despachable_type', '=', get_class($comprador))
                                                ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                                ->where('solicitudes.comprador_id', '=', $comprador->id) // Solicitudes for this Comprador
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->first();
                                        }
        
                                        break;
                                    }

                                    // Coordinador logistico at Sucursal (centro)
                                    case 'colsol': {
            
                                        // If user belongs to this Sucursal (centro)
                                        if(
                                            (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                            ($user->stationable->id === $centrodistribucion->id)
                                        )
                                        {
                                            $oc = Oc::select('ocs.*')
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('despachos.destinable_type', '=', get_class($centrodistribucion))
                                                ->where('despachos.destinable_id', '=', $centrodistribucion->id) // Dispatched to Sucursal (centro)
                                                ->where('despachos.despachable_type', '=', get_class($comprador))
                                                ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                                ->where('solicitudes.comprador_id', '=', $comprador->id) // Solicitudes for this Comprador
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                            $cantidadRecepcionado = $p->pivot->getCantidadRecepcionado($centrodistribucion);
                                            $cantidadDespachado = $p->pivot->getCantidadDespachado($comprador);

                                            if($cantidadRecepcionado < $cantidadDespachado)
                                            {
                                                if(($cantidadRecepcionado + $ocList[$oc->id][$parteId]) <= $cantidadDespachado)
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
                                                        'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor a la cantidad pendiente de recepcion en la OC: ' . $oc->id,
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
                                                    'La parte "' . $p->nparte . '" no tiene partes pendiente de recepcion en la OC: ' . $oc->id,
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
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a registrar recepciones para centro de distribucion',
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

    public function show_centrodistribucion($centrodistribucion_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales recepciones_show'))
            {
                $validatorInput = ['recepcion_id' => $id];
            
                $validatorRules = [
                    'recepcion_id' => 'required|exists:recepciones,id,recepcionable_id,' . $centrodistribucion_id . ',recepcionable_type,' . get_class(new Sucursal()),
                ];
        
                $validatorMessages = [
                    'recepcion_id.required' => 'Debes ingresar la recepcion',
                    'recepcion_id.exists' => 'La recepcion ingresada no existe para el centro de distribucion',                    
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
                    if($centrodistribucion = Sucursal::where('type', '=', 'centro')->where('id', '=', $centrodistribucion_id)->first())
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
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                            ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                            ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
										    ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                            ->first();

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
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('recepciones.id', '=', $id) // For this Recepcion
                                                ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                                ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('recepciones.id', '=', $id) // For this Recepcion
                                                ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                                ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.id', '=', $user->stationable->id) // Same Sucursal as user station
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
                            $recepcion->makeHidden([
                                'sourceable_id',
                                'sourceable_type',
                                'recepcionable_id',
                                'recepcionable_type',
                                'proveedor_id',
                                'partes_total',
                                'updated_at',
                            ]);

                            $recepcion->sourceable;
                            $recepcion->sourceable->makeHidden([
                                'rut',
                                'address',
                                'contact',
                                'phone',
                                'country_id',
                                'created_at', 
                                'updated_at'
                            ]);

                            $recepcion->sourceable->country;
                            $recepcion->sourceable->country->makeHidden(['created_at', 'updated_at']);

                            $recepcion->recepcionable;
                            $recepcion->recepcionable->makeHidden([
                                'type',
                                'rut',
                                'address',
                                'city',
                                'country_id',
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
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $ocParte->pivot->makeHidden([
                                    'recepcion_id',
                                    'ocparte_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $ocParte->oc;
                                $ocParte->oc->makeHidden([
                                    'cotizacion_id',
                                    'proveedor_id',
                                    'filedata_id',
                                    'estadooc_id',
                                    'motivobaja_id',
                                    'usdvalue',
                                    'partes',
                                    'dias',
                                    'partes_total',
                                    'monto',
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

                                $ocParte->parte->makeHidden([
                                    'marca_id',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $ocParte->parte->marca;
                                $ocParte->parte->marca->makeHidden(['created_at', 'updated_at']);
                            }
                            
                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $recepcion
                            );                
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
                            'El centro de distribucion no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar recepciones de centro de distribucion',
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
     * selecting data and updating a Recepcion for Sucursal (centro)
     * 
     */
    public function update_prepare_centrodistribucion($centrodistribucion_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales recepciones_update'))
            {
                $validatorInput = ['recepcion_id' => $id];
            
                $validatorRules = [
                    'recepcion_id' => 'required|exists:recepciones,id,recepcionable_id,' . $centrodistribucion_id . ',recepcionable_type,' . get_class(new Sucursal()),
                ];
        
                $validatorMessages = [
                    'recepcion_id.required' => 'Debes ingresar la recepcion',
                    'recepcion_id.exists' => 'La recepcion ingresado no existe para el centro de distribucion',                    
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
                    if($centrodistribucion = Sucursal::where('type', '=', 'centro')->where('id', '=', $centrodistribucion_id)->first())
                    {
                        $ocParteList = null;
                        $recepcion = null;

                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {

                                // If user belongs to this Sucursal's (centro) country
                                if($user->stationable->country->id === $centrodistribucion->country->id)
                                {
                                    // Only if Recepcion contains OcPartes from OCs generated from its same country
                                    $recepcion = Recepcion::select('recepciones.*')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                                ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('recepciones.id', '=', $id) // For this Recepcion
                                                ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                                ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->first();
    
                                    if($recepcion !== null)
                                    {
                                         // Get only OcPartes on OCs generated from its country and dispatched from Comprador to Sucursal (centro)
                                        $ocParteList = OcParte::select('oc_parte.*')
                                                    ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                    ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                    ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                    ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                    ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('despachos.destinable_type', '=', get_class($centrodistribucion))
                                                    ->where('despachos.destinable_id', '=', $centrodistribucion->id) // Dispatched to Sucursal (centro)
                                                    ->where('despachos.despachable_type', '=', get_class($recepcion->sourceable)) // Recepcion's source is Comprador
                                                    ->where('despachos.despachable_id', '=', $recepcion->sourceable->id) // Dispatched by Comprador
                                                    ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                    ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                    ->groupBy('oc_parte.id')
                                                    ->get();
    
    
                                         // For OcPartes in current Recepcion
                                         $ocParteList = $recepcion->ocpartes->reduce(function($carry, $ocParte) use ($ocParteList)
                                            {
                                                $contains = $carry->contains(function($op) use ($ocParte)
                                                    {
                                                        return ($ocParte->id === $op->id);
                                                    }
                                                );
    
                                                // If OcParte from Recepcion isn't in queue
                                                if($contains === false)
                                                {
                                                    // Add OcParte to list
                                                    array_push($carry, $ocParte);
                                                }
    
                                                return $carry;
                                            },
                                            $ocParteList // Initialize with previous list as base
                                        );
                                    }
                                }

                                break;
                            }

                    
                            // Coordinador logistico at Sucursal (or Centro)
                            case 'colsol': {

                                // If user belongs to this Sucursal (centro)
                                if(
                                    (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                    ($user->stationable->id === $centrodistribucion->id)
                                )
                                {
                                    // Only if Recepcion contains OcPartes from OCs generated from its same country
                                    $recepcion = Recepcion::select('recepciones.*')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                                ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('recepciones.id', '=', $id) // For this Recepcion
                                                ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                                ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->first();

                                    if($recepcion !== null)
                                    {
                                        // Get only OcPartes on OCs generated from its country and dispatched from Comprador to Sucursal (centro)
                                        $ocParteList = OcParte::select('oc_parte.*')
                                                    ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                    ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                    ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                    ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                    ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('despachos.destinable_type', '=', get_class($centrodistribucion))
                                                    ->where('despachos.destinable_id', '=', $centrodistribucion->id) // Dispatched to Sucursal (centro)
                                                    ->where('despachos.despachable_type', '=', get_class($recepcion->sourceable)) // Recepcion's source is Comprador
                                                    ->where('despachos.despachable_id', '=', $recepcion->sourceable->id) // Dispatched by Comprador
                                                    ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                    ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                    ->groupBy('oc_parte.id')
                                                    ->get();

                                         // For OcPartes in current Recepcion
                                        $ocParteList = $recepcion->ocpartes->reduce(function($carry, $ocParte) use ($ocParteList)
                                            {
                                                $contains = $carry->contains(function($op) use ($ocParte)
                                                    {
                                                        return ($ocParte->id === $op->id);
                                                    }
                                                );

                                                // If OcParte from Recepcion isn't in queue
                                                if($contains === false)
                                                {
                                                    // Add OcParte to list
                                                    array_push($carry, $ocParte);
                                                }

                                                return $carry;
                                            },
                                            $ocParteList // Initialize with previous list as base
                                        );

                                    }
                                }                                

                                break;
                            }
                            

                            default: {
                                break;
                            }
                        }

                        if(
                            ($ocParteList !== null) &&
                            ($recepcion !== null)
                        )
                        {   
                            $queueOcPartes = $ocParteList->reduce(function($carry, $ocParte) use ($recepcion, $centrodistribucion)
                                {
                                    $cantidadRecepcionado = $ocParte->getCantidadRecepcionado($centrodistribucion);
                                    $cantidadDespachado = $ocParte->getCantidadDespachado($recepcion->sourceable); // Recepcion's source is Comprador

                                    // Try to find OcParte in Recepcion
                                    $op = $recepcion->ocpartes->find($ocParte->id);

                                    if(
                                        // If OcParte is in Recepcion
                                        ($op !== null) ||
                                        // Or if OcParte isn't in Recepcion and hasn't been full received yet
                                        (($op === null) && ($cantidadRecepcionado < $cantidadDespachado))
                                    )
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
                                            'monto',
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

                            $recepcion->makeHidden([
                                'sourceable_id', 
                                'sourceable_type',
                                'recepcionable_id', 
                                'recepcionable_type', 
                                'partes_total',
                                'created_at', 
                                'updated_at'
                            ]);

                            $recepcion->sourceable;
                            $recepcion->sourceable->makeHidden([
                                'rut',
                                'address',
                                'contact',
                                'phone',
                                'country_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $recepcion->sourceable->country;
                            $recepcion->sourceable->country->makeHidden([
                                'created_at',
                                'updated_at',
                            ]);

                            $recepcion->recepcionable;
                            $recepcion->recepcionable->makeHidden([
                                'type',
                                'rut',
                                'address',
                                'city',
                                'country_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $recepcion->ocpartes = $recepcion->ocpartes->map(function($ocParte) use ($recepcion)
                                {
                                    // Set minimum cantidad as (cantidad in Despachos + cantidad in Entregas) - (cantidad in Recepciones - cantidad in Recepcion) at recepcionable Sucursal (centro)
                                    $ocParte->cantidad_min = ($ocParte->getCantidadDespachado($recepcion->recepcionable) + $ocParte->getCantidadEntregado($recepcion->recepcionable)) - ($ocParte->getCantidadRecepcionado($recepcion->recepcionable) - $ocParte->pivot->cantidad);

                                    $ocParte->makeHidden([
                                        'oc_id',
                                        'parte_id',
                                        'estadoocparte_id',
                                        'tiempoentrega',
                                        'created_at',
                                        'updated_at'
                                    ]);

                                    $ocParte->pivot;
                                    $ocParte->pivot->makeHidden([
                                        'recepcion_id',
                                        'ocparte_id',
                                        'created_at',
                                        'updated_at'
                                    ]);

                                    $ocParte->parte;
                                    $ocParte->parte->makeHidden([
                                        'marca_id',
                                        'created_at',
                                        'updated_at',
                                    ]);

                                    $ocParte->parte->marca;
                                    $ocParte->parte->marca->makeHidden([
                                        'created_at',
                                        'updated_at',
                                    ]);

                                    $ocParte->oc;
                                    $ocParte->oc->makeHidden([
                                        'cotizacion_id',
                                        'proveedor_id',
                                        'filedata_id',
                                        'estadooc_id',
                                        'motivobaja_id',
                                        'usdvalue',
                                        'partes',
                                        'dias',
                                        'partes_total',
                                        'monto',
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

                                    return $ocParte;
                                }
                            );

                            $data = [
                                "queue_ocpartes" => $queueOcPartes,
                                "recepcion" => $recepcion
                            ];

                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $data
                            );
              
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
                            'El centro de distribucion no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar recepciones de centro de distribucion',
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

    public function update_centrodistribucion(Request $request, $centrodistribucion_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales recepciones_update'))
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
                    'ocs.min' => 'El recepcion debe contener al menos 1 parte recepcionada',
                    'ocs.*.id.required' => 'Debes seleccionar la OC a recepcionar',
                    'ocs.*.id.exists' => 'La OC ingresada no existe',
                    'ocs.*.partes.required' => 'Debes seleccionar las partes recepcionadas',
                    'ocs.*.partes.array' => 'Lista de partes recepcionadas invalida',
                    'ocs.*.partes.min' => 'El recepcion debe contener al menos 1 parte recepcionada',
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
                else if(($centrodistribucion = Sucursal::where('type', '=', 'centro')->where('id', '=', $centrodistribucion_id)->first()) === null)
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El centro de distribucion no existe',
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
                            
                            // If user belongs to this Sucursal's (centro) country
                            if($user->stationable->country->id === $centrodistribucion->country->id)
                            {
                                // Only if Recepcion contains OcPartes from OCs generated from its same country
                                $recepcion = Recepcion::select('recepciones.*')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                            ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->first();
                            }

                            break;
                        }

                        // Coordinador logistico at Sucursal (or Centro)
                        case 'colsol': {

                            // If user belongs to this Sucursal (centro)
                            if(
                                (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                ($user->stationable->id === $centrodistribucion->id)
                            )
                            {
                                // Only if Recepcion contains OcPartes from OCs generated from its same country
                                $recepcion = Recepcion::select('recepciones.*')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                            ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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

                        DB::beginTransaction();

                        // Fill the data
                        $recepcion->fill($request->all());

                        if($recepcion->save())
                        {
                            $success = true;

                            $syncData = [];
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
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=' , 'oc_parte.id')
                                                ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('ocs.id', '=', $ocId)
                                                ->where('despachos.despachable_type', '=', get_class($recepcion->sourceable)) // Recepcion's source is Comprador
                                                ->where('despachos.despachable_id', '=', $recepcion->sourceable->id) // OcParte dispatched from Comprador
                                                ->where('despachos.destinable_type', '=', get_class($centrodistribucion))
                                                ->where('despachos.destinable_id', '=', $centrodistribucion->id) // OcParte dispatched to Sucursal (centro)
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->first();
            
                                            break;
                                        }
            
                                        // Coordinador logistico at Sucursal (or Centro)
                                        case 'colsol': {

                                            // If user belongs to this Sucursal (centro)
                                            if(
                                                (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                                ($user->stationable->id === $centrodistribucion->id)
                                            )
                                            {
                                                // Only if Oc was generated from its same country
                                                $oc = Oc::select('ocs.*')
                                                    ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                    ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=' , 'oc_parte.id')
                                                    ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                    ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                    ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('ocs.id', '=', $ocId)
                                                    ->where('despachos.despachable_type', '=', get_class($recepcion->sourceable)) // Recepcion's source is Comprador
                                                    ->where('despachos.despachable_id', '=', $recepcion->sourceable->id) // OcParte dispatched from Comprador
                                                    ->where('despachos.destinable_type', '=', get_class($centrodistribucion))
                                                    ->where('despachos.destinable_id', '=', $centrodistribucion->id) // OcParte dispatched to Sucursal (centro)
                                                    ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                    ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                                $newCantidad = $p->pivot->getCantidadRecepcionado($centrodistribucion) + $diffList[$oc->id][$parteId];

                                                /*
                                                 *  If new cantidad in Recepciones is equal or more than
                                                 *  cantidad in Despachos + cantidad in Entregas from destination Sucursal (centro) 
                                                 */

                                                if($newCantidad >= $p->pivot->getCantidadDespachado($centrodistribucion) + $p->pivot->getCantidadEntregado($centrodistribucion))
                                                {
                                                    // If new cantidad in Recepciones is equal or less than cantidad in Despachos to Sucursal (centro)
                                                    if($newCantidad <= $p->pivot->getCantidadDespachado($recepcion->sourceable))
                                                    {
                                                        // If OC is in the request
                                                        if(in_array($oc->id, array_keys($ocList)) === true)
                                                        {
                                                            // If Parte is in the request for the OC
                                                            if(in_array($parteId, array_keys($ocList[$oc->id])) === true)
                                                            {
                                                                // Add the OcParte to sync using the ID which is unique
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
                                                            'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor a la cantidad pendiente de recepcion en la OC: ' . $oc->id,
                                                            null
                                                        );
                    
                                                        $success = false;
                    
                                                        break;
                                                    }
                                                }
                                                else
                                                {
                                                    // If the received partes are less than dispathed and delivered at destination Sucursal (centro)
                                                    $response = HelpController::buildResponse(
                                                        409,
                                                        'La cantidad ingresada para la parte "' . $p->nparte . '" es menor a la cantidad ya despachada y/o entregada en el centro de distribucion para la OC: ' . $oc->id,
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
                    'No tienes acceso a actualizar recepciones para centro de distribucion',
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

    public function destroy_centrodistribucion($centrodistribucion_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales recepciones_destroy'))
            {
                if($centrodistribucion = Sucursal::where('type', '=', 'centro')->where('id', '=', $centrodistribucion_id)->first())
                {
                    $recepcion = null;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {
                            
                            // If user belongs to this Sucursal's (centro) country
                            if($user->stationable->country->id === $centrodistribucion->country->id)
                            {
                                // Only if Recepcion contains OcPartes from OCs generated from its same country
                                $recepcion = Recepcion::select('recepciones.*')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            -->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                            ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->first();
                            }

                            break;
                        }

                        // Coordinador logistico at Sucursal (or Centro)
                        case 'colsol': {

                            // If user belongs to this Sucursal (centro)
                            if(
                                (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                ($user->stationable->id === $centrodistribucion->id)
                            )
                            {
                                // Only if Recepcion contains OcPartes from OCs generated from its same country
                                $recepcion = Recepcion::select('recepciones.*')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                            ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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

                        DB::beginTransaction();

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

                                        // If user belongs to this Sucursal's (centro) country
                                        if($user->stationable->country->id === $centrodistribucion->country->id)
                                        {
                                            // Only if Oc was generated from its same country
                                            $oc = Oc::select('ocs.*')
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=' , 'oc_parte.id')
                                                ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('ocs.id', '=', $ocId)
                                                ->where('despachos.despachable_type', '=', get_class($recepcion->sourceable)) // Recepcion's source is Comprador
                                                ->where('despachos.despachable_id', '=', $recepcion->sourceable->id) // OcParte dispatched from Comprador
                                                ->where('despachos.destinable_type', '=', get_class($centrodistribucion))
                                                ->where('despachos.destinable_id', '=', $centrodistribucion->id) // OcParte dispatched to Sucursal (centro)
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->first();    
                                        }

                                        break;
                                    }
        
                                    // Coordinador logistico at Sucursal (or Centro)
                                    case 'colsol': {

                                        // If user belongs to this Sucursal (centro)
                                        if(
                                            (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                            ($user->stationable->id === $centrodistribucion->id)
                                        )
                                        {
                                            // Only if Oc was generated from its same country
                                            $oc = Oc::select('ocs.*')
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=' , 'oc_parte.id')
                                                ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('ocs.id', '=', $ocId)
                                                ->where('despachos.despachable_type', '=', get_class($recepcion->sourceable)) // Recepcion's source is Comprador
                                                ->where('despachos.despachable_id', '=', $recepcion->sourceable->id) // OcParte dispatched from Comprador
                                                ->where('despachos.destinable_type', '=', get_class($centrodistribucion))
                                                ->where('despachos.destinable_id', '=', $centrodistribucion->id) // OcParte dispatched to Sucursal (centro)
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                            $newCantidad = $p->pivot->getCantidadRecepcionado($centrodistribucion) + $ocList[$oc->id][$parteId];

                                            /*
                                            *  If new cantidad in Recepciones is less than
                                            *  cantidad in Despachos + cantidad in Entregas at Sucursal (centro) 
                                            */

                                            if($newCantidad < $p->pivot->getCantidadDespachado($centrodistribucion) + $p->pivot->getCantidadEntregado($centrodistribucion))
                                            {
                                                $response = HelpController::buildResponse(
                                                    409,
                                                    'La parte "' . $p->nparte . '" ya tiene partes despachadas y/o entregadas en la OC: ' . $oc->id,
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
                            DB::commit();

                            $response = HelpController::buildResponse(
                                200,
                                'Recepcion eliminada',
                                null
                            );
                        }
                        else
                        {
                            DB::rollback();

                            // Error message was already given
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
                    'No tienes acceso a eliminar recepciones para centro de distribucion',
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


    /*
     *  Sucursales
     */

    public function index_sucursal($id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales recepciones_index'))
            {
                if($sucursal = Sucursal::where('type', '=', 'sucursal')->where('id', '=', $id)->first())
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
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                        ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Received at Sucursal
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->groupBy('recepciones.id')
                                        ->get();

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // Get only Recepciones containing OcPartes from OCs generated from its same Sucursal
                            $recepciones = Recepcion::select('recepciones.*')
                                        ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                        ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                        ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                        ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Received at Sucursal
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
										->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                        ->groupBy('recepciones.id')
                                        ->get();

                            break;
                        }


                        // Coordinador logistico at Sucursal (or Centro)
                        case 'colsol': {

                            // If user belongs to Sucursal
                            if($user->stationable->type === 'sucursal')
                            {
                                // Get only Recepciones containing OcPartes from OCs generated from its same Sucursal
                                $recepciones = Recepcion::select('recepciones.*')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                            ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Received at Sucursal
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.id', '=', $user->stationable->id) // Same Sucursal as user station
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
                        $recepciones = $recepciones->map(function($recepcion)
                            {
                                $recepcion->partes_total;
                                        
                                $recepcion->makeHidden([
                                    'sourceable_id', 
                                    'sourceable_type',
                                    'recepcionable_id', 
                                    'recepcionable_type',
                                    'ocpartes', 
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $recepcion->sourceable;
                                $recepcion->sourceable->makeHidden([
                                    'type',
                                    'rut',
                                    'address',
                                    'contact',
                                    'phone',
                                    'country_id',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                return $recepcion;
                            }
                        );

                        $response = HelpController::buildResponse(
                            200,
                            null,
                            $recepciones
                        );
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
                        'La sucursal no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar recepciones de sucursales',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener las recepciones de la sucursal [!]',
                null
            );
        }
            
        return $response;
    }

    public function store_prepare_sucursal($sucursal_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales recepciones_store'))
            {
                if($sucursal = Sucursal::where('id', '=', $sucursal_id)->where('type', '=', 'sucursal')->first())
                {
                    $centrosdistribucion = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // If user belongs to this Sucursal's country
                            if($user->stationable->country->id === $sucursal->country->id)
                            {
                                // Get only Sucursales (centro) with OcPartes dispatched to Sucursal on Ocs generated from its country
                                $centrosdistribucion = Sucursal::select('sucursales.*')
                                                    ->join('despachos', 'despachos.despachable_id', '=', 'sucursales.id')
                                                    ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                                    ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                                    ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                    ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                    ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                    ->where('despachos.despachable_type', '=', get_class(new Sucursal())) // Dispatched by Sucursal (centro)
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('despachos.destinable_type', '=', get_class($sucursal))
                                                    ->where('despachos.destinable_id', '=', $sucursal->id) // Despachos dispatched to Sucursal
                                                    ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                    ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                    ->where('sucursales.type', '=', 'centro') // Dispatched by a Sucursal (centro)
                                                    ->where('sucursales.country_id', '=', $user->stationable->country->id) // Solicitud from same Country as user station
                                                    ->groupBy('sucursales.id')
                                                    ->get();
                            }
                            else
                            {
                                // Set as forbidden
                                $forbidden = true;
                            }
                            
                            break;
                        }

                        // Coordinador logistico at Sucursal
                        case 'colsol': {
    
                            // If user belongs to this Sucursal
                            if(
                                (get_class($user->stationable) === get_class($sucursal)) &&
                                ($user->stationable->id === $sucursal->id)
                            )
                            {
                                // Get only Sucursales (centro) with OcPartes dispatched to Sucursal on Ocs generated from its country
                                $centrosdistribucion = Sucursal::select('sucursales.*')
                                                    ->join('despachos', 'despachos.despachable_id', '=', 'sucursales.id')
                                                    ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                                    ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                                    ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                    ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                    ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                    ->where('despachos.despachable_type', '=', get_class(new Sucursal())) // Dispatched by Sucursal (centro)
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('despachos.destinable_type', '=', get_class($sucursal))
                                                    ->where('despachos.destinable_id', '=', $sucursal->id) // Despachos dispatched to Sucursal
                                                    ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                    ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                    ->where('sucursales.type', '=', 'centro') // Dispatched by a Sucursal (centro)
                                                    ->where('sucursales.id', '=', $user->stationable->id) // Solicitud from same Sucursal as user station
                                                    ->groupBy('sucursales.id')
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

                    if($centrosdistribucion !== null)
                    {
                        $centrosdistribucion = $centrosdistribucion->map(function($centrodistribucion)
                            {
                                $centrodistribucion->makeHidden([
                                    'rut',
                                    'address',
                                    'contact',
                                    'phone',
                                    'country_id',
                                    'created_at',
                                    'updated_at'
                                ]);

                                return $centrodistribucion;
                            }
                        );

                    
                        $data = [
                            "centrosdistribucion" => $centrosdistribucion
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
                            'No tienes acceso registrar recepciones para la sucursal',
                            null
                        );
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            500,
                            'Error al preparar la recepcion',
                            null
                        );
                    }
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        412,
                        'La sucursal no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a registrar recepciones para sucursal',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al preparar la recepcion [!]',
                null
            );
        }
            
        return $response;
    }

    public function queueOcPartes_sucursal($sucursal_id, $centrodistribucion_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales despachos_store'))
            {
                if($sucursal = Sucursal::where('id', '=', $sucursal_id)->where('type', '=', 'sucursal')->first())
                {
                    if($centrodistribucion = Sucursal::where('id', '=', $centrodistribucion_id)->where('type', '=', 'centro')->first())
                    {
                        $ocParteList = null;
                        $forbidden = false;
    
                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {
    
                                // If user belongs to this Sucursal's country
                                if($user->stationable->country->id === $centrodistribucion->country->id)
                                {
                                    // Get only OcPartes on OCs generated from its country and dispatched from Sucursal (centro) to Sucursal
                                    $ocParteList = OcParte::select('oc_parte.*')
                                                ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('despachos.destinable_type', '=', get_class($sucursal))
                                                ->where('despachos.destinable_id', '=', $sucursal->id) // Dispatched to Sucursal
                                                ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                                ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
    
                            // Coordinador logistico at Sucursal
                            case 'colsol': {
    
                                // If user belongs to this Sucursal
                                if(
                                    (get_class($user->stationable) === get_class($sucursal)) &&
                                    ($user->stationable->id === $sucursal->id)
                                )
                                {
                                    // Get only OcPartes on OCs generated from its Sucursal and dispatched from Sucursal (centro) to Sucursal
                                    $ocParteList = OcParte::select('oc_parte.*')
                                                ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('despachos.destinable_type', '=', get_class($sucursal))
                                                ->where('despachos.destinable_id', '=', $sucursal->id) // Dispatched to Sucursal
                                                ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                                ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.id', '=', $user->stationable->id) // Same Sucursal as user station
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
                            $queueOcPartes = $ocParteList->reduce(function($carry, $ocParte) use ($sucursal, $centrodistribucion)
                                {
                                    $cantidadRecepcionado = $ocParte->getCantidadRecepcionado($sucursal);
                                    $cantidadDespachado = $ocParte->getCantidadDespachado($centrodistribucion);

                                    // Add to list only if has cantidad in transit
                                    if($cantidadRecepcionado < $cantidadDespachado)
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
                                            'monto',
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
                            'El centro de distribucion no existe',
                            null
                        );
                    }
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        412,
                        'La sucursal no existe',
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

    public function store_sucursal(Request $request, $sucursal_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales recepciones_store'))
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
                else if(($sucursal = Sucursal::where('type', '=', 'sucursal')->where('id', '=', $sucursal_id)->first()) === null)
                {
                    $response = HelpController::buildResponse(
                        412,
                        'La sucursal no existe',
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

                    $recepcion = new Recepcion();
                    // Set the morph source for Recepcion as Sucursal (centro)
                    $recepcion->sourceable_id = $centrodistribucion->id;
                    $recepcion->sourceable_type = get_class($centrodistribucion);
                    // Set the morph destination for Recepcion as Sucursal
                    $recepcion->recepcionable_id = $sucursal->id;
                    $recepcion->recepcionable_type = get_class($sucursal);
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

                                        // If user belongs to this Sucursal's country
                                        if($user->stationable->country->id === $sucursal->country->id)
                                        {
                                            $oc = Oc::select('ocs.*')
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('despachos.destinable_type', '=', get_class($sucursal))
                                                ->where('despachos.destinable_id', '=', $sucursal->id) // Dispatched to Sucursal
                                                ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                                ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->first();
                                        }
        
                                        break;
                                    }

                                    // Coordinador logistico at Sucursal
                                    case 'colsol': {
            
                                        // If user belongs to this Sucursal
                                        if(
                                            (get_class($user->stationable) === get_class($sucursal)) &&
                                            ($user->stationable->id === $sucursal->id)
                                        )
                                        {
                                            $oc = Oc::select('ocs.*')
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('despachos.destinable_type', '=', get_class($sucursal))
                                                ->where('despachos.destinable_id', '=', $sucursal->id) // Dispatched to Sucursal
                                                ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                                ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.id', '=', $user->stationable->id) // Same Sucursal as user station
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
                                            $cantidadRecepcionado = $p->pivot->getCantidadRecepcionado($sucursal);
                                            $cantidadDespachado = $p->pivot->getCantidadDespachado($centrodistribucion);

                                            if($cantidadRecepcionado < $cantidadDespachado)
                                            {
                                                if(($cantidadRecepcionado + $ocList[$oc->id][$parteId]) <= $cantidadDespachado)
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
                                                        'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor a la cantidad pendiente de recepcion en la OC: ' . $oc->id,
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
                                                    'La parte "' . $p->nparte . '" no tiene partes pendiente de recepcion en la OC: ' . $oc->id,
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
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a registrar recepciones para sucursal',
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

    public function show_sucursal($sucursal_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales recepciones_show'))
            {
                $validatorInput = ['recepcion_id' => $id];
            
                $validatorRules = [
                    'recepcion_id' => 'required|exists:recepciones,id,recepcionable_id,' . $sucursal_id . ',recepcionable_type,' . get_class(new Sucursal()),
                ];
        
                $validatorMessages = [
                    'recepcion_id.required' => 'Debes ingresar la recepcion',
                    'recepcion_id.exists' => 'La recepcion ingresada no existe para la sucursal',                    
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
                    if($sucursal = Sucursal::where('type', '=', 'sucursal')->where('id', '=', $sucursal_id)->first())
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
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                            ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Received at Sucursal
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                            ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Received at Sucursal
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
										    ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                            ->first();

                                break;
                            }

                            // Coordinador logistico at Sucursal (or Centro)
                            case 'colsol': {

                                // If user belongs to Sucursal
                                if($user->stationable->type === 'sucursal')
                                {
                                    // Only if Recepcion contains OcPartes from OCs generated from its same Sucursal
                                    $recepcion = Recepcion::select('recepciones.*')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                                ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('recepciones.id', '=', $id) // For this Recepcion
                                                ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                                ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Received at Sucursal
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->where('sucursalfaena.id', '=', $user->stationable->id) // Same Sucursal as user station
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
                            $recepcion->makeHidden([
                                'sourceable_id',
                                'sourceable_type',
                                'recepcionable_id',
                                'recepcionable_type',
                                'proveedor_id',
                                'partes_total',
                                'updated_at',
                            ]);

                            $recepcion->sourceable;
                            $recepcion->sourceable->makeHidden([
                                'type',
                                'rut',
                                'address',
                                'country_id',
                                'created_at', 
                                'updated_at'
                            ]);

                            $recepcion->recepcionable;
                            $recepcion->recepcionable->makeHidden([
                                'type',
                                'rut',
                                'address',
                                'city',
                                'country_id',
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
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $ocParte->pivot->makeHidden([
                                    'recepcion_id',
                                    'ocparte_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $ocParte->oc;
                                $ocParte->oc->makeHidden([
                                    'cotizacion_id',
                                    'proveedor_id',
                                    'filedata_id',
                                    'estadooc_id',
                                    'motivobaja_id',
                                    'usdvalue',
                                    'partes',
                                    'dias',
                                    'partes_total',
                                    'monto',
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

                                $ocParte->parte->makeHidden([
                                    'marca_id',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $ocParte->parte->marca;
                                $ocParte->parte->marca->makeHidden(['created_at', 'updated_at']);
                            }
                            
                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $recepcion
                            );                
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
                            'La sucursal no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar recepciones de sucursal',
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
     * selecting data and updating a Recepcion for Sucursal
     * 
     */
    public function update_prepare_sucursal($sucursal_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales recepciones_update'))
            {
                $validatorInput = ['recepcion_id' => $id];
            
                $validatorRules = [
                    'recepcion_id' => 'required|exists:recepciones,id,recepcionable_id,' . $sucursal_id . ',recepcionable_type,' . get_class(new Sucursal()),
                ];
        
                $validatorMessages = [
                    'recepcion_id.required' => 'Debes ingresar la recepcion',
                    'recepcion_id.exists' => 'La recepcion ingresado no existe para la sucursal',                    
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
                    if($sucursal = Sucursal::where('type', '=', 'sucursal')->where('id', '=', $sucursal_id)->first())
                    {
                        $ocParteList = null;
                        $recepcion = null;

                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {

                                // If user belongs to this Sucursal's country
                                if($user->stationable->country->id === $sucursal->country->id)
                                {
                                    // Only if Recepcion contains OcPartes from OCs generated from its same country
                                    $recepcion = Recepcion::select('recepciones.*')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                                ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                                ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                                ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('recepciones.id', '=', $id) // For this Recepcion
                                                ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                                ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Received at Sucursal
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
												->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->first();
    
                                    if($recepcion !== null)
                                    {
                                         // Get only OcPartes on OCs generated from its country and dispatched from Comprador to Sucursal
                                        $ocParteList = OcParte::select('oc_parte.*')
                                                    ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                    ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                    ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
												    ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
												    ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('despachos.destinable_type', '=', get_class($sucursal))
                                                    ->where('despachos.destinable_id', '=', $sucursal->id) // Dispatched to Sucursal
                                                    ->where('despachos.despachable_type', '=', get_class($recepcion->sourceable)) // Recepcion's source is Sucursal (centro)
                                                    ->where('despachos.despachable_id', '=', $recepcion->sourceable->id) // Dispatched by Sucursal (centro)
                                                    ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
												    ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                    ->groupBy('oc_parte.id')
                                                    ->get();
    
    
                                         // For OcPartes in current Recepcion
                                         $ocParteList = $recepcion->ocpartes->reduce(function($carry, $ocParte) use ($ocParteList)
                                            {
                                                $contains = $carry->contains(function($op) use ($ocParte)
                                                    {
                                                        return ($ocParte->id === $op->id);
                                                    }
                                                );
    
                                                // If OcParte from Recepcion isn't in queue
                                                if($contains === false)
                                                {
                                                    // Add OcParte to list
                                                    array_push($carry, $ocParte);
                                                }
    
                                                return $carry;
                                            },
                                            $ocParteList // Initialize with previous list as base
                                        );
                                    }
                                }

                                break;
                            }

                    
                            // Coordinador logistico at Sucursal (or Centro)
                            case 'colsol': {

                                // If user belongs to this Sucursal
                                if(
                                    (get_class($user->stationable) === get_class($sucursal)) &&
                                    ($user->stationable->id === $sucursal->id)
                                )
                                {
                                    // Only if Recepcion contains OcPartes from OCs generated from its same Sucursal
                                    $recepcion = Recepcion::select('recepciones.*')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                                ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
												->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
												->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('recepciones.id', '=', $id) // For this Recepcion
                                                ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                                ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Received at Sucursal
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
												->where('sucursalfaena.id', '=', $user->stationable->id) // Same Sucursal as user station
                                                ->first();

                                    if($recepcion !== null)
                                    {
                                        // Get only OcPartes on OCs generated from its country and dispatched from Comprador to Sucursal
                                        $ocParteList = OcParte::select('oc_parte.*')
                                                    ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                    ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                    ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
												    ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
												    ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('despachos.destinable_type', '=', get_class($sucursal))
                                                    ->where('despachos.destinable_id', '=', $sucursal->id) // Dispatched to Sucursal
                                                    ->where('despachos.despachable_type', '=', get_class($recepcion->sourceable)) // Recepcion's source is Sucursal (centro)
                                                    ->where('despachos.despachable_id', '=', $recepcion->sourceable->id) // Dispatched by Sucursal (centro)
                                                    ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
												    ->where('sucursalfaena.id', '=', $user->stationable->id) // Same Sucursal as user station
                                                    ->groupBy('oc_parte.id')
                                                    ->get();

                                         // For OcPartes in current Recepcion
                                        $ocParteList = $recepcion->ocpartes->reduce(function($carry, $ocParte) use ($ocParteList)
                                            {
                                                $contains = $carry->contains(function($op) use ($ocParte)
                                                    {
                                                        return ($ocParte->id === $op->id);
                                                    }
                                                );

                                                // If OcParte from Recepcion isn't in queue
                                                if($contains === false)
                                                {
                                                    // Add OcParte to list
                                                    array_push($carry, $ocParte);
                                                }

                                                return $carry;
                                            },
                                            $ocParteList // Initialize with previous list as base
                                        );

                                    }
                                }                                

                                break;
                            }
                            

                            default: {
                                break;
                            }
                        }

                        if(
                            ($ocParteList !== null) &&
                            ($recepcion !== null)
                        )
                        {   
                            $queueOcPartes = $ocParteList->reduce(function($carry, $ocParte) use ($recepcion, $sucursal)
                                {
                                    $cantidadRecepcionado = $ocParte->getCantidadRecepcionado($sucursal);
                                    $cantidadDespachado = $ocParte->getCantidadDespachado($recepcion->sourceable); // Recepcion's source is Sucursal (centro)

                                    // Try to find OcParte in Recepcion
                                    $op = $recepcion->ocpartes->find($ocParte->id);

                                    if(
                                        // If OcParte is in Recepcion
                                        ($op !== null) ||
                                        // Or if OcParte isn't in Recepcion and hasn't been full received yet
                                        (($op === null) && ($cantidadRecepcionado < $cantidadDespachado))
                                    )
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
                                            'monto',
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

                            $recepcion->makeHidden([
                                'sourceable_id', 
                                'sourceable_type',
                                'recepcionable_id', 
                                'recepcionable_type',
                                'partes_total',
                                'created_at', 
                                'updated_at'
                            ]);

                            $recepcion->sourceable;
                            $recepcion->sourceable->makeHidden([
                                'type',
                                'rut',
                                'address',
                                'contact',
                                'phone',
                                'country_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $recepcion->recepcionable;
                            $recepcion->recepcionable->makeHidden([
                                'type',
                                'rut',
                                'address',
                                'city',
                                'country_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $recepcion->ocpartes = $recepcion->ocpartes->map(function($ocParte) use ($recepcion)
                                {
                                    // Set minimum cantidad as (cantidad in Despachos + cantidad in Entregas) - (cantidad in Recepciones - cantidad in Recepcion) at recepcionable Sucursal (centro)
                                    $ocParte->cantidad_min = ($ocParte->getCantidadDespachado($recepcion->recepcionable) + $ocParte->getCantidadEntregado($recepcion->recepcionable)) - ($ocParte->getCantidadRecepcionado($recepcion->recepcionable) - $ocParte->pivot->cantidad);

                                    $ocParte->makeHidden([
                                        'oc_id',
                                        'parte_id',
                                        'estadoocparte_id',
                                        'tiempoentrega',
                                        'created_at',
                                        'updated_at'
                                    ]);

                                    $ocParte->pivot;
                                    $ocParte->pivot->makeHidden([
                                        'recepcion_id',
                                        'ocparte_id',
                                        'created_at',
                                        'updated_at'
                                    ]);

                                    $ocParte->parte;
                                    $ocParte->parte->makeHidden([
                                        'marca_id',
                                        'created_at',
                                        'updated_at',
                                    ]);

                                    $ocParte->parte->marca;
                                    $ocParte->parte->marca->makeHidden([
                                        'created_at',
                                        'updated_at',
                                    ]);

                                    $ocParte->oc;
                                    $ocParte->oc->makeHidden([
                                        'cotizacion_id',
                                        'proveedor_id',
                                        'filedata_id',
                                        'estadooc_id',
                                        'motivobaja_id',
                                        'usdvalue',
                                        'partes',
                                        'dias',
                                        'partes_total',
                                        'monto',
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

                                    return $ocParte;
                                }
                            );

                            $data = [
                                "queue_ocpartes" => $queueOcPartes,
                                "recepcion" => $recepcion
                            ];

                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $data
                            );
              
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
                            'La sucursal no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar recepciones de sucursal',
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

    public function update_sucursal(Request $request, $sucursal_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales recepciones_update'))
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
                    'ocs.min' => 'El recepcion debe contener al menos 1 parte recepcionada',
                    'ocs.*.id.required' => 'Debes seleccionar la OC a recepcionar',
                    'ocs.*.id.exists' => 'La OC ingresada no existe',
                    'ocs.*.partes.required' => 'Debes seleccionar las partes recepcionadas',
                    'ocs.*.partes.array' => 'Lista de partes recepcionadas invalida',
                    'ocs.*.partes.min' => 'El recepcion debe contener al menos 1 parte recepcionada',
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
                else if(($sucursal = Sucursal::where('type', '=', 'sucursal')->where('id', '=', $sucursal_id)->first()) === null)
                {
                    $response = HelpController::buildResponse(
                        412,
                        'La sucursal no existe',
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
                            
                            // If user belongs to this Sucursal's country
                            if($user->stationable->country->id === $sucursal->country->id)
                            {
                                // Only if Recepcion contains OcPartes from OCs generated from its same country
                                $recepcion = Recepcion::select('recepciones.*')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                            ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Received at Sucursal
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->first();
                            }

                            break;
                        }

                        // Coordinador logistico at Sucursal (or Centro)
                        case 'colsol': {

                            // If user belongs to this Sucursal
                            if(
                                (get_class($user->stationable) === get_class($sucursal)) &&
                                ($user->stationable->id === $sucursal->id)
                            )
                            {
                                // Only if Recepcion contains OcPartes from OCs generated from its same country
                                $recepcion = Recepcion::select('recepciones.*')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                            ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Received at Sucursal
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.id', '=', $user->stationable->id) // Same Sucursal as user station
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

                        DB::beginTransaction();

                        // Fill the data
                        $recepcion->fill($request->all());

                        if($recepcion->save())
                        {
                            $success = true;

                            $syncData = [];
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

                                            // If user belongs to this Sucursal's country
                                            if($user->stationable->country->id === $sucursal->country->id)
                                            {
                                                // Only if Oc was generated from its same country
                                                $oc = Oc::select('ocs.*')
                                                    ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                    ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=' , 'oc_parte.id')
                                                    ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
												    ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
												    ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('ocs.id', '=', $ocId)
                                                    ->where('despachos.despachable_type', '=', get_class($recepcion->sourceable)) // Recepcion's source is Sucursal (centro)
                                                    ->where('despachos.despachable_id', '=', $recepcion->sourceable->id) // OcParte dispatched from Sucursal (centro)
                                                    ->where('despachos.destinable_type', '=', get_class($sucursal))
                                                    ->where('despachos.destinable_id', '=', $sucursal->id) // OcParte dispatched to Sucursal
                                                    ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
												    ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                    ->first();
                                            }
            
                                            break;
                                        }
            
                                        // Coordinador logistico at Sucursal (or Centro)
                                        case 'colsol': {

                                            // If user belongs to this Sucursal
                                            if(
                                                (get_class($user->stationable) === get_class($sucursal)) &&
                                                ($user->stationable->id === $sucursal->id)
                                            )
                                            {
                                                // Only if Oc was generated from its same country
                                                $oc = Oc::select('ocs.*')
                                                    ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                    ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=' , 'oc_parte.id')
                                                    ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
												    ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
												    ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('ocs.id', '=', $ocId)
                                                    ->where('despachos.despachable_type', '=', get_class($recepcion->sourceable)) // Recepcion's source is Sucursal (centro)
                                                    ->where('despachos.despachable_id', '=', $recepcion->sourceable->id) // OcParte dispatched from Sucursal (centro)
                                                    ->where('despachos.destinable_type', '=', get_class($sucursal))
                                                    ->where('despachos.destinable_id', '=', $sucursal->id) // OcParte dispatched to Sucursal
                                                    ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
												    ->where('sucursalfaena.id', '=', $user->stationable->id) // Same Sucursal as user station
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
                                                $newCantidad = $p->pivot->getCantidadRecepcionado($sucursal) + $diffList[$oc->id][$parteId];

                                                /*
                                                 *  If new cantidad in Recepciones is equal or more than
                                                 *  cantidad in Entregas from destination Sucursal
                                                 */

                                                if($newCantidad >= $p->pivot->getCantidadEntregado($sucursal))
                                                {
                                                    // If new cantidad in Recepciones is equal or less than cantidad in Despachos to Sucursal
                                                    if($newCantidad <= $p->pivot->getCantidadDespachado($recepcion->sourceable))
                                                    {
                                                        // If OC is in the request
                                                        if(in_array($oc->id, array_keys($ocList)) === true)
                                                        {
                                                            // If Parte is in the request for the OC
                                                            if(in_array($parteId, array_keys($ocList[$oc->id])) === true)
                                                            {
                                                                // Add the OcParte to sync using the ID which is unique
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
                                                            'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor a la cantidad pendiente de recepcion en la OC: ' . $oc->id,
                                                            null
                                                        );
                    
                                                        $success = false;
                    
                                                        break;
                                                    }
                                                }
                                                else
                                                {
                                                    // If the received partes are less than dispathed and delivered at destination Sucursal (centro)
                                                    $response = HelpController::buildResponse(
                                                        409,
                                                        'La cantidad ingresada para la parte "' . $p->nparte . '" es menor a la cantidad ya entregada en la sucursal para la OC: ' . $oc->id,
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
                    'No tienes acceso a actualizar recepciones para sucursal',
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

    public function destroy_sucursal($sucursal_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales recepciones_destroy'))
            {
                if($sucursal = Sucursal::where('type', '=', 'sucursal')->where('id', '=', $sucursal_id)->first())
                {
                    $recepcion = null;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {
                            
                            // If user belongs to this Sucursal's country
                            if($user->stationable->country->id === $sucursal->country->id)
                            {
                                // Only if Recepcion contains OcPartes from OCs generated from its same country
                                $recepcion = Recepcion::select('recepciones.*')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                            ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Received at Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->first();
                            }

                            break;
                        }

                        // Coordinador logistico at Sucursal (or Centro)
                        case 'colsol': {

                            // If user belongs to this Sucursal
                            if(
                                (get_class($user->stationable) === get_class($sucursal)) &&
                                ($user->stationable->id === $sucursal->id)
                            )
                            {
                                // Only if Recepcion contains OcPartes from OCs generated from its same Sucursal
                                $recepcion = Recepcion::select('recepciones.*')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.recepcion_id', '=', 'recepciones.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'recepcion_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('recepciones.id', '=', $id) // For this Recepcion
                                            ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                            ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Received at Sucursal
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.id', '=', $user->stationable->id) // Same Sucursal as user station
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

                        DB::beginTransaction();

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

                                        // If user belongs to this Sucursal's (centro) country
                                        if($user->stationable->country->id === $centrodistribucion->country->id)
                                        {
                                            // Only if Oc was generated from its same country
                                            $oc = Oc::select('ocs.*')
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=' , 'oc_parte.id')
                                                ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
												->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
												->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('ocs.id', '=', $ocId)
                                                ->where('despachos.despachable_type', '=', get_class($recepcion->sourceable)) // Recepcion's source is Sucursal (centro)
                                                ->where('despachos.despachable_id', '=', $recepcion->sourceable->id) // OcParte dispatched from Sucursal (centro)
                                                ->where('despachos.destinable_type', '=', get_class($sucursal))
                                                ->where('despachos.destinable_id', '=', $sucursal->id) // OcParte dispatched to Sucursal
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
												->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->first();
                                        }
        
                                        break;
                                    }
        
                                    // Coordinador logistico at Sucursal (or Centro)
                                    case 'colsol': {

                                        // If user belongs to this Sucursal
                                        if(
                                            (get_class($user->stationable) === get_class($sucursal)) &&
                                            ($user->stationable->id === $sucursal->id)
                                        )
                                        {
                                            // Only if Oc was generated from its same Sucursal
                                            $oc = Oc::select('ocs.*')
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('despacho_ocparte', 'despacho_ocparte.ocparte_id', '=' , 'oc_parte.id')
                                                ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
												->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
												->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('ocs.id', '=', $ocId)
                                                ->where('despachos.despachable_type', '=', get_class($recepcion->sourceable)) // Recepcion's source is Sucursal (centro)
                                                ->where('despachos.despachable_id', '=', $recepcion->sourceable->id) // OcParte dispatched from Sucursal (centro)
                                                ->where('despachos.destinable_type', '=', get_class($sucursal))
                                                ->where('despachos.destinable_id', '=', $sucursal->id) // OcParte dispatched to Sucursal
                                                ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
												->where('sucursalfaena.id', '=', $user->stationable->id) // Same Sucursal as user station
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
                                            $newCantidad = $p->pivot->getCantidadRecepcionado($sucursal) + $ocList[$oc->id][$parteId];

                                            /*
                                            *  If new cantidad in Recepciones is less than
                                            *  cantidad in Entregas at Sucursal
                                            */

                                            if($newCantidad < $p->pivot->getCantidadEntregado($sucursal))
                                            {
                                                $response = HelpController::buildResponse(
                                                    409,
                                                    'La parte "' . $p->nparte . '" ya tiene partes entregadas en la OC: ' . $oc->id,
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
                            DB::commit();

                            $response = HelpController::buildResponse(
                                200,
                                'Recepcion eliminada',
                                null
                            );
                        }
                        else
                        {
                            DB::rollback();

                            // Error message was already given
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
                        'La sucursal no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a eliminar recepciones para sucursal',
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
