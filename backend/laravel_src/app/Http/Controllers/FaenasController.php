<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;

use App\Models\Faena;
use App\Models\Cliente;
use App\Models\Sucursal;

class FaenasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($cliente_id)
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
                else if(($cliente = Cliente::find($cliente_id)) === null)
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El cliente no existe',
                        null
                    );
                }
                else        
                {
                    $faenas = $cliente->faenas->filter(function($faena)
                    {
                        $faena->makeHidden([
                            'cliente_id',
                            'sucursal_id',
                            'created_at',
                            'updated_at'
                        ]);

                        $faena->sucursal;
                        $faena->sucursal->makeHidden([
                            'type',
                            'rut',
                            'address',
                            'country_id',
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
    public function store(Request $request, $cliente_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('faenas store'))
            {
                $validatorInput = $request->only('sucursal_id', 'rut', 'name', 'address', 'city', 'contact', 'phone');
            
                $validatorRules = [
                    'sucursal_id' => 'required|exists:sucursales,id',
                    'rut' => 'required|min:1',
                    'name' => 'required|min:4',
                    'address' => 'required|min:1',
                    'city' => 'required|min:1',
                    'contact' => 'required|min:1',
                    'phone' => 'required|min:1',
                ];

                $validatorMessages = [
                    'sucursal_id.required' => 'Debes seleccionar una sucursal',
                    'sucursal_id.exists' => 'La sucursal ingresada no existe',
                    'rut.required' => 'Debes ingresar el RUT',
                    'rut.min' => 'El RUT debe tener al menos 1 caracter',
                    'name.required' => 'Debes ingresar el nombre',
                    'name.min' => 'El nombre debe tener al menos 4 caracteres',
                    'address.required' => 'Debes ingresar la direccion',
                    'address.min' => 'La direccion debe tener al menos 1 caracter',
                    'city.required' => 'Debes ingresar la ciudad',
                    'city.min' => 'La ciudad debe tener al menos 1 caracter',
                    'contact.required' => 'Debes ingresar el nombre de contacto',
                    'contact.min' => 'El nombre de contacto debe tener al menos 1 caracter',
                    'phone.required' => 'Debes ingresar el telefono',
                    'phone.min' => 'El telefono debe tener al menos 1 caracter',
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
                else if(!($cliente = Cliente::find($cliente_id)))
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El cliente no existe',
                        null
                    );
                }
                else if(!($sucursal = Sucursal::where('id', '=', $request->sucursal_id)->where('country_id', '=', $cliente->country_id)->first()))
                {
                    $response = HelpController::buildResponse(
                        400,
                        [
                            'sucursal_id' => [
                                'La sucursal de entrega debe estar en el mismo pais del cliente'
                            ]
                        ],
                        null
                    );
                }
                else if(Cliente::find($cliente_id)->faenas->where('name', $request->name)->first())
                {
                    $response = HelpController::buildResponse(
                        400,
                        [
                            'name' => [
                                'Ya existe una faena con el nombre ingresado para el cliente seleccionado'
                            ]
                        ],
                        null
                    );
                }
                else if(Cliente::find($cliente_id)->faenas->where('rut', $request->rut)->first())
                {
                    $response = HelpController::buildResponse(
                        400,
                        [
                            'rut' => [
                                'Ya existe una faena con el RUT ingresado para el cliente seleccionado'
                            ]
                        ],
                        null
                    );
                } 
                else       
                {
                    $faena = new Faena();
                    $faena->fill($request->all());
                    $faena->cliente_id = $cliente->id;
                    
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
    public function show($cliente_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('faenas show'))
            {
                if($cliente = Cliente::find($cliente_id))
                {
                    if($faena = Faena::find($id))
                    {
                        $faena->makeHidden([
                            'cliente_id',
                            'sucursal_id',
                            'created_at', 
                            'updated_at'
                        ]);

                        $faena->cliente;
                        $faena->cliente->makeHidden([
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);

                        $faena->sucursal;
                        $faena->sucursal->makeHidden([
                            'type',
                            'rut',
                            'address',
                            'country_id',
                            'created_at',
                            'updated_at'
                        ]);

                        $sucursales = Sucursal::where('country_id', '=', $cliente->country_id)->get();
                        if($sucursales !== null)
                        {
                            $sucursales = $sucursales->filter(function($sucursal)
                            {
                                $sucursal->makeHidden([
                                    'type',
                                    'rut',
                                    'address',
                                    'country',
                                    'country_id',
                                    'created_at', 
                                    'updated_at'
                                ]);

                                $sucursal->country;
                                $sucursal->country->makeHidden(['created_at', 'updated_at']);

                                return $sucursal;
                            });

                            $data = [
                                "faena" => $faena,
                                "sucursales" => $sucursales,
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
                                'Error al obtener la lista de sucursales',
                                null
                            );
                        }
                    }   
                    else     
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'La faena no existe',
                            null
                        );
                    }
                }
                else
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El cliente no existe',
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
                'Error al obtener la faena [!]' .$e,
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
    public function update(Request $request, $cliente_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('faenas update'))
            {
                $validatorInput = $request->only('sucursal_id', 'rut', 'name', 'address', 'city', 'contact', 'phone');
            
                $validatorRules = [
                    'sucursal_id' => 'required|exists:sucursales,id',
                    'rut' => 'required|min:1',
                    'name' => 'required|min:4',
                    'address' => 'required|min:1',
                    'city' => 'required|min:1',
                    'contact' => 'required|min:1',
                    'phone' => 'required|min:1'
                ];

                $validatorMessages = [
                    'cliente_id.required' => 'Debes seleccionar el cliente',
                    'cliente_id.exists' => 'El cliente ingresado no existe',
                    'sucursal_id.required' => 'Debes seleccionar una sucursal',
                    'sucursal_id.exists' => 'La sucursal ingresada no existe',
                    'rut.required' => 'Debes ingresar el RUT',
                    'rut.min' => 'El RUT debe tener al menos 1 caracter',
                    'name.required' => 'Debes ingresar el nombre',
                    'name.min' => 'El nombre debe tener al menos 4 caracteres',
                    'address.required' => 'Debes ingresar la direccion',
                    'address.min' => 'La direccion debe tener al menos 1 caracter',
                    'city.required' => 'Debes ingresar la ciudad',
                    'city.min' => 'La ciudad debe tener al menos 1 caracter',
                    'contact.required' => 'Debes ingresar el nombre de contacto',
                    'contact.min' => 'El nombre de contacto debe tener al menos 1 caracter',
                    'phone.required' => 'Debes ingresar el telefono',
                    'phone.min' => 'El telefono debe tener al menos 1 caracter',
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
                else if(!($cliente = Cliente::find($cliente_id)))
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El cliente no existe',
                        null
                    );
                }
                else if(!($sucursal = Sucursal::where('id', '=', $request->sucursal_id)->where('country_id', '=', $cliente->country_id)->first()))
                {
                    $response = HelpController::buildResponse(
                        400,
                        [
                            'sucursal_id' => [
                                'La sucursal de entrega debe estar en el mismo pais del cliente'
                            ]
                        ],
                        null
                    );
                }
                else if($cliente->faenas->where('name', $request->name)->where('id', '<>', $id)->first())
                {
                    $response = HelpController::buildResponse(
                        400,
                        [
                            'name' => [
                                'Ya existe una faena con el nombre ingresado para el cliente seleccionado'
                            ]
                        ],
                        null
                    );
                }  
                else if($cliente->faenas->where('rut', $request->name)->where('id', '<>', $id)->first())
                {
                    
                    $response = HelpController::buildResponse(
                        400,
                        [
                            'rut' => [
                                'Ya existe una faena con el RUT ingresado para el cliente seleccionado'
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
                        $faena->cliente_id = $cliente_id;

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
                            412,
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
    public function destroy($cliente_id, $id)
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
                        412,
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
