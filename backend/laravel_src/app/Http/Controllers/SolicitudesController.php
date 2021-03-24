<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Solicitud;
use App\Models\Parte;

class SolicitudesController extends Controller
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
            if($user->role->hasRoutepermission('solicitudes index'))
            {
                if($solicitudes = Solicitud::all())
                {
                    foreach($solicitudes as $solicitud)
                    {
                        $solicitud->makeHidden(['cliente_id', 'marca_id', 'user_id', 'estadosolicitud_id']);

                        $totalPartes = 0;
                        foreach($solicitud->partes as $parte)
                        {   
                            $parte->makeHidden(['marca_id', 'created_at', 'updated_at']);
                            
                            $parte->pivot;
                            $totalPartes += $parte->pivot->cantidad;
                            $parte->pivot->makeHidden(['solicitud_id', 'parte_id']);

                            $parte->marca;
                            $parte->marca->makeHidden(['created_at', 'updated_at']);
                        }

                        $solicitud->partes_total;
                        $solicitud->cliente;
                        $solicitud->cliente->makeHidden(['created_at', 'updated_at']);
                        $solicitud->marca;
                        $solicitud->marca->makeHidden(['created_at', 'updated_at']);
                        $solicitud->user;
                        $solicitud->user->makeHidden(['email', 'phone', 'role_id', 'email_verified_at', 'created_at', 'updated_at']);
                        $solicitud->estadosolicitud;
                        $solicitud->estadosolicitud->makeHidden(['created_at', 'updated_at']);
                    }

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $solicitudes
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de solicitudes',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar solicitudes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de solicitudes [!]',
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
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('solicitudes store'))
            {
                $validatorInput = $request->only(
                    'cliente_id',
                    'marca_id',
                    'comentario',
                    'partes'
                );
                
                $validatorRules = [
                    'cliente_id' => 'required|exists:clientes,id',
                    'marca_id' => 'required|exists:marcas,id',
                    'partes' => 'required|array|min:1',
                    'partes.*.nparte'  => 'required',
                    'partes.*.cantidad'  => 'required|numeric|min:1',
                ];
        
                $validatorMessages = [
                    'cliente_id.required' => 'Debes seleccionar un cliente',
                    'cliente_id.exists' => 'El cliente no existe',
                    'marca_id.required' => 'Debes seleccionar una marca',
                    'marca_id.exists' => 'La marca no existe',
                    'partes.required' => 'Debes seleccionar las partes',
                    'partes.array' => 'Lista de partes invalida',
                    'partes.min' => 'La solicitud debe contener al menos 1 parte',
                    'partes.*.nparte.required' => 'La lista de partes es invalida',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte debe ser mayor a 0',
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
                    $solicitud = new Solicitud();
                    $solicitud->fill($request->all());
                    $solicitud->user_id = $user->id;
                    $solicitud->estadosolicitud_id = 1; //Initial Estadosolicitud
        
                    DB::beginTransaction();
        
                    if($solicitud->save())
                    {
                        $success = true;
        
                        //Attaching each Parte to the Solicitud
                        foreach($request->partes as $parte)
                        {
                            if($p = Parte::where('nparte', $parte['nparte'])->where('marca_id', $request->marca_id)->first())
                            {
                                $solicitud->partes()->attach([ 
                                    $p->id => [
                                        'cantidad' => $parte['cantidad']
                                    ]
                                ]);
                            }
                            else
                            {
                                $p = new Parte();
                                $p->nparte = $parte['nparte'];
                                $p->marca_id = $request->marca_id;
                                if($p->save())
                                {
                                    $solicitud->partes()->attach([ 
                                        $p->id => [
                                            'cantidad' => $parte['cantidad']
                                        ]
                                    ]);
                                }
                                else
                                {
                                    $success = false;
        
                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al crear la parte N:' . $parte['nparte'],
                                        null
                                    );
            
                                    break;
                                }
                            }
                        }
        
                        if($success === true)
                        {
                            DB::commit();
        
                            $response = HelpController::buildResponse(
                                201,
                                'Solicitud creada',
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
                            'Error al crear la solicitud',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a registrar solicitudes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            DB::rollback();

            $response = HelpController::buildResponse(
                500,
                'Error al crear la solicitud [!]',
                null
            );
        }

        return $response;
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
            if($user->role->hasRoutepermission('solicitudes show'))
            {
                if($solicitud = Solicitud::find($id))
                {
                    $solicitud->makeHidden([
                        'cliente_id',
                        'marca_id',
                        'estadosolicitud_id',
                        'created_at', 
                        'updated_at'
                    ]);

                    $solicitud->cliente;
                    $solicitud->cliente->makeHidden(['created_at', 'updated_at']);
                    
                    $solicitud->marca;
                    $solicitud->marca->makeHidden(['created_at', 'updated_at']);

                    $solicitud->estadosolicitud;
                    $solicitud->estadosolicitud->makeHidden(['created_at', 'updated_at']);

                    $solicitud->partes;
                    foreach($solicitud->partes as $parte)
                    {
                        $parte->makeHidden([
                            'marca_id', 
                            'created_at', 
                            'updated_at'
                        ]);

                        $parte->marca;
                        $parte->marca->makeHidden(['created_at', 'updated_at']);

                        switch($user->role_id)
                        {
                            case 1: { // Administrador

                                $parte->pivot->makeHidden([
                                    'marca_id', 
                                    'created_at', 
                                    'updated_at'
                                ]);

                                break;
                            }

                            case 2: { // Vendedor

                                $parte->pivot->makeHidden([
                                    'costo',
                                    'margen',
                                    'peso',
                                    'flete',
                                    'marca_id', 
                                    'created_at', 
                                    'updated_at'
                                ]);

                                break;
                            }

                            default: {

                                break;
                            }
                        }
                    }
                    
                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $solicitud
                    );
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        400,
                        'La solicitud no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar solicitudes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la solicitud [!]',
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
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('solicitudes update'))
            {
                $validatorInput = $request->only(
                    'cliente_id',
                    'marca_id',
                    'comentario',
                    'partes'
                );
                
                $validatorRules = [
                    'cliente_id' => 'required|exists:clientes,id',
                    'marca_id' => 'required|exists:marcas,id',
                    'partes' => 'required|array|min:1',
                    'partes.*.nparte'  => 'required',
                    'partes.*.cantidad'  => 'required|numeric|min:1',
                ];
        
                $validatorMessages = [
                    'cliente_id.required' => 'Debes seleccionar un cliente',
                    'cliente_id.exists' => 'El cliente no existe',
                    'marca_id.required' => 'Debes seleccionar una marca',
                    'marca_id.exists' => 'La marca no existe',
                    'partes.required' => 'Debes seleccionar las partes',
                    'partes.array' => 'Lista de partes invalida',
                    'partes.min' => 'La solicitud debe contener al menos 1 parte',
                    'partes.*.nparte.required' => 'La lista de partes es invalida',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte debe ser mayor a 0',
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
                    if($solicitud = Solicitud::find($id))
                    {
                        $solicitud->fill($request->all());
        
                        DB::beginTransaction();
        
                        if($solicitud->save())
                        {
                            $success = true;
        
                            $syncData = [];
                            foreach($request->partes as $parte)
                            {
                                if($p = Parte::where('nparte', $parte['nparte'])->where('marca_id', $request->marca_id)->first())
                                {
                                    $syncData[$p->id] =  array('cantidad' => $parte['cantidad']);
                                }
                                else
                                {
                                    $p = new Parte();
                                    $p->nparte = $parte['nparte'];
                                    $p->marca_id = $request->marca_id;
                                    if($p->save())
                                    {
                                        $syncData[$p->id] =  array('cantidad' => $parte['cantidad']);
                                    }
                                    else
                                    {
                                        $success = false;
            
                                        $response = HelpController::buildResponse(
                                            500,
                                            'Error al crear la parte N:' . $parte['nparte'],
                                            null
                                        );
                
                                        break;
                                    }
                                }
                            }
        
                            if($success === true)
                            {
                                $solicitud->partes()->sync($syncData);

                                DB::commit();
        
                                $response = HelpController::buildResponse(
                                    200,
                                    'Solicitud editada',
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
                                'Error al editar la solicitud',
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            400,
                            'La solicitud no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar solicitudes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            DB::rollback();
        
            $response = HelpController::buildResponse(
                500,
                'Error al editar la solicitud [!]',
                null
            );
        }
        
        
        return $response;
    }

    public function complete(Request $request, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('solicitudes complete'))
            {
                $validatorInput = $request->only(
                    'partes'
                );
                
                $validatorRules = [
                    'partes' => 'required|array|min:1',
                    'partes.*.nparte'  => 'required',
                    'partes.*.description'  => 'nullable',
                    'partes.*.cantidad'  => 'required|numeric|min:1',
                    'partes.*.costo'  => 'nullable|numeric|min:0',
                    'partes.*.margen'  => 'nullable|numeric|min:0',
                    'partes.*.tiempoentrega'  => 'nullable|numeric|min:0',
                    'partes.*.peso'  => 'nullable|numeric|min:1',
                    'partes.*.flete'  => 'nullable|numeric|min:0',
                    'partes.*.backorder'  => 'required|boolean',
                ];
        
                $validatorMessages = [
                    'partes.required' => 'Debes seleccionar las partes',
                    'partes.array' => 'Lista de partes invalida',
                    'partes.min' => 'La solicitud debe contener al menos 1 parte',
                    'partes.*.nparte.required' => 'La lista de partes es invalida',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte debe ser mayor a 0',
                    'partes.*.costo.numeric' => 'El costo para la parte debe ser numerico',
                    'partes.*.costo.min' => 'El costo para la parte debe ser mayor o igual a 0',
                    'partes.*.margen.numeric' => 'El margen para la parte debe ser numerico',
                    'partes.*.margen.min' => 'El margen para la parte debe ser mayor o igual a 0',
                    'partes.*.tiempoentrega.numeric' => 'El tiempo de entrega para la parte debe ser numerico',
                    'partes.*.tiempoentrega.min' => 'El tiempo de entrega para la parte debe ser mayor o igual a 0',
                    'partes.*.peso.numeric' => 'El peso para la parte debe ser numerico',
                    'partes.*.peso.min' => 'El peso para la parte debe ser mayor a 0',
                    'partes.*.flete.numeric' => 'El flete para la parte debe ser numerico',
                    'partes.*.flete.min' => 'El flete para la parte debe ser mayor o igual a 0',
                    'partes.*.backorder.required' => 'Debes seleccionar si la parte es backorder',
                    'partes.*.backorder.boolean' => 'La seleccion de backorder para la parte es invalida',
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
                    if($solicitud = Solicitud::find($id))
                    {    
                        $success = true;
        
                        DB::beginTransaction();

                        $syncData = [];
                        $monto = null;
                        foreach($request->partes as $parte)
                        {
                            if($p = $solicitud->partes->where('nparte', $parte['nparte'])->first())
                            {
                                if(($parte['costo'] !== null) && ($parte['margen'] !== null) && ($parte['flete'] !== null))
                                {
                                    $monto = ($parte['costo'] * $parte['cantidad']) + (($parte['costo'] * $parte['cantidad']) * $parte['margen'] / 100) + $parte['flete'];
                                }
                                else
                                {
                                    $monto = null;
                                }

                                $syncData[$p->id] =  array(
                                    'descripcion' => $parte['descripcion'],
                                    'cantidad' => $parte['cantidad'],
                                    'costo' => $parte['costo'],
                                    'margen' => $parte['margen'],
                                    'tiempoentrega' => $parte['tiempoentrega'],
                                    'peso' => $parte['peso'],
                                    'flete' => $parte['flete'],
                                    'backorder' => $parte['backorder'],
                                    'monto' => $monto
                                );
                            }
                            else
                            {
                                $success = false;

                                $response = HelpController::buildResponse(
                                    422,
                                    'La parte N:' . $parte['nparte'] . ' no existe en la solicitud seleccionada',
                                    null
                                );

                                break;
                            }
                        }

                        $solicitud->partes()->sync($syncData);

                        $completed = true;
                        foreach($syncData as $parte)
                        {
                            if(
                                ($parte['costo'] === null) || 
                                ($parte['margen'] === null) || 
                                ($parte['tiempoentrega'] === null) || 
                                ($parte['peso'] === null) || 
                                ($parte['flete'] === null) || 
                                ($parte['monto'] === null)
                            )
                            {
                                $completed = false;
                                
                                break;
                            }
                        }

                        if($completed === true)
                        {
                            $solicitud->estadosolicitud_id = 2; // Completada
                        }
                        else
                        {
                            $solicitud->estadosolicitud_id = 1; // Pendiente
                        }

                        $solicitud->save();

                        if($success === true)
                        {
                            DB::commit();
                            $solicitud = Solicitud::find($id);

                            $solicitud->makeHidden([
                                'cliente_id',
                                'estadosolicitud_id',
                                'created_at', 
                                'updated_at'
                            ]);
            
                            $solicitud->cliente;
                            $solicitud->cliente->makeHidden(['created_at', 'updated_at']);
                            
                            $solicitud->estadosolicitud;
                            $solicitud->estadosolicitud->makeHidden(['created_at', 'updated_at']);
            
                            $solicitud->partes;
                            foreach($solicitud->partes as $parte)
                            {
                                $parte->makeHidden(['marca_id', 'created_at', 'updated_at']);
                                
                                $parte->marca;
                                $parte->marca->makeHidden(['created_at', 'updated_at']);
                            }

                            $response = HelpController::buildResponse(
                                200,
                                ($completed === true) ? 'Solicitud completada' : 'Solicitud actualizada',
                                $solicitud
                            );
                        }
                        else
                        {
                            DB::rollback();
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            400,
                            'La solicitud no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar solicitudes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            DB::rollback();

            $response = HelpController::buildResponse(
                500,
                'Error al completar la solicitud [!]',
                null
            );
        }

        return $response;
    }

    public function close($id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('solicitudes close'))
            {
                if($solicitud = Solicitud::find($id))
                {    
                    if($solicitud->estadosolicitud_id === 2) // If Estadosolicitud = 'Completada'
                    {
                        $solicitud->estadosolicitud_id = 3; // Cerrada
                        
                        if($solicitud->save())
                        {
                            $response = HelpController::buildResponse(
                                200,
                                'Solicitud cerrada',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al cerrar la solicitud',
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            409,
                            'La solicitud no esta completa',
                            null
                        );
                    }
                }
                else
                {
                    $response = HelpController::buildResponse(
                        400,
                        'La solicitud no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a cerrar solicitudes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al cerrar la solicitud [!]',
                null
            );
        }

        return $response;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('solicitudes destroy'))
            {
                if($solicitud = Solicitud::find($id))
                {    
                    if(($solicitud->estadosolicitud_id === 1) || ($solicitud->estadosolicitud_id === 2))// If Estadosolicitud = 'Pendiente' or 'Completada'
                    {
                        if($solicitud->delete())
                        {
                            $response = HelpController::buildResponse(
                                200,
                                'Solicitud eliminada',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al eliminar la solicitud',
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            409,
                            'La solicitud ya esta cerrada',
                            null
                        );
                    }
                }
                else
                {
                    $response = HelpController::buildResponse(
                        400,
                        'La solicitud no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a cerrar solicitudes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al eliminar la solicitud [!]',
                null
            );
        }

        return $response;
    }

}
