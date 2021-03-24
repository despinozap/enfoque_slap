<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Parameter;
use Auth;

class ParametersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('parameters index'))
            {
                if($parameters = Parameter::all())
                {                    
                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $parameters
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de parametros',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar parametros',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de parametros [!]',
                null
            );
        }
        
        return $response;
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
            if($user->role->hasRoutepermission('parameters show'))
            {
                if($parameter = Parameter::find($id))
                {
                    $parameter->makeHidden([
                        'created_at', 
                        'updated_at'
                    ]);
                    
                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $parameter
                    );
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        400,
                        'El parametro no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar parametros',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener el parametro [!]',
                null
            );
        }
            
        return $response;
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
            if($user->role->hasRoutepermission('parameters update'))
            {
                $validatorInput = $request->only('name', 'description', 'value');
            
                $validatorRules = [
                    'value' => 'required',
                ];

                $validatorMessages = [
                    'value.required' => 'Debes ingresar el valor',
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
                    if($parameter = Parameter::find($id))
                    {
                        $parameter->fill($request->all());

                        if($parameter->save())
                        {
                            $response = HelpController::buildResponse(
                                200,
                                'Parametro actualizado',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al actualizar el parametro',
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            400,
                            'El parametro no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar parametros',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al actualizar el parametro [!]',
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
        //
    }
}
