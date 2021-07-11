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
                    $forbidden = false;

                    switch($user->role->name)
                    {

                        // Administrador
                        case 'admin': {

                            // Get only Despachos containing OcPartes from OCs generated from its same country
                            $despachos = Despacho::select('despachos.*')
                                        ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                        ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                        ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                        ->where('despachos.despachable_type', '=', get_class($comprador))
                                        ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                        ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->groupBy('despachos.id')
                                        ->get();

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // Get only Despachos containing OcPartes from belonging OCs generated from its same Sucursal
                            $despachos = Despacho::select('despachos.*')
                                        ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                        ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                        ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->where('despachos.despachable_type', '=', get_class($comprador))
                                        ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                        ->where('solicitudes.sucursal_id', '=', $user->stationable->id) // Same Sucursal as user station
                                        ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                        ->groupBy('despachos.id')
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
                                // Get only Despachos from its Comprador
                                $despachos = Despacho::select('despachos.*')
                                            ->where('despachos.despachable_type', '=', get_class($comprador))
                                            ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
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
                                // Get only Despachos from its Comprador
                                $despachos = Despacho::select('despachos.*')
                                            ->where('despachos.despachable_type', '=', get_class($comprador))
                                            ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
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
                                // Get only Despachos containing OcPartes from OCs generated from its same country
                                $despachos = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('despachos.despachable_type', '=', get_class($comprador))
                                            ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->groupBy('despachos.id')
                                            ->get();
                            }
                            // If user belongs to Sucursal
                            else if($user->stationable->type === 'sucursal')
                            {
                                // Get only Despachos containing OcPartes from belonging OCs generated from its same Sucursal
                                $despachos = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->where('despachos.despachable_type', '=', get_class($comprador))
                                            ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                            ->where('solicitudes.sucursal_id', '=', $user->stationable->id) // Same Sucursal as user station
                                            ->groupBy('despachos.id')
                                            ->get();
                            }
                            

                            break;
                        }

                        default:
                        {
                            break;
                        }
                    }

                    if($despachos !== null)
                    {
                        $despachos = $despachos->map(function($despacho)
                            {
                                $despacho->partes_total;
                                        
                                $despacho->makeHidden([
                                    'despachable_id', 
                                    'despachable_type',
                                    'destinable_id', 
                                    'destinable_type', 
                                    'ocpartes',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $despacho->despachable;
                                $despacho->despachable->makeHidden([
                                    'rut',
                                    'address',
                                    'city',
                                    'contact',
                                    'phone',
                                    'country_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $despacho->destinable;
                                $despacho->destinable->makeHidden([
                                    'type',
                                    'rut',
                                    'address',
                                    'city',
                                    'country_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $despacho->destinable->country;
                                $despacho->destinable->country->makeHidden([
                                    'created_at',
                                    'updated_at',
                                ]);

                                return $despacho;
                            }
                        );

                        $response = HelpController::buildResponse(
                            200,
                            null,
                            $despachos
                        );
                    }
                    else if($forbidden === true)
                    {
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a visualizar los despachos',
                            null
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
                'Error al obtener los despachos del comprador [!]',
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
                                        ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                        ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                        ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                        ->where('recepciones.recepcionable_id', '=', $comprador->id) // Recepciones received at Comprador
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
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Recepciones received at Comprador
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
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Recepciones received at Comprador
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
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
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
                                    // Get only OcPartes on OCs generated from the Sucursal's (centro) country and received at Comprador
                                    $ocParteList = OcParte::select('oc_parte.*')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
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
                                    // Get only OcPartes on OCs generated from the Sucursal's (centro) country and received at Comprador
                                    $ocParteList = OcParte::select('oc_parte.*')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
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
                'Error al obtener OCs pendiente de despacho [!]',
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
                                            ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                            ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                            ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
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
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
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
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
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
                                                        'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor a la cantidad pendiente de despacho en la OC: ' . $oc->id,
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
                                                    'La parte "' . $p->nparte . '" no tiene partes pendiente de despacho en la OC: ' . $oc->id,
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
                'Error al crear el despacho [!]',
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
            if($user->role->hasRoutepermission('compradores despachos_show'))
            {
                $validatorInput = ['despacho_id' => $id];
            
                $validatorRules = [
                    'despacho_id' => 'required|exists:despachos,id,despachable_id,' . $comprador_id . ',despachable_type,' . get_class(new Comprador()),
                ];
        
                $validatorMessages = [
                    'despacho_id.required' => 'Debes ingresar el despacho',
                    'despacho_id.exists' => 'El despacho ingresado no existe para el comprador',                    
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
                        $despacho = null;

                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {
                                
                                // Only if Despacho contains OcPartes from OCs generated from its same country
                                $despacho = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('despachos.id', '=', $id) // For this Despacho
                                            ->where('despachos.despachable_type', '=', get_class($comprador))
                                            ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->first();

                                break;
                            }

                            // Vendedor
                            case 'seller': {

                                // Only if Despacho contains OcPartes from belonging OCs generated from its same Sucursal
                                $despacho = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->where('despachos.id', '=', $id) // For this Despacho
                                            ->where('despachos.despachable_type', '=', get_class($comprador))
                                            ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
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
                                    // Only if Despacho was dispatched by Comprador
                                    $despacho = Despacho::select('despachos.*')
                                                ->where('despachos.id', '=', $id) // For this Despacho
                                                ->where('despachos.despachable_type', '=', get_class($comprador))
                                                ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
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
                                    // Only if Despacho was dispatched by Comprador
                                    $despacho = Despacho::select('despachos.*')
                                                ->where('despachos.id', '=', $id) // For this Despacho
                                                ->where('despachos.despachable_type', '=', get_class($comprador))
                                                ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                                ->first();
                                }

                                break;
                            }

                            // Coordinador logistico at Sucursal (or Centro)
                            case 'colsol': {

                                // If user belongs to Sucursal (centro)
                                if($user->stationable->type === 'centro')
                                {
                                    // Only if Despacho contains OcPartes from OCs generated from its same country
                                    $despacho = Despacho::select('despachos.*')
                                                ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                                ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('despachos.id', '=', $id) // For this Despacho
                                                ->where('despachos.despachable_type', '=', get_class($comprador))
                                                ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                                ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->first();
                                }
                                // If user belongs to Sucursal
                                else if($user->stationable->type === 'sucursal')
                                {
                                    // Only if Despacho contains OcPartes from belonging OCs generated from its same Sucursal
                                    $despacho = Despacho::select('despachos.*')
                                                ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                                ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->where('despachos.id', '=', $id) // For this Despacho
                                                ->where('despachos.despachable_type', '=', get_class($comprador))
                                                ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                                ->where('solicitudes.sucursal_id', '=', $user->stationable->id) // Same Sucursal as user station
                                                ->first();
                                }
                                

                                break;
                            }
                            

                            default: {
                                break;
                            }
                        }
                        
                        if($despacho !== null)
                        {           
                            $despacho->makeHidden([
                                'despachable_id', 
                                'despachable_type',
                                'destinable_id', 
                                'destinable_type', 
                                'partes_total',
                                'created_at', 
                                'updated_at'
                            ]);

                            $despacho->despachable;
                            $despacho->despachable->makeHidden([
                                'rut',
                                'address',
                                'city',
                                'contact',
                                'phone',
                                'country_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $despacho->destinable;
                            $despacho->destinable->makeHidden([
                                'type',
                                'rut',
                                'address',
                                'city',
                                'country_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $despacho->destinable->country;
                            $despacho->destinable->country->makeHidden([
                                'created_at',
                                'updated_at',
                            ]);

                            $despacho->ocpartes = $despacho->ocpartes->map(function($ocParte)
                                {
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
                                        'despacho_id',
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

                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $despacho
                            );
              
                        }
                        // If wasn't catched
                        else
                        {
                            // If Despacho exists
                            if(Despacho::find($id))
                            {
                                // It was filtered, so it's forbidden
                                $response = HelpController::buildResponse(
                                    405,
                                    'No tienes acceso a visualizar el despacho',
                                    null
                                );
                            }
                            // It doesn't exist
                            else
                            {
                                $response = HelpController::buildResponse(
                                    412,
                                    'El despacho no existe',
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
                    'No tienes acceso a visualizar despachos de comprador',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener el despacho [!]',
                null
            );
        }
        
        return $response;
    }

    /**
     * It retrieves all the required info for
     * selecting data and updating a Despacho for Comprador
     * 
     */
    public function update_prepare_comprador($comprador_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores despachos_update'))
            {
                $validatorInput = ['despacho_id' => $id];
            
                $validatorRules = [
                    'despacho_id' => 'required|exists:despachos,id,despachable_id,' . $comprador_id . ',despachable_type,' . get_class(new Comprador()),
                ];
        
                $validatorMessages = [
                    'despacho_id.required' => 'Debes ingresar el despacho',
                    'despacho_id.exists' => 'El despacho ingresado no existe para el comprador',                    
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
                        $ocParteList = null;
                        $despacho = null;

                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {

                                // Only if Despacho contains OcPartes from OCs generated from its same country
                                $despacho = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('despachos.id', '=', $id) // For this Despacho
                                            ->where('despachos.despachable_type', '=', get_class($comprador))
                                            ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->first();

                                if($despacho !== null)
                                {
                                    // Get only OcPartes (queue) on OCs generated from its country and received at Comprador
                                    $ocParteList = OcParte::select('oc_parte.*')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                                ->where('sucursales.country_id', '=', $despacho->destinable->country->id) // Same Country as Sucursal (centro)
                                                ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->groupBy('oc_parte.id')
                                                ->get();

                                    // For OcPartes in current Despacho
                                    $ocParteList = $despacho->ocpartes->reduce(function($carry, $ocParte) use ($ocParteList)
                                        {
                                            $contains = $carry->contains(function($op) use ($ocParte)
                                                {
                                                    return ($ocParte->id === $op->id);
                                                }
                                            );

                                            // If OcParte from Despacho isn't in queue
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
                                    // Only if Despacho was dispatched by Comprador
                                    $despacho = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('despachos.id', '=', $id) // For this Despacho
                                            ->where('despachos.despachable_type', '=', get_class($comprador))
                                            ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                            ->first();

                                    if($despacho !== null)
                                    {
                                        // Get only OcPartes (queue) on OCs generated from its country and received at Comprador
                                        $ocParteList = OcParte::select('oc_parte.*')
                                                    ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                    ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                    ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                    ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                                    ->where('sucursales.country_id', '=', $despacho->destinable->country->id) // Same Country as Sucursal (centro)
                                                    ->groupBy('oc_parte.id')
                                                    ->get();

                                        // For OcPartes in current Despacho
                                        $ocParteList = $despacho->ocpartes->reduce(function($carry, $ocParte) use ($ocParteList)
                                            {
                                                $contains = $carry->contains(function($op) use ($ocParte)
                                                    {
                                                        return ($ocParte->id === $op->id);
                                                    }
                                                );

                                                // If OcParte from Despacho isn't in queue
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

                            // Coordinador logistico at Comprador
                            case 'colcom': {

                                // If user belongs to this Comprador
                                if(
                                    (get_class($user->stationable) === get_class($comprador)) &&
                                    ($user->stationable->id === $comprador->id)
                                )
                                {
                                    // Only if Despacho was dispatched by Comprador
                                    $despacho = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('despachos.id', '=', $id) // For this Despacho
                                            ->where('despachos.despachable_type', '=', get_class($comprador))
                                            ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                            ->first();

                                    if($despacho !== null)
                                    {
                                        // Get only OcPartes (queue) on OCs generated from its country and received at Comprador
                                        $ocParteList = OcParte::select('oc_parte.*')
                                                    ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                    ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                    ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                    ->where('recepciones.recepcionable_id', '=', $comprador->id) // Received at Comprador
                                                    ->where('sucursales.country_id', '=', $despacho->destinable->country->id) // Same Country as Sucursal (centro)
                                                    ->groupBy('oc_parte.id')
                                                    ->get();

                                        // For OcPartes in current Despacho
                                        $ocParteList = $despacho->ocpartes->reduce(function($carry, $ocParte) use ($ocParteList)
                                            {
                                                $contains = $carry->contains(function($op) use ($ocParte)
                                                    {
                                                        return ($ocParte->id === $op->id);
                                                    }
                                                );

                                                // If OcParte from Despacho isn't in queue
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
                            ($despacho !== null)
                        )
                        {   
                            $queueOcPartes = $ocParteList->reduce(function($carry, $ocParte) use ($comprador, $despacho)
                                {
                                    $cantidadRecepcionado = $ocParte->getCantidadRecepcionado($comprador);
                                    $cantidadDespachado = $ocParte->getCantidadDespachado($comprador);

                                    // Try to find OcParte in Despacho
                                    $op = $despacho->ocpartes->find($ocParte->id);

                                    if(
                                        // If OcParte is in Despacho
                                        ($op !== null) ||
                                        // Or if OcParte isn't in Despacho and hasn't been full dispatched yet
                                        (($op === null) && ($cantidadDespachado < $cantidadRecepcionado))
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
                                        // Set minimum cantidad as total cantidad in Recepciones at destinable Sucursal (centro)
                                        $ocParte->cantidad_min = $ocParte->getCantidadRecepcionado($despacho->destinable);

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

                            $despacho->makeHidden([
                                'despachable_id', 
                                'despachable_type',
                                'destinable_id', 
                                'destinable_type', 
                                'partes_total',
                                'created_at', 
                                'updated_at'
                            ]);

                            $despacho->despachable;
                            $despacho->despachable->makeHidden([
                                'rut',
                                'address',
                                'city',
                                'contact',
                                'phone',
                                'country_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $despacho->destinable;
                            $despacho->destinable->makeHidden([
                                'type',
                                'rut',
                                'address',
                                'city',
                                'country_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $despacho->destinable->country;
                            $despacho->destinable->country->makeHidden([
                                'created_at',
                                'updated_at',
                            ]);

                            $despacho->ocpartes = $despacho->ocpartes->map(function($ocParte) use ($comprador)
                                {
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
                                        'despacho_id',
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
                                "despacho" => $despacho
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
                            // If Despacho exists
                            if(Despacho::find($id))
                            {
                                // It was filtered, so it's forbidden
                                $response = HelpController::buildResponse(
                                    405,
                                    'No tienes acceso a actualizar el despacho',
                                    null
                                );
                            }
                            // It doesn't exist
                            else
                            {
                                $response = HelpController::buildResponse(
                                    412,
                                    'El despacho no existe',
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
                    'No tienes acceso a actualizar despachos de comprador',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener el despacho [!]',
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
            if($user->role->hasRoutepermission('compradores despachos_update'))
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
                    'fecha.required' => 'Debes ingresar la fecha de despacho',
                    'fecha.date_format' => 'El formato de fecha de despacho es invalido',
                    'fecha.before' => 'La fecha debe ser igual o anterior a hoy',
                    'ndocumento.min' => 'El numero de documento debe tener al menos un digito',
                    'responsable.required' => 'Debes ingresar el nombre de la persona que despacha',
                    'responsable.min' => 'El nombre de la persona que despacha debe tener al menos un digito',
                    'ocs.required' => 'Debes seleccionar las partes despachadas',
                    'ocs.array' => 'Lista de partes despachadas invalida',
                    'ocs.min' => 'El despacho debe contener al menos 1 parte despachada',
                    'ocs.*.id.required' => 'Debes seleccionar la OC a despachar',
                    'ocs.*.id.exists' => 'La OC ingresada no existe',
                    'ocs.*.partes.required' => 'Debes seleccionar las partes despachadas',
                    'ocs.*.partes.array' => 'Lista de partes despachadas invalida',
                    'ocs.*.partes.min' => 'El despacho debe contener al menos 1 parte despachada',
                    'ocs.*.partes.*.id.required' => 'La lista de partes despachadas es invalida',
                    'ocs.*.partes.*.id.exists' => 'La parte despachada ingresada no existe',
                    'ocs.*.partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte despachada',
                    'ocs.*.partes.*.cantidad.numeric' => 'La cantidad para la parte despachada debe ser numerica',
                    'ocs.*.partes.*.cantidad.min' => 'La cantidad para la parte despachada debe ser mayor a 0',
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
                    $despacho = null;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {
                            
                            // Only if Despacho contains OcPartes from OCs generated from its same country
                            $despacho = Despacho::select('despachos.*')
                                        ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                        ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                        ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                        ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                        ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                        ->where('despachos.id', '=', $id) // For this Despacho
                                        ->where('despachos.despachable_type', '=', get_class($comprador))
                                        ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
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
                                // Only if Despacho was dispatched by Comprador
                                $despacho = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('despachos.id', '=', $id) // For this Despacho
                                            ->where('despachos.despachable_type', '=', get_class($comprador))
                                            ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
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
                                // Only if Despacho was dispatched by Comprador
                                $despacho = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('despachos.id', '=', $id) // For this Despacho
                                            ->where('despachos.despachable_type', '=', get_class($comprador))
                                            ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                            ->first();
                            }

                            break;
                        }

                        default: {
                            break;
                        }
                    }

                    if($despacho !== null)
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

                        // For each OcParte in Despacho
                        foreach($despacho->ocpartes as $ocParte)
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
                                // If the Parte is already in list, it's kept in Despacho
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
                        $despacho->fill($request->all());

                        if($despacho->save())
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
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=' , 'oc_parte.id')
                                                ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('ocs.id', '=', $ocId)
                                                ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                ->where('recepciones.recepcionable_id', '=', $comprador->id) // OcParte in Recepcion at Comprador
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
                                                    ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                    ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=' , 'oc_parte.id')
                                                    ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('ocs.id', '=', $ocId)
                                                    ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                    ->where('recepciones.recepcionable_id', '=', $comprador->id) // OcParte in Recepcion at Comprador
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
                                                    ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=' , 'oc_parte.id')
                                                    ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('ocs.id', '=', $ocId)
                                                    ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                    ->where('recepciones.recepcionable_id', '=', $comprador->id) // OcParte in Recepcion at Comprador
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
                                                // Calc new cantidad with cantidad in Despachos + diff (negative when removing)
                                                $newCantidad = $p->pivot->getCantidadDespachado($comprador) + $diffList[$oc->id][$parteId];

                                                // If new cantidad in Despachos is equal or more than received at destination Sucursal (centro)
                                                if($newCantidad >= $p->pivot->getCantidadRecepcionado($despacho->destinable))
                                                {
                                                    // If new cantidad in Despachos is equal or less than cantidad in Recepciones
                                                    if($newCantidad <= $p->pivot->getCantidadRecepcionado($comprador))
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
                                                        // If the dispatched parts are more than waiting in queue
                                                        $response = HelpController::buildResponse(
                                                            409,
                                                            'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor a la cantidad pendiente de despacho en la OC: ' . $oc->id,
                                                            null
                                                        );
                    
                                                        $success = false;
                    
                                                        break;
                                                    }
                                                }
                                                else
                                                {
                                                    // If the dispatched partes are less than received at destination Sucursal (centro)
                                                    $response = HelpController::buildResponse(
                                                        409,
                                                        'La cantidad ingresada para la parte "' . $p->nparte . '" es menor a la cantidad ya recepcionada en el centro de distribucion para la OC: ' . $oc->id,
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
                                                'No tienes acceso a actualizar despachos para la OC: ' . $ocId,
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

                            if(($success === true) && ($despacho->ocpartes()->sync($syncData)))
                            {
                                DB::commit();
                                    
                                $response = HelpController::buildResponse(
                                    201,
                                    'Despacho actualizado',
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
                                'Error al actualizar el despacho',
                                null
                            );
                        }
                    }
                    // If wasn't catched
                    else
                    {
                        // If Despacho exists
                        if(Despacho::find($id))
                        {
                            // It was filtered, so it's forbidden
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a actualizar el despacho',
                                null
                            );
                        }
                        // It doesn't exist
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'El despacho no existe',
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
                    'No tienes acceso a actualizar despachos para comprador',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al actualizar el despacho [!]',
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
                    $despacho = null;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {
                            
                            // Only if Despacho contains OcPartes from OCs generated from its same country
                            $despacho = Despacho::select('despachos.*')
                                        ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                        ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                        ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                        ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                        ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                        ->where('despachos.id', '=', $id) // For this Despacho
                                        ->where('despachos.despachable_type', '=', get_class($comprador))
                                        ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
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
                                // Only if Despacho was dispatched by Comprador
                                $despacho = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('despachos.id', '=', $id) // For this Despacho
                                            ->where('despachos.despachable_type', '=', get_class($comprador))
                                            ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
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
                                // Only if Despacho was dispatched at Comprador
                                $despacho = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('despachos.id', '=', $id) // For this Despacho
                                            ->where('despachos.despachable_type', '=', get_class($comprador))
                                            ->where('despachos.despachable_id', '=', $comprador->id) // Dispatched by Comprador
                                            ->first();
                            }

                            break;
                        }

                        default: {
                            break;
                        }
                    }

                    if($despacho !== null)
                    {
                        $ocList = array();

                        // For each OcParte in Despacho
                        foreach($despacho->ocpartes as $ocParte)
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
                                            ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
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
                                            // Calc new cantidad with cantidad in Despachos + diff (negative when removing)
                                            $newCantidad = $p->pivot->getCantidadDespachado($comprador) + $ocList[$oc->id][$parteId];

                                            // If new cantidad in Despachos is less than received at destination Sucursal (centro)
                                            if($newCantidad < $p->pivot->getCantidadRecepcionado($despacho->destinable))
                                            {
                                                $response = HelpController::buildResponse(
                                                    409,
                                                    'La parte "' . $p->nparte . '" tiene cantidades ya recepcionadas en el centro de distribucion para la OC: ' . $oc->id,
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
                                                'Error al eliminar el despacho',
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
                                            'No tienes acceso a eliminar despachos para la OC: ' . $ocId,
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

                        if(($success === true) && ($despacho->delete()))
                        {  
                            $response = HelpController::buildResponse(
                                200,
                                'Despacho eliminado',
                                null
                            );
                        }
                    }
                    // If wasn't catched
                    else
                    {
                        // If Despacho exists
                        if(Despacho::find($id))
                        {
                            // It was filtered, so it's forbidden
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a eliminar el despacho',
                                null
                            );
                        }
                        // It doesn't exist
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'El despacho no existe',
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
                    'No tienes acceso a eliminar despachos para comprador',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al eliminar el despacho [!]',
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
            if($user->role->hasRoutepermission('sucursales despachos_index'))
            {
                if($centrodistribucion = Sucursal::where('type', '=', 'centro')->where('id', '=', $id)->first())
                {
                    $despachos = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {

                        // Administrador
                        case 'admin': {

                            // Get only Despachos containing OcPartes from OCs generated from its same country
                            $despachos = Despacho::select('despachos.*')
                                        ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                        ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                        ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                        ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                        ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                        ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                        ->groupBy('despachos.id')
                                        ->get();

                            break;
                        }

                        // Vendedor
                        case 'seller': {

                            // Get only Despachos containing OcPartes from belonging OCs generated from its same Sucursal
                            $despachos = Despacho::select('despachos.*')
                                        ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                        ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                        ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                        ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                        ->where('solicitudes.sucursal_id', '=', $user->stationable->id) // Same Sucursal as user station
                                        ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
                                        ->groupBy('despachos.id')
                                        ->get();

                            break;
                        }

                        // Coordinador logistico at Sucursal (or Centro)
                        case 'colsol': {

                            // If user belongs to Sucursal (centro)
                            if($user->stationable->type === 'centro')
                            {
                                // Get only Despachos containing OcPartes from OCs generated from its same country
                                $despachos = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                            ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->groupBy('despachos.id')
                                            ->get();
                            }
                            // If user belongs to Sucursal
                            else if($user->stationable->type === 'sucursal')
                            {
                                // Get only Despachos containing OcPartes from belonging OCs generated from its same Sucursal
                                $despachos = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                            ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                            ->where('solicitudes.sucursal_id', '=', $user->stationable->id) // Same Sucursal as user station
                                            ->groupBy('despachos.id')
                                            ->get();
                            }
                            

                            break;
                        }

                        default:
                        {
                            break;
                        }
                    }

                    if($despachos !== null)
                    {
                        $despachos = $despachos->map(function($despacho)
                            {
                                $despacho->partes_total;
                                        
                                $despacho->makeHidden([
                                    'despachable_id', 
                                    'despachable_type',
                                    'destinable_id', 
                                    'destinable_type', 
                                    'ocpartes',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $despacho->despachable;
                                $despacho->despachable->makeHidden([
                                    'type',
                                    'rut',
                                    'address',
                                    'country_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $despacho->destinable;
                                $despacho->destinable->makeHidden([
                                    'type',
                                    'rut',
                                    'address',
                                    'city',
                                    'country_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                return $despacho;
                            }
                        );

                        $response = HelpController::buildResponse(
                            200,
                            null,
                            $despachos
                        );
                    }
                    else if($forbidden === true)
                    {
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a visualizar los despachos',
                            null
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
                        'El centro de distribucion no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar despachos de centros de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener los despachos del centro de distribucion [!]' . $e,
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
            if($user->role->hasRoutepermission('sucursales despachos_store'))
            {
                if($centrodistribucion = Sucursal::where('type', '=', 'centro')->where('id', '=', $centrodistribucion_id)->first())
                {
                    $sucursalList = null;
                    $forbidden = false;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {

                            // If user belongs to the Sucursal' (centro) country
                            if($user->stationable->country->id === $centrodistribucion->country->id)
                            {
                                // Get only Sucursales in the same Country from where Ocs were generated and OcPartes were received at Sucursal (centro)
                                $sucursalList = Sucursal::select('sucursales.*')
                                            ->join('solicitudes', 'solicitudes.sucursal_id', '=', 'sucursales.id')
                                            ->join('cotizaciones', 'cotizaciones.solicitud_id', '=', 'solicitudes.id')
                                            ->join('ocs', 'ocs.cotizacion_id', '=', 'cotizaciones.id')
                                            ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id') // Only for OcPartes in Recepciones
                                            ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                            ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Recepciones received at Sucursal (centro)
                                            ->where('sucursales.type', '=', 'sucursal')
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

                        // Coordinador logistico at Sucursal (or Centro)
                        case 'colsol': {

                            // If user belongs to this Sucursal (centro)
                            if(
                                (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                ($user->stationable->id === $centrodistribucion->id)
                            )
                            {
                                // Get only Sucursales in the same Country from where Ocs were generated and OcPartes were received at Sucursal (centro)
                                $sucursalList = Sucursal::select('sucursales.*')
                                            ->join('solicitudes', 'solicitudes.sucursal_id', '=', 'sucursales.id')
                                            ->join('cotizaciones', 'cotizaciones.solicitud_id', '=', 'solicitudes.id')
                                            ->join('ocs', 'ocs.cotizacion_id', '=', 'cotizaciones.id')
                                            ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id') // Only for OcPartes in Recepciones
                                            ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                            ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Recepciones received at Sucursal (centro)
                                            ->where('sucursales.type', '=', 'sucursal')
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

                    if($sucursalList !== null)
                    {
                        $sucursalList = $sucursalList->map(function($sucursal)
                            {
                                $sucursal->makeHidden([
                                    'type',
                                    'rut',
                                    'address',
                                    'city',
                                    'country_id',
                                    'created_at',
                                    'updated_at'
                                ]);

                                $sucursal->country;
                                $sucursal->country->makeHidden(['created_at', 'updated_at']);

                                return $sucursal;
                            }
                        );

                    
                        $data = [
                            "sucursales" => $sucursalList
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
                            'No tienes acceso registrar despachos para el centro de distribucion',
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
                        'El centro de distribucion no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a registrar despachos para centro de distribucion',
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

    public function queueOcPartes_centrodistribucion($centrodistribucion_id, $sucursal_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales despachos_store'))
            {
                if($centrodistribucion = Sucursal::where('type', '=', 'centro')->where('id', '=', $centrodistribucion_id)->first())
                {
                    if($sucursal = Sucursal::where('id', '=', $sucursal_id)->where('type', '=', 'sucursal')->first())
                    {
                        $ocParteList = null;
                        $forbidden = false;
    
                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {

                                // If user belongs to the Sucursal' (centro) country
                                if($user->stationable->country->id === $centrodistribucion->country->id)
                                {
                                    // Get only OcPartes on OCs generated from the given Sucursal and received at Sucursal (centro)
                                    $ocParteList = OcParte::select('oc_parte.*')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                                ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                                ->where('sucursales.id', '=', $sucursal->id) // Solicitudes belonging to given Sucursal
                                                ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
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

                            // Coordinador logistico at Sucursal (or Centro)
                            case 'colsol': {

                                // If user belongs to this Sucursal (centro)
                                if(
                                    (get_class($user->stationable) === get_class($centrodistribucion)) &&
                                    ($user->stationable->id === $centrodistribucion->id)
                                )
                                {
                                    // Get only OcPartes on OCs generated from the given Sucursal and received at Sucursal (centro)
                                    $ocParteList = OcParte::select('oc_parte.*')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                                ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                                ->where('sucursales.id', '=', $sucursal->id) // Solicitudes belonging to given Sucursal
                                                ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                            
                            default: {
                                break;
                            }
                        }

                        if($ocParteList !== null)
                        {
                            $queueOcPartes = $ocParteList->reduce(function($carry, $ocParte) use ($centrodistribucion)
                                {
                                    $cantidadRecepcionado = $ocParte->getCantidadRecepcionado($centrodistribucion);
                                    $cantidadDespachado = $ocParte->getCantidadDespachado($centrodistribucion);
                                    $cantidadEntregado = $ocParte->getCantidadEntregado($centrodistribucion);

                                    // Add to list only if has stock at Sucursal (centro)
                                    if($cantidadDespachado + $cantidadEntregado < $cantidadRecepcionado)
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
                                        $ocParte->cantidad_entregado = $cantidadEntregado;

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
                            'La sucursal no existe',
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

    public function store_centrodistribucion(Request $request, $centrodistribucion_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales despachos_store'))
            {
                $validatorInput = $request->only('sucursal_id', 'fecha', 'ndocumento', 'responsable', 'comentario', 'ocs');
            
                $validatorRules = [
                    'sucursal_id' => 'required|exists:sucursales,id,type,"sucursal"',
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
                    'sucursal_id.required' => 'Debes seleccionar la sucursal',
                    'sucursal_id.exists' => 'La sucursal no existe',
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
                else if(($sucursal = Sucursal::where('type', '=', 'sucursal')->where('id', '=', $request->sucursal_id)->first()) === null)
                {
                    $response = HelpController::buildResponse(
                        412,
                        'La sucursal seleccionada no existe',
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
                    // Set the morph source for Despacho as Sucursal (centro)
                    $despacho->despachable_id = $centrodistribucion->id;
                    $despacho->despachable_type = get_class($centrodistribucion);
                    // Set the morph destination for Despacho as Sucursal
                    $despacho->destinable_id = $sucursal->id;
                    $despacho->destinable_type = get_class($sucursal);
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
                                            ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                            ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                            ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                            ->where('solicitudes.sucursal_id', '=', $sucursal->id) // Solicitudes for this Sucursal
                                            ->where('sucursales.country_id', '=', $centrodistribucion->country->id) // Same Country as Sucursal (centro)
                                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                            $oc = Oc::select('ocs.*')
                                                ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                                ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                                ->where('solicitudes.sucursal_id', '=', $sucursal->id) // Solicitudes for this Sucursal
                                                ->where('sucursales.country_id', '=', $centrodistribucion->country->id) // Same Country as Sucursal (centro)
                                                ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                            $cantidadDespachado = $p->pivot->getCantidadDespachado($centrodistribucion);
                                            $cantidadEntregado = $p->pivot->getCantidadEntregado($centrodistribucion);

                                            if($cantidadDespachado + $cantidadEntregado < $cantidadRecepcionado)
                                            {
                                                if(($cantidadDespachado + $cantidadEntregado + $ocList[$oc->id][$parteId]) <= $cantidadRecepcionado)
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
                                                        'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor a la cantidad pendiente de despacho en la OC: ' . $oc->id,
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
                                                    'La parte "' . $p->nparte . '" no tiene partes pendiente de despacho en la OC: ' . $oc->id,
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
                    'No tienes acceso a registrar despachos para centro de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al crear el despacho [!]',
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
            if($user->role->hasRoutepermission('sucursales despachos_show'))
            {
                $validatorInput = ['despacho_id' => $id];
            
                $validatorRules = [
                    'despacho_id' => 'required|exists:despachos,id,despachable_id,' . $centrodistribucion_id . ',despachable_type,' . get_class(new Sucursal()),
                ];
        
                $validatorMessages = [
                    'despacho_id.required' => 'Debes ingresar el despacho',
                    'despacho_id.exists' => 'El despacho ingresado no existe para el centro de distribucion',                    
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
                        $despacho = null;

                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {
                                
                                // Only if Despacho contains OcPartes from OCs generated from its same country
                                $despacho = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('despachos.id', '=', $id) // For this Despacho
                                            ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                            ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->first();

                                break;
                            }

                            // Vendedor
                            case 'seller': {

                                // Only if Despacho contains OcPartes from belonging OCs generated from its same Sucursal
                                $despacho = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->where('despachos.id', '=', $id) // For this Despacho
                                            ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                            ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                            ->where('solicitudes.sucursal_id', '=', $user->stationable->id) // Same Sucursal as user station
                                            ->where('solicitudes.user_id', '=', $user->id) // Belonging to user
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
                                    // Only if Despacho contains OcPartes from OCs generated from its same country
                                    $despacho = Despacho::select('despachos.*')
                                                ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                                ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('despachos.id', '=', $id) // For this Despacho
                                                ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                                ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                                ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->first();
                                }
                                // If user belongs to Sucursal
                                else if($user->stationable->type === 'sucursal')
                                {
                                    // Only if Despacho contains OcPartes from belonging OCs generated from its same Sucursal
                                    $despacho = Despacho::select('despachos.*')
                                                ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                                ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->where('despachos.id', '=', $id) // For this Despacho
                                                ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                                ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                                ->where('solicitudes.sucursal_id', '=', $user->stationable->id) // Same Sucursal as user station
                                                ->first();
                                }
                                

                                break;
                            }
                            

                            default: {
                                break;
                            }
                        }
                        
                        if($despacho !== null)
                        {           
                            $despacho->makeHidden([
                                'despachable_id', 
                                'despachable_type',
                                'destinable_id', 
                                'destinable_type', 
                                'partes_total',
                                'created_at', 
                                'updated_at'
                            ]);

                            $despacho->despachable;
                            $despacho->despachable->makeHidden([
                                'type',
                                'rut',
                                'address',
                                'country_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $despacho->destinable;
                            $despacho->destinable->makeHidden([
                                'type',
                                'rut',
                                'address',
                                'country_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $despacho->ocpartes = $despacho->ocpartes->map(function($ocParte)
                                {
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
                                        'despacho_id',
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

                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $despacho
                            );
              
                        }
                        // If wasn't catched
                        else
                        {
                            // If Despacho exists
                            if(Despacho::find($id))
                            {
                                // It was filtered, so it's forbidden
                                $response = HelpController::buildResponse(
                                    405,
                                    'No tienes acceso a visualizar el despacho',
                                    null
                                );
                            }
                            // It doesn't exist
                            else
                            {
                                $response = HelpController::buildResponse(
                                    412,
                                    'El despacho no existe',
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
                    'No tienes acceso a visualizar despachos de centro de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener el despacho [!]',
                null
            );
        }
        
        return $response;
    }

    /**
     * It retrieves all the required info for
     * selecting data and updating a Despacho for Sucursal (centro)
     * 
     */
    public function update_prepare_centrodistribucion($centrodistribucion_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales despachos_update'))
            {
                $validatorInput = ['despacho_id' => $id];
            
                $validatorRules = [
                    'despacho_id' => 'required|exists:despachos,id,despachable_id,' . $centrodistribucion_id . ',despachable_type,' . get_class(new Sucursal()),
                ];
        
                $validatorMessages = [
                    'despacho_id.required' => 'Debes ingresar el despacho',
                    'despacho_id.exists' => 'El despacho ingresado no existe para el centro de distribucion',                    
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
                        $despacho = null;

                        switch($user->role->name)
                        {
                            // Administrador
                            case 'admin': {

                                // Only if Despacho contains OcPartes from OCs generated from its same country
                                $despacho = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('despachos.id', '=', $id) // For this Despacho
                                            ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                            ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                            ->where('sucursales.type', '=', 'sucursal') // Solicitud belonging to a Sucursal
                                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->first();

                                if($despacho !== null)
                                {
                                    // Get only OcPartes (queue) on OCs generated from its country and received at Sucursal (centro)
                                    $ocParteList = OcParte::select('oc_parte.*')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('recepciones.recepcionable_type', '=', get_class($despacho->despachable)) // Despacho's despachable is Sucursal (centro)
                                                ->where('recepciones.recepcionable_id', '=', $despacho->despachable->id) // Received at Sucursal (centro)
                                                ->where('sucursales.type', '=', 'sucursal') // Solicitud belonging to a Sucursal
                                                ->where('sucursales.country_id', '=', $despacho->destinable->country->id) // Same Country as Sucursal (centro)
                                                ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                                ->groupBy('oc_parte.id')
                                                ->get();

                                    // For OcPartes in current Despacho
                                    $ocParteList = $despacho->ocpartes->reduce(function($carry, $ocParte) use ($ocParteList)
                                        {
                                            $contains = $carry->contains(function($op) use ($ocParte)
                                                {
                                                    return ($ocParte->id === $op->id);
                                                }
                                            );

                                            // If OcParte from Despacho isn't in queue
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
                                    // Only if Despacho contains OcPartes from OCs generated from its same country
                                $despacho = Despacho::select('despachos.*')
                                ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                ->where('despachos.id', '=', $id) // For this Despacho
                                ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                ->where('sucursales.type', '=', 'sucursal') // Solicitud belonging to a Sucursal
                                ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                ->first();

                                if($despacho !== null)
                                {
                                    // Get only OcPartes (queue) on OCs generated from its country and received at Sucursal (centro)
                                    $ocParteList = OcParte::select('oc_parte.*')
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('recepciones.recepcionable_type', '=', get_class($despacho->despachable)) // Despacho's despachable is Sucursal (centro)
                                                ->where('recepciones.recepcionable_id', '=', $despacho->despachable->id) // Received at Sucursal (centro)
                                                ->where('sucursales.type', '=', 'sucursal') // Solicitud belonging to a Sucursal
                                                ->where('sucursales.country_id', '=', $despacho->destinable->country->id) // Same Country as Sucursal (centro)
                                                ->groupBy('oc_parte.id')
                                                ->get();

                                    // For OcPartes in current Despacho
                                    $ocParteList = $despacho->ocpartes->reduce(function($carry, $ocParte) use ($ocParteList)
                                        {
                                            $contains = $carry->contains(function($op) use ($ocParte)
                                                {
                                                    return ($ocParte->id === $op->id);
                                                }
                                            );

                                            // If OcParte from Despacho isn't in queue
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
                            ($despacho !== null)
                        )
                        {   
                            $queueOcPartes = $ocParteList->reduce(function($carry, $ocParte) use ($centrodistribucion, $despacho)
                                {
                                    $cantidadRecepcionado = $ocParte->getCantidadRecepcionado($centrodistribucion);
                                    $cantidadDespachado = $ocParte->getCantidadDespachado($centrodistribucion);
                                    $cantidadEntregado = $ocParte->getCantidadEntregado($centrodistribucion);

                                    // Try to find OcParte in Despacho
                                    $op = $despacho->ocpartes->find($ocParte->id);

                                    if(
                                        // If OcParte is in Despacho
                                        ($op !== null) ||
                                        // Or if OcParte isn't in Despacho and hasn't been full dispatched/delivered yet
                                        (($op === null) && ($cantidadDespachado + $cantidadEntregado < $cantidadRecepcionado))
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
                                        $ocParte->cantidad_entregado = $cantidadEntregado;

                                        // Set minimum cantidad as total cantidad in Recepciones at destinable Sucursal
                                        $ocParte->cantidad_min = $ocParte->getCantidadRecepcionado($despacho->destinable);

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

                            $despacho->makeHidden([
                                'despachable_id', 
                                'despachable_type',
                                'destinable_id', 
                                'destinable_type', 
                                'partes_total',
                                'created_at', 
                                'updated_at'
                            ]);

                            $despacho->despachable;
                            $despacho->despachable->makeHidden([
                                'type',
                                'rut',
                                'address',
                                'country_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $despacho->destinable;
                            $despacho->destinable->makeHidden([
                                'type',
                                'rut',
                                'address',
                                'city',
                                'country_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $despacho->ocpartes = $despacho->ocpartes->map(function($ocParte) use ($centrodistribucion)
                                {
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
                                        'despacho_id',
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
                                "despacho" => $despacho
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
                            // If Despacho exists
                            if(Despacho::find($id))
                            {
                                // It was filtered, so it's forbidden
                                $response = HelpController::buildResponse(
                                    405,
                                    'No tienes acceso a actualizar el despacho',
                                    null
                                );
                            }
                            // It doesn't exist
                            else
                            {
                                $response = HelpController::buildResponse(
                                    412,
                                    'El despacho no existe',
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
                    'No tienes acceso a actualizar despachos de centro de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener el despacho [!]',
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
            if($user->role->hasRoutepermission('sucursales despachos_update'))
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
                    'fecha.required' => 'Debes ingresar la fecha de despacho',
                    'fecha.date_format' => 'El formato de fecha de despacho es invalido',
                    'fecha.before' => 'La fecha debe ser igual o anterior a hoy',
                    'ndocumento.min' => 'El numero de documento debe tener al menos un digito',
                    'responsable.required' => 'Debes ingresar el nombre de la persona que despacha',
                    'responsable.min' => 'El nombre de la persona que despacha debe tener al menos un digito',
                    'ocs.required' => 'Debes seleccionar las partes despachadas',
                    'ocs.array' => 'Lista de partes despachadas invalida',
                    'ocs.min' => 'El despacho debe contener al menos 1 parte despachada',
                    'ocs.*.id.required' => 'Debes seleccionar la OC a despachar',
                    'ocs.*.id.exists' => 'La OC ingresada no existe',
                    'ocs.*.partes.required' => 'Debes seleccionar las partes despachadas',
                    'ocs.*.partes.array' => 'Lista de partes despachadas invalida',
                    'ocs.*.partes.min' => 'El despacho debe contener al menos 1 parte despachada',
                    'ocs.*.partes.*.id.required' => 'La lista de partes despachadas es invalida',
                    'ocs.*.partes.*.id.exists' => 'La parte despachada ingresada no existe',
                    'ocs.*.partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte despachada',
                    'ocs.*.partes.*.cantidad.numeric' => 'La cantidad para la parte despachada debe ser numerica',
                    'ocs.*.partes.*.cantidad.min' => 'La cantidad para la parte despachada debe ser mayor a 0',
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
                    $despacho = null;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {
                            
                            // Only if Despacho contains OcPartes from OCs generated from its same country
                            $despacho = Despacho::select('despachos.*')
                                        ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                        ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                        ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                        ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                        ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                        ->where('despachos.id', '=', $id) // For this Despacho
                                        ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                        ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                        ->where('sucursales.type', '=', 'sucursal') // Solicitud belonging to a Sucursal
                                        ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                // Only if Despacho contains OcPartes from OCs generated from its same country
                                $despacho = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('despachos.id', '=', $id) // For this Despacho
                                            ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                            ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                            ->where('sucursales.type', '=', 'sucursal') // Solicitud belonging to a Sucursal
                                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->first();
                            }

                            break;
                        }

                        default: {
                            break;
                        }
                    }

                    if($despacho !== null)
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

                        // For each OcParte in Despacho
                        foreach($despacho->ocpartes as $ocParte)
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
                                // If the Parte is already in list, it's kept in Despacho
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
                        $despacho->fill($request->all());

                        if($despacho->save())
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
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                                ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                                ->where('solicitudes.sucursal_id', '=', $despacho->destinable->id) // Solicitudes for the Despacho's destination Sucursal
                                                ->where('sucursales.country_id', '=', $centrodistribucion->country->id) // Same Country as Sucursal (centro)
                                                ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                                    ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                    ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                    ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                    ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                    ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                                    ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                                    ->where('solicitudes.sucursal_id', '=', $despacho->destinable->id) // Solicitudes for the Despacho's destination Sucursal
                                                    ->where('sucursales.country_id', '=', $centrodistribucion->country->id) // Same Country as Sucursal (centro)
                                                    ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                                // Calc new cantidad with cantidad in Despachos + diff (negative when removing)
                                                $newCantidad = $p->pivot->getCantidadDespachado($centrodistribucion) + $diffList[$oc->id][$parteId];

                                                // If new cantidad in Despachos is equal or more than received at destination Sucursal (centro)
                                                if($newCantidad >= $p->pivot->getCantidadRecepcionado($despacho->destinable))
                                                {
                                                    // If new cantidad in Despachos + cantidad in Entregas is equal or less than cantidad in Recepciones
                                                    if($newCantidad + $p->pivot->getCantidadEntregado($centrodistribucion) <= $p->pivot->getCantidadRecepcionado($centrodistribucion))
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
                                                        // If the dispatched parts are more than waiting in queue
                                                        $response = HelpController::buildResponse(
                                                            409,
                                                            'La cantidad ingresada para la parte "' . $p->nparte . '" es mayor a la cantidad pendiente de despacho en la OC: ' . $oc->id,
                                                            null
                                                        );
                    
                                                        $success = false;
                    
                                                        break;
                                                    }
                                                }
                                                else
                                                {
                                                    // If the dispatched partes are less than received at destination Sucursal
                                                    $response = HelpController::buildResponse(
                                                        409,
                                                        'La cantidad ingresada para la parte "' . $p->nparte . '" es menor a la cantidad ya recepcionada en la surursal para la OC: ' . $oc->id,
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
                                                'No tienes acceso a actualizar despachos para la OC: ' . $ocId,
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

                            if(($success === true) && ($despacho->ocpartes()->sync($syncData)))
                            {
                                DB::commit();
                                    
                                $response = HelpController::buildResponse(
                                    201,
                                    'Despacho actualizado',
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
                                'Error al actualizar el despacho',
                                null
                            );
                        }
                    }
                    // If wasn't catched
                    else
                    {
                        // If Despacho exists
                        if(Despacho::find($id))
                        {
                            // It was filtered, so it's forbidden
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a actualizar el despacho',
                                null
                            );
                        }
                        // It doesn't exist
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'El despacho no existe',
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
                    'No tienes acceso a actualizar despachos para centro de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al actualizar el despacho [!]',
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
            if($user->role->hasRoutepermission('sucursales despachos_destroy'))
            {
                if($centrodistribucion = Sucursal::where('type', '=', 'centro')->where('id', '=', $centrodistribucion_id)->first())
                {
                    $despacho = null;

                    switch($user->role->name)
                    {
                        // Administrador
                        case 'admin': {
                            
                            // Only if Despacho contains OcPartes from OCs generated from its same country
                            $despacho = Despacho::select('despachos.*')
                                        ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                        ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                        ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                        ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                        ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                        ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                        ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                        ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                        ->where('despachos.id', '=', $id) // For this Despacho
                                        ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                        ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                        ->where('sucursales.type', '=', 'sucursal') // Solicitud belonging to a Sucursal
                                        ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                // Only if Despacho contains OcPartes from OCs generated from its same country
                                $despacho = Despacho::select('despachos.*')
                                            ->join('despacho_ocparte', 'despacho_ocparte.despacho_id', '=', 'despachos.id')
                                            ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('despachos.id', '=', $id) // For this Despacho
                                            ->where('despachos.despachable_type', '=', get_class($centrodistribucion))
                                            ->where('despachos.despachable_id', '=', $centrodistribucion->id) // Dispatched by Sucursal (centro)
                                            ->where('sucursales.type', '=', 'sucursal') // Solicitud belonging to a Sucursal
                                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
                                            ->first();
                            }

                            break;
                        }

                        default: {
                            break;
                        }
                    }

                    if($despacho !== null)
                    {
                        $ocList = array();

                        // For each OcParte in Despacho
                        foreach($despacho->ocpartes as $ocParte)
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
                                            ->join('oc_parte', 'oc_parte.oc_id', '=', 'ocs.id')
                                            ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                            ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                            ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                            ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                            ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                            ->where('solicitudes.sucursal_id', '=', $despacho->destinable->id) // Solicitudes for the Despacho's destination Sucursal
                                            ->where('sucursales.country_id', '=', $centrodistribucion->country->id) // Same Country as Sucursal (centro)
                                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                                ->join('recepcion_ocparte', 'recepcion_ocparte.ocparte_id', '=', 'oc_parte.id')
                                                ->join('recepciones', 'recepciones.id', '=', 'recepcion_ocparte.recepcion_id')
                                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                                ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                                ->whereIn('oc_parte.estadoocparte_id', [1, 2])  // OcParte with estadoocparte = 'Pendiente' or 'En transito'
                                                ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                                ->where('recepciones.recepcionable_id', '=', $centrodistribucion->id) // Received at Sucursal (centro)
                                                ->where('solicitudes.sucursal_id', '=', $despacho->destinable->id) // Solicitudes for the Despacho's destination Sucursal
                                                ->where('sucursales.country_id', '=', $centrodistribucion->country->id) // Same Country as Sucursal (centro)
                                                ->where('sucursales.country_id', '=', $user->stationable->country->id) // Same Country as user station
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
                                            // Calc new cantidad with cantidad in Despachos + diff (negative when removing)
                                            $newCantidad = $p->pivot->getCantidadDespachado($centrodistribucion) + $ocList[$oc->id][$parteId];

                                            // If new cantidad in Despachos is less than received at destination Sucursal
                                            if($newCantidad < $p->pivot->getCantidadRecepcionado($despacho->destinable))
                                            {
                                                $response = HelpController::buildResponse(
                                                    409,
                                                    'La parte "' . $p->nparte . '" tiene cantidades ya recepcionadas en la sucursal para la OC: ' . $oc->id,
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
                                                'Error al eliminar el despacho',
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
                                            'No tienes acceso a eliminar despachos para la OC: ' . $ocId,
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

                        if(($success === true) && ($despacho->delete()))
                        {  
                            $response = HelpController::buildResponse(
                                200,
                                'Despacho eliminado',
                                null
                            );
                        }
                    }
                    // If wasn't catched
                    else
                    {
                        // If Despacho exists
                        if(Despacho::find($id))
                        {
                            // It was filtered, so it's forbidden
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a eliminar el despacho',
                                null
                            );
                        }
                        // It doesn't exist
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'El despacho no existe',
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
                    'No tienes acceso a eliminar despachos para centro de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al eliminar el despacho [!]',
                null
            );
        }
        
        return $response;
    }
}
