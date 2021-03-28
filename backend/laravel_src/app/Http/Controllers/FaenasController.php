<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;

use App\Models\Faena;
use App\Models\Cliente;

class FaenasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $cliente_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('faenas index'))
            {
                $validatorInput = ['cliente_id' => $cliente_id];
            
                $validatorRules = [
                    'cliente_id' => 'required|exists:clientes,id',
                ];

                $validatorMessages = [
                    'cliente_id.required' => 'Debes seleccionar un cliente',
                    'cliente_id.exists' => 'El cliente no existe',
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
                    if($faenas = Cliente::find($request->cliente_id)->faenas)
                    {
                        $faenas = $faenas->filter(function($faena)
                        {
                            $faena->makeHidden([
                                'cliente_id',
                                'created_at',
                                'updated_at'
                            ]);

                            return $faena;
                        });

                        $response = HelpController::buildResponse(
                            200,
                            null,
                            $faenas
                        );
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            500,
                            'Error al obtener la lista de faenas',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar faenas',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de faenas [!]',
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
            if($user->role->hasRoutepermission('faenas index_full'))
            {
                if($faenas = Faena::all())
                {
                    $faenas = $faenas->filter(function($faena)
                    {
                        $faena->makeHidden([
                            'cliente_id',
                            'created_at',
                            'updated_at'
                        ]);

                        return $faena;
                    });

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $faenas
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de faenas',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar faenas',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de faenas [!]',
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
            if($user->role->hasRoutepermission('faenas store'))
            {
                $validatorInput = $request->only('name', 'cliente_id');
            
                $validatorRules = [
                    'name' => 'required|min:4',
                    'cliente_id' => 'required|exists:clientes,id'
                ];

                $validatorMessages = [
                    'name.required' => 'Debes ingresar el nombre',
                    'name.min' => 'El nombre debe tener al menos 4 caracteres',
                    'cliente_id.required' => 'Debes seleccionar el cliente',
                    'cliente_id.exists' => 'El cliente ingresado no existe'
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
                else if(Cliente::find($request->cliente_id)->faenas->where('name', $request->name)->first())
                {
                    $response = HelpController::buildResponse(
                        409,
                        'Ya existe una faena con el nombre ingresado para el cliente seleccionado',
                        null
                    );
                }
                else       
                {
                    $faena = new Faena();
                    $faena->fill($request->all());
                    
                    if($faena->save())
                    {
                        $response = HelpController::buildResponse(
                            201,
                            'Faena creada',
                            null
                        );
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            500,
                            'Error al crear la faena',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a agregar faenas',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al crear la faena [!]',
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
            if($user->role->hasRoutepermission('faenas show'))
            {
                if($faena = Faena::find($id))
                {
                    $faena->makeHidden([
                        'cliente_id',
                        'created_at', 
                        'updated_at'
                    ]);

                    $faena->cliente;
                    $faena->cliente->makeHidden([
                        'created_at', 
                        'updated_at'
                    ]);

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $faena
                    );
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        400,
                        'La faena no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar faenas',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la faena [!]',
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
            if($user->role->hasRoutepermission('faenas update'))
            {
                $validatorInput = $request->only('name', 'cliente_id');
            
                $validatorRules = [
                    'name' => 'required|min:4',
                    //'cliente_id' => 'required|exists:clientes,id'
                ];

                $validatorMessages = [
                    'name.required' => 'Debes ingresar el nombre',
                    'name.min' => 'El nombre debe tener al menos 4 caracteres',
                    'cliente_id.required' => 'Debes seleccionar el cliente',
                    'cliente_id.exists' => 'El cliente ingresado no existe'
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
                else if(Cliente::find($request->cliente_id)->faenas->where('name', $request->name)->where('id', '<>', $id)->first())
                {
                    
                    $response = HelpController::buildResponse(
                        409,
                        [
                            'name' => [
                                'Ya existe una faena con el nombre ingresado para el cliente seleccionado'
                            ]
                        ],
                        null
                    );
                }   
                else     
                {
                    if($faena = Faena::find($id))
                    {
                        $faena->fill($request->all());

                        if($faena->save())
                        {
                            $response = HelpController::buildResponse(
                                200,
                                'Faena actualizada',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al actualizar la faena',
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            400,
                            'La faena no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar faenas',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al actualizar la faena [!]',
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
            if($user->role->hasRoutepermission('faenas destroy'))
            {
                if($faena = Faena::find($id))
                {
                    if($faena->solicitudes->count() === 0)
                    {
                        if($faena->delete())
                        {
                            $response = HelpController::buildResponse(
                                200,
                                'Faena eliminada',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al eliminar la faena',
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            409,
                            'No puedes eliminar una faena con solicitudes asociadas',
                            null
                        );
                    }
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        400,
                        'La faena no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a eliminar faenas',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al eliminar la faena [!]',
                null
            );
        }

        return $response;
    }
}
