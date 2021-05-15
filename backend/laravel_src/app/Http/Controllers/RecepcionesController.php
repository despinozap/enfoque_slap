<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Comprador;
use App\Models\OcParte;
use App\Models\Recepcion;
use App\Models\OcParteRecepcion;
use App\Models\Proveedorrecepcion;
use App\Models\OcParteDespacho;

class RecepcionesController extends Controller
{
    
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
                            'recepcionable_id', 
                            'recepcionable_type', 
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $recepcion->ocpartes;
                        $recepcion->ocpartes = $recepcion->ocpartes->filter(function($ocparte)
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
                                'recepcion_id',
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

                        $recepcion->proveedorrecepcion;
                        if($recepcion->proveedorrecepcion !== null)
                        {
                            $recepcion->proveedorrecepcion->makeHidden([
                                'recepcion_id',
                                'proveedor_id',
                                'created_at', 
                                'updated_at'
                            ]);

                            $recepcion->proveedorrecepcion->proveedor;
                            $recepcion->proveedorrecepcion->proveedor->makeHidden([
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

    
    public function queuePartes_comprador($comprador_id, $proveedor_id)
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
                        
                        $ocParteList = OcParte::select('oc_parte.*')
                                        ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                        ->where('ocs.proveedor_id', $proveedor->id)
                                        ->where('ocs.estadooc_id', '=', 2) // Estadooc = 'En proceso'
                                        ->where('oc_parte.estadoocparte_id', '=', '1') // Estadoocparte = 'Pendiente'
                                        ->get();


                        // Retrieves the partes list with cantidad_pendiente (total) for proveedor
                        $queuePartesData = $ocParteList->reduce(function($carry, $ocParte)
                            {
                                if($ocParte->cantidad_pendiente > 0)
                                {
                                    if(isset($carry[$ocParte->parte->id]))
                                    {
                                        // If parte is already in the list, adds the cantidad_pendiente to the total
                                        $carry[$ocParte->parte->id]['cantidad_pendiente'] += $ocParte->cantidad_pendiente;
                                    }
                                    else
                                    {
                                        // If parte is not in the list, inserts the parte to the list
                                        $parte = [
                                            "id" => $ocParte->parte->id,
                                            "nparte" => $ocParte->parte->nparte,
                                            "marca" => $ocParte->parte->marca->makeHidden(['created_at', 'updated_at']),
                                            "cantidad_pendiente" => $ocParte->cantidad_pendiente,
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
                    'partes.*.comentario'  => 'sometimes|nullable'
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
                        DB::beginTransaction();

                        $recepcion = new Recepcion();
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

                            foreach(array_keys($cantidades) as $parteId)
                            {
                                // For each parte sent, gets the OcParte list where Estadoocparte is 'Pendiente' for the selected Proveedor
                                if($ocParteList = OcParte::select('oc_parte.*')
                                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                ->where('oc_parte.parte_id', '=', $parteId)
                                                ->where('oc_parte.estadoocparte_id', '=', 1) // Estadoocparte = 'Pendiente'
                                                ->where('ocs.proveedor_id', '=', $request->proveedor_id)
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
                                                    if($cantidades[$parteId] >= $ocParte->cantidad_pendiente)
                                                    {
                                                        // If is receiving more or equal than required for this OcParte, fill the OcParte
                                                        $cantidad = $ocParte->cantidad_pendiente;

                                                        $ocParte->estadoocparte_id = 2; // All the partes were received, so change status to 'Process'
                                                        if(!$ocParte->save())
                                                        {
                                                            // If fails on updating OcParte status
                                                            $response = HelpController::buildResponse(
                                                                500,
                                                                'Error al actualizar el estado de una parte recibida',
                                                                null
                                                            );
                        
                                                            $success = false;
                        
                                                            break;
                                                        }
                                                    }
                                                    else
                                                    {
                                                        // If receiving less than required to fill the OcParte, it continues with Estadoocparte 'Pendiente'
                                                        $cantidad = $cantidades[$parteId];
                                                    }
                                                    
                                                    // Attach the OcParte to Recepcion with defined Cantidad
                                                    $recepcion->ocpartes()->attach(
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
                                                // If the received parts are more than waiting in queue
                                                $response = HelpController::buildResponse(
                                                    409,
                                                    'La cantidad de partes recepcionadas es mayor a la cantidad de partes pendientes de recepcion',
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
                                            'La parte ingresada no tiene partes pendientes de recepcion',
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
                                        'Error al obtener las partes pendiente de recepcion',
                                        null
                                    );

                                    $success = false;

                                    break;
                                }
                            }


                            if($success === true)
                            {
                                $proveedorRecepcion = new Proveedorrecepcion();
                                $proveedorRecepcion->proveedor_id = $request->proveedor_id;
                                $proveedorRecepcion->recepcion_id = $recepcion->id;

                                if($proveedorRecepcion->save())
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

                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al crear la recepcion',
                                        null
                                    );
                                }
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
                    'recepcion_id' => 'required|exists:recepciones,id,recepcionable_id,' . $comprador_id . ',recepcionable_type,' . get_class(new Comprador()), // Try to add recepcionable_type
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
                        if($recepcion = Recepcion::find($id))
                        {
                            $recepcion->makeHidden([
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
                                'created_at', 
                                'updated_at'
                            ]);

                            if($recepcion->proveedorrecepcion)
                            {
                                $recepcion->proveedorrecepcion->makeHidden([
                                    'id',
                                    'proveedor_id',
                                    'recepcion_id',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $recepcion->proveedorrecepcion->proveedor;
                                $recepcion->proveedorrecepcion->proveedor->makeHidden([
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

                            $recepcion->ocpartes;
                            foreach($recepcion->ocpartes as $ocparte)
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
                                    'recepcion_id',
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
                                $recepcion
                            );
                        }   
                        else     
                        {
                            $response = HelpController::buildResponse(
                                400,
                                'La recepcion no existe',
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


    public function update_comprador(Request $request, $comprador_id, $id)
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
                    'partes.*.comentario'  => 'sometimes|nullable'
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
                        if($recepcion = Recepcion::find($id))
                        {
                            DB::beginTransaction();

                            // Fill the data
                            $recepcion->fecha = $request->fecha;
                            $recepcion->ndocumento = $request->ndocumento;
                            $recepcion->responsable = $request->responsable;
                            $recepcion->comentario = $request->comentario;

                            if($recepcion->save())
                            {
                                $data = array();
                                $success = true;

                                // Get new cantidades array
                                $newCantidades = array_reduce($request->partes, function($carry, $parte)
                                    {
                                        $carry[$parte['id']] = $parte['cantidad'];

                                        return $carry;
                                    },
                                    array()
                                );

                                // Get the OcParteRecepcion list in Recepcion
                                if($ocParteRecepcionList = OcParteRecepcion::where('ocparte_recepcion.recepcion_id', '=', $id)->get())
                                {
                                    // Get previous cantidades array
                                    $previousCantidades = $ocParteRecepcionList->reduce(function($carry, $ocParteRecepcion)
                                        {
                                            if(isset($carry[$ocParteRecepcion->ocparte->parte->id]))
                                            {
                                                // If parte is already in the list, adds the cantidad in OcParteRecepcion to the total
                                                $carry[$ocParteRecepcion->ocparte->parte->id] += $ocParteRecepcion->cantidad;
                                            }
                                            else
                                            {
                                                // If parte is not in the list, inserts the parte to the list
                                                $carry[$ocParteRecepcion->ocparte->parte->id] = $ocParteRecepcion->cantidad;
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
                                            // If it doesn't come, rest the previous cantidad (as negative)
                                            $diffCantidades[$parteId] = $previousCantidades[$parteId] * -1;
                                        }
                                    }

                                    // Check if all the partes in the new list are in the diff array
                                    foreach(array_keys($newCantidades) as $parteId)
                                    {
                                        // If the new parte isn't in the diff list
                                        if(!isset($diffCantidades[$parteId]))
                                        {
                                            // Add the new parte to the diff list
                                            $diffCantidades[$parteId] = $newCantidades[$parteId];
                                        }
                                    }

                                    // For all the partes in diff list
                                    foreach(array_keys($diffCantidades) as $parteId)
                                    {
                                        // If we are removing parts from the recepcion
                                        if($diffCantidades[$parteId] < 0)
                                        {                                            
                                            // Get all the OcParteRecepcion in Recepciones from the Comprador
                                            if($ocParteRecepcionList = OcParteRecepcion::select('ocparte_recepcion.*')
                                                                    ->join('oc_parte', 'oc_parte.id', '=', 'ocparte_recepcion.ocparte_id')
                                                                    ->join('recepciones', 'recepciones.id', '=', 'ocparte_recepcion.recepcion_id')
                                                                    ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                                    ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                                    ->where('recepciones.recepcionable_id', '=', $comprador->id)
                                                                    ->where('oc_parte.parte_id', '=', $parteId)
                                                                    ->orderBy('ocs.created_at', 'DESC')
                                                                    ->get()
                                            )
                                            {
                                                if($ocParteRecepcionList->count() > 0)
                                                {
                                                    // Get previous cantidades array
                                                    $cantidad_recepciones = $ocParteRecepcionList->reduce(function($carry, $ocParteRecepcion)
                                                        {
                                                            return $carry + $ocParteRecepcion->cantidad;
                                                        },
                                                        0
                                                    );
                                                }
                                                else
                                                {
                                                    // If there aren't OcParteRecepcion for the entered Parte
                                                    $response = HelpController::buildResponse(
                                                        500,
                                                        'Error al obtener las partes ya recepcionadas',
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
                                                    'Error al obtener las partes en la recepcion',
                                                    null
                                                );

                                                $success = false;

                                                break;
                                            }

                                            // Get all the OcParteDespacho in Despachos from the Comprador
                                            if($ocParteDespachoList = OcParteDespacho::select('despacho_ocparte.*')
                                                                    ->join('oc_parte', 'oc_parte.id', '=', 'despacho_ocparte.ocparte_id')
                                                                    ->join('despachos', 'despachos.id', '=', 'despacho_ocparte.despacho_id')
                                                                    ->where('despachos.despachable_type', '=', get_class($comprador))
                                                                    ->where('despachos.despachable_id', '=', $comprador->id)
                                                                    ->where('oc_parte.parte_id', '=', $parteId)
                                                                    ->get()
                                            )
                                            {
                                                if($ocParteDespachoList->count() > 0)
                                                {
                                                    // Get previous cantidades array
                                                    $cantidad_despachos = $ocParteDespachoList->reduce(function($carry, $ocParteDespacho)
                                                        {
                                                            return $carry + $ocParteDespacho->cantidad;
                                                        },
                                                        0
                                                    );
                                                }
                                                else
                                                {
                                                    $cantidad_despachos = 0;
                                                }
                                            }
                                            else
                                            {
                                                $response = HelpController::buildResponse(
                                                    500,
                                                    'Error al obtener las partes en la recepcion',
                                                    null
                                                );

                                                $success = false;

                                                break;
                                            }

                                            
                                            // Check if the cantidad_recepciones + diff (negative) are still higher than cantidad_despachos
                                            if(($cantidad_recepciones + $diffCantidades[$parteId]) >= $cantidad_despachos)
                                            {
                                                //Do the magic

                                                if($ocParteRecepcionList->count() > 0)
                                                {
                                                    if($success === true)
                                                    {
                                                        foreach($ocParteRecepcionList as $ocParteRecepcion)
                                                        {
                                                            if($diffCantidades[$parteId] < 0)
                                                            {
                                                                if(abs($diffCantidades[$parteId]) >= $ocParteRecepcion->cantidad)
                                                                {
                                                                    // If is removing more or equal than required for this OcParte, delete the OcParteRecepcion
                                                                    $diffCantidades[$parteId] = $diffCantidades[$parteId] + $ocParteRecepcion->cantidad;

                                                                    if(!$ocParteRecepcion->delete())
                                                                    {
                                                                        // If fails on removing OcParteRecepcion
                                                                        $response = HelpController::buildResponse(
                                                                            500,
                                                                            'Error al actualizar la cantidad de partes recibidas',
                                                                            null
                                                                        );
                                    
                                                                        $success = false;
                                    
                                                                        break;
                                                                    }
                                                                }
                                                                else
                                                                {                                                                    
                                                                    $ocParteRecepcion->cantidad = $ocParteRecepcion->cantidad + $diffCantidades[$parteId]; // Negative addition
                                                                    if($ocParteRecepcion->save())
                                                                    {
                                                                        // If removing less than in OcParteRecepcion
                                                                        $diffCantidades[$parteId] = 0;
                                                                    }
                                                                    else
                                                                    {
                                                                        // If fails on updating OcParte status
                                                                        $response = HelpController::buildResponse(
                                                                            500,
                                                                            'Error al actualizar la cantidad de partes recibidas',
                                                                            null
                                                                        );
                                    
                                                                        $success = false;
                                    
                                                                        break;
                                                                    }
                                                                }

                                                                $ocParte = $ocParteRecepcion->ocparte;
                                                                $ocParte->estadoocparte_id = 1; // Estadoocparte goes back to 'Pendiente'
                                                                if(!$ocParte->save())
                                                                {
                                                                    // If fails on updating OcParte status
                                                                    $response = HelpController::buildResponse(
                                                                        500,
                                                                        'Error al actualizar el estado de una parte recibida',
                                                                        null
                                                                    );
                                
                                                                    $success = false;
                                
                                                                    break;
                                                                }
                                                            }
                                                            else
                                                            {
                                                                break;
                                                            } 
                                                        }

                                                        if($diffCantidades[$parteId] < 0)
                                                        {
                                                            // If the received parts are more than waiting in queue
                                                            $response = HelpController::buildResponse(
                                                                409,
                                                                'Error indefinido para actualizacion de recepcion',
                                                                null
                                                            );

                                                            $data = 'Undefined error';

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
                                                        'La parte ingresada no tiene partes pendientes de recepcion',
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
                                                    'La nueva cantidad de partes es menor a la ya despachada por el comprador',
                                                    null
                                                );

                                                $success = false;

                                                break;
                                            }

                                        }
                                        // If we are adding more parts to the recepcion
                                        else if($diffCantidades[$parteId] > 0)
                                        {

                                            // For each parte sent, gets the OcParte list where Estadoocparte is 'Pendiente' for the selected Proveedor
                                            if($ocParteList = OcParte::select('oc_parte.*')
                                                            ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                                            ->where('oc_parte.parte_id', '=', $parteId)
                                                            ->where('oc_parte.estadoocparte_id', '=', 1) // Estadoocparte = 'Pendiente'
                                                            ->where('ocs.proveedor_id', '=', $request->proveedor_id)
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
                                                            if($diffCantidades[$parteId] > 0)
                                                            {
                                                                if($diffCantidades[$parteId] >= $ocParte->cantidad_pendiente)
                                                                {
                                                                    // If is receiving more or equal than required for this OcParte, fill the OcParte
                                                                    $cantidad = $ocParte->cantidad_pendiente;

                                                                    $ocParte->estadoocparte_id = 2; // All the partes were received, so change status to 'Process'
                                                                    if(!$ocParte->save())
                                                                    {
                                                                        // If fails on updating OcParte status
                                                                        $response = HelpController::buildResponse(
                                                                            500,
                                                                            'Error al actualizar el estado de una parte recibida',
                                                                            null
                                                                        );
                                    
                                                                        $success = false;
                                    
                                                                        break;
                                                                    }
                                                                }
                                                                else
                                                                {
                                                                    // If receiving less than required to fill the OcParte, it continues with Estadoocparte 'Pendiente'
                                                                    $cantidad = $diffCantidades[$parteId];
                                                                }
                                                                
                                                                // Attach the OcParte to Recepcion with defined Cantidad
                                                                $recepcion->ocpartes()->attach(
                                                                    array(
                                                                        $ocParte->id => array(
                                                                            "cantidad" => $cantidad
                                                                        )
                                                                    )
                                                                );

                                                                // Updates the cantidad left
                                                                $diffCantidades[$parteId] = $diffCantidades[$parteId] - $cantidad;
                                                            }
                                                            else
                                                            {
                                                                break;
                                                            } 
                                                        }

                                                        if($diffCantidades[$parteId] > 0)
                                                        {
                                                            // If the received parts are more than waiting in queue
                                                            $response = HelpController::buildResponse(
                                                                409,
                                                                'La cantidad de partes recepcionadas es mayor a la cantidad de partes pendientes de recepcion',
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
                                                        'La parte ingresada no tiene partes pendientes de recepcion',
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
                                                    'Error al obtener las partes pendiente de recepcion',
                                                    null
                                                );

                                                $success = false;

                                                break;
                                            }
                                        }
                                        // If the have the same quantity, nothing changes (diff = 0)
                                        else
                                        {
                                            //Do nothing
                                        }
                                    }


                                    if($success === true)
                                    {
                                        $proveedorRecepcion = $recepcion->proveedorrecepcion;
                                        $proveedorRecepcion->proveedor_id = $request->proveedor_id;

                                        if($proveedorRecepcion->save())
                                        {
                                            // REPLACE BY COMMIT
                                            DB::commit();
                                            
                                            $response = HelpController::buildResponse(
                                                201,
                                                'Recepcion actualizada',
                                                $data
                                            );
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
                                        DB::rollback();
                                    }
                                }
                                else
                                {
                                    DB::rollback();

                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al obtener las partes en la recepcion',
                                        null
                                    );
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
                                400,
                                'La recepcion no existe',
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
                    'No tienes acceso a actualizar recepciones para comprador',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al actualizar la recepcion [!]' . $e,
                null
            );
        }
        
        return $response;
    }


    public function destroy_comprador($comprador_id, $id)
    {
        //MAKE SURE TO REMOVE ALL THE OcParteRecepcion ROWS
        return null;
    }


}
