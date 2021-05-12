<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Comprador;
use App\Models\Proveedor;
use App\Models\OcParte;
use App\Models\Recepcion;
use App\Models\Proveedorrecepcion;

class CompradoresController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores index'))
            {
                if($compradores = Comprador::all())
                {
                    $compradores = $compradores->filter(function($comprador)
                    {
                        $comprador->makeHidden([
                            'created_at', 
                            'updated_at'
                        ]);

                        $comprador->proveedores;
                        $comprador->proveedores->makeHidden([
                            'comprador_id',
                            'created_at', 
                            'updated_at'
                        ]);

                        return $comprador;
                    });
                    
                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $compradores
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de compradores',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar compradores',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de compradores [!]',
                null
            );
        }
        
        return $response;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores show'))
            {
                if($comprador = Comprador::find($id))
                {
                    $comprador->makeHidden([
                        'created_at', 
                        'updated_at'
                    ]);

                    $comprador->proveedores;
                    $comprador->proveedores = $comprador->proveedores->filter(function($proveedor)
                    {
                        return $proveedor->makeHidden(['comprador_id', 'created_at', 'updated_at']);
                    });

                    
                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $comprador
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
                    'No tienes acceso a visualizar compradores',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener el comprador [!]',
                null
            );
        }
            
        return $response;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function indexRecepciones($id)
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

    
    public function queuePartes($comprador_id, $proveedor_id)
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
                'Error al obtener partes pendiente de recepcion [!]' . $e,
                null
            );
        }
            
        return $response;
    }


    public function storeRecepcion(Request $request, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores recepciones_store'))
            {
                $validatorInput = $request->only('proveedor_id', 'fecha', 'ndocumento', 'responsable', 'comentario', 'partes');
            
                $validatorRules = [
                    'proveedor_id' => 'required|exists:proveedores,id,comprador_id,' . $id,
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
                    if($comprador = Comprador::find($id))
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

                                                // Updates the cantidad left
                                                $cantidades[$parteId] = $cantidades[$parteId] - $cantidad;
                                                
                                                $recepcion->ocpartes()->attach(
                                                    array(
                                                        $parteId => array(
                                                            "cantidad" => $cantidad
                                                        )
                                                    )
                                                );
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
                                        $response = HelpController::buildResponse(
                                            500,
                                            'Error al obtener las partes pendiente de recepcion',
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
                                    //REPLACE THIS BY commit()
                                    DB::commit();
                                    
                                    $response = HelpController::buildResponse(
                                        201,
                                        'Recepcion creada',
                                        $data
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
                'Error al crear la recepcion [!]' . $e,
                null
            );
        }
        
        return $response;
    }


    public function showRecepcion($comprador_id, $id)
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
                                    'backorder',
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
                'Error al obtener la recepcion [!]' . $e,
                null
            );
        }
        
        return $response;
    }
}
