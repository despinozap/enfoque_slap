<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Comprador;
use App\Models\Despacho;
use App\Models\Sucursal;

class DespachosController extends Controller
{

    /*
     *  Compradores 
     */
    public function index_comprador($id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores despachos_index'))
            {
                if($comprador = Comprador::find($id))
                {
                    $despachos = null;
                    $oc = null;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            $despachos = Despacho::all();

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            $despachos = Despacho::all();

                            break;
                        }

                        // Agente de compra
                        case 'agtcom': {

                            $despachos = Despacho::all();

                            break;
                        }

                        default:
                        {
                            break;
                        }
                    }

                    if($despachos !== null)
                    {
                        $despachos = $despachos->map(function($carry, $despacho)
                            {
                                $despacho->partes_total;
                                
                                $despacho->makeHidden([
                                    'despachable_id', 
                                    'despachable_type',
                                    'destinable_id', 
                                    'destinable_type', 
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $despacho->ocpartes;
                                $despacho->ocpartes = $despacho->ocpartes->map(function($ocParte)
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
                                        'despacho_id',
                                        'ocparte_id',
                                        'created_at',
                                        'updated_at'
                                    ]);

                                    return $ocParte;
                                });

                                $despacho->destinable;
                                $despacho->destinable->makeHidden([
                                    'created_at', 
                                    'updated_at'
                                ]);
                                
                                array_push($carry, $recepcion);

                                return $carry;
                            },
                            array()
                        );

                        $response = HelpController::buildResponse(
                            200,
                            null,
                            $despachos
                        );
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            500,
                            'Error al obtener la lista de despachos',
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
                    'No tienes acceso a visualizar despachos de compradores',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener las despachos del comprador [!]',
                null
            );
        }
            
        return $response;
    }

    public function queueOcs_comprador($comprador_id, $centrodistribucion_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores despachos_store'))
            {
                if($comprador = Comprador::find($comprador_id))
                {
                    if($centrodistribucion = Sucursal::find($centrodistribucion_id))
                    {
                        $ocParteList = null;

                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {

                                $ocParteList = OcParte::select('oc_parte.*')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->where('solicitudes.comprador_id', '=', $comprador->id) // For this Comprador
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'\
                                            ->get();

                                break;
                            }

                            // Agente de compra
                            case 'agtcom': {

                                $ocParteList = OcParte::select('oc_parte.*')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->where('solicitudes.comprador_id', '=', $comprador->id) // For this Comprador
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'\
                                            ->get();

                                break;
                            }

                            default:
                            {
                                break;
                            }
                        }

                        if($ocParteList !== null)
                        {
                            $queueOcs = $ocParteList->reduce(function($carry, $ocParte) use ($comprador)
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
                    'No tienes acceso a visualizar OCs pendiente de despacho',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener OCs pendiente de despacho [!]',
                null
            );
        }
            
        return $response;
    }

}
