<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Comprador;
use App\Models\OcParte;
use App\Models\Despacho;

class DespachosController extends Controller
{

    public function index_comprador($id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores despachos_index'))
            {
                if($comprador = Comprador::find($id))
                {
                    $comprador->makeHidden([
                        'created_at', 
                        'updated_at'
                    ]);

                    $comprador->despachos;
                    $comprador->despachos = $comprador->despachos->filter(function($despacho)
                    {
                        $despacho->partes_total;
                        
                        $despacho->makeHidden([
                            'despachable_id', 
                            'despachable_type', 
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $despacho->ocpartes;
                        $despacho->ocpartes = $despacho->ocpartes->filter(function($ocparte)
                        {
                            $ocparte->makeHidden([
                                'oc_id',
                                'parte_id',
                                'tiempoentrega',
                                'estadoocparte_id',
                                'created_at',
                                'updated_at',
                                'cantidad_pendiente',
                                'cantidad_compradorrecepcionado',
                                'cantidad_compradordespachado',
                                'cantidad_centrodistribucionrecepcionado',
                                'cantidad_centrodistribuciondespachado',
                                'cantidad_sucursalrecepcionado',
                                'cantidad_sucursaldespachado',
                            ]);

                            $ocparte->pivot->makeHidden([
                                'ocparte_id',
                                'despacho_id',
                                'oc_parte_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $ocparte->oc;
                            $ocparte->oc->makeHidden([
                                'cotizacion_id',
                                'proveedor_id',
                                'filedata_id',
                                'estadooc_id',
                                'noccliente',
                                'motivobaja_id',
                                'usdvalue',
                                'partes_total',
                                'dias',
                                'partes',
                                'created_at', 
                                'updated_at'
                            ]);

                            $ocparte->parte;
                            $ocparte->parte->makeHidden(['marca_id', 'created_at', 'updated_at']);

                            $ocparte->parte->marca;
                            $ocparte->parte->marca->makeHidden(['created_at', 'updated_at']);

                            return $ocparte;
                        });

                        return $despacho;
                    });

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $comprador->despachos
                    );
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

    // TO REVIEW
    public function queuePartes_comprador($id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores despachos_store'))
            {
                if($comprador = Comprador::find($id))
                {
                        
                    $ocParteList = OcParte::select('oc_parte.*')
                                    ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                    ->where('ocs.estadooc_id', '=', 2) // Estadooc = 'En proceso'
                                    ->get();


                    // Retrieves the partes list with cantidad_stock for dispatching
                    $queuePartesData = $ocParteList->reduce(function($carry, $ocParte)
                        {
                            // Get how many partes have been received but not dispatched yet in Comprador
                            $cantidad_stock = $ocParte->cantidad_compradorrecepcionado - $ocParte->cantidad_compradordespachado;
                            if($cantidad_stock > 0)
                            {
                                if(isset($carry[$ocParte->parte->id]))
                                {
                                    // If parte is already in the list, adds the cantidad_pendiente to the total
                                    $carry[$ocParte->parte->id]['cantidad_stock'] += $cantidad_stock;
                                }
                                else
                                {
                                    // If parte is not in the list, inserts the parte to the list
                                    $parte = [
                                        "id" => $ocParte->parte->id,
                                        "nparte" => $ocParte->parte->nparte,
                                        "marca" => $ocParte->parte->marca->makeHidden(['created_at', 'updated_at']),
                                        "cantidad_stock" => $cantidad_stock,
                                    ];

                                    $carry[$parte['id']] = $parte;
                                }
                                
                            }

                            return $carry;
                        },
                        array()
                    );

                    // Transform the queuePartesData key-value array into a list
                    $queuePartes = array();
                    foreach(array_keys($queuePartesData) as $key)
                    {
                        array_push($queuePartes, $queuePartesData[$key]);
                    }

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $queuePartes
                    );
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
                    'No tienes acceso a visualizar partes disponibles para despachar',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener partes disponibles para despachar [!]' . $e,
                null
            );
        }
            
        return $response;
    }

    // TO REVIEW
    public function store_comprador(Request $request, $comprador_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores despachos_store'))
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
                    if($comprador = Comprador::find($comprador_id))
                    {
                        DB::beginTransaction();

                        $despacho = new Despacho();
                        // Set the morph for Recepcion as Comprador
                        $despacho->despachable_id = $comprador->id;
                        $despacho->despachable_type = get_class($comprador);
                        // Fill the data
                        $despacho->fecha = $request->fecha;
                        $despacho->ndocumento = $request->ndocumento;
                        $despacho->responsable = $request->responsable;
                        $despacho->comentario = $request->comentario;

                        if($despacho->save())
                        {
                            $data = array();
                            $success = true;

                            $cantidades = array_reduce($request->partes, function($carry, $parte)
                                {
                                    $carry[$parte['id']] = $parte['cantidad'];

                                    return $carry;
                                },
                                array()
                            );

                            foreach(array_keys($cantidades) as $parteId)
                            {
                                // For each parte sent, gets the OcParte list where are in stock in Comprador and Estadoocparte is different than 'Entregado'
                                if($ocParteList = OcParte::select('oc_parte.*')
                                                ->join('ocparte_recepcion', 'ocparte_recepcion.ocparte_id', '=', 'oc_parte.id')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->join('recepciones', 'recepciones.id', '=', 'ocparte_recepcion.recepcion_id')
                                                ->where('oc_parte.parte_id', '=', $parteId) // For this Parte
                                                ->where('oc_parte.estadoocparte_id', '<>', 3) // Estadoocparte != 'Entregado'
                                                ->where('recepciones.recepcionable_type', '=', 'App\\Models\\Comprador') // Are in recepciones belonging to Comprador
                                                ->where('recepciones.recepcionable_id', '=', $comprador->id) // Belong to the specified Comprador
                                                ->where('ocs.estadooc_id', '=', 2) // Estadooc = 'En proceso'
                                                ->orderBy('ocs.created_at', 'ASC')
                                                ->get()
                                )
                                {
                                    if($ocParteList->count() > 0)
                                    {
                                        if($success === true)
                                        {
                                            foreach($ocParteList as $ocParte)
                                            {
                                                if($cantidades[$parteId] > 0)
                                                {
                                                    // Getting the current cantidad_stock in Comprador
                                                    $cantidad_stock = $ocParte->cantidad_compradorrecepcionado - $ocParte->cantidad_compradordespachado;
                                                    
                                                    if($cantidades[$parteId] >= $cantidad_stock)
                                                    {
                                                        // If is dispatching more or equal than in stock for the OcParte, fill the OcParte
                                                        $cantidad = $cantidad_stock;
                                                    }
                                                    else
                                                    {
                                                        // If dispatching less than in stock for the OcParte
                                                        $cantidad = $cantidades[$parteId];
                                                    }
                                                    
                                                    // Attach the OcParte to Recepcion with defined Cantidad
                                                    $despacho->ocpartes()->attach(
                                                        array(
                                                            $ocParte->id => array(
                                                                "cantidad" => $cantidad
                                                            )
                                                        )
                                                    );

                                                    // Updates the cantidad left
                                                    $cantidades[$parteId] = $cantidades[$parteId] - $cantidad;
                                                }
                                                else
                                                {
                                                    break;
                                                } 
                                            }

                                            if($cantidades[$parteId] > 0)
                                            {
                                                // If the dispatched parts are more than in Comprador's stock
                                                $response = HelpController::buildResponse(
                                                    409,
                                                    'La cantidad de partes despachadas es mayor a la cantidad de partes pendientes de despacho',
                                                    null
                                                );
            
                                                $success = false;
            
                                                break;
                                            }
                                        }
                                        else
                                        {
                                            // If it failed during the partes iteration, then break the higher loop
                                            break;
                                        }
                                        
                                    }
                                    else
                                    {
                                        // If there aren't OcParte waiting for the entered Parte
                                        $response = HelpController::buildResponse(
                                            409,
                                            'La parte ingresada no tiene partes pendientes de despacho',
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
                                        'Error al obtener las partes pendiente de despacho',
                                        null
                                    );

                                    $success = false;

                                    break;
                                }
                            }


                            if($success === true)
                            {

                                DB::rollback();
                                    
                                $response = HelpController::buildResponse(
                                    201,
                                    'Despacho creado',
                                    $data
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
                    else
                    {
                        $response = HelpController::buildResponse(
                            400,
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


    public function show_comprador($comprador_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores despachos_show'))
            {
                $validatorInput = ['despacho_id' => $id];
            
                $validatorRules = [
                    'despacho_id' => 'required|exists:despachos,id,despachable_id,' . $comprador_id . ',despachable_type,' . get_class(new Comprador()), // Try to add recepcionable_type
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
                        if($despacho = Despacho::find($id))
                        {
                            $despacho->makeHidden([
                                'despachable_id',
                                'despachable_type',
                                'proveedor_id',
                                'partes_total',
                                'updated_at',
                            ]);

                            $despacho->despachable;
                            $despacho->despachable->makeHidden([
                                'rut',
                                'address',
                                'city',
                                'contact',
                                'phone',
                                'created_at', 
                                'updated_at'
                            ]);

                            $despacho->ocpartes;
                            foreach($despacho->ocpartes as $ocparte)
                            {
                                $ocparte->makeHidden([
                                    'oc_id',
                                    'parte_id',
                                    'estadoocparte_id',
                                    'tiempoentrega',
                                    'cantidad_pendiente',
                                    'cantidad_compradorrecepcionado',
                                    'cantidad_compradordespachado',
                                    'cantidad_centrodistribucionrecepcionado',
                                    'cantidad_centrodistribuciondespachado',
                                    'cantidad_sucursalrecepcionado',
                                    'cantidad_sucursaldespachado',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $ocparte->pivot->makeHidden([
                                    'despacho_id',
                                    'ocparte_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $ocparte->oc;
                                $ocparte->oc->makeHidden([
                                    'cotizacion_id',
                                    'proveedor_id',
                                    'filedata_id',
                                    'estadooc_id',
                                    'noccliente',
                                    'motivobaja_id',
                                    'usdvalue',
                                    'partes_total',
                                    'dias',
                                    'partes',
                                    'created_at', 
                                    'updated_at'
                                ]);
                                
                                $ocparte->oc->cotizacion;
                                $ocparte->oc->cotizacion->makeHidden([
                                    'solicitud_id',
                                    'motivorechazo_id',
                                    'estadocotizacion_id',
                                    'usdvalue',
                                    'partes_total',
                                    'dias',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $ocparte->oc->cotizacion->solicitud;
                                $ocparte->oc->cotizacion->solicitud->makeHidden([
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

                                $ocparte->oc->cotizacion->solicitud->faena;
                                $ocparte->oc->cotizacion->solicitud->faena->makeHidden([
                                    'cliente_id',
                                    'rut',
                                    'address',
                                    'city',
                                    'contact',
                                    'phone',
                                    'created_at',
                                    'updated_at'
                                ]);
            
                                $ocparte->oc->cotizacion->solicitud->faena->cliente;
                                $ocparte->oc->cotizacion->solicitud->faena->cliente->makeHidden([
                                    'sucursal_id', 
                                    'created_at', 
                                    'updated_at'
                                ]);
                                
                                $ocparte->parte;
                                $ocparte->parte->makeHidden([
                                    'marca_id',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $ocparte->parte->marca;
                                $ocparte->parte->marca->makeHidden(['created_at', 'updated_at']);
                            }
                            
                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $despacho
                            );
                        }   
                        else     
                        {
                            $response = HelpController::buildResponse(
                                400,
                                'El despacho no existe',
                                null
                            );
                        }                        
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            400,
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
}
