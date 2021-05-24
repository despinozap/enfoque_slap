<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Comprador;
use App\Models\Parte;
use App\Models\Despacho;
use App\Models\Sucursal;

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
                            'destinable_id', 
                            'destinable_type', 
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $despacho->partes;
                        $despacho->partes = $despacho->partes->filter(function($parte)
                        {
                            $parte->makeHidden([
                                'marca_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $parte->pivot->makeHidden([
                                'parte_id',
                                'despacho_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $parte->marca;
                            $parte->marca->makeHidden(['created_at', 'updated_at']);

                            return $parte;
                        });

                        $despacho->destinable;
                        $despacho->destinable->makeHidden([
                            'type',
                            'rut',
                            'address',
                            'city',
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);

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


    public function store_prepare_comprador($comprador_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores despachos_store'))
            {
                if($comprador = Comprador::find($comprador_id))
                {
                    // Centrosditribucion
                    {
                        $centrosdistribucion = Sucursal::where('type', '=', 'centro')->get();
                        $centrosdistribucion = $centrosdistribucion->filter(function($centrodistribucion)
                        {
                            $centrodistribucion->makeHidden([
                                'created_at',
                                'updated_at'
                            ]);
    
                            return $centrodistribucion;
                        });
                    }

                    // QueuePartes
                    {
                        // Get all the Partes in Recepciones for Comprador
                        $parteList = Parte::select('partes.*')
                                    ->join('parte_recepcion', 'parte_recepcion.parte_id', '=', 'partes.id')
                                    ->join('recepciones', 'recepciones.id', '=', 'parte_recepcion.recepcion_id')
                                    ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                    ->where('recepciones.recepcionable_id', '=', $comprador->id)
                                    ->groupBy('partes.id')
                                    ->get();
    
                        // Retrieves the partes list with cantidad_stock for dispatching
                        $queuePartes = $parteList->reduce(function($carry, $parte) use ($comprador)
                            {
                                // Get how many partes have been received but not dispatched yet in Comprador
                                $cantidadStock = $parte->getCantidadRecepcionado($comprador) - $parte->getCantidadDespachado($comprador);
                                if($cantidadStock > 0)
                                {
                                    $parteData = [
                                        "id" => $parte->id,
                                        "nparte" => $parte->nparte,
                                        "marca" => $parte->marca->makeHidden(['created_at', 'updated_at']),
                                        "cantidad_stock" => $cantidadStock,
                                    ];
                                    
                                    array_push($carry, $parteData);
                                }
    
                                return $carry;
                            },
                            array()
                        );
                    }

                    $data = [
                        'centrosdistribucion' => $centrosdistribucion,
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
                'Error al obtener partes disponibles para despachar [!]',
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
                $validatorInput = $request->only('sucursal_id', 'fecha', 'ndocumento', 'responsable', 'comentario', 'partes');
            
                $validatorRules = [
                    'sucursal_id' => 'required|exists:sucursales,id,type,"centro"',
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
                    if($comprador = Comprador::find($comprador_id))
                    {
                        if($centrodistribucion = Sucursal::where('id', '=', $request->sucursal_id)->where('type', '=', 'centro')->first())
                        {
                            DB::beginTransaction();

                            $despacho = new Despacho();
                            // Set the morph destination for Despacho as Centrodistribucion
                            $despacho->destinable_id = $centrodistribucion->id;
                            $despacho->destinable_type = get_class($centrodistribucion);
                            // Set the morph for Despacho as Comprador
                            $despacho->despachable_id = $comprador->id;
                            $despacho->despachable_type = get_class($comprador);
                            // Fill the data
                            $despacho->fecha = $request->fecha;
                            $despacho->ndocumento = $request->ndocumento;
                            $despacho->responsable = $request->responsable;
                            $despacho->comentario = $request->comentario;

                            if($despacho->save())
                            {
                                $success = true;

                                $cantidades = array_reduce($request->partes, function($carry, $parte)
                                    {
                                        $carry[$parte['id']] = $parte['cantidad'];

                                        return $carry;
                                    },
                                    array()
                                );

                                // Get all the Partes in Recepciones for Comprador
                                if(
                                    $parteList = Parte::select('partes.*')
                                                ->join('parte_recepcion', 'parte_recepcion.parte_id', '=', 'partes.id')
                                                ->join('recepciones', 'recepciones.id', '=', 'parte_recepcion.recepcion_id')
                                                ->where('recepciones.recepcionable_type', '=', get_class($comprador))
                                                ->where('recepciones.recepcionable_id', '=', $comprador->id)
                                                ->groupBy('partes.id')
                                                ->get()
                                )
                                {
                                    if($parteList->count() > 0)
                                    {
                                        // Retrieves the partes list with cantidad_stock for dispatching
                                        $stockCantidades = $parteList->reduce(function($carry, $parte) use ($comprador)
                                            {
                                                // Get how many partes have been received but not dispatched yet in Comprador
                                                $cantidadStock = $parte->getCantidadRecepcionado($comprador) - $parte->getCantidadDespachado($comprador);
                                                if($cantidadStock > 0)
                                                {
                                                    $carry[$parte->id] = $cantidadStock;
                                                }

                                                return $carry;
                                            },
                                            array()
                                        );

                                        foreach(array_keys($cantidades) as $parteId)
                                        {
                                            if($parte = Parte::find($parteId))
                                            {
                                                // If the Parte has stock in Comprador
                                                if(isset($stockCantidades[$parteId]))
                                                {
                                                    // If cantidad is less or equal to stock
                                                    if($cantidades[$parteId] <= $stockCantidades[$parteId])
                                                    {
                                                        $despacho->partes()->attach(
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
                                                            'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor a la cantidad de pendiente de despacho',
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
                                                        'La parte "' . $parte->nparte . '" no tiene partes pendiente de despacho',
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
                                            'No se han encontrado partes recepcionadas',
                                            null
                                        );
    
                                        $success = false;
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
                        else
                        {
                            $response = HelpController::buildResponse(
                                400,
                                'El centro de distribucion no existe',
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
                            $despacho->partes_total;
                        
                            $despacho->makeHidden([
                                'despachable_id', 
                                'despachable_type',
                                'destinable_id', 
                                'destinable_type', 
                                'created_at', 
                                'updated_at'
                            ]);
                            
                            $despacho->partes;
                            $despacho->partes = $despacho->partes->filter(function($parte)
                            {
                                $parte->makeHidden([
                                    'marca_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $parte->pivot->makeHidden([
                                    'parte_id',
                                    'despacho_id',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $parte->marca;
                                $parte->marca->makeHidden(['created_at', 'updated_at']);

                                return $parte;
                            });

                            $despacho->destinable;
                            $despacho->destinable->makeHidden([
                                'type',
                                'rut',
                                'address',
                                'city',
                                'country_id',
                                'created_at', 
                                'updated_at'
                            ]);

                            
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
                'Error al obtener el despacho [!]' . $e,
                null
            );
        }
        
        return $response;
    }
}
