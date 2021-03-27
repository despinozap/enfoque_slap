<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Cliente;
use Auth;

class ClientesController extends Controller
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
            if($user->role->hasRoutepermission('clientes index'))
            {
                if($clientes = Cliente::all())
                {
                    $clientes = $clientes->filter(function($cliente)
                    {
                        $cliente->makeHidden([
                            'created_at', 
                            'updated_at'
                        ]);

                        return $cliente;
                    });
                    
                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $clientes
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de clientes',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar clientes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de clientes [!]',
                null
            );
        }
        
        return $response;
    }

    public function indexFull()
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('clientes index_full'))
            {
                
                if($clientes = Cliente::all())
                {
                    $clientes = $clientes->filter(function($cliente)
                    {
                        $cliente->makeHidden([
                            'created_at',
                            'updated_at'
                        ]);

                        return $cliente;
                    });

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $clientes
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de clientes',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar clientes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de clientes [!]',
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
            if($user->role->hasRoutepermission('clientes store'))
            {
                $validatorInput = $request->only('name');
            
                $validatorRules = [
                    'name' => 'required|min:2'
                ];

                $validatorMessages = [
                    'name.required' => 'Debes ingresar el nombre',
                    'name.min' => 'El nombre debe tener al menos 2 caracteres',
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
                    $cliente = new Cliente();
                    $cliente->fill($request->all());
                    
                    if($cliente->save())
                    {
                        $response = HelpController::buildResponse(
                            201,
                            'Cliente creado',
                            null
                        );
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            500,
                            'Error al crear el cliente',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a agregar clientes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al crear el cliente [!]',
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
            if($user->role->hasRoutepermission('clientes show'))
            {
                if($cliente = Cliente::find($id))
                {
                    $cliente->makeHidden([
                        'created_at', 
                        'updated_at'
                    ]);

                    $cliente->faenas;
                    $cliente->faenas = $cliente->faenas->filter(function($faena)
                    {
                        $faena->makeHidden(['cliente_id', 'created_at', 'updated_at']);
                    });

                    
                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $cliente
                    );
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        400,
                        'El cliente no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar clientes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener el cliente [!]',
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
            if($user->role->hasRoutepermission('clientes update'))
            {
                $validatorInput = $request->only('name');
            
                $validatorRules = [
                    'name' => 'required|min:2',
                ];

                $validatorMessages = [
                    'name.required' => 'Debes ingresar el nombre',
                    'name.min' => 'El nombre debe tener al menos 2 caracteres',
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
                    if($cliente = Cliente::find($id))
                    {
                        $cliente->fill($request->all());

                        if($cliente->save())
                        {
                            $response = HelpController::buildResponse(
                                200,
                                'Cliente actualizado',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al actualizar el cliente',
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            400,
                            'El cliente no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar clientes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al actualizar el cliente [!]',
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
            if($user->role->hasRoutepermission('clientes destroy'))
            {
                if($cliente = Cliente::find($id))
                {
                    if($cliente->getSolicitudesAttribute()->count() === 0)
                    {
                        if($cliente->delete())
                        {
                            $response = HelpController::buildResponse(
                                200,
                                'Cliente eliminado',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al eliminar el cliente',
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            409,
                            'No puedes eliminar un cliente con solicitudes asociadas',
                            null
                        );
                    }
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        400,
                        'El cliente no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a eliminar clientes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al eliminar el cliente [!]',
                null
            );
        }

        return $response;
    }
}
