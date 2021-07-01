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
                    $comprador->makeHidden([
                        'created_at', 
                        'updated_at'
                    ]);

                    $comprador->recepciones;
                    $comprador->recepciones = $comprador->recepciones->filter(function($recepcion)
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
                        
                        $recepcion->partes;
                        $recepcion->partes = $recepcion->partes->filter(function($parte)
                        {
                            $parte->makeHidden([
                                'marca_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $parte->pivot->makeHidden([
                                'parte_id',
                                'recepcion_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $parte->marca;
                            $parte->marca->makeHidden(['created_at', 'updated_at']);

                            return $parte;
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

                        return $recepcion;
                    });

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $comprador->recepciones
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
                        $ocList = Oc::select('ocs.*')
                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                ->where('solicitudes.comprador_id', '=', $comprador->id) // For this Comprador
                                ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                ->where('ocs.proveedor_id', '=', $proveedor->id)
                                ->get();


                        $queueOcs = $ocList->filter(function($oc) use ($comprador)
                            {
                                foreach($oc->partes as $parte)
                                {
                                    // If OcParte has less cantidad in Recepciones than total in OC
                                    if($parte->pivot->getCantidadRecepcionado($comprador) < $parte->pivot->cantidad)
                                    {
                                        // Filter data to response
                                        $oc->makeHidden([
                                            'cotizacion_id',
                                            'proveedor_id',
                                            'filedata_id',
                                            'estadooc_id',
                                            'motivobaja_id',
                                            'usdvalue',
                                            'partes'
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

                                        // Add to the filtered list
                                        return true;
                                    }
                                }
                            }
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
                $validatorInput = $request->only('proveedor_id', 'fecha', 'ndocumento', 'responsable', 'comentario', 'partes');
            
                $validatorRules = [
                    'proveedor_id' => 'required|exists:proveedores,id,comprador_id,' . $comprador_id,
                    'fecha' => 'required|date_format:Y-m-d|before:tomorrow', // it includes today
                    'ndocumento' => 'nullable|min:1',
                    'responsable' => 'required|min:1',
                    'comentario' => 'sometimes|nullable',
                    'partes' => 'required|array|min:1',
                    'partes.*.id'  => 'required|exists:partes,id',
                    'partes.*.cantidad'  => 'required|numeric|min:1',
                ];
        
                $validatorMessages = [
                    'proveedor_id.required' => 'Debes ingresar el proveedor',
                    'proveedor_id.exists' => 'El proveedor ingresado no existe para el comprador',
                    'fecha.required' => 'Debes ingresar la fecha de recepcion',
                    'fecha.date_format' => 'El formato de fecha de recepcion es invalido',
                    'fecha.before' => 'La fecha debe ser igual o anterior a hoy',
                    'ndocumento.min' => 'El numero de documento debe tener al menos un digito',
                    'responsable.required' => 'Debes ingresar el nombre de la persona que recibe',
                    'responsable.min' => 'El nombre de la persona que recibe debe tener al menos un digito',
                    'partes.required' => 'Debes seleccionar las partes recepcionadas',
                    'partes.array' => 'Lista de partes recepcionadas invalida',
                    'partes.min' => 'La recepcion debe contener al menos 1 parte recepcionada',
                    'partes.*.id.required' => 'La lista de partes recepcionadas es invalida',
                    'partes.*.id.exists' => 'La parte recepcionada ingresada no existe',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte recepcionada',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte recepcionada debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte recepcionada debe ser mayor a 0',
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
                        if($proveedor = Proveedor::where('id', '=', $request->proveedor_id)->where('comprador_id', '=', $comprador_id)->first())
                        {
                            DB::beginTransaction();

                            $recepcion = new Recepcion();
                            // Set the morph source for Recepcion as Proveedor
                            $recepcion->sourceable_id = $proveedor->id;
                            $recepcion->sourceable_type = get_class($proveedor);
                            // Set the morph for Recepcion as Comprador
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

                                $cantidades = array_reduce($request->partes, function($carry, $parte)
                                    {
                                        $carry[$parte['id']] = $parte['cantidad'];

                                        return $carry;
                                    },
                                    array()
                                );

                                // For each parte sent, gets the OcParte list for the selected Proveedor
                                if($ocParteList = OcParte::select('oc_parte.*')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->where('ocs.proveedor_id', $proveedor->id)
                                                ->whereIn('ocs.estadooc_id', [2, 3]) // Estadooc = 'En proceso' (2) or 'Cerrada' (3)
                                                ->get()
                                )
                                {
                                    if($ocParteList->count() > 0)
                                    {
                                        $cantidadesOc = $ocParteList->reduce(function($carry, $ocParte)
                                            {
                                                if(isset($carry[$ocParte->parte->id]))
                                                {
                                                    $carry[$ocParte->parte->id] += $ocParte->cantidad;
                                                }
                                                else
                                                {
                                                    $carry[$ocParte->parte->id] = $ocParte->cantidad;
                                                }

                                                return $carry;
                                            }, 
                                            array()
                                        );

                                        $success = true;
                                        foreach(array_keys($cantidades) as $parteId)
                                        {
                                            if($parte = Parte::find($parteId))
                                            {
                                                if(isset($cantidadesOc[$parte->id]))
                                                {
                                                    // Get cantidad total in Recepciones at Comprador from Proveedor
                                                    $cantidadPendiente = $cantidadesOc[$parte->id] - $parte->getCantidadRecepcionado_sourceable($comprador, $proveedor);

                                                    if($cantidadPendiente > 0)
                                                    {
                                                        if($cantidades[$parte->id] <= $cantidadPendiente)
                                                        {

                                                            $recepcion->partes()->attach(
                                                                array(
                                                                    $parte->id => array(
                                                                        "cantidad" => $cantidades[$parte->id]
                                                                    )
                                                                )
                                                            );

                                                        }
                                                        else
                                                        {
                                                            // If the received parts are more than waiting in queue
                                                            $response = HelpController::buildResponse(
                                                                409,
                                                                'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor a la cantidad de pendiente de recepcion',
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
                                                            'La parte "' . $parte->nparte . '" no tiene partes pendiente de recepcion',
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
                                                        'La parte "' . $parte->nparte . '" no tiene partes pendiente de recepcion',
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
                                                    'Error al obtener una de las partes pendientes de recepcion',
                                                    null
                                                );
                
                                                $success = false;
                                                
                                                break;
                                            }                                   
                                        }
                                    }
                                    else
                                    {
                                        // If there aren't OcParte waiting for the entered Parte
                                        $response = HelpController::buildResponse(
                                            409,
                                            'No se han encontrado partes para recepcionar',
                                            null
                                        );
    
                                        $success = false;
                                    }
                                }
                                else
                                {
                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al obtener las partes pendiente de recepcion',
                                        null
                                    );

                                    $success = false;
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
                        if($recepcion = $comprador->recepciones->find($id))
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

                            $recepcion->partes;
                            foreach($recepcion->partes as $parte)
                            {                                
                                $parte->makeHidden([
                                    'marca_id',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $parte->pivot->makeHidden([
                                    'recepcion_id',
                                    'parte_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $parte->marca;
                                $parte->marca->makeHidden(['created_at', 'updated_at']);
                            }
                            
                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $recepcion
                            );
                        }   
                        else     
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La recepcion no existe',
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
                        if($recepcion = $comprador->recepciones->find($id))
                        {
                            $recepcion->makeHidden([
                                'recepcionable_id',
                                'recepcionable_type',
                                'sourceable_id',
                                'sourceable_type',
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
                                'created_at', 
                                'updated_at'
                            ]);

                            $recepcion->partes;
                            foreach($recepcion->partes as $parte)
                            {                                
                                $parte->makeHidden([
                                    'marca_id',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $parte->pivot->makeHidden([
                                    'recepcion_id',
                                    'parte_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $parte->marca;
                                $parte->marca->makeHidden(['created_at', 'updated_at']);
                            }

                            if(get_class($recepcion->sourceable) === get_class(new Proveedor()))
                            {

                                $ocParteList = OcParte::select('oc_parte.*')
                                        ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                        ->where('ocs.proveedor_id', $recepcion->sourceable->id) // For Proveedor
                                        ->whereIn('ocs.estadooc_id', [2, 3]) // Estadooc = 'En proceso' (2) or 'Cerrada' (3)
                                        ->get();

                                if($ocParteList !== null)
                                {
                                    $cantidadesOc = $ocParteList->reduce(function($carry, $ocParte)
                                        {
                                            if(isset($carry[$ocParte->parte->id]))
                                            {
                                                $carry[$ocParte->parte->id] += $ocParte->cantidad;
                                            }
                                            else
                                            {
                                                $carry[$ocParte->parte->id] = $ocParte->cantidad;
                                            }
    
                                            return $carry;
                                        }, 
                                        array()
                                    );
    
                                    $success = true;
                                    $queuePartes = array();
    
                                    foreach(array_keys($cantidadesOc) as $parteId)
                                    {
                                        if($parte = Parte::find($parteId))
                                        {
                                            // Get cantidad total in Recepciones at Comprador from Proveedor
                                            $cantidadPendiente = $cantidadesOc[$parteId] - $parte->getCantidadRecepcionado_sourceable($comprador, $recepcion->sourceable);
                                            $cantidadDespachos = 0;

                                            // If the Parte is already in the Recepcion, then add the cantidad to queue calc in Despachos if already taken
                                            if($p = $recepcion->partes->find($parte->id))
                                            {
                                                $cantidadPendiente = $cantidadPendiente + $p->pivot->cantidad;
                                                $cantidadDespachos = $parte->getCantidadDespachado($comprador) - ($parte->getCantidadRecepcionado($comprador) - $p->pivot->cantidad);
                                                $cantidadDespachos = $cantidadDespachos >= 0 ? $cantidadDespachos : 0;
                                            }

                                            if($cantidadPendiente > 0)
                                            {
                                                $parteData = [
                                                    "id" => $parte->id,
                                                    "nparte" => $parte->nparte,
                                                    "marca" => $parte->marca->makeHidden(['created_at', 'updated_at']),
                                                    "cantidad_pendiente" => $cantidadPendiente,
                                                    "cantidad_despachos" => $cantidadDespachos
                                                ];
    
                                                array_push($queuePartes, $parteData);
                                            }
                                        }
                                        else
                                        {
                                            $response = HelpController::buildResponse(
                                                500,
                                                'Error al obtener una de las partes pendientes de recepcion',
                                                null
                                            );
    
                                            $success = false;
                                            
                                            break;
                                        }
                                    }

                                    if($success === true)
                                    {
                                        $data = [
                                            'recepcion' => $recepcion,
                                            'queue_partes' => $queuePartes
                                        ];
        
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
                                            'Error al obtener partes pendiente de recepcion',
                                            null
                                        );
                                    }
                                }
                                else
                                {
                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al obtener partes pendiente de recepcion',
                                        null
                                    );
                                }
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al obtener el proveedor de la recepcion',
                                    null
                                );
                            }
                        }   
                        else     
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La recepcion no existe',
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
                    'fecha.required' => 'Debes ingresar la fecha de recepcion',
                    'fecha.date_format' => 'El formato de fecha de recepcion es invalido',
                    'fecha.before' => 'La fecha debe ser igual o anterior a hoy',
                    'ndocumento.min' => 'El numero de documento debe tener al menos un digito',
                    'responsable.required' => 'Debes ingresar el nombre de la persona que recepciona',
                    'responsable.min' => 'El nombre de la persona que recepciona debe tener al menos un digito',
                    'partes.required' => 'Debes seleccionar las partes recepcionadas',
                    'partes.array' => 'Lista de partes recepcionadas invalida',
                    'partes.min' => 'La recepcion debe contener al menos 1 parte recepcionada',
                    'partes.*.id.required' => 'La lista de partes recepcionadas es invalida',
                    'partes.*.id.exists' => 'La parte recepcionada ingresada no existe',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte recepcionada',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte recepcionada debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte recepcionada debe ser mayor a 0',
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
                        if($recepcion = $comprador->recepciones->find($id))
                        {
                            DB::beginTransaction();

                            // Fill the data
                            $recepcion->fecha = $request->fecha;
                            $recepcion->ndocumento = $request->ndocumento;
                            $recepcion->responsable = $request->responsable;
                            $recepcion->comentario = $request->comentario;

                            if($recepcion->save())
                            {
                                // For each parte sent, gets the OcParte list for the Proveedor
                                if($ocParteList = OcParte::select('oc_parte.*')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->where('ocs.proveedor_id', $recepcion->sourceable->id)
                                                ->whereIn('ocs.estadooc_id', [2, 3]) // Estadooc = 'En proceso' (2) or 'Cerrada' (3)
                                                ->get()
                                )
                                {
                                    if($ocParteList->count() > 0)
                                    {
                                        $ocsCantidades = $ocParteList->reduce(function($carry, $ocParte)
                                            {
                                                if(isset($carry[$ocParte->parte->id]))
                                                {
                                                    $carry[$ocParte->parte->id] += $ocParte->cantidad;
                                                }
                                                else
                                                {
                                                    $carry[$ocParte->parte->id] = $ocParte->cantidad;
                                                }

                                                return $carry;
                                            }, 
                                            array()
                                        );

                                        // Get new cantidades array
                                        $newCantidades = array_reduce($request->partes, function($carry, $parte)
                                            {
                                                $carry[$parte['id']] = $parte['cantidad'];

                                                return $carry;
                                            },
                                            array()
                                        );


                                        // Get previous cantidades array
                                        $previousCantidades = $recepcion->partes->reduce(function($carry, $parte)
                                            {
                                                if(isset($carry[$parte->id]))
                                                {
                                                    // If parte is already in the list, adds the cantidad in ParteRecepcion to the total
                                                    $carry[$parte->id] += $parte->pivot->cantidad;
                                                }
                                                else
                                                {
                                                    // If parte is not in the list, inserts the parte to the list
                                                    $carry[$parte->id] = $parte->pivot->cantidad;
                                                }

                                                return $carry;
                                            },
                                            array()
                                        );

                                        // Define diff cantidades array between the previous and the new one
                                        $diffCantidades = array();
                                        // For all the previous partes
                                        foreach(array_keys($previousCantidades) as $parteId)
                                        {
                                            if(isset($newCantidades[$parteId]))
                                            {
                                                // If it comes in the new list, sets the new cantidad
                                                $diffCantidades[$parteId] = $newCantidades[$parteId] - $previousCantidades[$parteId];
                                            }
                                            else
                                            {
                                                // If it doesn't come, rest the previous cantidad as negative (the ParteRecepcion will be removed)
                                                $diffCantidades[$parteId] = $previousCantidades[$parteId] * -1;
                                            }
                                        }

                                        // If there are new partes weren't catched in previous list, then add them to the diff list
                                        foreach(array_keys($newCantidades) as $parteId)
                                        {
                                            // If the new parte isn't in the diff list
                                            if(!isset($diffCantidades[$parteId]))
                                            {
                                                // Add the new parte to the diff list
                                                $diffCantidades[$parteId] = $newCantidades[$parteId];
                                            }
                                        }

                                        $success = true;

                                        // For all the partes in diff list
                                        foreach(array_keys($diffCantidades) as $parteId)
                                        {
                                            if(isset($ocsCantidades[$parteId]))
                                            {
                                                // If we are removing parts from the recepcion
                                                if($diffCantidades[$parteId] < 0)
                                                {       
                                                    if($parte = $recepcion->partes->find($parteId))
                                                    {
                                                        // Calc pendiente using cantidad in OCs - cantidad in Recepciones for Proveedor - diff (negative sum)
                                                        $cantidadPendiente = $ocsCantidades[$parteId] - $parte->getCantidadRecepcionado_sourceable($comprador, $recepcion->sourceable) - $diffCantidades[$parteId];
                                                        if($cantidadPendiente >= 0)
                                                        {
                                                            // Calc stock using cantidad in Recepciones for Comprador - diff (negative sum) - cantidad in Despachos
                                                            $cantidadStock = $parte->getCantidadRecepcionado($comprador) + $diffCantidades[$parteId] - $parte->getCantidadDespachado($comprador);                          
                                                            if($cantidadStock >= 0)
                                                            {
                                                                // If cantidad the same than in previousCantidad, we're removing the ParteRecepcion
                                                                if($previousCantidades[$parteId] === abs($diffCantidades[$parteId]))
                                                                {
                                                                    if(!$recepcion->partes()->detach($parteId))
                                                                    {
                                                                        $response = HelpController::buildResponse(
                                                                            500,
                                                                            'Error al eliminar una parte de la recepcion',
                                                                            null
                                                                        );
                                    
                                                                        $success = false;
                                    
                                                                        break;
                                                                    }
                                                                }
                                                                else
                                                                {
                                                                    // Set new cantidad adding the negative diff
                                                                    $parte->pivot->cantidad = $parte->pivot->cantidad + $diffCantidades[$parteId];
                                                                    if(!$parte->pivot->save())
                                                                    {
                                                                        $response = HelpController::buildResponse(
                                                                            500,
                                                                            'Error al actualizar una parte de la recepcion',
                                                                            null
                                                                        );
                                    
                                                                        $success = false;
                                    
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                            else
                                                            {
                                                                // If the received parts are more than waiting in queue
                                                                $response = HelpController::buildResponse(
                                                                    409,
                                                                    'La cantidad ingresada para la parte "' . $parte->nparte . '" es menor a la cantidad ya despachada',
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
                                                                'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor a la cantidad pendiente de recepcion',
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
                                                            'Error al obtener una de las partes pendientes de recepcion',
                                                            null
                                                        );
            
                                                        $success = false;
            
                                                        break;
                                                    }
                                                }
                                                // If we are adding more parts to the recepcion
                                                else if($diffCantidades[$parteId] > 0)
                                                {
                                                    // If the Parte is kept in Recepcion
                                                    if(isset($previousCantidades[$parteId]))
                                                    {
                                                        if($parte = $recepcion->partes->find($parteId))
                                                        {
                                                            // Calc pendiente using cantidad in OCs - cantidad in Recepciones for Proveedor - diff
                                                            $cantidadPendiente = $ocsCantidades[$parteId] - $parte->getCantidadRecepcionado_sourceable($comprador, $recepcion->sourceable) - $diffCantidades[$parteId];
                                                            if($cantidadPendiente >= 0)
                                                            {
                                                                // Calc stock using cantidad in Recepciones for Comprador - diff (negative sum) - cantidad in Despachos
                                                                $cantidadStock = $parte->getCantidadRecepcionado($comprador) + $diffCantidades[$parteId] - $parte->getCantidadDespachado($comprador);                          
                                                                if($cantidadStock >= 0)
                                                                {
                                                                    // Set new cantidad adding the negative diff
                                                                    $parte->pivot->cantidad = $parte->pivot->cantidad + $diffCantidades[$parteId];
                                                                    if(!$parte->pivot->save())
                                                                    {
                                                                        $response = HelpController::buildResponse(
                                                                            500,
                                                                            'Error al actualizar una parte de la recepcion',
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
                                                                        'La cantidad ingresada para la parte "' . $parte->nparte . '" es menor a la cantidad ya despachada',
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
                                                                    'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor a la cantidad pendiente de recepcion',
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
                                                                'Error al obtener una de las partes pendientes de recepcion',
                                                                null
                                                            );
                
                                                            $success = false;
                
                                                            break;
                                                        }
                                                    }
                                                    // If is a new Parte in Recepcion
                                                    else
                                                    {
                                                        if($parte = Parte::find($parteId))
                                                        {
                                                            // Calc pendiente using cantidad in OCs - cantidad in Recepciones for Proveedor - diff (negative sum)
                                                            $cantidadPendiente = $ocsCantidades[$parteId] - $parte->getCantidadRecepcionado_sourceable($comprador, $recepcion->sourceable) + $diffCantidades[$parteId];
                                                            if($cantidadPendiente >= 0)
                                                            {
                                                                // Calc stock using cantidad in Recepciones for Comprador - diff (negative sum) - cantidad in Despachos
                                                                $cantidadStock = $parte->getCantidadRecepcionado($comprador) + $diffCantidades[$parteId] - $parte->getCantidadDespachado($comprador);                          
                                                                if($cantidadStock >= 0)
                                                                {
                                                                    // Add the new Parte to Recepcion
                                                                    $recepcion->partes()->attach(
                                                                        array(
                                                                            $parteId => array(
                                                                                "cantidad" => $diffCantidades[$parteId] // For a new Parte, diff contains the full cantidad
                                                                            )
                                                                        )
                                                                    );
                                                                }
                                                                else
                                                                {
                                                                    // If the received parts are more than waiting in queue
                                                                    $response = HelpController::buildResponse(
                                                                        409,
                                                                        'La cantidad ingresada para la parte "' . $parte->nparte . '" es menor a la cantidad ya despachada',
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
                                                                    'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor a la cantidad pendiente de recepcion',
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
                                                                'Error al obtener una de las partes pendientes de recepcion',
                                                                null
                                                            );
                
                                                            $success = false;
                
                                                            break;
                                                        }
                                                    }
                                                }
                                                // If the have the same quantity, nothing changes (diff = 0)
                                                else
                                                {
                                                    //Do nothing, continue the loop
                                                }
                                            }
                                            else
                                            {
                                                $response = HelpController::buildResponse(
                                                    500,
                                                    'Error al obtener una de las partes pendientes de recepcion',
                                                    null
                                                );
    
                                                $success = false;
    
                                                break;
                                            }
                                        }
                                    }
                                    else
                                    {
                                        // If there aren't OcParte waiting for the entered Parte
                                        $response = HelpController::buildResponse(
                                            409,
                                            'No se han encontrado partes para recepcionar',
                                            null
                                        );

                                        $success = false;
                                    }
                                }
                                else
                                {
                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al obtener las partes pendiente de recepcion',
                                        null
                                    );

                                    $success = false;
                                
                                }

                                if($success === true)
                                {
                                    DB::commit();
                                    
                                    $response = HelpController::buildResponse(
                                        200,
                                        'Recepcion actualizada',
                                        null
                                    );
                                }
                                else
                                {
                                    DB::rollback();

                                    // The response error message was already set when success = false
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
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La recepcion no existe',
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
                    if($recepcion = $comprador->recepciones->find($id))
                    {
                        // Check if Recepcion was sourceable by a Proveedor
                        if(get_class($recepcion->sourceable) === get_class(new Proveedor()))
                        {
                            DB::beginTransaction();

                            $success = true;

                            // For all the partes in diff list
                            foreach($recepcion->partes as $parte)
                            {                                                                       
                                // Calc stock using cantidad in Recepciones for Comprador - cantidad in Recepcion - cantidad in Despachos
                                $cantidadStock = $parte->getCantidadRecepcionado($comprador) - $parte->pivot->cantidad - $parte->getCantidadDespachado($comprador);                          
                                if($cantidadStock >= 0)
                                {
                                    if(!$recepcion->partes()->detach($parte->id))
                                    {
                                        $response = HelpController::buildResponse(
                                            500,
                                            'Error al eliminar una parte de la recepcion',
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
                                        'La parte "' . $parte->nparte . '" tiene cantidades que ya fueron despachadas por el comprador',
                                        null
                                    );

                                    $success = false;

                                    break;
                                }
                            }

                            if($success === true)
                            {
                                if($recepcion->delete())
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
                                    
                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al eliminar la recepcion',
                                        null
                                    );
                                }
                            }
                            else
                            {
                                DB::rollback();

                                // The response error message was already set when success = false
                            }
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al obtener la recepcion',
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'La recepcion no existe',
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
     *  Sucursal (centro)
     */

    public function index_centrodistribucion($id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('centrosdistribucion recepciones_index'))
            {
                if($centrodistribucion = Sucursal::where('id', $id)->where('type', 'centro')->first())
                {
                    $centrodistribucion->makeHidden([
                        'created_at', 
                        'updated_at'
                    ]);

                    $centrodistribucion->recepciones;
                    $centrodistribucion->recepciones = $centrodistribucion->recepciones->filter(function($recepcion)
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
                        
                        $recepcion->partes;
                        $recepcion->partes = $recepcion->partes->filter(function($parte)
                        {
                            $parte->makeHidden([
                                'marca_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $parte->pivot->makeHidden([
                                'parte_id',
                                'recepcion_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $parte->marca;
                            $parte->marca->makeHidden(['created_at', 'updated_at']);

                            return $parte;
                        });

                        $recepcion->sourceable;
                        $recepcion->sourceable->makeHidden([
                            'rut',
                            'address',
                            'city',
                            'contact',
                            'phone',
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);

                        $recepcion->sourceable->country;
                        $recepcion->sourceable->country->makeHidden(['created_at', 'updated_at']);

                        return $recepcion;
                    });

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $centrodistribucion->recepciones
                    );
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
                    'No tienes acceso a visualizar recepciones de centro de distribucion',
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


    public function queuePartes_centrodistribucion($centrodistribucion_id, $comprador_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('centrosdistribucion recepciones_store'))
            {
                if($centrodistribucion = Sucursal::where('id', $centrodistribucion_id)->where('type', 'centro')->first())
                {
                    if($comprador = Comprador::find($comprador_id))
                    {
                        // Gets all the partes in Despacho to the Sucursal (centro) from the Comprador
                        $parteDespachoList = ParteDespacho::select('despacho_parte.*')
                                            ->join('despachos', 'despachos.id', '=', 'despacho_parte.despacho_id')
                                            ->where('despachos.despachable_type', get_class($comprador))
                                            ->where('despachos.despachable_id', $comprador->id) // From the Compador
                                            ->where('despachos.destinable_type', get_class($centrodistribucion))
                                            ->where('despachos.destinable_id', $centrodistribucion->id) // To the Sucursal
                                            ->get();


                        $cantidadesDespacho = $parteDespachoList->reduce(function($carry, $parteDespacho)
                            {
                                if(isset($carry[$parteDespacho->parte->id]))
                                {
                                    $carry[$parteDespacho->parte->id] += $parteDespacho->cantidad;
                                }
                                else
                                {
                                    $carry[$parteDespacho->parte->id] = $parteDespacho->cantidad;
                                }

                                return $carry;
                            }, 
                            array()
                        );

                        $success = true;
                        $queuePartes = array();

                        foreach(array_keys($cantidadesDespacho) as $parteId)
                        {
                            if($parte = Parte::find($parteId))
                            {
                                // Get cantidad total in Recepciones at Sucursal (centro) from Comprador
                                $cantidadPendiente = $cantidadesDespacho[$parteId] - $parte->getCantidadRecepcionado_sourceable($centrodistribucion, $comprador);

                                if($cantidadPendiente > 0)
                                {
                                    $parteData = [
                                        "id" => $parte->id,
                                        "nparte" => $parte->nparte,
                                        "marca" => $parte->marca->makeHidden(['created_at', 'updated_at']),
                                        "cantidad_pendiente" => $cantidadPendiente,
                                        "cantidad_despachos" => $parte->getCantidadDespachado($centrodistribucion)
                                    ];

                                    array_push($queuePartes, $parteData);
                                }
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al obtener una de las partes pendientes de recepcion',
                                    null
                                );

                                $success = false;
                                
                                break;
                            }
                        }

                        if($success === true)
                        {
                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $queuePartes
                            );
                        }
                        else
                        {
                            // Response already given when success = false
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
                    'No tienes acceso a visualizar partes pendiente de recepcion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener partes pendiente de recepcion [!]',
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
            if($user->role->hasRoutepermission('centrosdistribucion recepciones_store'))
            {
                $validatorInput = $request->only('comprador_id', 'fecha', 'ndocumento', 'responsable', 'comentario', 'partes');
            
                $validatorRules = [
                    'comprador_id' => 'required|exists:compradores,id',
                    'fecha' => 'required|date_format:Y-m-d|before:tomorrow', // it includes today
                    'ndocumento' => 'nullable|min:1',
                    'responsable' => 'required|min:1',
                    'comentario' => 'sometimes|nullable',
                    'partes' => 'required|array|min:1',
                    'partes.*.id'  => 'required|exists:partes,id',
                    'partes.*.cantidad'  => 'required|numeric|min:1',
                ];
        
                $validatorMessages = [
                    'comprador_id.required' => 'Debes ingresar el comprador',
                    'comprador_id.exists' => 'El comprador ingresado no existe',
                    'fecha.required' => 'Debes ingresar la fecha de recepcion',
                    'fecha.date_format' => 'El formato de fecha de recepcion es invalido',
                    'fecha.before' => 'La fecha debe ser igual o anterior a hoy',
                    'ndocumento.min' => 'El numero de documento debe tener al menos un digito',
                    'responsable.required' => 'Debes ingresar el nombre de la persona que recibe',
                    'responsable.min' => 'El nombre de la persona que recibe debe tener al menos un digito',
                    'partes.required' => 'Debes seleccionar las partes recepcionadas',
                    'partes.array' => 'Lista de partes recepcionadas invalida',
                    'partes.min' => 'La recepcion debe contener al menos 1 parte recepcionada',
                    'partes.*.id.required' => 'La lista de partes recepcionadas es invalida',
                    'partes.*.id.exists' => 'La parte recepcionada ingresada no existe',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte recepcionada',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte recepcionada debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte recepcionada debe ser mayor a 0',
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
                        if($comprador = Comprador::find($request->comprador_id))
                        {
                            DB::beginTransaction();

                            $recepcion = new Recepcion();
                            // Set the morph source for Recepcion as Comprador
                            $recepcion->sourceable_id = $comprador->id;
                            $recepcion->sourceable_type = get_class($comprador);
                            // Set the morph for Recepcion as Sucursal (centro)
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

                                $cantidades = array_reduce($request->partes, function($carry, $parte)
                                    {
                                        $carry[$parte['id']] = $parte['cantidad'];

                                        return $carry;
                                    },
                                    array()
                                );

                                // For each parte sent, gets the ParteDespacho list for Sucursal from the selected Comprador
                                if($parteDespachoList = ParteDespacho::select('despacho_parte.*')
                                                ->join('despachos', 'despachos.id', '=', 'despacho_parte.despacho_id')
                                                ->where('despachos.despachable_type', get_class($comprador))
                                                ->where('despachos.despachable_id', $comprador->id) // From Comprador
                                                ->where('despachos.destinable_type', get_class($centrodistribucion))
                                                ->where('despachos.destinable_id', $centrodistribucion->id) // To Sucursal
                                                ->get()
                                )
                                {
                                    if($parteDespachoList->count() > 0)
                                    {
                                        $cantidadesDespacho = $parteDespachoList->reduce(function($carry, $parteDespacho)
                                            {
                                                if(isset($carry[$parteDespacho->parte->id]))
                                                {
                                                    $carry[$parteDespacho->parte->id] += $parteDespacho->cantidad;
                                                }
                                                else
                                                {
                                                    $carry[$parteDespacho->parte->id] = $parteDespacho->cantidad;
                                                }

                                                return $carry;
                                            }, 
                                            array()
                                        );

                                        $success = true;
                                        foreach(array_keys($cantidades) as $parteId)
                                        {
                                            if($parte = Parte::find($parteId))
                                            {
                                                if(isset($cantidadesDespacho[$parte->id]))
                                                {
                                                    // Get cantidad total in Recepciones at Sucursal from Comprador
                                                    $cantidadPendiente = $cantidadesDespacho[$parte->id] - $parte->getCantidadRecepcionado_sourceable($centrodistribucion, $comprador);

                                                    if($cantidadPendiente > 0)
                                                    {
                                                        if($cantidades[$parte->id] <= $cantidadPendiente)
                                                        {

                                                            $recepcion->partes()->attach(
                                                                array(
                                                                    $parte->id => array(
                                                                        "cantidad" => $cantidades[$parte->id]
                                                                    )
                                                                )
                                                            );

                                                        }
                                                        else
                                                        {
                                                            // If the received parts are more than waiting in queue
                                                            $response = HelpController::buildResponse(
                                                                409,
                                                                'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor a la cantidad de pendiente de recepcion',
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
                                                            'La parte "' . $parte->nparte . '" no tiene partes pendiente de recepcion',
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
                                                        'La parte "' . $parte->nparte . '" no tiene partes pendiente de recepcion',
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
                                                    'Error al obtener una de las partes pendientes de recepcion',
                                                    null
                                                );
                
                                                $success = false;
                                                
                                                break;
                                            }                                   
                                        }
                                    }
                                    else
                                    {
                                        // If there aren't OcParte waiting for the entered Parte
                                        $response = HelpController::buildResponse(
                                            409,
                                            'No se han encontrado partes para recepcionar desde el comprador seleccionado',
                                            null
                                        );
    
                                        $success = false;
                                    }
                                }
                                else
                                {
                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al obtener las partes pendiente de recepcion',
                                        null
                                    );

                                    $success = false;
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
            if($user->role->hasRoutepermission('centrosdistribucion recepciones_show'))
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
                    if($centrodistribucion = Sucursal::where('id', $centrodistribucion_id)->where('type', 'centro')->first())
                    {
                        if($recepcion = $centrodistribucion->recepciones->find($id))
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
                                'country_id',
                                'created_at', 
                                'updated_at'
                            ]);

                            $recepcion->sourceable->country;
                            $recepcion->sourceable->country->makeHidden([
                                'created_at', 
                                'updated_at'
                            ]);

                            $recepcion->partes;
                            foreach($recepcion->partes as $parte)
                            {                                
                                $parte->makeHidden([
                                    'marca_id',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $parte->pivot->makeHidden([
                                    'recepcion_id',
                                    'parte_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $parte->marca;
                                $parte->marca->makeHidden(['created_at', 'updated_at']);
                            }
                            
                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $recepcion
                            );
                        }   
                        else     
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La recepcion no existe',
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
            if($user->role->hasRoutepermission('centrosdistribucion recepciones_update'))
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
                    if($centrodistribucion = Sucursal::where('id', $centrodistribucion_id)->where('type', 'centro')->first())
                    {
                        if($recepcion = $centrodistribucion->recepciones->find($id))
                        {
                            $recepcion->makeHidden([
                                'recepcionable_id',
                                'recepcionable_type',
                                'sourceable_id',
                                'sourceable_type',
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
                                'country_id',
                                'created_at', 
                                'updated_at'
                            ]);

                            $recepcion->sourceable->country;
                            $recepcion->sourceable->country->makeHidden([
                                'created_at', 
                                'updated_at'
                            ]);

                            $recepcion->partes;
                            foreach($recepcion->partes as $parte)
                            {                                
                                $parte->makeHidden([
                                    'marca_id',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $parte->pivot->makeHidden([
                                    'recepcion_id',
                                    'parte_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $parte->marca;
                                $parte->marca->makeHidden(['created_at', 'updated_at']);
                            }

                            if(get_class($recepcion->sourceable) === get_class(new Comprador()))
                            {
                                // Gets all the partes in Despacho to the Sucursal from the Comprador
                                $parteDespachoList = ParteDespacho::select('despacho_parte.*')
                                                    ->join('despachos', 'despachos.id', '=', 'despacho_parte.despacho_id')
                                                    ->where('despachos.despachable_type', get_class($recepcion->sourceable))
                                                    ->where('despachos.despachable_id', $recepcion->sourceable->id) // From the Comprador
                                                    ->where('despachos.destinable_type', get_class($centrodistribucion))
                                                    ->where('despachos.destinable_id', $centrodistribucion->id) // To the Sucursal
                                                    ->get();
                                                    

                                if($parteDespachoList !== null)
                                {
                                    $cantidadesDespacho = $parteDespachoList->reduce(function($carry, $parteDespacho)
                                        {
                                            if(isset($carry[$parteDespacho->parte->id]))
                                            {
                                                $carry[$parteDespacho->parte->id] += $parteDespacho->cantidad;
                                            }
                                            else
                                            {
                                                $carry[$parteDespacho->parte->id] = $parteDespacho->cantidad;
                                            }
    
                                            return $carry;
                                        }, 
                                        array()
                                    );

                                    $success = true;
                                    $queuePartes = array();
    
                                    foreach(array_keys($cantidadesDespacho) as $parteId)
                                    {
                                        if($parte = Parte::find($parteId))
                                        {
                                            // Get cantidad total in Recepciones at Sucursal (centro) from Comprador
                                            $cantidadPendiente = $cantidadesDespacho[$parteId] - $parte->getCantidadRecepcionado_sourceable($centrodistribucion, $recepcion->sourceable);
                                            $cantidadDespachos = 0;

                                            // If the Parte is already in the Recepcion, then add the cantidad to queue calc in Despachos if already taken
                                            if($p = $recepcion->partes->find($parte->id))
                                            {
                                                $cantidadPendiente = $cantidadPendiente + $p->pivot->cantidad;
                                                $cantidadDespachos = $parte->getCantidadEntregado($centrodistribucion) + $parte->getCantidadDespachado($centrodistribucion) - ($parte->getCantidadRecepcionado($centrodistribucion) - $p->pivot->cantidad);
                                                $cantidadDespachos = $cantidadDespachos >= 0 ? $cantidadDespachos : 0;
                                            }

                                            if($cantidadPendiente > 0)
                                            {
                                                $parteData = [
                                                    "id" => $parte->id,
                                                    "nparte" => $parte->nparte,
                                                    "marca" => $parte->marca->makeHidden(['created_at', 'updated_at']),
                                                    "cantidad_pendiente" => $cantidadPendiente,
                                                    "cantidad_despachos" => $cantidadDespachos
                                                ];
    
                                                array_push($queuePartes, $parteData);
                                            }
                                        }
                                        else
                                        {
                                            $response = HelpController::buildResponse(
                                                500,
                                                'Error al obtener una de las partes pendientes de recepcion',
                                                null
                                            );
    
                                            $success = false;
                                            
                                            break;
                                        }
                                    }

                                    if($success === true)
                                    {
                                        $data = [
                                            'recepcion' => $recepcion,
                                            'queue_partes' => $queuePartes
                                        ];
        
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
                                            'Error al obtener partes pendiente de recepcion',
                                            null
                                        );
                                    }
                                }
                                else
                                {
                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al obtener partes pendiente de recepcion',
                                        null
                                    );
                                }
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al obtener el comprador de la recepcion',
                                    null
                                );
                            }
                        }   
                        else     
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La recepcion no existe',
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
            if($user->role->hasRoutepermission('centrosdistribucion recepciones_update'))
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
                    'fecha.required' => 'Debes ingresar la fecha de recepcion',
                    'fecha.date_format' => 'El formato de fecha de recepcion es invalido',
                    'fecha.before' => 'La fecha debe ser igual o anterior a hoy',
                    'ndocumento.min' => 'El numero de documento debe tener al menos un digito',
                    'responsable.required' => 'Debes ingresar el nombre de la persona que recepciona',
                    'responsable.min' => 'El nombre de la persona que recepciona debe tener al menos un digito',
                    'partes.required' => 'Debes seleccionar las partes recepcionadas',
                    'partes.array' => 'Lista de partes recepcionadas invalida',
                    'partes.min' => 'La recepcion debe contener al menos 1 parte recepcionada',
                    'partes.*.id.required' => 'La lista de partes recepcionadas es invalida',
                    'partes.*.id.exists' => 'La parte recepcionada ingresada no existe',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte recepcionada',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte recepcionada debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte recepcionada debe ser mayor a 0',
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
                        if($recepcion = $centrodistribucion->recepciones->find($id))
                        {
                            DB::beginTransaction();

                            // Fill the data
                            $recepcion->fecha = $request->fecha;
                            $recepcion->ndocumento = $request->ndocumento;
                            $recepcion->responsable = $request->responsable;
                            $recepcion->comentario = $request->comentario;

                            if($recepcion->save())
                            {
                                // Gets all the partes in Despacho to the Sucursal (centro) from the Comprador
                                if(
                                    $parteDespachoList = ParteDespacho::select('despacho_parte.*')
                                                        ->join('despachos', 'despachos.id', '=', 'despacho_parte.despacho_id')
                                                        ->where('despachos.despachable_type', get_class($recepcion->sourceable))
                                                        ->where('despachos.despachable_id', $recepcion->sourceable->id) // From the Comprador
                                                        ->where('despachos.destinable_type', get_class($centrodistribucion))
                                                        ->where('despachos.destinable_id', $centrodistribucion->id) // To the Sucursal
                                                        ->get()
                                )
                                {
                                    if($parteDespachoList->count() > 0)
                                    {
                                        $despachoCantidades = $parteDespachoList->reduce(function($carry, $parteDespacho)
                                            {
                                                if(isset($carry[$parteDespacho->parte->id]))
                                                {
                                                    $carry[$parteDespacho->parte->id] += $parteDespacho->cantidad;
                                                }
                                                else
                                                {
                                                    $carry[$parteDespacho->parte->id] = $parteDespacho->cantidad;
                                                }

                                                return $carry;
                                            }, 
                                            array()
                                        );

                                        // Get new cantidades array
                                        $newCantidades = array_reduce($request->partes, function($carry, $parte)
                                            {
                                                $carry[$parte['id']] = $parte['cantidad'];

                                                return $carry;
                                            },
                                            array()
                                        );


                                        // Get previous cantidades array
                                        $previousCantidades = $recepcion->partes->reduce(function($carry, $parte)
                                            {
                                                if(isset($carry[$parte->id]))
                                                {
                                                    // If parte is already in the list, adds the cantidad in ParteRecepcion to the total
                                                    $carry[$parte->id] += $parte->pivot->cantidad;
                                                }
                                                else
                                                {
                                                    // If parte is not in the list, inserts the parte to the list
                                                    $carry[$parte->id] = $parte->pivot->cantidad;
                                                }

                                                return $carry;
                                            },
                                            array()
                                        );

                                        // Define diff cantidades array between the previous and the new one
                                        $diffCantidades = array();
                                        // For all the previous partes
                                        foreach(array_keys($previousCantidades) as $parteId)
                                        {
                                            if(isset($newCantidades[$parteId]))
                                            {
                                                // If it comes in the new list, sets the new cantidad
                                                $diffCantidades[$parteId] = $newCantidades[$parteId] - $previousCantidades[$parteId];
                                            }
                                            else
                                            {
                                                // If it doesn't come, rest the previous cantidad as negative (the ParteRecepcion will be removed)
                                                $diffCantidades[$parteId] = $previousCantidades[$parteId] * -1;
                                            }
                                        }

                                        // If there are new partes weren't catched in previous list, then add them to the diff list
                                        foreach(array_keys($newCantidades) as $parteId)
                                        {
                                            // If the new parte isn't in the diff list
                                            if(!isset($diffCantidades[$parteId]))
                                            {
                                                // Add the new parte to the diff list
                                                $diffCantidades[$parteId] = $newCantidades[$parteId];
                                            }
                                        }

                                        $success = true;

                                        // For all the partes in diff list
                                        foreach(array_keys($diffCantidades) as $parteId)
                                        {
                                            if(isset($despachoCantidades[$parteId]))
                                            {
                                                // If we are removing parts from the recepcion
                                                if($diffCantidades[$parteId] < 0)
                                                {       
                                                    if($parte = $recepcion->partes->find($parteId))
                                                    {
                                                        // Calc pendiente using cantidad in Despachos - cantidad in Recepciones for Sucursal (centro) - diff (negative sum)
                                                        $cantidadPendiente = $despachoCantidades[$parteId] - $parte->getCantidadRecepcionado_sourceable($centrodistribucion, $recepcion->sourceable) - $diffCantidades[$parteId];
                                                        if($cantidadPendiente >= 0)
                                                        {
                                                            // Calc stock using cantidad in Recepciones for Sucursal (centro) - diff (negative sum)  - cantidad in Entregas - cantidad in Despachos
                                                            $cantidadStock = $parte->getCantidadRecepcionado($centrodistribucion) + $diffCantidades[$parteId] - $parte->getCantidadEntregado($centrodistribucion) - $parte->getCantidadDespachado($centrodistribucion);                          
                                                            if($cantidadStock >= 0)
                                                            {
                                                                // If cantidad the same than in previousCantidad, we're removing the ParteRecepcion
                                                                if($previousCantidades[$parteId] === abs($diffCantidades[$parteId]))
                                                                {
                                                                    if(!$recepcion->partes()->detach($parteId))
                                                                    {
                                                                        $response = HelpController::buildResponse(
                                                                            500,
                                                                            'Error al eliminar una parte de la recepcion',
                                                                            null
                                                                        );
                                    
                                                                        $success = false;
                                    
                                                                        break;
                                                                    }
                                                                }
                                                                else
                                                                {
                                                                    // Set new cantidad adding the negative diff
                                                                    $parte->pivot->cantidad = $parte->pivot->cantidad + $diffCantidades[$parteId];
                                                                    if(!$parte->pivot->save())
                                                                    {
                                                                        $response = HelpController::buildResponse(
                                                                            500,
                                                                            'Error al actualizar una parte de la recepcion',
                                                                            null
                                                                        );
                                    
                                                                        $success = false;
                                    
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                            else
                                                            {
                                                                // If the received parts are more than waiting in queue
                                                                $response = HelpController::buildResponse(
                                                                    409,
                                                                    'La cantidad ingresada para la parte "' . $parte->nparte . '" es menor a la cantidad ya despachada',
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
                                                                'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor a la cantidad pendiente de recepcion',
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
                                                            'Error al obtener una de las partes pendientes de recepcion',
                                                            null
                                                        );
            
                                                        $success = false;
            
                                                        break;
                                                    }
                                                }
                                                // If we are adding more parts to the recepcion
                                                else if($diffCantidades[$parteId] > 0)
                                                {
                                                    // If the Parte is kept in Recepcion
                                                    if(isset($previousCantidades[$parteId]))
                                                    {
                                                        if($parte = $recepcion->partes->find($parteId))
                                                        {
                                                            // Calc pendiente using cantidad in Despachos - cantidad in Recepciones for Sucursal (centro) - diff
                                                            $cantidadPendiente = $despachoCantidades[$parteId] - $parte->getCantidadRecepcionado_sourceable($centrodistribucion, $recepcion->sourceable) - $diffCantidades[$parteId];
                                                            if($cantidadPendiente >= 0)
                                                            {
                                                                // Calc stock using cantidad in Recepciones for Sucursal (centro) + diff - cantidad in Entregas - cantidad in Despachos
                                                                $cantidadStock = $parte->getCantidadRecepcionado($centrodistribucion) + $diffCantidades[$parteId] - $parte->getCantidadEntregado($centrodistribucion) - $parte->getCantidadDespachado($centrodistribucion);                          
                                                                if($cantidadStock >= 0)
                                                                {
                                                                    // Set new cantidad adding the negative diff
                                                                    $parte->pivot->cantidad = $parte->pivot->cantidad + $diffCantidades[$parteId];
                                                                    if(!$parte->pivot->save())
                                                                    {
                                                                        $response = HelpController::buildResponse(
                                                                            500,
                                                                            'Error al actualizar una parte de la recepcion',
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
                                                                        'La cantidad ingresada para la parte "' . $parte->nparte . '" es menor a la cantidad ya despachada',
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
                                                                    'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor a la cantidad pendiente de recepcion',
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
                                                                'Error al obtener una de las partes pendientes de recepcion',
                                                                null
                                                            );
                
                                                            $success = false;
                
                                                            break;
                                                        }
                                                    }
                                                    // If is a new Parte in Recepcion
                                                    else
                                                    {
                                                        if($parte = Parte::find($parteId))
                                                        {
                                                            // Calc pendiente using cantidad in Despachos - cantidad in Recepciones from Comprador - diff
                                                            $cantidadPendiente = $despachoCantidades[$parteId] - $parte->getCantidadRecepcionado_sourceable($centrodistribucion, $recepcion->sourceable) - $diffCantidades[$parteId];
                                                            if($cantidadPendiente >= 0)
                                                            {
                                                                // Calc stock using cantidad in Recepciones for Sucursal (centro) + diff - cantidad in Entregas - cantidad ni Despachos
                                                                $cantidadStock = $parte->getCantidadRecepcionado($centrodistribucion) + $diffCantidades[$parteId] - $parte->getCantidadEntregado($centrodistribucion) - $parte->getCantidadDespachado($centrodistribucion);                          
                                                                if($cantidadStock >= 0)
                                                                {
                                                                    // Add the new Parte to Recepcion
                                                                    $recepcion->partes()->attach(
                                                                        array(
                                                                            $parteId => array(
                                                                                "cantidad" => $diffCantidades[$parteId] // For a new Parte, diff contains the full cantidad
                                                                            )
                                                                        )
                                                                    );
                                                                }
                                                                else
                                                                {
                                                                    // If the received parts are more than waiting in queue
                                                                    $response = HelpController::buildResponse(
                                                                        409,
                                                                        'La cantidad ingresada para la parte "' . $parte->nparte . '" es menor a la cantidad ya despachada',
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
                                                                    'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor a la cantidad pendiente de recepcion',
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
                                                                'Error al obtener una de las partes pendientes de recepcion',
                                                                null
                                                            );
                
                                                            $success = false;
                
                                                            break;
                                                        }
                                                    }
                                                }
                                                // If the have the same quantity, nothing changes (diff = 0)
                                                else
                                                {
                                                    //Do nothing, continue the loop
                                                }
                                            }
                                            else
                                            {
                                                $response = HelpController::buildResponse(
                                                    500,
                                                    'Error al obtener una de las partes pendientes de recepcion',
                                                    null
                                                );
    
                                                $success = false;
    
                                                break;
                                            }
                                        }
                                    }
                                    else
                                    {
                                        // If there aren't OcParte waiting for the entered Parte
                                        $response = HelpController::buildResponse(
                                            409,
                                            'No se han encontrado partes para recepcionar',
                                            null
                                        );

                                        $success = false;
                                    }
                                }
                                else
                                {
                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al obtener las partes pendiente de recepcion',
                                        null
                                    );

                                    $success = false;
                                
                                }

                                if($success === true)
                                {
                                    DB::commit();
                                    
                                    $response = HelpController::buildResponse(
                                        200,
                                        'Recepcion actualizada',
                                        null
                                    );
                                }
                                else
                                {
                                    DB::rollback();

                                    // The response error message was already set when success = false
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
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La recepcion no existe',
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
            if($user->role->hasRoutepermission('centrosdistribucion recepciones_destroy'))
            {
                if($centrodistribucion = Sucursal::where('id', $centrodistribucion_id)->where('type', 'centro')->first())
                {
                    if($recepcion = $centrodistribucion->recepciones->find($id))
                    {
                        // Check if Recepcion was sourceable by a Comprador
                        if(get_class($recepcion->sourceable) === get_class(new Comprador()))
                        {
                            DB::beginTransaction();

                            $success = true;

                            // For all the partes in diff list
                            foreach($recepcion->partes as $parte)
                            {                                                                       
                                // Calc stock using cantidad in Recepciones for Sucursal (centro) - cantidad in Recepcion - cantidad in Entregas - cantidad in Despachos
                                $cantidadStock = $parte->getCantidadRecepcionado($centrodistribucion) - $parte->pivot->cantidad - $parte->getCantidadEntregado($centrodistribucion) - $parte->getCantidadDespachado($centrodistribucion);                          
                                if($cantidadStock >= 0)
                                {
                                    if(!$recepcion->partes()->detach($parte->id))
                                    {
                                        $response = HelpController::buildResponse(
                                            500,
                                            'Error al eliminar una parte de la recepcion',
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
                                        'La parte "' . $parte->nparte . '" tiene cantidades que ya fueron despachadas por el centro de distribucion',
                                        null
                                    );

                                    $success = false;

                                    break;
                                }
                            }

                            if($success === true)
                            {
                                if($recepcion->delete())
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
                                    
                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al eliminar la recepcion',
                                        null
                                    );
                                }
                            }
                            else
                            {
                                DB::rollback();

                                // The response error message was already set when success = false
                            }
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al obtener la recepcion',
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'La recepcion no existe',
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
                    'No tienes acceso a eliminar recepciones para centro de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al eliminar la recepcion [!]' . $e,
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
            if($user->role->hasRoutepermission('sucursales recepciones_index'))
            {
                if($sucursal = Sucursal::where('id', $id)->where('type', 'sucursal')->first())
                {
                    $sucursal->makeHidden([
                        'created_at', 
                        'updated_at'
                    ]);

                    $sucursal->recepciones;
                    $sucursal->recepciones = $sucursal->recepciones->filter(function($recepcion)
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
                        
                        $recepcion->partes;
                        $recepcion->partes = $recepcion->partes->filter(function($parte)
                        {
                            $parte->makeHidden([
                                'marca_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $parte->pivot->makeHidden([
                                'parte_id',
                                'recepcion_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $parte->marca;
                            $parte->marca->makeHidden(['created_at', 'updated_at']);

                            return $parte;
                        });

                        $recepcion->sourceable;
                        $recepcion->sourceable->makeHidden([
                            'type',
                            'rut',
                            'address',
                            'city',
                            'contact',
                            'phone',
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);

                        return $recepcion;
                    });

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $sucursal->recepciones
                    );
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
                    'No tienes acceso a visualizar recepciones de sucursal',
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


    public function queuePartes_sucursal($sucursal_id, $centrodistribucion_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales recepciones_store'))
            {
                if($sucursal = Sucursal::where('id', $sucursal_id)
                            ->where('type', 'sucursal')
                            ->first()
                )
                {
                    if($centrodistribucion = Sucursal::where('id', $centrodistribucion_id)
                                            ->where('type', 'centro')
                                            ->where('country_id', '=', $sucursal->country_id)
                                            ->first()
                    )
                    {
                        // Gets all the partes in Despacho to the Sucursal from the Sucursal (centro)
                        $parteDespachoList = ParteDespacho::select('despacho_parte.*')
                                            ->join('despachos', 'despachos.id', '=', 'despacho_parte.despacho_id')
                                            ->where('despachos.despachable_type', get_class($centrodistribucion))
                                            ->where('despachos.despachable_id', $centrodistribucion->id) // From the Sucursal (centro)
                                            ->where('despachos.destinable_type', get_class($sucursal))
                                            ->where('despachos.destinable_id', $sucursal->id) // To the Sucursal
                                            ->get();


                        $cantidadesDespacho = $parteDespachoList->reduce(function($carry, $parteDespacho)
                            {
                                if(isset($carry[$parteDespacho->parte->id]))
                                {
                                    $carry[$parteDespacho->parte->id] += $parteDespacho->cantidad;
                                }
                                else
                                {
                                    $carry[$parteDespacho->parte->id] = $parteDespacho->cantidad;
                                }

                                return $carry;
                            }, 
                            array()
                        );

                        $success = true;
                        $queuePartes = array();

                        foreach(array_keys($cantidadesDespacho) as $parteId)
                        {
                            if($parte = Parte::find($parteId))
                            {
                                // Get cantidad total in Recepciones at Sucursal from Sucursal (centro)
                                $cantidadPendiente = $cantidadesDespacho[$parteId] - $parte->getCantidadRecepcionado_sourceable($sucursal, $centrodistribucion);

                                if($cantidadPendiente > 0)
                                {
                                    $parteData = [
                                        "id" => $parte->id,
                                        "nparte" => $parte->nparte,
                                        "marca" => $parte->marca->makeHidden(['created_at', 'updated_at']),
                                        "cantidad_pendiente" => $cantidadPendiente,
                                        "cantidad_despachos" => $parte->getCantidadDespachado($sucursal) + $parte->getCantidadEntregado($sucursal) 
                                    ];

                                    array_push($queuePartes, $parteData);
                                }
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al obtener una de las partes pendientes de recepcion',
                                    null
                                );

                                $success = false;
                                
                                break;
                            }
                        }

                        if($success === true)
                        {
                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $queuePartes
                            );
                        }
                        else
                        {
                            // Response already given when success = false
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
                    'No tienes acceso a visualizar partes pendiente de recepcion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener partes pendiente de recepcion [!]',
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
                $validatorInput = $request->only('sucursal_id', 'fecha', 'ndocumento', 'responsable', 'comentario', 'partes');
            
                $validatorRules = [
                    'sucursal_id' => 'required|exists:sucursales,id,type,"centro"', // Source is Sucursal (centro)
                    'fecha' => 'required|date_format:Y-m-d|before:tomorrow', // it includes today
                    'ndocumento' => 'nullable|min:1',
                    'responsable' => 'required|min:1',
                    'comentario' => 'sometimes|nullable',
                    'partes' => 'required|array|min:1',
                    'partes.*.id'  => 'required|exists:partes,id',
                    'partes.*.cantidad'  => 'required|numeric|min:1',
                ];
        
                $validatorMessages = [
                    'sucursal_id.required' => 'Debes ingresar el centro de distribucion',
                    'sucursal_id.exists' => 'El centro de distribucion ingresado no existe',
                    'fecha.required' => 'Debes ingresar la fecha de recepcion',
                    'fecha.date_format' => 'El formato de fecha de recepcion es invalido',
                    'fecha.before' => 'La fecha debe ser igual o anterior a hoy',
                    'ndocumento.min' => 'El numero de documento debe tener al menos un digito',
                    'responsable.required' => 'Debes ingresar el nombre de la persona que recibe',
                    'responsable.min' => 'El nombre de la persona que recibe debe tener al menos un digito',
                    'partes.required' => 'Debes seleccionar las partes recepcionadas',
                    'partes.array' => 'Lista de partes recepcionadas invalida',
                    'partes.min' => 'La recepcion debe contener al menos 1 parte recepcionada',
                    'partes.*.id.required' => 'La lista de partes recepcionadas es invalida',
                    'partes.*.id.exists' => 'La parte recepcionada ingresada no existe',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte recepcionada',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte recepcionada debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte recepcionada debe ser mayor a 0',
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
                    if($sucursal = Sucursal::where('id', $sucursal_id)
                            ->where('type', 'sucursal')
                            ->first()
                    )
                    {
                        if($centrodistribucion = Sucursal::where('id', $request->sucursal_id)
                                                ->where('type', 'centro')
                                                ->where('country_id', '=', $sucursal->country_id)
                                                ->first()
                        )
                        {
                            DB::beginTransaction();

                            $recepcion = new Recepcion();
                            // Set the morph source for Recepcion as Sucursal (centro)
                            $recepcion->sourceable_id = $centrodistribucion->id;
                            $recepcion->sourceable_type = get_class($centrodistribucion);
                            // Set the morph for Recepcion as Sucursal
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

                                $cantidades = array_reduce($request->partes, function($carry, $parte)
                                    {
                                        $carry[$parte['id']] = $parte['cantidad'];

                                        return $carry;
                                    },
                                    array()
                                );

                                // For each parte sent, gets the ParteDespacho list for Sucursal from the selected Sucursal (centro)
                                if($parteDespachoList = ParteDespacho::select('despacho_parte.*')
                                                ->join('despachos', 'despachos.id', '=', 'despacho_parte.despacho_id')
                                                ->where('despachos.despachable_type', get_class($centrodistribucion))
                                                ->where('despachos.despachable_id', $centrodistribucion->id) // From Sucursal (centro)
                                                ->where('despachos.destinable_type', get_class($sucursal))
                                                ->where('despachos.destinable_id', $sucursal->id) // To Sucursal
                                                ->get()
                                )
                                {
                                    if($parteDespachoList->count() > 0)
                                    {
                                        $cantidadesDespacho = $parteDespachoList->reduce(function($carry, $parteDespacho)
                                            {
                                                if(isset($carry[$parteDespacho->parte->id]))
                                                {
                                                    $carry[$parteDespacho->parte->id] += $parteDespacho->cantidad;
                                                }
                                                else
                                                {
                                                    $carry[$parteDespacho->parte->id] = $parteDespacho->cantidad;
                                                }

                                                return $carry;
                                            }, 
                                            array()
                                        );

                                        $success = true;
                                        foreach(array_keys($cantidades) as $parteId)
                                        {
                                            if($parte = Parte::find($parteId))
                                            {
                                                if(isset($cantidadesDespacho[$parte->id]))
                                                {
                                                    // Get cantidad total in Recepciones at Sucursal from Sucursal (centro)
                                                    $cantidadPendiente = $cantidadesDespacho[$parte->id] - $parte->getCantidadRecepcionado_sourceable($sucursal, $centrodistribucion);

                                                    if($cantidadPendiente > 0)
                                                    {
                                                        if($cantidades[$parte->id] <= $cantidadPendiente)
                                                        {

                                                            $recepcion->partes()->attach(
                                                                array(
                                                                    $parte->id => array(
                                                                        "cantidad" => $cantidades[$parte->id]
                                                                    )
                                                                )
                                                            );

                                                        }
                                                        else
                                                        {
                                                            // If the received parts are more than waiting in queue
                                                            $response = HelpController::buildResponse(
                                                                409,
                                                                'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor a la cantidad de pendiente de recepcion',
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
                                                            'La parte "' . $parte->nparte . '" no tiene partes pendiente de recepcion',
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
                                                        'La parte "' . $parte->nparte . '" no tiene partes pendiente de recepcion',
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
                                                    'Error al obtener una de las partes pendientes de recepcion',
                                                    null
                                                );
                
                                                $success = false;
                                                
                                                break;
                                            }                                   
                                        }
                                    }
                                    else
                                    {
                                        // If there aren't OcParte waiting for the entered Parte
                                        $response = HelpController::buildResponse(
                                            409,
                                            'No se han encontrado partes para recepcionar desde el centro de distribucion seleccionado',
                                            null
                                        );
    
                                        $success = false;
                                    }
                                }
                                else
                                {
                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al obtener las partes pendiente de recepcion',
                                        null
                                    );

                                    $success = false;
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
                    if($sucursal = Sucursal::where('id', $sucursal_id)->where('type', 'sucursal')->first())
                    {
                        if($recepcion = $sucursal->recepciones->find($id))
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
                                'type',
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
                                'type',
                                'rut',
                                'address',
                                'city',
                                'contact',
                                'phone',
                                'country_id',
                                'created_at', 
                                'updated_at'
                            ]);

                            $recepcion->partes;
                            foreach($recepcion->partes as $parte)
                            {                                
                                $parte->makeHidden([
                                    'marca_id',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $parte->pivot->makeHidden([
                                    'recepcion_id',
                                    'parte_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $parte->marca;
                                $parte->marca->makeHidden(['created_at', 'updated_at']);
                            }
                            
                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $recepcion
                            );
                        }   
                        else     
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La recepcion no existe',
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
                    if($sucursal = Sucursal::where('id', $sucursal_id)->where('type', 'sucursal')->first())
                    {
                        if($recepcion = $sucursal->recepciones->find($id))
                        {
                            $recepcion->makeHidden([
                                'recepcionable_id',
                                'recepcionable_type',
                                'sourceable_id',
                                'sourceable_type',
                                'partes_total',
                                'updated_at',
                            ]);

                            $recepcion->recepcionable;
                            $recepcion->recepcionable->makeHidden([
                                'type',
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
                                'type',
                                'rut',
                                'address',
                                'city',
                                'contact',
                                'phone',
                                'country_id',
                                'created_at', 
                                'updated_at'
                            ]);

                            $recepcion->partes;
                            foreach($recepcion->partes as $parte)
                            {                                
                                $parte->makeHidden([
                                    'marca_id',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $parte->pivot->makeHidden([
                                    'recepcion_id',
                                    'parte_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $parte->marca;
                                $parte->marca->makeHidden(['created_at', 'updated_at']);
                            }

                            if(get_class($recepcion->sourceable) === get_class(new Sucursal()))
                            {
                                // Gets all the partes in Despacho to the Sucursal (centro) from the Sucursal
                                $parteDespachoList = ParteDespacho::select('despacho_parte.*')
                                                    ->join('despachos', 'despachos.id', '=', 'despacho_parte.despacho_id')
                                                    ->where('despachos.despachable_type', get_class($recepcion->sourceable))
                                                    ->where('despachos.despachable_id', $recepcion->sourceable->id) // From the Sucursal (centro)
                                                    ->where('despachos.destinable_type', get_class($sucursal))
                                                    ->where('despachos.destinable_id', $sucursal->id) // To the Sucursal
                                                    ->get();
                                                    

                                if($parteDespachoList !== null)
                                {
                                    $cantidadesDespacho = $parteDespachoList->reduce(function($carry, $parteDespacho)
                                        {
                                            if(isset($carry[$parteDespacho->parte->id]))
                                            {
                                                $carry[$parteDespacho->parte->id] += $parteDespacho->cantidad;
                                            }
                                            else
                                            {
                                                $carry[$parteDespacho->parte->id] = $parteDespacho->cantidad;
                                            }
    
                                            return $carry;
                                        }, 
                                        array()
                                    );

                                    $success = true;
                                    $queuePartes = array();
    
                                    foreach(array_keys($cantidadesDespacho) as $parteId)
                                    {
                                        if($parte = Parte::find($parteId))
                                        {
                                            // Get cantidad total in Recepciones at Sucursal from Sucursal (centro)
                                            $cantidadPendiente = $cantidadesDespacho[$parteId] - $parte->getCantidadRecepcionado_sourceable($sucursal, $recepcion->sourceable);
                                            $cantidadDespachos = 0;

                                            // If the Parte is already in the Recepcion, then add the cantidad to queue calc in Entregas if already taken
                                            if($p = $recepcion->partes->find($parte->id))
                                            {
                                                $cantidadPendiente = $cantidadPendiente + $p->pivot->cantidad;
                                                $cantidadDespachos = $parte->getCantidadEntregado($sucursal) - ($parte->getCantidadRecepcionado($sucursal) - $p->pivot->cantidad);
                                                $cantidadDespachos = $cantidadDespachos >= 0 ? $cantidadDespachos : 0;
                                            }

                                            if($cantidadPendiente > 0)
                                            {
                                                $parteData = [
                                                    "id" => $parte->id,
                                                    "nparte" => $parte->nparte,
                                                    "marca" => $parte->marca->makeHidden(['created_at', 'updated_at']),
                                                    "cantidad_pendiente" => $cantidadPendiente,
                                                    "cantidad_despachos" => $cantidadDespachos
                                                ];
    
                                                array_push($queuePartes, $parteData);
                                            }
                                        }
                                        else
                                        {
                                            $response = HelpController::buildResponse(
                                                500,
                                                'Error al obtener una de las partes pendientes de recepcion',
                                                null
                                            );
    
                                            $success = false;
                                            
                                            break;
                                        }
                                    }

                                    if($success === true)
                                    {
                                        $data = [
                                            'recepcion' => $recepcion,
                                            'queue_partes' => $queuePartes
                                        ];
        
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
                                            'Error al obtener partes pendiente de recepcion',
                                            null
                                        );
                                    }
                                }
                                else
                                {
                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al obtener partes pendiente de recepcion',
                                        null
                                    );
                                }
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al obtener el centro de distribucion de la recepcion',
                                    null
                                );
                            }
                        }   
                        else     
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La recepcion no existe',
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
                    'fecha.required' => 'Debes ingresar la fecha de recepcion',
                    'fecha.date_format' => 'El formato de fecha de recepcion es invalido',
                    'fecha.before' => 'La fecha debe ser igual o anterior a hoy',
                    'ndocumento.min' => 'El numero de documento debe tener al menos un digito',
                    'responsable.required' => 'Debes ingresar el nombre de la persona que recepciona',
                    'responsable.min' => 'El nombre de la persona que recepciona debe tener al menos un digito',
                    'partes.required' => 'Debes seleccionar las partes recepcionadas',
                    'partes.array' => 'Lista de partes recepcionadas invalida',
                    'partes.min' => 'La recepcion debe contener al menos 1 parte recepcionada',
                    'partes.*.id.required' => 'La lista de partes recepcionadas es invalida',
                    'partes.*.id.exists' => 'La parte recepcionada ingresada no existe',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte recepcionada',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte recepcionada debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte recepcionada debe ser mayor a 0',
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
                        if($recepcion = $sucursal->recepciones->find($id))
                        {
                            DB::beginTransaction();

                            // Fill the data
                            $recepcion->fecha = $request->fecha;
                            $recepcion->ndocumento = $request->ndocumento;
                            $recepcion->responsable = $request->responsable;
                            $recepcion->comentario = $request->comentario;

                            if($recepcion->save())
                            {
                                // Gets all the partes in Despacho to the Sucursal from the Sucursal (centro)
                                if(
                                    $parteDespachoList = ParteDespacho::select('despacho_parte.*')
                                                        ->join('despachos', 'despachos.id', '=', 'despacho_parte.despacho_id')
                                                        ->where('despachos.despachable_type', get_class($recepcion->sourceable))
                                                        ->where('despachos.despachable_id', $recepcion->sourceable->id) // From the Sucursal (centro)
                                                        ->where('despachos.destinable_type', get_class($sucursal))
                                                        ->where('despachos.destinable_id', $sucursal->id) // To the Sucursal
                                                        ->get()
                                )
                                {
                                    if($parteDespachoList->count() > 0)
                                    {
                                        $despachoCantidades = $parteDespachoList->reduce(function($carry, $parteDespacho)
                                            {
                                                if(isset($carry[$parteDespacho->parte->id]))
                                                {
                                                    $carry[$parteDespacho->parte->id] += $parteDespacho->cantidad;
                                                }
                                                else
                                                {
                                                    $carry[$parteDespacho->parte->id] = $parteDespacho->cantidad;
                                                }

                                                return $carry;
                                            }, 
                                            array()
                                        );

                                        // Get new cantidades array
                                        $newCantidades = array_reduce($request->partes, function($carry, $parte)
                                            {
                                                $carry[$parte['id']] = $parte['cantidad'];

                                                return $carry;
                                            },
                                            array()
                                        );


                                        // Get previous cantidades array
                                        $previousCantidades = $recepcion->partes->reduce(function($carry, $parte)
                                            {
                                                if(isset($carry[$parte->id]))
                                                {
                                                    // If parte is already in the list, adds the cantidad in ParteRecepcion to the total
                                                    $carry[$parte->id] += $parte->pivot->cantidad;
                                                }
                                                else
                                                {
                                                    // If parte is not in the list, inserts the parte to the list
                                                    $carry[$parte->id] = $parte->pivot->cantidad;
                                                }

                                                return $carry;
                                            },
                                            array()
                                        );

                                        // Define diff cantidades array between the previous and the new one
                                        $diffCantidades = array();
                                        // For all the previous partes
                                        foreach(array_keys($previousCantidades) as $parteId)
                                        {
                                            if(isset($newCantidades[$parteId]))
                                            {
                                                // If it comes in the new list, sets the new cantidad
                                                $diffCantidades[$parteId] = $newCantidades[$parteId] - $previousCantidades[$parteId];
                                            }
                                            else
                                            {
                                                // If it doesn't come, rest the previous cantidad as negative (the ParteRecepcion will be removed)
                                                $diffCantidades[$parteId] = $previousCantidades[$parteId] * -1;
                                            }
                                        }

                                        // If there are new partes weren't catched in previous list, then add them to the diff list
                                        foreach(array_keys($newCantidades) as $parteId)
                                        {
                                            // If the new parte isn't in the diff list
                                            if(!isset($diffCantidades[$parteId]))
                                            {
                                                // Add the new parte to the diff list
                                                $diffCantidades[$parteId] = $newCantidades[$parteId];
                                            }
                                        }

                                        $success = true;

                                        // For all the partes in diff list
                                        foreach(array_keys($diffCantidades) as $parteId)
                                        {
                                            if(isset($despachoCantidades[$parteId]))
                                            {
                                                // If we are removing parts from the recepcion
                                                if($diffCantidades[$parteId] < 0)
                                                {       
                                                    if($parte = $recepcion->partes->find($parteId))
                                                    {
                                                        // Calc pendiente using cantidad in Despachos - cantidad in Recepciones for Sucursal - diff (negative sum)
                                                        $cantidadPendiente = $despachoCantidades[$parteId] - $parte->getCantidadRecepcionado_sourceable($sucursal, $recepcion->sourceable) - $diffCantidades[$parteId];
                                                        if($cantidadPendiente >= 0)
                                                        {
                                                            // Calc stock using cantidad in Recepciones for Sucursal - diff (negative sum)  - cantidad in Entregas
                                                            $cantidadStock = $parte->getCantidadRecepcionado($sucursal) + $diffCantidades[$parteId] - $parte->getCantidadEntregado($sucursal);                          
                                                            if($cantidadStock >= 0)
                                                            {
                                                                // If cantidad the same than in previousCantidad, we're removing the ParteRecepcion
                                                                if($previousCantidades[$parteId] === abs($diffCantidades[$parteId]))
                                                                {
                                                                    if(!$recepcion->partes()->detach($parteId))
                                                                    {
                                                                        $response = HelpController::buildResponse(
                                                                            500,
                                                                            'Error al eliminar una parte de la recepcion',
                                                                            null
                                                                        );
                                    
                                                                        $success = false;
                                    
                                                                        break;
                                                                    }
                                                                }
                                                                else
                                                                {
                                                                    // Set new cantidad adding the negative diff
                                                                    $parte->pivot->cantidad = $parte->pivot->cantidad + $diffCantidades[$parteId];
                                                                    if(!$parte->pivot->save())
                                                                    {
                                                                        $response = HelpController::buildResponse(
                                                                            500,
                                                                            'Error al actualizar una parte de la recepcion',
                                                                            null
                                                                        );
                                    
                                                                        $success = false;
                                    
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                            else
                                                            {
                                                                // If the received parts are more than waiting in queue
                                                                $response = HelpController::buildResponse(
                                                                    409,
                                                                    'La cantidad ingresada para la parte "' . $parte->nparte . '" es menor a la cantidad ya despachada',
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
                                                                'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor a la cantidad pendiente de recepcion',
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
                                                            'Error al obtener una de las partes pendientes de recepcion',
                                                            null
                                                        );
            
                                                        $success = false;
            
                                                        break;
                                                    }
                                                }
                                                // If we are adding more parts to the recepcion
                                                else if($diffCantidades[$parteId] > 0)
                                                {
                                                    // If the Parte is kept in Recepcion
                                                    if(isset($previousCantidades[$parteId]))
                                                    {
                                                        if($parte = $recepcion->partes->find($parteId))
                                                        {
                                                            // Calc pendiente using cantidad in Despachos - cantidad in Recepciones for Sucursal - diff
                                                            $cantidadPendiente = $despachoCantidades[$parteId] - $parte->getCantidadRecepcionado_sourceable($sucursal, $recepcion->sourceable) - $diffCantidades[$parteId];
                                                            if($cantidadPendiente >= 0)
                                                            {
                                                                // Calc stock using cantidad in Recepciones for Sucursal + diff - cantidad in Entregas
                                                                $cantidadStock = $parte->getCantidadRecepcionado($sucursal) + $diffCantidades[$parteId] - $parte->getCantidadEntregado($sucursal);                          
                                                                if($cantidadStock >= 0)
                                                                {
                                                                    // Set new cantidad adding the negative diff
                                                                    $parte->pivot->cantidad = $parte->pivot->cantidad + $diffCantidades[$parteId];
                                                                    if(!$parte->pivot->save())
                                                                    {
                                                                        $response = HelpController::buildResponse(
                                                                            500,
                                                                            'Error al actualizar una parte de la recepcion',
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
                                                                        'La cantidad ingresada para la parte "' . $parte->nparte . '" es menor a la cantidad ya despachada',
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
                                                                    'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor a la cantidad pendiente de recepcion',
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
                                                                'Error al obtener una de las partes pendientes de recepcion',
                                                                null
                                                            );
                
                                                            $success = false;
                
                                                            break;
                                                        }
                                                    }
                                                    // If is a new Parte in Recepcion
                                                    else
                                                    {
                                                        if($parte = Parte::find($parteId))
                                                        {
                                                            // Calc pendiente using cantidad in Despachos - cantidad in Recepciones from Sucursal (centro) - diff
                                                            $cantidadPendiente = $despachoCantidades[$parteId] - $parte->getCantidadRecepcionado_sourceable($sucursal, $recepcion->sourceable) - $diffCantidades[$parteId];
                                                            if($cantidadPendiente >= 0)
                                                            {
                                                                // Calc stock using cantidad in Recepciones for Sucursal (centro) + diff - cantidad in Entregas
                                                                $cantidadStock = $parte->getCantidadRecepcionado($sucursal) + $diffCantidades[$parteId] - $parte->getCantidadEntregado($sucursal);                          
                                                                if($cantidadStock >= 0)
                                                                {
                                                                    // Add the new Parte to Recepcion
                                                                    $recepcion->partes()->attach(
                                                                        array(
                                                                            $parteId => array(
                                                                                "cantidad" => $diffCantidades[$parteId] // For a new Parte, diff contains the full cantidad
                                                                            )
                                                                        )
                                                                    );
                                                                }
                                                                else
                                                                {
                                                                    // If the received parts are more than waiting in queue
                                                                    $response = HelpController::buildResponse(
                                                                        409,
                                                                        'La cantidad ingresada para la parte "' . $parte->nparte . '" es menor a la cantidad ya despachada',
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
                                                                    'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor a la cantidad pendiente de recepcion',
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
                                                                'Error al obtener una de las partes pendientes de recepcion',
                                                                null
                                                            );
                
                                                            $success = false;
                
                                                            break;
                                                        }
                                                    }
                                                }
                                                // If the have the same quantity, nothing changes (diff = 0)
                                                else
                                                {
                                                    //Do nothing, continue the loop
                                                }
                                            }
                                            else
                                            {
                                                $response = HelpController::buildResponse(
                                                    500,
                                                    'Error al obtener una de las partes pendientes de recepcion',
                                                    null
                                                );
    
                                                $success = false;
    
                                                break;
                                            }
                                        }
                                    }
                                    else
                                    {
                                        // If there aren't OcParte waiting for the entered Parte
                                        $response = HelpController::buildResponse(
                                            409,
                                            'No se han encontrado partes para recepcionar',
                                            null
                                        );

                                        $success = false;
                                    }
                                }
                                else
                                {
                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al obtener las partes pendiente de recepcion',
                                        null
                                    );

                                    $success = false;
                                
                                }

                                if($success === true)
                                {
                                    DB::commit();
                                    
                                    $response = HelpController::buildResponse(
                                        200,
                                        'Recepcion actualizada',
                                        null
                                    );
                                }
                                else
                                {
                                    DB::rollback();

                                    // The response error message was already set when success = false
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
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La recepcion no existe',
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
                if($sucursal = Sucursal::where('id', $sucursal_id)->where('type', 'sucursal')->first())
                {
                    if($recepcion = $sucursal->recepciones->find($id))
                    {
                        // Check if Recepcion was sourceable by a Sucursal (centro)
                        if(get_class($recepcion->sourceable) === get_class(new Sucursal()))
                        {
                            DB::beginTransaction();

                            $success = true;

                            // For all the partes in diff list
                            foreach($recepcion->partes as $parte)
                            {                                                                       
                                // Calc stock using cantidad in Recepciones for Sucursal - cantidad in Recepcion - cantidad in Entregas
                                $cantidadStock = $parte->getCantidadRecepcionado($sucursal) - $parte->pivot->cantidad - $parte->getCantidadEntregado($sucursal);                          
                                if($cantidadStock >= 0)
                                {
                                    if(!$recepcion->partes()->detach($parte->id))
                                    {
                                        $response = HelpController::buildResponse(
                                            500,
                                            'Error al eliminar una parte de la recepcion',
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
                                        'La parte "' . $parte->nparte . '" tiene cantidades que ya fueron despachadas por la sucursal',
                                        null
                                    );

                                    $success = false;

                                    break;
                                }
                            }

                            if($success === true)
                            {
                                if($recepcion->delete())
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
                                    
                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al eliminar la recepcion',
                                        null
                                    );
                                }
                            }
                            else
                            {
                                DB::rollback();

                                // The response error message was already set when success = false
                            }
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al obtener la recepcion',
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'La recepcion no existe',
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
