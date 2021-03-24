<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Parte;
use App\Models\Marca;
use Auth;

class PartesController extends Controller
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
            if($user->role->hasRoutepermission('partes index'))
            {
                if($partes = Parte::all())
                {
                    foreach($partes as $parte)
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
                    }

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $partes
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de partes',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar partes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de partes [!]',
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
            if($user->role->hasRoutepermission('partes show'))
            {
                if($parte = Parte::find($id))
                {
                    $parte->marca;

                    $parte->makeHidden([
                        'marca_id',
                        'created_at', 
                        'updated_at'
                    ]);

                    $parte->marca->makeHidden(['created_at', 'updated_at']);

                    
                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $parte
                    );
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        400,
                        'La parte no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar partes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la parte [!]',
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
            if($user->role->hasRoutepermission('partes update'))
            {
                $validatorInput = $request->only('nparte', 'marca_id');
            
                $validatorRules = [
                    'nparte'  => 'required',
                    'marca_id' => 'required|exists:marcas,id'
                ];

                $validatorMessages = [
                    'nparte.required' => 'Debes ingresar el numero de parte',
                    'marca_id.required' => 'Debes seleccionar la marca',
                    'marca_id.exists' => 'La marca ingresada no existe'
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
                    
                    if($parte = Parte::find($id))
                    {
                        if(Marca::find($request->marca_id)->partes->where('nparte', $request->nparte)->where('id', '<>', $id)->first())
                        {
                            $response = HelpController::buildResponse(
                                409,
                                'Ya existe una parte con el numero de parte ingresado en la marca seleccionada',
                                null
                            );
                        }
                        else
                        {
                            if($parte->marca_id == $request->marca_id)
                            {
                                $parte->fill($request->all());

                                    if($parte->save())
                                    {
                                        $response = HelpController::buildResponse(
                                            200,
                                            'Parte actualizada',
                                            null
                                        );
                                    }
                                    else
                                    {
                                        $response = HelpController::buildResponse(
                                            500,
                                            'Error al actualizar la parte',
                                            null
                                        );
                                    }
                            }
                            else
                            {
                                if($parte->solicitudes->count() === 0)
                                {
                                    $parte->fill($request->all());

                                    if($parte->save())
                                    {
                                        $response = HelpController::buildResponse(
                                            200,
                                            'Parte actualizada',
                                            null
                                        );
                                    }
                                    else
                                    {
                                        $response = HelpController::buildResponse(
                                            500,
                                            'Error al actualizar la parte',
                                            null
                                        );
                                    }
                                }
                                else
                                {
                                    $response = HelpController::buildResponse(
                                        409,
                                        'No puedes cambiar la marca de una parte asociada a solicitudes',
                                        null
                                    );
                                }
                            }

                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            400,
                            'La parte no existe',
                            null
                        );
                    }
                    
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar partes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al actualizar la parte [!]',
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
            if($user->role->hasRoutepermission('partes destroy'))
            {
                if($parte = Parte::find($id))
                {
                    if($parte->solicitudes->count() === 0)
                    {
                        if($parte->delete())
                        {
                            $response = HelpController::buildResponse(
                                200,
                                'Parte eliminada',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al eliminar la parte',
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            409,
                            'No puedes eliminar una parte asociada a solicitudes',
                            null
                        );
                    }
                }
                else
                {
                    $response = HelpController::buildResponse(
                        400,
                        'La parte no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a eliminar partes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al eliminar la parte [!]',
                null
            );
        }

        return $response;
    }
}
