<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Sucursal;
use App\Models\Oc;
use App\Models\OcParte;
use App\Models\Faena;
use App\Models\Entrega;
use App\Models\OcParteEntrega;

class EntregasController extends Controller
{
    /*
     *  Sucursal (centro)
     */
    
    public function index_centrodistribucion($id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales entregas_index'))
            {
                if($centrodistribucion = Sucursal::where('id', $id)->where('type', 'centro')->first())
                {
                    $entregas = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // If user belongs to the Sucursal's (centro) country
                            if($user->stationable->country->id === $centrodistribucion->country->id)
                            {
                                // Get only Entregas containing OcPartes from OCs generated from its same country
                                $entregas = Entrega::select('entregas.*')
                                            ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('entregas.sucursal_id', '=', $centrodistribucion->id) // Delivered by Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->get();
                                          
                            }
                            else
                            {
                                // Set as forbidden
                                $forbidden = true;
                            }

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // If user belongs to this Sucursal (centro)
                            if(
                                (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                ($user->stationable->id === $centrodistribucion->id)
                            )
                            {
                                // Get only Entregas containing OcPartes from OCs generated from its same country
                                $entregas = Entrega::select('entregas.*')
                                            ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('entregas.sucursal_id', '=', $centrodistribucion->id) // Delivered by Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
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

                    if($entregas !== null)
                    {
                        $entregas = $entregas->map(function($entrega)
                            {
                                $entrega->partes_total;
                        
                                $entrega->makeHidden([
                                    'sucursal_id',
                                    'oc_id',
                                    'ocpartes',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $entrega->oc->makeHidden([
                                    'cotizacion_id',
                                    'proveedor_id',
                                    'filedata_id',
                                    'motivobaja_id',
                                    'usdvalue',
                                    'partes',
                                    'partes_total',
                                    'monto',
                                    'estadooc_id', 
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $entrega->oc->cotizacion;
                                $entrega->oc->cotizacion->makeHidden([
                                    'solicitud_id',
                                    'motivorechazo_id',
                                    'estadocotizacion_id',
                                    'usdvalue',
                                    'partes_total',
                                    'dias',
                                    'monto',
                                    'created_at', 
                                    'updated_at',
                                    'partes',
                                ]);
                                
                                $entrega->oc->cotizacion->solicitud;
                                $entrega->oc->cotizacion->solicitud->makeHidden([
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
                                            
                                $entrega->oc->cotizacion->solicitud->sucursal;
                                $entrega->oc->cotizacion->solicitud->sucursal->makeHidden([
                                    'type',
                                    'rut',
                                    'address',
                                    'city',
                                    'country_id',
                                    'created_at', 
                                    'updated_at'
                                ]);
                                
                                $entrega->oc->cotizacion->solicitud->faena;
                                $entrega->oc->cotizacion->solicitud->faena->makeHidden([
                                    'rut',
                                    'address',
                                    'city',
                                    'contact',
                                    'phone',
                                    'sucursal_id',
                                    'cliente_id', 
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $entrega->oc->cotizacion->solicitud->faena->cliente;
                                $entrega->oc->cotizacion->solicitud->faena->cliente->makeHidden([
                                    'country_id',
                                    'created_at', 
                                    'updated_at'
                                ]);
                                
                                $entrega->oc->cotizacion->solicitud->marca;
                                $entrega->oc->cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);
                                
                                $entrega->oc->cotizacion->solicitud->user;
                                $entrega->oc->cotizacion->solicitud->user->makeHidden([
                                    'stationable_type',
                                    'stationable_id',
                                    'email', 
                                    'phone', 
                                    'country_id', 
                                    'role_id', 
                                    'email_verified_at', 
                                    'created_at', 
                                    'updated_at'
                                ]);
                                
                                $entrega->oc->estadooc;
                                $entrega->oc->estadooc->makeHidden(['created_at', 'updated_at']);

                                return $entrega;
                            }
                        );

                        $response = HelpController::buildResponse(
                            200,
                            null,
                            $entregas
                        );
                    }
                    else if($forbidden === true)
                    {
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a visualizar las entregas',
                            null
                        );
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            500,
                            'Error al obtener la lista de entregas',
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
                    'No tienes acceso a visualizar entregas de centro de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener las entregas del centro de distribucion [!]',
                null
            );
        }
            
        return $response;
    }

    public function queueOcs_centrodistribucion($centrodistribucion_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales entregas_store'))
            {
                if($centrodistribucion = Sucursal::where('id', $centrodistribucion_id)->where('type', 'centro')->first())
                {
                    $ocParteList = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // If user belongs to the Sucursal's (centro) country
                            if($user->stationable->country->id === $centrodistribucion->country->id)
                            {
                                // Get all the OcPartes in Recepciones at Sucursal (centro) for delivering at Sucursal (centro)
                                $ocParteList = OcParte::select('oc_parte.*')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                            ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                            ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Recepciones received at Sucursal (centro)
                                            ->where('sucursalfaena.id', '=', $centrodistribucion->id) // Faena with delivery at Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->get();
                                            
                            }
                            else
                            {
                                // Set as forbidden
                                $forbidden = true;
                            }

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // If user belongs to this Sucursal (centro)
                            if(
                                (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                ($user->stationable->id === $centrodistribucion->id)
                            )
                            {
                                // Get all the OcPartes in Recepciones at Sucursal (centro) for delivering at Sucursal (centro)
                                $ocParteList = OcParte::select('oc_parte.*')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                            ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                            ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Recepciones received at Sucursal (centro)
                                            ->where('sucursalfaena.id', '=', $centrodistribucion->id) // Faena with delivery at Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
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
                        // Retrieves the Oc with pending OcParte for Entrega
                        $ocList = $ocParteList->reduce(function($carry, $ocParte) use ($centrodistribucion)
                            {
                                // If the Oc (id) isn't in the list already, check it
                                if(in_array($ocParte->oc->id, array_keys($carry)) === false)
                                {
                                    // Get delivered cantidad for OcParte at Sucursal (centro)
                                    $cantidadEntregado = $ocParte->getCantidadEntregado($centrodistribucion);

                                    // If has delivered less cantidad than total, add the Oc to the list
                                    if($cantidadEntregado < $ocParte->cantidad)
                                    {
                                        $ocParte->cantidad_entregado = $cantidadEntregado;

                                        $ocParte->oc->makeHidden([
                                            'cotizacion_id',
                                            'proveedor_id',
                                            'filedata_id',
                                            'motivobaja_id',
                                            'partes',
                                            'estadooc_id', 
                                            'created_at', 
                                            //'updated_at'
                                        ]);

                                        $ocParte->oc->cotizacion;
                                        $ocParte->oc->cotizacion->makeHidden([
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
                                        
                                        $ocParte->oc->cotizacion->solicitud;
                                        $ocParte->oc->cotizacion->solicitud->makeHidden([
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
                                            'rut',
                                            'address',
                                            'city',
                                            'contact',
                                            'phone',
                                            'cliente_id', 
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
                                        $ocParte->oc->cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);
                                        
                                        $ocParte->oc->cotizacion->solicitud->user;
                                        $ocParte->oc->cotizacion->solicitud->user->makeHidden([
                                            'stationable_type',
                                            'stationable_id',
                                            'email', 
                                            'phone', 
                                            'country_id', 
                                            'role_id', 
                                            'email_verified_at', 
                                            'created_at', 
                                            'updated_at'
                                        ]);
                                        
                                        $ocParte->oc->estadooc;
                                        $ocParte->oc->estadooc->makeHidden(['created_at', 'updated_at']);

                                        $carry[$ocParte->oc->id] = $ocParte->oc;
                                    }
                                }
                                
                                return $carry;
                            },
                            array()
                        );

                        $ocs = array();
                        foreach(array_keys($ocList) as $ocId)
                        {
                            array_push($ocs, $ocList[$ocId]);
                        }

                        $response = HelpController::buildResponse(
                            200,
                            null,
                            $ocs
                        );

                    }
                    else if($forbidden === true)
                    {
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a visualizar las OCs pendiente de entrega',
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
                    405,
                    'No tienes acceso a visualizar partes disponibles para entregar',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener partes disponibles para entregar [!]',
                null
            );
        }
            
        return $response;
    }

    public function store_prepare_centrodistribucion($centrodistribucion_id, $oc_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales entregas_store'))
            {
                if($centrodistribucion = Sucursal::where('id', $centrodistribucion_id)->where('type', 'centro')->first())
                {
                    $oc = null;
        
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // If user belongs to the Sucursal's (centro) country
                            if($user->stationable->country->id === $centrodistribucion->country->id)
                            {
                                // Get the Oc if has OcParte in Recepciones at Sucursal (centro) for delivering at Sucursal (centro)
                                $oc = Oc::select('ocs.*')
                                    ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                    ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                    ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                    ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                    ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                    ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                    ->where('ocs.id', '=', $oc_id)
                                    ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                    ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Recepciones received at Sucursal (centro)
                                    ->where('sucursalfaena.id', '=', $centrodistribucion->id) // Faena with delivery at Sucursal (centro)
                                    ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                    ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                    ->first();
                                            
                            }
                            else
                            {
                                // Set as forbidden
                                $forbidden = true;
                            }

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // If user belongs to this Sucursal (centro)
                            if(
                                (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                ($user->stationable->id === $centrodistribucion->id)
                            )
                            {
                                // Get the Oc if has OcParte in Recepciones at Sucursal (centro) for delivering at Sucursal (centro)
                                $oc = Oc::select('ocs.*')
                                    ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                    ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                    ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                    ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                    ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                    ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                    ->where('ocs.id', '=', $oc_id)
                                    ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                    ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Recepciones received at Sucursal (centro)
                                    ->where('sucursalfaena.id', '=', $centrodistribucion->id) // Faena with delivery at Sucursal (centro)
                                    ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                    ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                    ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                    ->first();
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

                    if($oc !== null)
                    {
                        $oc->makeHidden([
                            'cotizacion_id',
                            'proveedor_id',
                            'filedata_id',
                            'motivobaja_id',
                            'partes',
                            'partes_total',
                            'usdvalue',
                            'monto',
                            'estadooc_id', 
                            'created_at', 
                            //'updated_at'
                        ]);

                        $oc->cotizacion;
                        $oc->cotizacion->makeHidden([
                            'solicitud_id',
                            'motivorechazo_id',
                            'estadocotizacion_id',
                            'usdvalue',
                            'partes_total',
                            'dias',
                            'monto',
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
                            'rut',
                            'address',
                            'city',
                            'contact',
                            'phone',
                            'cliente_id',
                            'sucursal_id',
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
                            'stationable_type',
                            'stationable_id',
                            'email', 
                            'phone', 
                            'country_id', 
                            'role_id', 
                            'email_verified_at', 
                            'created_at', 
                            'updated_at'
                        ]);

                        $oc->estadooc;
                        $oc->estadooc->makeHidden(['created_at', 'updated_at']);

                        $queuePartes = $oc->partes->reduce(function($carry, $parte) use($centrodistribucion)
                            {
                                $parte->makeHidden([
                                    'marca_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $parte->marca;
                                $parte->marca->makeHidden([
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $parte->pivot->makeHidden([
                                    'oc_id',
                                    'parte_id',
                                    'estadoocparte_id',
                                    'created_at',
                                    'updated_at'
                                ]);

                                $parte->pivot->estadoocparte;
                                $parte->pivot->estadoocparte->makeHidden([
                                    'created_at', 
                                    'updated_at'
                                ]);

                                // Get the stock cantidad using cantidad in Recepciones - cantidad in Entregas - cantidad in Despachos at Sucursal (centro)
                                $cantidadStock = $parte->pivot->getCantidadRecepcionado($centrodistribucion) - $parte->pivot->getCantidadEntregado($centrodistribucion) - $parte->pivot->getCantidadDespachado($centrodistribucion);

                                // Add it to the queue only if there's stock in Sucursal (centro)
                                if($cantidadStock > 0)
                                {
                                    // Set cantidad in Entregas and stock for OcParte
                                    $parte->pivot->cantidad_entregado = $parte->pivot->getCantidadEntregado($centrodistribucion);
                                    $parte->pivot->cantidad_stock = $cantidadStock;

                                    // Add parte to queue
                                    array_push($carry, $parte);
                                }

                                return $carry;      
                            },
                            array()
                        );

                        $data = [
                            "oc" => $oc,
                            "queue_partes" => $queuePartes 
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
                            'No tienes acceso a registrar entregas para la OC',
                            null
                        );
                    }
                    else
                    {
                        if(Oc::find($oc_id))
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'La OC no tiene partes disponibles para entrega',
                                null
                            );
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
                    'No tienes acceso a visualizar partes disponibles para entregar',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener partes disponibles para entregar [!]',
                null
            );
        }
            
        return $response;
    }

    public function store_centrodistribucion(Request $request, $centrodistribucion_id, $oc_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales entregas_store'))
            {
                $validatorInput = $request->only('fecha', 'ndocumento', 'responsable', 'comentario', 'partes');
            
                $validatorRules = [
                    'fecha' => 'required|date_format:Y-m-d|before:tomorrow', // it includes today
                    'ndocumento' => 'nullable|min:1',
                    'responsable' => 'required|min:1',
                    'comentario' => 'sometimes|nullable',
                    'partes' => 'required|array|min:1',
                    'partes.*.id'  => 'required|exists:partes,id',
                    'partes.*.cantidad'  => 'required|numeric|min:1',
                ];
        
                $validatorMessages = [
                    'fecha.required' => 'Debes ingresar la fecha de despacho',
                    'fecha.date_format' => 'El formato de fecha de despacho es invalido',
                    'fecha.before' => 'La fecha debe ser igual o anterior a hoy',
                    'ndocumento.min' => 'El numero de documento debe tener al menos un digito',
                    'responsable.required' => 'Debes ingresar el nombre de la persona que despacha',
                    'responsable.min' => 'El nombre de la persona que despacha debe tener al menos un digito',
                    'partes.required' => 'Debes seleccionar las partes despachadas',
                    'partes.array' => 'Lista de partes despachadas invalida',
                    'partes.min' => 'El despacho debe contener al menos 1 parte despachada',
                    'partes.*.id.required' => 'La lista de partes despachadas es invalida',
                    'partes.*.id.exists' => 'La parte despachada ingresada no existe',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte despachada',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte despachada debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte despachada debe ser mayor a 0',
                    
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
                    if($centrodistribucion = Sucursal::where('id', $centrodistribucion_id)->where('type', 'centro')->first())
                    {
                        $oc = null;
        
                        $forbidden = false;

                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {

                                // If user belongs to the Sucursal's (centro) country
                                if($user->stationable->country->id === $centrodistribucion->country->id)
                                {
                                    // Get the Oc if has OcParte in Recepciones at Sucursal (centro) for delivering at Sucursal (centro)
                                    $oc = Oc::select('ocs.*')
                                        ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                        ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                        ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                        ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                        ->where('ocs.id', '=', $oc_id)
                                        ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                        ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Recepciones received at Sucursal (centro)
                                        ->where('sucursalfaena.id', '=', $centrodistribucion->id) // Faena with delivery at Sucursal (centro)
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->first();
                                                
                                }
                                else
                                {
                                    // Set as forbidden
                                    $forbidden = true;
                                }

                                break;
                            }

                            // Vendedor
                            case 'seller': {

                                // If user belongs to this Sucursal (centro)
                                if(
                                    (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                    ($user->stationable->id === $centrodistribucion->id)
                                )
                                {
                                    // Get the Oc if has OcParte in Recepciones at Sucursal (centro) for delivering at Sucursal (centro)
                                    $oc = Oc::select('ocs.*')
                                        ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                        ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                        ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                        ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                        ->where('ocs.id', '=', $oc_id)
                                        ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                        ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Recepciones received at Sucursal (centro)
                                        ->where('sucursalfaena.id', '=', $centrodistribucion->id) // Faena with delivery at Sucursal (centro)
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                        ->first();
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

                        if($oc !== null)
                        {
                            DB::beginTransaction();

                            $entrega = new Entrega();
                            // Fill the data
                            $entrega->sucursal_id = $centrodistribucion->id;
                            $entrega->oc_id = $oc->id;
                            $entrega->fecha = $request->fecha;
                            $entrega->ndocumento = $request->ndocumento;
                            $entrega->responsable = $request->responsable;
                            $entrega->comentario = $request->comentario;

                            if($entrega->save())
                            {
                                $success = true;

                                $ocpartes = array();
                                foreach($request->partes as $p)
                                {
                                    if($parte = $oc->partes->find($p['id']))
                                    {
                                        // Calc cantidad pendiente with cantidad in Oc - cantidad in Entregas at Sucursal (centro)
                                        $cantidadPendiente = $parte->pivot->cantidad - $parte->pivot->getCantidadEntregado($centrodistribucion);
                                        if($cantidadPendiente > 0)
                                        {
                                            if($p['cantidad'] <= $cantidadPendiente)
                                            {
                                                // Get cantidad in Entregas for OcParte
                                                $cantidadEntregado = $parte->pivot->getCantidadEntregado($centrodistribucion);

                                                // Calc OcParte cantidad stock with cantidad in Recepciones - cantidad in Entregas - cantidad in Despachos
                                                $cantidadStock = $parte->pivot->getCantidadRecepcionado($centrodistribucion) - $cantidadEntregado - $parte->pivot->getCantidadDespachado($centrodistribucion);
                                                if($cantidadStock > 0)
                                                {
                                                    if($p['cantidad'] <= $cantidadStock)
                                                    {
                                                        // If cantidad its equal to pending for fully deliver OcParte in Oc
                                                        if($p['cantidad'] === $parte->pivot->cantidad - $cantidadEntregado)
                                                        {
                                                            // All partes were delivered at Sucursal (centro)
                                                            $parte->pivot->estadoocparte_id = 3; // Estadoocparte = 'Entregado'

                                                            // If fail on saving the new status for OcParte
                                                            if(!($parte->pivot->save()))
                                                            {
                                                                $response = HelpController::buildResponse(
                                                                    500,
                                                                    'Error al cambiar el estado de la parte "' . $parte->nparte . '"',
                                                                    null
                                                                );
                            
                                                                $success = false;
                            
                                                                break;
                                                            }
                                                        }

                                                        // Add the OcParte to the Entrega
                                                        $entrega->ocpartes()->attach(
                                                            array(
                                                                $parte->pivot->id => array(
                                                                    "cantidad" => $p['cantidad']
                                                                )
                                                            )
                                                        );
                                                    }
                                                    else
                                                    {
                                                        // If the entered cantidad for parte is more than in stock
                                                        $response = HelpController::buildResponse(
                                                            409,
                                                            'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor al stock disponible en el centro de distribucion',
                                                            null
                                                        );

                                                        $success = false;
                
                                                        break;
                                                    }
                                                }
                                                else
                                                {
                                                    // If the entered parte has no stock in Sucursal (centro)
                                                    $response = HelpController::buildResponse(
                                                        409,
                                                        'La parte "' . $parte->nparte . '" no tiene stock disponible en el centro de distribucion',
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
                                                    'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor a la cantidad de pendiente de entrega',
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
                                                'La parte "' . $parte->nparte . '" no tiene partes pendiente de entrega en la OC',
                                                null
                                            );

                                            $success = false;
    
                                            break;
                                        }
                                    }
                                    else
                                    {
                                        // If the entered parte isn't in the Oc
                                        $response = HelpController::buildResponse(
                                            409,
                                            'Una de las partes ingresadas no existe en la OC',
                                            null
                                        );
    
                                        $success = false;
    
                                        break;
                                    }
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
                                            'Error al actualizar el estado de la Oc',
                                            null
                                        );
    
                                        $success = false;
                                    }
                                }

                                if($success === true)
                                {

                                    DB::commit();
                                        
                                    $response = HelpController::buildResponse(
                                        201,
                                        'Entrega creada',
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
                                    'Error al crear la entrega',
                                    null
                                );
                            }
                        }
                        else if($forbidden === true)
                        {
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a registrar entregas para la OC',
                                null
                            );
                        }
                        else
                        {
                            if(Oc::find($oc_id))
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'La OC no tiene partes disponibles para entrega',
                                    null
                                );
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
                    'No tienes acceso a registrar entregas para centro de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al crear la entrega [!]',
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
            if($user->role->hasRoutepermission('sucursales entregas_show'))
            {
                if($centrodistribucion = Sucursal::where('id', $centrodistribucion_id)->where('type', 'centro')->first())
                {
                    $entrega = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // If user belongs to the Sucursal's (centro) country
                            if($user->stationable->country->id === $centrodistribucion->country->id)
                            {
                                // Only if Entrega contains OcPartes from OCs generated from its same country
                                $entrega = Entrega::select('entregas.*')
                                        ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('entregas.id', '=', $id) // For this Entrega
                                        ->where('entregas.sucursal_id', '=', $centrodistribucion->id) // Delivered by Sucursal (centro)
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->first();
                                            
                            }
                            else
                            {
                                // Set as forbidden
                                $forbidden = true;
                            }

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // If user belongs to this Sucursal (centro)
                            if(
                                (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                ($user->stationable->id === $centrodistribucion->id)
                            )
                            {
                                // Only if Entrega contains OcPartes from OCs generated from its same country
                                $entrega = Entrega::select('entregas.*')
                                        ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('entregas.id', '=', $id) // For this Entrega
                                        ->where('entregas.sucursal_id', '=', $centrodistribucion->id) // Delivered by Sucursal (centro)
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                        ->first();
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

                    if($entrega !== null)
                    {
                        $entrega->makeHidden([
                            'sucursal_id',
                            'oc_id',
                            'partes_total',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->oc->makeHidden([
                            'cotizacion_id',
                            'proveedor_id',
                            'filedata_id',
                            'motivobaja_id',
                            'usdvalue',
                            'partes',
                            'partes_total',
                            'monto',
                            'estadooc_id', 
                            'created_at', 
                            'updated_at'
                        ]);

                        $entrega->oc->cotizacion;
                        $entrega->oc->cotizacion->makeHidden([
                            'solicitud_id',
                            'motivorechazo_id',
                            'estadocotizacion_id',
                            'usdvalue',
                            'partes_total',
                            'dias',
                            'monto',
                            'created_at', 
                            'updated_at',
                            'partes',
                        ]);
                        
                        $entrega->oc->cotizacion->solicitud;
                        $entrega->oc->cotizacion->solicitud->makeHidden([
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
                                    
                        $entrega->oc->cotizacion->solicitud->sucursal;
                        $entrega->oc->cotizacion->solicitud->sucursal->makeHidden([
                            'type',
                            'rut',
                            'address',
                            'city',
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->oc->cotizacion->solicitud->faena;
                        $entrega->oc->cotizacion->solicitud->faena->makeHidden([
                            'rut',
                            'address',
                            'city',
                            'contact',
                            'phone',
                            'sucursal_id',
                            'cliente_id', 
                            'created_at', 
                            'updated_at'
                        ]);

                        $entrega->oc->cotizacion->solicitud->faena->cliente;
                        $entrega->oc->cotizacion->solicitud->faena->cliente->makeHidden([
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->oc->cotizacion->solicitud->marca;
                        $entrega->oc->cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);
                        
                        $entrega->oc->cotizacion->solicitud->user;
                        $entrega->oc->cotizacion->solicitud->user->makeHidden([
                            'stationable_type',
                            'stationable_id',
                            'email', 
                            'phone', 
                            'country_id', 
                            'role_id', 
                            'email_verified_at', 
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->oc->estadooc;
                        $entrega->oc->estadooc->makeHidden(['created_at', 'updated_at']);

                        $entrega->ocpartes;
                        $entrega->ocpartes = $entrega->ocpartes->filter(function($ocparte)
                        {
                            $ocparte->cantidad_entregado = $ocparte->getCantidadTotalEntregado();

                            $ocparte->makeHidden([
                                'oc_id',
                                'parte_id',
                                'estadoocparte_id',
                                'tiempoentrega',
                                'created_at',
                                'updated_at'
                            ]);

                            $ocparte->pivot->makeHidden([
                                'entrega_id',
                                'ocparte_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $ocparte->parte;
                            $ocparte->parte->makeHidden([
                                'marca_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $ocparte->parte->marca;
                            $ocparte->parte->marca->makeHidden(['created_at', 'updated_at']);

                            $ocparte->estadoocparte;
                            $ocparte->estadoocparte->makeHidden([
                                'created_at',
                                'updated_at'
                            ]);

                            return $ocparte;
                        });

                        $response = HelpController::buildResponse(
                            200,
                            null,
                            $entrega
                        );
                    }
                    else if($forbidden === true)
                    {
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a visualizar la entrega',
                            null
                        );
                    }
                    else
                    {
                        if(Entrega::find($id))
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La entrega no existe en el centro de distribucion',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La entrega no existe',
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
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar entregas de centro de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la entrega [!]',
                null
            );
        }
            
        return $response;
    }

    /**
     * It retrieves all the required info for
     * selecting data and updating an Entrega for Sucursal (centro)
     * 
     */
    public function update_prepare_centrodistribucion($centrodistribucion_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales entregas_update'))
            {
                if($centrodistribucion = Sucursal::where('id', $centrodistribucion_id)->where('type', 'centro')->first())
                {
                    $entrega = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // If user belongs to the Sucursal's (centro) country
                            if($user->stationable->country->id === $centrodistribucion->country->id)
                            {
                                // Only if Entrega contains OcPartes from OCs generated from its same country
                                $entrega = Entrega::select('entregas.*')
                                        ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('entregas.id', '=', $id) // For this Entrega
                                        ->where('entregas.sucursal_id', '=', $centrodistribucion->id) // Delivered by Sucursal (centro)
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->first();
                                            
                            }
                            else
                            {
                                // Set as forbidden
                                $forbidden = true;
                            }

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // If user belongs to this Sucursal (centro)
                            if(
                                (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                ($user->stationable->id === $centrodistribucion->id)
                            )
                            {
                                // Only if Entrega contains OcPartes from OCs generated from its same country
                                $entrega = Entrega::select('entregas.*')
                                        ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('entregas.id', '=', $id) // For this Entrega
                                        ->where('entregas.sucursal_id', '=', $centrodistribucion->id) // Delivered by Sucursal (centro)
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                        ->first();
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

                    if($entrega !== null)
                    {
                        $entrega->makeHidden([
                            'sucursal_id',
                            'oc_id',
                            'partes_total',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->oc->makeHidden([
                            'cotizacion_id',
                            'proveedor_id',
                            'filedata_id',
                            'motivobaja_id',
                            'usdvalue',
                            'partes',
                            'partes_total',
                            'monto',
                            'estadooc_id', 
                            'created_at', 
                            'updated_at'
                        ]);

                        $entrega->oc->cotizacion;
                        $entrega->oc->cotizacion->makeHidden([
                            'solicitud_id',
                            'motivorechazo_id',
                            'estadocotizacion_id',
                            'usdvalue',
                            'partes_total',
                            'dias',
                            'monto',
                            'created_at', 
                            'updated_at',
                            'partes',
                        ]);
                        
                        $entrega->oc->cotizacion->solicitud;
                        $entrega->oc->cotizacion->solicitud->makeHidden([
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
                                    
                        $entrega->oc->cotizacion->solicitud->sucursal;
                        $entrega->oc->cotizacion->solicitud->sucursal->makeHidden([
                            'type',
                            'rut',
                            'address',
                            'city',
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->oc->cotizacion->solicitud->faena;
                        $entrega->oc->cotizacion->solicitud->faena->makeHidden([
                            'rut',
                            'address',
                            'city',
                            'contact',
                            'phone',
                            'sucursal_id',
                            'cliente_id', 
                            'created_at', 
                            'updated_at'
                        ]);

                        $entrega->oc->cotizacion->solicitud->faena->cliente;
                        $entrega->oc->cotizacion->solicitud->faena->cliente->makeHidden([
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->oc->cotizacion->solicitud->marca;
                        $entrega->oc->cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);
                        
                        $entrega->oc->cotizacion->solicitud->user;
                        $entrega->oc->cotizacion->solicitud->user->makeHidden([
                            'stationable_type',
                            'stationable_id',
                            'email', 
                            'phone', 
                            'country_id', 
                            'role_id', 
                            'email_verified_at', 
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->oc->estadooc;
                        $entrega->oc->estadooc->makeHidden(['created_at', 'updated_at']);

                        $entrega->ocpartes;
                        $entrega->ocpartes = $entrega->ocpartes->filter(function($ocparte)
                        {
                            $ocparte->makeHidden([
                                'oc_id',
                                'parte_id',
                                'descripcion',
                                'cantidad',
                                'backorder',
                                'estadoocparte_id',
                                'tiempoentrega',
                                'created_at',
                                'updated_at'
                            ]);

                            $ocparte->parte;
                            $ocparte->parte->makeHidden([
                                'nparte',
                                'marca_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $ocparte->pivot->makeHidden([
                                'entrega_id',
                                'ocparte_id',
                                'created_at',
                                'updated_at',
                            ]);

                            return $ocparte;
                        });

                        $queuePartes = $entrega->oc->partes->reduce(function($carry, $parte) use($centrodistribucion, $entrega)
                            {
                                // Get cantidad in Entregas for OcParte at Sucursal (centro)
                                $cantidadEntregado = $parte->pivot->getCantidadEntregado($centrodistribucion);

                                // Calc stock cantidad at Sucursal (centro) with cantidad in Recepciones - cantidad in Entregas - cantidad in Despachos at Sucursal (centro) 
                                $cantidadStock = $parte->pivot->getCantidadRecepcionado($centrodistribucion) - $cantidadEntregado - $parte->pivot->getCantidadDespachado($centrodistribucion);

                                // If the OcParte is already in the Entrega
                                if($ocParteEntrega = $entrega->ocpartes->find($parte->pivot->id))
                                {
                                    $cantidadStock = $cantidadStock + $ocParteEntrega->pivot->cantidad; // Add the cantidad in Entrega to set available for updating
                                }

                                // As stock includes cantidad in Entrega (if exists), then filter only partes with stock
                                if($cantidadStock > 0)
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

                                    $parte->pivot->cantidad_entregado = $cantidadEntregado;
                                    $parte->pivot->cantidad_stock = $cantidadStock;

                                    $parte->pivot->makeHidden([
                                        'oc_id',
                                        'parte_id',
                                        'estadoocparte_id',
                                        'created_at',
                                        'updated_at'
                                    ]);

                                    $parte->pivot->estadoocparte;
                                    $parte->pivot->estadoocparte->makeHidden([
                                        'created_at',
                                        'updated_at'
                                    ]);
                                    
                                    array_push($carry, $parte);
                                }

                                return $carry;      
                            },
                            array()
                        );

                        $data = [
                            "entrega" => $entrega,
                            "queue_partes" => $queuePartes 
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
                            'No tienes acceso a actualizar la entrega',
                            null
                        );
                    }
                    else
                    {
                        if(Entrega::find($id))
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La entrega no existe en el centro de distribucion',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La entrega no existe',
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
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar entregas de centro de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la entrega [!]',
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
            if($user->role->hasRoutepermission('sucursales entregas_update'))
            {
                $validatorInput = $request->only('fecha', 'ndocumento', 'responsable', 'comentario', 'partes');
            
                $validatorRules = [
                    'fecha' => 'required|date_format:Y-m-d|before:tomorrow', // it includes today
                    'ndocumento' => 'nullable|min:1',
                    'responsable' => 'required|min:1',
                    'comentario' => 'sometimes|nullable',
                    'partes' => 'required|array|min:1',
                    'partes.*.id'  => 'required|exists:partes,id',
                    'partes.*.cantidad'  => 'required|numeric|min:1',
                    'partes.*.comentario'  => 'sometimes|nullable'
                ];
        
                $validatorMessages = [
                    'fecha.required' => 'Debes ingresar la fecha de despacho',
                    'fecha.date_format' => 'El formato de fecha de despacho es invalido',
                    'fecha.before' => 'La fecha debe ser igual o anterior a hoy',
                    'ndocumento.min' => 'El numero de documento debe tener al menos un digito',
                    'responsable.required' => 'Debes ingresar el nombre de la persona que despacha',
                    'responsable.min' => 'El nombre de la persona que despacha debe tener al menos un digito',
                    'partes.required' => 'Debes seleccionar las partes despachadas',
                    'partes.array' => 'Lista de partes despachadas invalida',
                    'partes.min' => 'El despacho debe contener al menos 1 parte despachada',
                    'partes.*.id.required' => 'La lista de partes despachadas es invalida',
                    'partes.*.id.exists' => 'La parte despachada ingresada no existe',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte despachada',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte despachada debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte despachada debe ser mayor a 0',
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
                    if($centrodistribucion = Sucursal::where('id', $centrodistribucion_id)->where('type', 'centro')->first())
                    {
                        $entrega = null;
                        $forbidden = false;

                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {

                                // If user belongs to the Sucursal's (centro) country
                                if($user->stationable->country->id === $centrodistribucion->country->id)
                                {
                                    // Only if Entrega contains OcPartes from OCs generated from its same country
                                    $entrega = Entrega::select('entregas.*')
                                            ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('entregas.id', '=', $id) // For this Entrega
                                            ->where('entregas.sucursal_id', '=', $centrodistribucion->id) // Delivered by Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->first();
                                                
                                }
                                else
                                {
                                    // Set as forbidden
                                    $forbidden = true;
                                }

                                break;
                            }

                            // Vendedor
                            case 'seller': {

                                // If user belongs to this Sucursal (centro)
                                if(
                                    (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                    ($user->stationable->id === $centrodistribucion->id)
                                )
                                {
                                    // Only if Entrega contains OcPartes from OCs generated from its same country
                                    $entrega = Entrega::select('entregas.*')
                                            ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('entregas.id', '=', $id) // For this Entrega
                                            ->where('entregas.sucursal_id', '=', $centrodistribucion->id) // Delivered by Sucursal (centro)
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                            ->first();
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
                        

                        if($entrega !== null)
                        {
                            // Clean partes list in request and store on diffList for validations and parteList for sync
                            $diffList = array();
                            $parteList = array();

                            foreach($request->partes as $parte)
                            {
                                if(in_array($parte['id'], array_keys($diffList)))
                                {
                                    $diffList[$parte['id']] += $parte['cantidad'];
                                    $parteList[$parte['id']] += $parte['cantidad'];
                                }
                                else
                                {
                                    $diffList[$parte['id']] = $parte['cantidad'];
                                    $parteList[$parte['id']] = $parte['cantidad'];
                                }
                            }

                            // For each OcParte in Entrega
                            foreach($entrega->ocpartes as $ocParte)
                            {
                                // If the Parte is already in list, it's kept in Entrega
                                if((in_array($ocParte->parte->id, array_keys($diffList))) === true)
                                {
                                    // Add the diff cantidad with cantidad given in request - old cantidad
                                    $diffList[$ocParte->parte->id] -= $ocParte->pivot->cantidad;
                                }
                                // If the OcParte isn't in the list, it's going to be removed and don't add it on the parteList (for sync)
                                else
                                {
                                    $diffList[$ocParte->parte->id] = ($ocParte->pivot->cantidad * -1);
                                }
                            }

                            DB::beginTransaction();

                            // Fill the data
                            $entrega->fill($request->all());

                            if($entrega->save())
                            {
                                $success = true;
                                $ocFullDelivered = true;

                                //Attaching each Parte to the Entrega
                                $syncData = [];
                                foreach(array_keys($diffList) as $parteId)
                                {
                                    if($p = $entrega->oc->partes->find($parteId))
                                    {
                                        // Calc new cantidad with cantidad total in Entregas + diff (negative when removing)
                                        $newCantidadEntregas = $p->pivot->getCantidadTotalEntregado() + $diffList[$parteId];

                                        // If new cantidad total in Entregas is equal or less than cantidad total in Oc
                                        if($newCantidadEntregas <= $p->pivot->cantidad)
                                        {
                                            // Calc new cantidad with cantidad in Entregas + diff (negative when removing)
                                            $newCantidad = $p->pivot->getCantidadEntregado($centrodistribucion) + $diffList[$parteId];

                                            // If new cantidad in Entregas + cantidad in Despachos is equal or less than cantidad in Recepciones
                                            if($newCantidad + $p->pivot->getCantidadDespachado($centrodistribucion) <= $p->pivot->getCantidadRecepcionado($centrodistribucion))
                                            {
                                                // If Parte is in the request
                                                if(in_array($parteId, array_keys($parteList)) === true)
                                                {
                                                    // All partes were delivered at Sucursal (centro)
                                                    if($newCantidadEntregas === $p->pivot->cantidad)
                                                    {
                                                        $p->pivot->estadoocparte_id = 3; // Estadoocparte = 'Entregado'
                                                    }
                                                    // If all partes were at least received at Solicitud's Comprador
                                                    else if($p->pivot->getCantidadRecepcionado($p->pivot->oc->cotizacion->solicitud->comprador) === $p->pivot->cantidad)
                                                    {
                                                        $p->pivot->estadoocparte_id = 2; // Estadoocparte = 'En transito'
                                                    }
                                                    else
                                                    {
                                                        $p->pivot->estadoocparte_id = 1; // Estadoocparte = 'Pendiente'
                                                    }

                                                    // Add the OcParte to sync using the ID which is unique
                                                    $syncData[$p->pivot->id] = array(
                                                        'cantidad' => $parteList[$parteId]
                                                    );
                                                }
                                            }
                                            else
                                            {
                                                // If the dispatched parts are more than waiting in queue
                                                $response = HelpController::buildResponse(
                                                    409,
                                                    'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor al stock disponible para entrega en el centro de distribucion',
                                                    null
                                                );
            
                                                $success = false;
            
                                                break;
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
                                            // If the delivered partes are more than total in Oc
                                            $response = HelpController::buildResponse(
                                                409,
                                                'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor a la cantidad pendiente de entrega para la OC',
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

                                // Eval if all the OcPartes in Oc were fully delivered in Entregas
                                $ocFullDelivered = $entrega->oc->partes->reduce(function($carry, $parte)
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
                                    $entrega->oc->estadooc_id = 3; // Estadooc = 'Cerrada'
                                    if(!$entrega->oc->save())
                                    {
                                        $response = HelpController::buildResponse(
                                            500,
                                            'Error al actualizar el estado de la OC',
                                            null
                                        );
    
                                        $success = false;
                                    }
                                }

                                if($success === true)
                                {
                                    if($entrega->ocpartes()->sync($syncData))
                                    {
                                        DB::commit();
                                    
                                        $response = HelpController::buildResponse(
                                            200,
                                            'Entrega actualizada',
                                            null
                                        );
                                           
                                    }
                                    else
                                    {
                                        DB::rollback();

                                        $response = HelpController::buildResponse(
                                            500,
                                            'Error al actualizar la entrega',
                                            null
                                        );
    
                                        $success = false;
                                    }
                                }
                                else
                                {
                                    // Error message was already given
                                }
                            }
                            else
                            {       
                                DB::rollback();

                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al actualizar la entrega',
                                    null
                                );
                            }
                        }
                        else if($forbidden === true)
                        {
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a actualizar la entrega',
                                null
                            );
                        }
                        else
                        {
                            if(Entrega::find($id))
                            {
                                $response = HelpController::buildResponse(
                                    412,
                                    'La entrega no existe en el centro de distribucion',
                                    null
                                );
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    412,
                                    'La entrega no existe',
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
                    'No tienes acceso a actualizar entregas para centro de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al actualizar la entrega [!]',
                null
            );
        }
        
        return $response;
    }

    public function destroy_centrodistribucion(Request $request, $centrodistribucion_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales entregas_update'))
            {
                if($centrodistribucion = Sucursal::where('id', $centrodistribucion_id)->where('type', 'centro')->first())
                {
                    $entrega = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // If user belongs to the Sucursal's (centro) country
                            if($user->stationable->country->id === $centrodistribucion->country->id)
                            {
                                // Only if Entrega contains OcPartes from OCs generated from its same country
                                $entrega = Entrega::select('entregas.*')
                                        ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('entregas.id', '=', $id) // For this Entrega
                                        ->where('entregas.sucursal_id', '=', $centrodistribucion->id) // Delivered by Sucursal (centro)
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->first();
                                            
                            }
                            else
                            {
                                // Set as forbidden
                                $forbidden = true;
                            }

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // If user belongs to this Sucursal (centro)
                            if(
                                (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                ($user->stationable->id === $centrodistribucion->id)
                            )
                            {
                                // Only if Entrega contains OcPartes from OCs generated from its same country
                                $entrega = Entrega::select('entregas.*')
                                        ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('entregas.id', '=', $id) // For this Entrega
                                        ->where('entregas.sucursal_id', '=', $centrodistribucion->id) // Delivered by Sucursal (centro)
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                        ->first();
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
                    

                    if($entrega !== null)
                    {
                        DB::beginTransaction();

                        $success = true;

                        foreach($entrega->ocpartes as $ocParte)
                        {
                            // If all partes were at least received at Solicitud's Comprador
                            if($ocParte->getCantidadRecepcionado($ocParte->oc->cotizacion->solicitud->comprador) === $ocParte->cantidad)
                            {
                                $ocParte->estadoocparte_id = 2; // Estadoocparte = 'En transito'
                            }
                            else
                            {
                                $ocParte->estadoocparte_id = 1; // Estadoocparte = 'Pendiente'
                            }

                            // If fails on saving the new status for OcParte
                            if(!($ocParte->save()))
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al cambiar el estado de la parte "' . $ocParte->parte->nparte . '"',
                                    null
                                );

                                $success = false;

                                break;
                            }
                        }
            
                        // Oc goes back to estadooc = 'En proceso'
                        $entrega->oc->estadooc_id = 2; // Estadooc = 'En proceso'
                        if(!$entrega->oc->save())
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al actualizar el estado de la OC',
                                null
                            );

                            $success = false;
                        }

                        if(($success === true) && ($entrega->delete()))
                        {  
                            DB::commit();

                            $response = HelpController::buildResponse(
                                200,
                                'Entrega eliminada',
                                null
                            );
                        }
                        else
                        {
                            DB::rollback();

                            // Error message was already given
                        }
                    }
                    else if($forbidden === true)
                    {
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a eliminar la entrega',
                            null
                        );
                    }
                    else
                    {
                        if(Entrega::find($id))
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La entrega no existe en el centro de distribucion',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La entrega no existe',
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
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a eliminar entregas para centro de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al eliminar la entrega [!]',
                null
            );
        }
        
        return $response;
    }


    /*
     *  Sucursal
     */

    public function index_sucursal($id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales entregas_index'))
            {
                if($sucursal = Sucursal::where('id', $id)->where('type', 'sucursal')->first())
                {
                    $entregas = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // If user belongs to the Sucursal's country
                            if($user->stationable->country->id === $sucursal->country->id)
                            {
                                // Get only Entregas containing OcPartes from OCs generated from its same country
                                $entregas = Entrega::select('entregas.*')
                                            ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('entregas.sucursal_id', '=', $sucursal->id) // Delivered by Sucursal
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->get();
                                          
                            }
                            else
                            {
                                // Set as forbidden
                                $forbidden = true;
                            }

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // If user belongs to this Sucursal
                            if(
                                (get_class($user->stationable) === get_class($sucursal)) &&
                                ($user->stationable->id === $sucursal->id)
                            )
                            {
                                // Get only Entregas containing OcPartes from OCs generated from its same country
                                $entregas = Entrega::select('entregas.*')
                                            ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('entregas.sucursal_id', '=', $sucursal->id) // Delivered by Sucursal
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
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

                    if($entregas !== null)
                    {
                        $entregas = $entregas->map(function($entrega)
                            {
                                $entrega->partes_total;
                        
                                $entrega->makeHidden([
                                    'sucursal_id',
                                    'oc_id',
                                    'ocpartes',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $entrega->oc->makeHidden([
                                    'cotizacion_id',
                                    'proveedor_id',
                                    'filedata_id',
                                    'motivobaja_id',
                                    'usdvalue',
                                    'partes',
                                    'partes_total',
                                    'monto',
                                    'estadooc_id', 
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $entrega->oc->cotizacion;
                                $entrega->oc->cotizacion->makeHidden([
                                    'solicitud_id',
                                    'motivorechazo_id',
                                    'estadocotizacion_id',
                                    'usdvalue',
                                    'partes_total',
                                    'dias',
                                    'monto',
                                    'created_at', 
                                    'updated_at',
                                    'partes',
                                ]);
                                
                                $entrega->oc->cotizacion->solicitud;
                                $entrega->oc->cotizacion->solicitud->makeHidden([
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
                                            
                                $entrega->oc->cotizacion->solicitud->sucursal;
                                $entrega->oc->cotizacion->solicitud->sucursal->makeHidden([
                                    'type',
                                    'rut',
                                    'address',
                                    'city',
                                    'country_id',
                                    'created_at', 
                                    'updated_at'
                                ]);
                                
                                $entrega->oc->cotizacion->solicitud->faena;
                                $entrega->oc->cotizacion->solicitud->faena->makeHidden([
                                    'rut',
                                    'address',
                                    'city',
                                    'contact',
                                    'phone',
                                    'sucursal_id',
                                    'cliente_id', 
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $entrega->oc->cotizacion->solicitud->faena->cliente;
                                $entrega->oc->cotizacion->solicitud->faena->cliente->makeHidden([
                                    'country_id',
                                    'created_at', 
                                    'updated_at'
                                ]);
                                
                                $entrega->oc->cotizacion->solicitud->marca;
                                $entrega->oc->cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);
                                
                                $entrega->oc->cotizacion->solicitud->user;
                                $entrega->oc->cotizacion->solicitud->user->makeHidden([
                                    'stationable_type',
                                    'stationable_id',
                                    'email', 
                                    'phone', 
                                    'country_id', 
                                    'role_id', 
                                    'email_verified_at', 
                                    'created_at', 
                                    'updated_at'
                                ]);
                                
                                $entrega->oc->estadooc;
                                $entrega->oc->estadooc->makeHidden(['created_at', 'updated_at']);

                                return $entrega;
                            }
                        );

                        $response = HelpController::buildResponse(
                            200,
                            null,
                            $entregas
                        );
                    }
                    else if($forbidden === true)
                    {
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a visualizar las entregas',
                            null
                        );
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            500,
                            'Error al obtener la lista de entregas',
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
                    'No tienes acceso a visualizar entregas de sucursal',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener las entregas de la surusal [!]',
                null
            );
        }
            
        return $response;
    }

    public function queueOcs_sucursal($sucursal_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales entregas_store'))
            {
                if($sucursal = Sucursal::where('id', $sucursal_id)->where('type', 'sucursal')->first())
                {
                    $ocParteList = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // If user belongs to the Sucursal's country
                            if($user->stationable->country->id === $sucursal->country->id)
                            {
                                // Get all the OcPartes in Recepciones at Sucursal for delivering at Sucursal
                                $ocParteList = OcParte::select('oc_parte.*')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                            ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                            ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Recepciones received at Sucursal
                                            ->where('sucursalfaena.id', '=', $sucursal->id) // Faena with delivery at Sucursal
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->get();
                                            
                            }
                            else
                            {
                                // Set as forbidden
                                $forbidden = true;
                            }

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // If user belongs to this Sucursal
                            if(
                                (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                ($user->stationable->id === $centrodistribucion->id)
                            )
                            {
                                // Get all the OcPartes in Recepciones at Sucursal for delivering at Sucursal
                                $ocParteList = OcParte::select('oc_parte.*')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                            ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                            ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Recepciones received at Sucursal
                                            ->where('sucursalfaena.id', '=', $sucursal->id) // Faena with delivery at Sucursal
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
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
                        // Retrieves the Oc with pending OcParte for Entrega
                        $ocList = $ocParteList->reduce(function($carry, $ocParte) use ($sucursal)
                            {
                                // If the Oc (id) isn't in the list already, check it
                                if(in_array($ocParte->oc->id, array_keys($carry)) === false)
                                {
                                    // Get delivered cantidad for OcParte at Sucursal
                                    $cantidadEntregado = $ocParte->getCantidadEntregado($sucursal);

                                    // If has delivered less cantidad than total, add the Oc to the list
                                    if($cantidadEntregado < $ocParte->cantidad)
                                    {
                                        $ocParte->cantidad_entregado = $cantidadEntregado;

                                        $ocParte->oc->makeHidden([
                                            'cotizacion_id',
                                            'proveedor_id',
                                            'filedata_id',
                                            'motivobaja_id',
                                            'partes',
                                            'estadooc_id', 
                                            'created_at', 
                                            //'updated_at'
                                        ]);

                                        $ocParte->oc->cotizacion;
                                        $ocParte->oc->cotizacion->makeHidden([
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
                                        
                                        $ocParte->oc->cotizacion->solicitud;
                                        $ocParte->oc->cotizacion->solicitud->makeHidden([
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
                                            'rut',
                                            'address',
                                            'city',
                                            'contact',
                                            'phone',
                                            'cliente_id', 
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
                                        $ocParte->oc->cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);
                                        
                                        $ocParte->oc->cotizacion->solicitud->user;
                                        $ocParte->oc->cotizacion->solicitud->user->makeHidden([
                                            'stationable_type',
                                            'stationable_id',
                                            'email', 
                                            'phone', 
                                            'country_id', 
                                            'role_id', 
                                            'email_verified_at', 
                                            'created_at', 
                                            'updated_at'
                                        ]);
                                        
                                        $ocParte->oc->estadooc;
                                        $ocParte->oc->estadooc->makeHidden(['created_at', 'updated_at']);

                                        $carry[$ocParte->oc->id] = $ocParte->oc;
                                    }
                                }
                                
                                return $carry;
                            },
                            array()
                        );

                        $ocs = array();
                        foreach(array_keys($ocList) as $ocId)
                        {
                            array_push($ocs, $ocList[$ocId]);
                        }

                        $response = HelpController::buildResponse(
                            200,
                            null,
                            $ocs
                        );

                    }
                    else if($forbidden === true)
                    {
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a visualizar las OCs pendiente de entrega',
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
                        'La sucursal no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar partes disponibles para entregar',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener partes disponibles para entregar [!]',
                null
            );
        }
            
        return $response;
    }

    public function store_prepare_sucursal($sucursal_id, $oc_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales entregas_store'))
            {
                if($sucursal = Sucursal::where('id', $sucursal_id)->where('type', 'sucursal')->first())
                {
                    $oc = null;
        
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // If user belongs to the Sucursal's country
                            if($user->stationable->country->id === $sucursal->country->id)
                            {
                                // Get the Oc if has OcParte in Recepciones at Sucursal for delivering at Sucursal
                                $oc = Oc::select('ocs.*')
                                    ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                    ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                    ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                    ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                    ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                    ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                    ->where('ocs.id', '=', $oc_id)
                                    ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                    ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Recepciones received at Sucursal
                                    ->where('sucursalfaena.id', '=', $sucursal->id) // Faena with delivery at Sucursal
                                    ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                    ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                    ->first();
                                            
                            }
                            else
                            {
                                // Set as forbidden
                                $forbidden = true;
                            }

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // If user belongs to this Sucursal
                            if(
                                (get_class($user->stationable) === get_class($sucursal)) &&
                                ($user->stationable->id === $sucursal->id)
                            )
                            {
                                // Get the Oc if has OcParte in Recepciones at Sucursal for delivering at Sucursal
                                $oc = Oc::select('ocs.*')
                                    ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                    ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                    ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                    ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                    ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                    ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                    ->where('ocs.id', '=', $oc_id)
                                    ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                    ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Recepciones received at Sucursal
                                    ->where('sucursalfaena.id', '=', $sucursal->id) // Faena with delivery at Sucursal
                                    ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                    ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                    ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                    ->first();
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

                    if($oc !== null)
                    {
                        $oc->makeHidden([
                            'cotizacion_id',
                            'proveedor_id',
                            'filedata_id',
                            'motivobaja_id',
                            'partes',
                            'partes_total',
                            'usdvalue',
                            'monto',
                            'estadooc_id', 
                            'created_at', 
                            //'updated_at'
                        ]);

                        $oc->cotizacion;
                        $oc->cotizacion->makeHidden([
                            'solicitud_id',
                            'motivorechazo_id',
                            'estadocotizacion_id',
                            'usdvalue',
                            'partes_total',
                            'dias',
                            'monto',
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
                            'rut',
                            'address',
                            'city',
                            'contact',
                            'phone',
                            'cliente_id',
                            'sucursal_id',
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
                            'stationable_type',
                            'stationable_id',
                            'email', 
                            'phone', 
                            'country_id', 
                            'role_id', 
                            'email_verified_at', 
                            'created_at', 
                            'updated_at'
                        ]);

                        $oc->estadooc;
                        $oc->estadooc->makeHidden(['created_at', 'updated_at']);

                        $queuePartes = $oc->partes->reduce(function($carry, $parte) use($sucursal)
                            {
                                $parte->makeHidden([
                                    'marca_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $parte->marca;
                                $parte->marca->makeHidden([
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $parte->pivot->makeHidden([
                                    'oc_id',
                                    'parte_id',
                                    'estadoocparte_id',
                                    'created_at',
                                    'updated_at'
                                ]);

                                $parte->pivot->estadoocparte;
                                $parte->pivot->estadoocparte->makeHidden([
                                    'created_at', 
                                    'updated_at'
                                ]);

                                // Get the stock cantidad using cantidad in Recepciones - cantidad in Entregas
                                $cantidadStock = $parte->pivot->getCantidadRecepcionado($sucursal) - $parte->pivot->getCantidadEntregado($sucursal);

                                // Add it to the queue only if there's stock in Sucursal
                                if($cantidadStock > 0)
                                {
                                    // Set cantidad in Entregas and stock for OcParte
                                    $parte->pivot->cantidad_entregado = $parte->pivot->getCantidadEntregado($sucursal);
                                    $parte->pivot->cantidad_stock = $cantidadStock;

                                    // Add parte to queue
                                    array_push($carry, $parte);
                                }

                                return $carry;      
                            },
                            array()
                        );

                        $data = [
                            "oc" => $oc,
                            "queue_partes" => $queuePartes 
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
                            'No tienes acceso a registrar entregas para la OC',
                            null
                        );
                    }
                    else
                    {
                        if(Oc::find($oc_id))
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'La OC no tiene partes disponibles para entrega',
                                null
                            );
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
                    'No tienes acceso a visualizar partes disponibles para entregar',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener partes disponibles para entregar [!]',
                null
            );
        }
            
        return $response;
    }

    public function store_sucursal(Request $request, $sucursal_id, $oc_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales entregas_store'))
            {
                $validatorInput = $request->only('fecha', 'ndocumento', 'responsable', 'comentario', 'partes');
            
                $validatorRules = [
                    'fecha' => 'required|date_format:Y-m-d|before:tomorrow', // it includes today
                    'ndocumento' => 'nullable|min:1',
                    'responsable' => 'required|min:1',
                    'comentario' => 'sometimes|nullable',
                    'partes' => 'required|array|min:1',
                    'partes.*.id'  => 'required|exists:partes,id',
                    'partes.*.cantidad'  => 'required|numeric|min:1',
                ];
        
                $validatorMessages = [
                    'fecha.required' => 'Debes ingresar la fecha de despacho',
                    'fecha.date_format' => 'El formato de fecha de despacho es invalido',
                    'fecha.before' => 'La fecha debe ser igual o anterior a hoy',
                    'ndocumento.min' => 'El numero de documento debe tener al menos un digito',
                    'responsable.required' => 'Debes ingresar el nombre de la persona que despacha',
                    'responsable.min' => 'El nombre de la persona que despacha debe tener al menos un digito',
                    'partes.required' => 'Debes seleccionar las partes despachadas',
                    'partes.array' => 'Lista de partes despachadas invalida',
                    'partes.min' => 'El despacho debe contener al menos 1 parte despachada',
                    'partes.*.id.required' => 'La lista de partes despachadas es invalida',
                    'partes.*.id.exists' => 'La parte despachada ingresada no existe',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte despachada',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte despachada debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte despachada debe ser mayor a 0',
                    
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
                    if($sucursal = Sucursal::where('id', $sucursal_id)->where('type', 'sucursal')->first())
                    {
                        $oc = null;
        
                        $forbidden = false;

                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {

                                // If user belongs to the Sucursal's country
                                if($user->stationable->country->id === $sucursal->country->id)
                                {
                                    // Get the Oc if has OcParte in Recepciones at Sucursal for delivering at Sucursal
                                    $oc = Oc::select('ocs.*')
                                        ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                        ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                        ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                        ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                        ->where('ocs.id', '=', $oc_id)
                                        ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                        ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Recepciones received at Sucursal
                                        ->where('sucursalfaena.id', '=', $sucursal->id) // Faena with delivery at Sucursal
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->first();
                                                
                                }
                                else
                                {
                                    // Set as forbidden
                                    $forbidden = true;
                                }

                                break;
                            }

                            // Vendedor
                            case 'seller': {

                                // If user belongs to this Sucursal
                                if(
                                    (get_class($user->stationable) === get_class($sucursal)) &&
                                    ($user->stationable->id === $sucursal->id)
                                )
                                {
                                    // Get the Oc if has OcParte in Recepciones at Sucursal for delivering at Sucursal
                                    $oc = Oc::select('ocs.*')
                                        ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                        ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                        ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                        ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                        ->where('ocs.id', '=', $oc_id)
                                        ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                        ->where('recepciones.recepcionable_id', '=', $sucursal->id) // Recepciones received at Sucursal
                                        ->where('sucursalfaena.id', '=', $sucursal->id) // Faena with delivery at Sucursal
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                        ->first();
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

                        if($oc !== null)
                        {
                            DB::beginTransaction();

                            $entrega = new Entrega();
                            // Fill the data
                            $entrega->sucursal_id = $sucursal->id;
                            $entrega->oc_id = $oc->id;
                            $entrega->fecha = $request->fecha;
                            $entrega->ndocumento = $request->ndocumento;
                            $entrega->responsable = $request->responsable;
                            $entrega->comentario = $request->comentario;

                            if($entrega->save())
                            {
                                $success = true;

                                $ocpartes = array();
                                foreach($request->partes as $p)
                                {
                                    if($parte = $oc->partes->find($p['id']))
                                    {
                                        // Calc cantidad pendiente with cantidad in Oc - cantidad in Entregas at Sucursal
                                        $cantidadPendiente = $parte->pivot->cantidad - $parte->pivot->getCantidadEntregado($sucursal);
                                        if($cantidadPendiente > 0)
                                        {
                                            if($p['cantidad'] <= $cantidadPendiente)
                                            {
                                                // Get cantidad in Entregas for OcParte
                                                $cantidadEntregado = $parte->pivot->getCantidadEntregado($sucursal);

                                                // Calc OcParte cantidad stock with cantidad in Recepciones - cantidad in Entregas
                                                $cantidadStock = $parte->pivot->getCantidadRecepcionado($sucursal) - $cantidadEntregado;
                                                if($cantidadStock > 0)
                                                {
                                                    if($p['cantidad'] <= $cantidadStock)
                                                    {
                                                        // If cantidad its equal to pending for fully deliver OcParte in Oc
                                                        if($p['cantidad'] === $parte->pivot->cantidad - $cantidadEntregado)
                                                        {
                                                            // All partes were delivered at Sucursal (centro)
                                                            $parte->pivot->estadoocparte_id = 3; // Estadoocparte = 'Entregado'

                                                            // If fail on saving the new status for OcParte
                                                            if(!($parte->pivot->save()))
                                                            {
                                                                $response = HelpController::buildResponse(
                                                                    500,
                                                                    'Error al cambiar el estado de la parte "' . $parte->nparte . '"',
                                                                    null
                                                                );
                            
                                                                $success = false;
                            
                                                                break;
                                                            }
                                                        }

                                                        // Add the OcParte to the Entrega
                                                        $entrega->ocpartes()->attach(
                                                            array(
                                                                $parte->pivot->id => array(
                                                                    "cantidad" => $p['cantidad']
                                                                )
                                                            )
                                                        );
                                                    }
                                                    else
                                                    {
                                                        // If the entered cantidad for parte is more than in stock
                                                        $response = HelpController::buildResponse(
                                                            409,
                                                            'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor al stock disponible en la sucursal',
                                                            null
                                                        );

                                                        $success = false;
                
                                                        break;
                                                    }
                                                }
                                                else
                                                {
                                                    // If the entered parte has no stock in Sucursal (centro)
                                                    $response = HelpController::buildResponse(
                                                        409,
                                                        'La parte "' . $parte->nparte . '" no tiene stock disponible en la sucursal',
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
                                                    'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor a la cantidad de pendiente de entrega',
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
                                                'La parte "' . $parte->nparte . '" no tiene partes pendiente de entrega en la OC',
                                                null
                                            );

                                            $success = false;
    
                                            break;
                                        }
                                    }
                                    else
                                    {
                                        // If the entered parte isn't in the Oc
                                        $response = HelpController::buildResponse(
                                            409,
                                            'Una de las partes ingresadas no existe en la OC',
                                            null
                                        );
    
                                        $success = false;
    
                                        break;
                                    }
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
                                            'Error al actualizar el estado de la Oc',
                                            null
                                        );
    
                                        $success = false;
                                    }
                                }

                                if($success === true)
                                {

                                    DB::commit();
                                        
                                    $response = HelpController::buildResponse(
                                        201,
                                        'Entrega creada',
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
                                    'Error al crear la entrega',
                                    null
                                );
                            }
                        }
                        else if($forbidden === true)
                        {
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a registrar entregas para la OC',
                                null
                            );
                        }
                        else
                        {
                            if(Oc::find($oc_id))
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'La OC no tiene partes disponibles para entrega',
                                    null
                                );
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
                    'No tienes acceso a registrar entregas para sucursal',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al crear la entrega [!]',
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
            if($user->role->hasRoutepermission('sucursales entregas_show'))
            {
                if($sucursal = Sucursal::where('id', $sucursal_id)->where('type', 'sucursal')->first())
                {
                    $entrega = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // If user belongs to the Sucursal's country
                            if($user->stationable->country->id === $sucursal->country->id)
                            {
                                // Only if Entrega contains OcPartes from OCs generated from its same country
                                $entrega = Entrega::select('entregas.*')
                                        ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('entregas.id', '=', $id) // For this Entrega
                                        ->where('entregas.sucursal_id', '=', $sucursal->id) // Delivered by Sucursal
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->first();
                                            
                            }
                            else
                            {
                                // Set as forbidden
                                $forbidden = true;
                            }

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // If user belongs to this Sucursal
                            if(
                                (get_class($user->stationable) === get_class($sucursal)) &&
                                ($user->stationable->id === $sucursal->id)
                            )
                            {
                                // Only if Entrega contains OcPartes from OCs generated from its same country
                                $entrega = Entrega::select('entregas.*')
                                        ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('entregas.id', '=', $id) // For this Entrega
                                        ->where('entregas.sucursal_id', '=', $sucursal->id) // Delivered by Sucursal
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                        ->first();
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

                    if($entrega !== null)
                    {
                        $entrega->makeHidden([
                            'sucursal_id',
                            'oc_id',
                            'partes_total',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->oc->makeHidden([
                            'cotizacion_id',
                            'proveedor_id',
                            'filedata_id',
                            'motivobaja_id',
                            'usdvalue',
                            'partes',
                            'partes_total',
                            'monto',
                            'estadooc_id', 
                            'created_at', 
                            'updated_at'
                        ]);

                        $entrega->oc->cotizacion;
                        $entrega->oc->cotizacion->makeHidden([
                            'solicitud_id',
                            'motivorechazo_id',
                            'estadocotizacion_id',
                            'usdvalue',
                            'partes_total',
                            'dias',
                            'monto',
                            'created_at', 
                            'updated_at',
                            'partes',
                        ]);
                        
                        $entrega->oc->cotizacion->solicitud;
                        $entrega->oc->cotizacion->solicitud->makeHidden([
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
                                    
                        $entrega->oc->cotizacion->solicitud->sucursal;
                        $entrega->oc->cotizacion->solicitud->sucursal->makeHidden([
                            'type',
                            'rut',
                            'address',
                            'city',
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->oc->cotizacion->solicitud->faena;
                        $entrega->oc->cotizacion->solicitud->faena->makeHidden([
                            'rut',
                            'address',
                            'city',
                            'contact',
                            'phone',
                            'sucursal_id',
                            'cliente_id', 
                            'created_at', 
                            'updated_at'
                        ]);

                        $entrega->oc->cotizacion->solicitud->faena->cliente;
                        $entrega->oc->cotizacion->solicitud->faena->cliente->makeHidden([
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->oc->cotizacion->solicitud->marca;
                        $entrega->oc->cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);
                        
                        $entrega->oc->cotizacion->solicitud->user;
                        $entrega->oc->cotizacion->solicitud->user->makeHidden([
                            'stationable_type',
                            'stationable_id',
                            'email', 
                            'phone', 
                            'country_id', 
                            'role_id', 
                            'email_verified_at', 
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->oc->estadooc;
                        $entrega->oc->estadooc->makeHidden(['created_at', 'updated_at']);

                        $entrega->ocpartes;
                        $entrega->ocpartes = $entrega->ocpartes->filter(function($ocparte)
                        {
                            $ocparte->cantidad_entregado = $ocparte->getCantidadTotalEntregado();

                            $ocparte->makeHidden([
                                'oc_id',
                                'parte_id',
                                'estadoocparte_id',
                                'tiempoentrega',
                                'created_at',
                                'updated_at'
                            ]);

                            $ocparte->pivot->makeHidden([
                                'entrega_id',
                                'ocparte_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $ocparte->parte;
                            $ocparte->parte->makeHidden([
                                'marca_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $ocparte->parte->marca;
                            $ocparte->parte->marca->makeHidden(['created_at', 'updated_at']);

                            $ocparte->estadoocparte;
                            $ocparte->estadoocparte->makeHidden([
                                'created_at',
                                'updated_at'
                            ]);

                            return $ocparte;
                        });

                        $response = HelpController::buildResponse(
                            200,
                            null,
                            $entrega
                        );
                    }
                    else if($forbidden === true)
                    {
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a visualizar la entrega',
                            null
                        );
                    }
                    else
                    {
                        if(Entrega::find($id))
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La entrega no existe en la sucursal',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La entrega no existe',
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
                    'No tienes acceso a visualizar entregas de sucursal',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la entrega [!]',
                null
            );
        }
            
        return $response;
    }

    /**
     * It retrieves all the required info for
     * selecting data and updating an Entrega for Sucursal
     * 
     */
    public function update_prepare_sucursal($sucursal_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales entregas_update'))
            {
                if($sucursal = Sucursal::where('id', $sucursal_id)->where('type', 'sucursal')->first())
                {
                    $entrega = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // If user belongs to the Sucursal's country
                            if($user->stationable->country->id === $sucursal->country->id)
                            {
                                // Only if Entrega contains OcPartes from OCs generated from its same country
                                $entrega = Entrega::select('entregas.*')
                                        ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('entregas.id', '=', $id) // For this Entrega
                                        ->where('entregas.sucursal_id', '=', $sucursal->id) // Delivered by Sucursal
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->first();
                                            
                            }
                            else
                            {
                                // Set as forbidden
                                $forbidden = true;
                            }

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // If user belongs to this Sucursal
                            if(
                                (get_class($user->stationable) === get_class($sucursal)) &&
                                ($user->stationable->id === $sucursal->id)
                            )
                            {
                                // Only if Entrega contains OcPartes from OCs generated from its same country
                                $entrega = Entrega::select('entregas.*')
                                        ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('entregas.id', '=', $id) // For this Entrega
                                        ->where('entregas.sucursal_id', '=', $sucursal->id) // Delivered by Sucursal
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                        ->first();
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

                    if($entrega !== null)
                    {
                        $entrega->makeHidden([
                            'sucursal_id',
                            'oc_id',
                            'partes_total',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->oc->makeHidden([
                            'cotizacion_id',
                            'proveedor_id',
                            'filedata_id',
                            'motivobaja_id',
                            'usdvalue',
                            'partes',
                            'partes_total',
                            'monto',
                            'estadooc_id', 
                            'created_at', 
                            'updated_at'
                        ]);

                        $entrega->oc->cotizacion;
                        $entrega->oc->cotizacion->makeHidden([
                            'solicitud_id',
                            'motivorechazo_id',
                            'estadocotizacion_id',
                            'usdvalue',
                            'partes_total',
                            'dias',
                            'monto',
                            'created_at', 
                            'updated_at',
                            'partes',
                        ]);
                        
                        $entrega->oc->cotizacion->solicitud;
                        $entrega->oc->cotizacion->solicitud->makeHidden([
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
                                    
                        $entrega->oc->cotizacion->solicitud->sucursal;
                        $entrega->oc->cotizacion->solicitud->sucursal->makeHidden([
                            'type',
                            'rut',
                            'address',
                            'city',
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->oc->cotizacion->solicitud->faena;
                        $entrega->oc->cotizacion->solicitud->faena->makeHidden([
                            'rut',
                            'address',
                            'city',
                            'contact',
                            'phone',
                            'sucursal_id',
                            'cliente_id', 
                            'created_at', 
                            'updated_at'
                        ]);

                        $entrega->oc->cotizacion->solicitud->faena->cliente;
                        $entrega->oc->cotizacion->solicitud->faena->cliente->makeHidden([
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->oc->cotizacion->solicitud->marca;
                        $entrega->oc->cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);
                        
                        $entrega->oc->cotizacion->solicitud->user;
                        $entrega->oc->cotizacion->solicitud->user->makeHidden([
                            'stationable_type',
                            'stationable_id',
                            'email', 
                            'phone', 
                            'country_id', 
                            'role_id', 
                            'email_verified_at', 
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->oc->estadooc;
                        $entrega->oc->estadooc->makeHidden(['created_at', 'updated_at']);

                        $entrega->ocpartes;
                        $entrega->ocpartes = $entrega->ocpartes->filter(function($ocparte)
                        {
                            $ocparte->makeHidden([
                                'oc_id',
                                'parte_id',
                                'descripcion',
                                'cantidad',
                                'backorder',
                                'estadoocparte_id',
                                'tiempoentrega',
                                'created_at',
                                'updated_at'
                            ]);

                            $ocparte->parte;
                            $ocparte->parte->makeHidden([
                                'nparte',
                                'marca_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $ocparte->pivot->makeHidden([
                                'entrega_id',
                                'ocparte_id',
                                'created_at',
                                'updated_at',
                            ]);

                            return $ocparte;
                        });

                        $queuePartes = $entrega->oc->partes->reduce(function($carry, $parte) use($sucursal, $entrega)
                            {
                                // Get cantidad in Entregas for OcParte at Sucursal
                                $cantidadEntregado = $parte->pivot->getCantidadEntregado($sucursal);

                                // Calc stock cantidad at Sucursal with cantidad in Recepciones - cantidad in Entregas
                                $cantidadStock = $parte->pivot->getCantidadRecepcionado($sucursal) - $cantidadEntregado;

                                // If the OcParte is already in the Entrega
                                if($ocParteEntrega = $entrega->ocpartes->find($parte->pivot->id))
                                {
                                    $cantidadStock = $cantidadStock + $ocParteEntrega->pivot->cantidad; // Add the cantidad in Entrega to set available for updating
                                }

                                // As stock includes cantidad in Entrega (if exists), then filter only partes with stock
                                if($cantidadStock > 0)
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

                                    $parte->pivot->cantidad_entregado = $cantidadEntregado;
                                    $parte->pivot->cantidad_stock = $cantidadStock;

                                    $parte->pivot->makeHidden([
                                        'oc_id',
                                        'parte_id',
                                        'estadoocparte_id',
                                        'created_at',
                                        'updated_at'
                                    ]);

                                    $parte->pivot->estadoocparte;
                                    $parte->pivot->estadoocparte->makeHidden([
                                        'created_at',
                                        'updated_at'
                                    ]);
                                    
                                    array_push($carry, $parte);
                                }

                                return $carry;      
                            },
                            array()
                        );

                        $data = [
                            "entrega" => $entrega,
                            "queue_partes" => $queuePartes 
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
                            'No tienes acceso a actualizar la entrega',
                            null
                        );
                    }
                    else
                    {
                        if(Entrega::find($id))
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La entrega no existe en la sucursal',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La entrega no existe',
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
                    'No tienes acceso a actualizar entregas de sucursal',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la entrega [!]',
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
            if($user->role->hasRoutepermission('sucursales entregas_update'))
            {
                $validatorInput = $request->only('fecha', 'ndocumento', 'responsable', 'comentario', 'partes');
            
                $validatorRules = [
                    'fecha' => 'required|date_format:Y-m-d|before:tomorrow', // it includes today
                    'ndocumento' => 'nullable|min:1',
                    'responsable' => 'required|min:1',
                    'comentario' => 'sometimes|nullable',
                    'partes' => 'required|array|min:1',
                    'partes.*.id'  => 'required|exists:partes,id',
                    'partes.*.cantidad'  => 'required|numeric|min:1',
                    'partes.*.comentario'  => 'sometimes|nullable'
                ];
        
                $validatorMessages = [
                    'fecha.required' => 'Debes ingresar la fecha de despacho',
                    'fecha.date_format' => 'El formato de fecha de despacho es invalido',
                    'fecha.before' => 'La fecha debe ser igual o anterior a hoy',
                    'ndocumento.min' => 'El numero de documento debe tener al menos un digito',
                    'responsable.required' => 'Debes ingresar el nombre de la persona que despacha',
                    'responsable.min' => 'El nombre de la persona que despacha debe tener al menos un digito',
                    'partes.required' => 'Debes seleccionar las partes despachadas',
                    'partes.array' => 'Lista de partes despachadas invalida',
                    'partes.min' => 'El despacho debe contener al menos 1 parte despachada',
                    'partes.*.id.required' => 'La lista de partes despachadas es invalida',
                    'partes.*.id.exists' => 'La parte despachada ingresada no existe',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte despachada',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte despachada debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte despachada debe ser mayor a 0',
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
                    if($sucursal = Sucursal::where('id', $sucursal_id)->where('type', 'sucursal')->first())
                    {
                        $entrega = null;
                        $forbidden = false;

                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {

                                // If user belongs to the Sucursal's country
                                if($user->stationable->country->id === $sucursal->country->id)
                                {
                                    // Only if Entrega contains OcPartes from OCs generated from its same country
                                    $entrega = Entrega::select('entregas.*')
                                            ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('entregas.id', '=', $id) // For this Entrega
                                            ->where('entregas.sucursal_id', '=', $sucursal->id) // Delivered by Sucursal
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->first();
                                                
                                }
                                else
                                {
                                    // Set as forbidden
                                    $forbidden = true;
                                }

                                break;
                            }

                            // Vendedor
                            case 'seller': {

                                // If user belongs to this Sucursal
                                if(
                                    (get_class($user->stationable) === get_class($sucursal)) &&
                                    ($user->stationable->id === $sucursal->id)
                                )
                                {
                                    // Only if Entrega contains OcPartes from OCs generated from its same country
                                    $entrega = Entrega::select('entregas.*')
                                            ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                            ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                            ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                            ->where('entregas.id', '=', $id) // For this Entrega
                                            ->where('entregas.sucursal_id', '=', $sucursal->id) // Delivered by Sucursal
                                            ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                            ->first();
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
                        

                        if($entrega !== null)
                        {
                            // Clean partes list in request and store on diffList for validations and parteList for sync
                            $diffList = array();
                            $parteList = array();

                            foreach($request->partes as $parte)
                            {
                                if(in_array($parte['id'], array_keys($diffList)))
                                {
                                    $diffList[$parte['id']] += $parte['cantidad'];
                                    $parteList[$parte['id']] += $parte['cantidad'];
                                }
                                else
                                {
                                    $diffList[$parte['id']] = $parte['cantidad'];
                                    $parteList[$parte['id']] = $parte['cantidad'];
                                }
                            }

                            // For each OcParte in Entrega
                            foreach($entrega->ocpartes as $ocParte)
                            {
                                // If the Parte is already in list, it's kept in Entrega
                                if((in_array($ocParte->parte->id, array_keys($diffList))) === true)
                                {
                                    // Add the diff cantidad with cantidad given in request - old cantidad
                                    $diffList[$ocParte->parte->id] -= $ocParte->pivot->cantidad;
                                }
                                // If the OcParte isn't in the list, it's going to be removed and don't add it on the parteList (for sync)
                                else
                                {
                                    $diffList[$ocParte->parte->id] = ($ocParte->pivot->cantidad * -1);
                                }
                            }

                            DB::beginTransaction();

                            // Fill the data
                            $entrega->fill($request->all());

                            if($entrega->save())
                            {
                                $success = true;
                                $ocFullDelivered = true;

                                //Attaching each Parte to the Entrega
                                $syncData = [];
                                foreach(array_keys($diffList) as $parteId)
                                {
                                    if($p = $entrega->oc->partes->find($parteId))
                                    {
                                        // Calc new cantidad with cantidad total in Entregas + diff (negative when removing)
                                        $newCantidadEntregas = $p->pivot->getCantidadTotalEntregado() + $diffList[$parteId];

                                        // If new cantidad total in Entregas is equal or less than cantidad total in Oc
                                        if($newCantidadEntregas <= $p->pivot->cantidad)
                                        {
                                            // Calc new cantidad with cantidad in Entregas + diff (negative when removing)
                                            $newCantidad = $p->pivot->getCantidadEntregado($sucursal) + $diffList[$parteId];

                                            // If new cantidad in Entregas is equal or less than cantidad in Recepciones
                                            if($newCantidad <= $p->pivot->getCantidadRecepcionado($sucursal))
                                            {
                                                // If Parte is in the request
                                                if(in_array($parteId, array_keys($parteList)) === true)
                                                {
                                                    // All partes were delivered at Sucursal
                                                    if($newCantidadEntregas === $p->pivot->cantidad)
                                                    {
                                                        $p->pivot->estadoocparte_id = 3; // Estadoocparte = 'Entregado'
                                                    }
                                                    // If all partes were at least received at Solicitud's Comprador
                                                    else if($p->pivot->getCantidadRecepcionado($p->pivot->oc->cotizacion->solicitud->comprador) === $p->pivot->cantidad)
                                                    {
                                                        $p->pivot->estadoocparte_id = 2; // Estadoocparte = 'En transito'
                                                    }
                                                    else
                                                    {
                                                        $p->pivot->estadoocparte_id = 1; // Estadoocparte = 'Pendiente'
                                                    }

                                                    // Add the OcParte to sync using the ID which is unique
                                                    $syncData[$p->pivot->id] = array(
                                                        'cantidad' => $parteList[$parteId]
                                                    );
                                                }
                                            }
                                            else
                                            {
                                                // If the dispatched parts are more than waiting in queue
                                                $response = HelpController::buildResponse(
                                                    409,
                                                    'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor al stock disponible para entrega en la sucursal',
                                                    null
                                                );
            
                                                $success = false;
            
                                                break;
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
                                            // If the delivered partes are more than total in Oc
                                            $response = HelpController::buildResponse(
                                                409,
                                                'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor a la cantidad pendiente de entrega para la OC',
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

                                // Eval if all the OcPartes in Oc were fully delivered in Entregas
                                $ocFullDelivered = $entrega->oc->partes->reduce(function($carry, $parte)
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
                                    $entrega->oc->estadooc_id = 3; // Estadooc = 'Cerrada'
                                    if(!$entrega->oc->save())
                                    {
                                        $response = HelpController::buildResponse(
                                            500,
                                            'Error al actualizar el estado de la OC',
                                            null
                                        );
    
                                        $success = false;
                                    }
                                }

                                if($success === true)
                                {
                                    if($entrega->ocpartes()->sync($syncData))
                                    {
                                        DB::commit();
                                    
                                        $response = HelpController::buildResponse(
                                            200,
                                            'Entrega actualizada',
                                            null
                                        );
                                           
                                    }
                                    else
                                    {
                                        DB::rollback();

                                        $response = HelpController::buildResponse(
                                            500,
                                            'Error al actualizar la entrega',
                                            null
                                        );
    
                                        $success = false;
                                    }
                                }
                                else
                                {
                                    // Error message was already given
                                }
                            }
                            else
                            {       
                                DB::rollback();

                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al actualizar la entrega',
                                    null
                                );
                            }
                        }
                        else if($forbidden === true)
                        {
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a actualizar la entrega',
                                null
                            );
                        }
                        else
                        {
                            if(Entrega::find($id))
                            {
                                $response = HelpController::buildResponse(
                                    412,
                                    'La entrega no existe en la sucursal',
                                    null
                                );
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    412,
                                    'La entrega no existe',
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
                    'No tienes acceso a actualizar entregas para sucursal',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al actualizar la entrega [!]',
                null
            );
        }
        
        return $response;
    }

    public function destroy_sucursal(Request $request, $sucursal_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales entregas_update'))
            {
                if($sucursal = Sucursal::where('id', $sucursal_id)->where('type', 'sucursal')->first())
                {
                    $entrega = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // If user belongs to the Sucursal's country
                            if($user->stationable->country->id === $sucursal->country->id)
                            {
                                // Only if Entrega contains OcPartes from OCs generated from its same country
                                $entrega = Entrega::select('entregas.*')
                                        ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('entregas.id', '=', $id) // For this Entrega
                                        ->where('entregas.sucursal_id', '=', $sucursal->id) // Delivered by Sucursal
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->first();
                                            
                            }
                            else
                            {
                                // Set as forbidden
                                $forbidden = true;
                            }

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // If user belongs to this Sucursal
                            if(
                                (get_class($user->stationable) === get_class($sucursal)) &&
                                ($user->stationable->id === $sucursal->id)
                            )
                            {
                                // Only if Entrega contains OcPartes from OCs generated from its same country
                                $entrega = Entrega::select('entregas.*')
                                        ->join('ocs', 'ocs.id', '=', 'entregas.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales AS sucursalsolicitud', 'sucursalsolicitud.id', '=', 'solicitudes.sucursal_id') // Sucursal where solicitud was generated at
                                        ->join('faenas', 'faenas.id', '=', 'solicitudes.faena_id') // Faena the Solicitud was generated for
                                        ->join('sucursales AS sucursalfaena', 'sucursalfaena.id', '=', 'faenas.sucursal_id') // Sucursal where faena is delivered
                                        ->where('entregas.id', '=', $id) // For this Entrega
                                        ->where('entregas.sucursal_id', '=', $sucursal->id) // Delivered by Sucursal
                                        ->where('sucursalsolicitud.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('sucursalfaena.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                        ->first();
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
                    

                    if($entrega !== null)
                    {
                        DB::beginTransaction();

                        $success = true;

                        foreach($entrega->ocpartes as $ocParte)
                        {
                            // If all partes were at least received at Solicitud's Comprador
                            if($ocParte->getCantidadRecepcionado($ocParte->oc->cotizacion->solicitud->comprador) === $ocParte->cantidad)
                            {
                                $ocParte->estadoocparte_id = 2; // Estadoocparte = 'En transito'
                            }
                            else
                            {
                                $ocParte->estadoocparte_id = 1; // Estadoocparte = 'Pendiente'
                            }

                            // If fails on saving the new status for OcParte
                            if(!($ocParte->save()))
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al cambiar el estado de la parte "' . $ocParte->parte->nparte . '"',
                                    null
                                );

                                $success = false;

                                break;
                            }
                        }
            
                        // Oc goes back to estadooc = 'En proceso'
                        $entrega->oc->estadooc_id = 2; // Estadooc = 'En proceso'
                        if(!$entrega->oc->save())
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al actualizar el estado de la OC',
                                null
                            );

                            $success = false;
                        }

                        if(($success === true) && ($entrega->delete()))
                        {  
                            DB::commit();

                            $response = HelpController::buildResponse(
                                200,
                                'Entrega eliminada',
                                null
                            );
                        }
                        else
                        {
                            DB::rollback();

                            // Error message was already given
                        }
                    }
                    else if($forbidden === true)
                    {
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a eliminar la entrega',
                            null
                        );
                    }
                    else
                    {
                        if(Entrega::find($id))
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La entrega no existe en la sucursal',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La entrega no existe',
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
                    'No tienes acceso a eliminar entregas para sucursal',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al eliminar la entrega [!]',
                null
            );
        }
        
        return $response;
    }
}
