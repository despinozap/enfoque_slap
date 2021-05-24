<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Comprador;
use App\Models\Parte;
use App\Models\OcParte;
use App\Models\Recepcion;
use App\Models\OcParteRecepcion;
use App\Models\Proveedor;
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
                                        ->where('ocs.estadooc_id', '<', 4) // Estadooc = 'Pendiente' (1) or 'En proceso' (2) or 'Cerrada' (3)
                                        ->get();


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
                                $cantidadPendiente = $cantidadesOc[$parteId] - $parte->getCantidadRecepcionado_sourceable($comprador, $proveedor);

                                if($cantidadPendiente > 0)
                                {
                                    $parteData = [
                                        "id" => $parte->id,
                                        "nparte" => $parte->nparte,
                                        "marca" => $parte->marca->makeHidden(['created_at', 'updated_at']),
                                        "cantidad_pendiente" => $cantidadPendiente,
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
                                                ->where('ocs.estadooc_id', '<', 4) // Estadooc = 'Pendiente' (1) or 'En proceso' (2) or 'Cerrada' (3)
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
                                400,
                                'El proveedor no existe para el comprador',
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
                                'sourceable_id',
                                'sourceable_type',
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
                                        ->where('ocs.estadooc_id', '<', 4) // Estadooc = 'Pendiente' (1) or 'En proceso' (2) or 'Cerrada' (3)
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

                                            // If the Parte is already in the Recepcion, then add the cantidad to queue
                                            if($p = $recepcion->partes->find($parte->id))
                                            {
                                                $cantidadPendiente = $cantidadPendiente + $p->pivot->cantidad;
                                            }

                                            if($cantidadPendiente > 0)
                                            {
                                                $parteData = [
                                                    "id" => $parte->id,
                                                    "nparte" => $parte->nparte,
                                                    "marca" => $parte->marca->makeHidden(['created_at', 'updated_at']),
                                                    "cantidad_pendiente" => $cantidadPendiente,
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
                            if(get_class($recepcion->sourceable) === get_class(new Proveedor()))
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
                                                    ->where('ocs.estadooc_id', '<', 4) // Estadooc = 'Pendiente' (1) or 'En proceso' (2) or 'Cerrada' (3)
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
                                                            $cantidadPendiente = $ocsCantidades[$parteId] - $parte->getCantidadRecepcionado_sourceable($comprador, $recepcion->sourceable) + $diffCantidades[$parteId];
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
                                                                // Calc pendiente using cantidad in OCs - cantidad in Recepciones for Proveedor - diff (negative sum)
                                                                $cantidadPendiente = $ocsCantidades[$parteId] - $parte->getCantidadRecepcionado_sourceable($comprador, $recepcion->sourceable) + $diffCantidades[$parteId];
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
                                    500,
                                    'Error al obtener la recepcion',
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
                    if($recepcion = Recepcion::join('compradores', 'recepciones.recepcionable_id', '=', 'compradores.id')
                                ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                ->where('recepciones.id', '=', $id)
                                ->get()
                                ->first()
                    )
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
                                        500,
                                        'La parte "' . $parte->nparte . '" tiene cantidades que ya despachadas por el comprador',
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
