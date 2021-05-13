<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;

use App\Models\Comprador;
use App\Models\Proveedor;
use App\Models\Recepcion;

class ProveedoresController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $comprador_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('proveedores index'))
            {
                $validatorInput = ['comprador_id' => $comprador_id];
            
                $validatorRules = [
                    'comprador_id' => 'required|exists:compradores,id',
                ];

                $validatorMessages = [
                    'comprador_id.required' => 'Debes seleccionar un comprador',
                    'comprador_id.exists' => 'El comprador no existe',
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
                else if(($comprador = Comprador::find($comprador_id)) === null)
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El comprador no existe',
                        null
                    );
                }
                else        
                {
                    $proveedores = $comprador->proveedores->filter(function($proveedor)
                    {
                        $proveedor->makeHidden([
                            'comprador_id',
                            'created_at',
                            'updated_at'
                        ]);

                        return $proveedor;
                    });

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $proveedores
                    );

                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar proveedores',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de proveedores [!]',
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
    public function store(Request $request, $comprador_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('proveedores store'))
            {
                $validatorInput = $request->only('rut', 'name', 'address', 'city', 'contact', 'phone');
            
                $validatorRules = [
                    'rut' => 'required|unique:proveedores,rut|min:1',
                    'name' => 'required|min:4',
                    'address' => 'required|min:1',
                    'city' => 'required|min:1',
                    'contact' => 'required|min:1',
                    'phone' => 'required|min:1'
                ];

                $validatorMessages = [
                    'rut.required' => 'Debes ingresar el RUT',
                    'rut.min' => 'El RUT debe tener al menos 1 caracter',
                    'rut.unique' => 'Otra faena ya tiene asociado el RUT ingresado',
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
                else if(!Comprador::find($comprador_id))
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El comprador no existe',
                        null
                    );
                }
                else if(Comprador::find($comprador_id)->proveedores->where('name', $request->name)->first())
                {
                    $response = HelpController::buildResponse(
                        409,
                        'Ya existe un proveedor con el nombre ingresado para el comprador seleccionado',
                        null
                    );
                }
                else       
                {
                    $proveedor = new Proveedor();
                    $proveedor->fill($request->all());
                    $proveedor->comprador_id = $comprador_id;
                    
                    if($proveedor->save())
                    {
                        $response = HelpController::buildResponse(
                            201,
                            'Proveedor creado',
                            null
                        );
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            500,
                            'Error al crear el proveedor',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a agregar proveedores',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al crear el proveedor [!]',
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
    public function show($comprador_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('proveedores show'))
            {
                if(Comprador::find($comprador_id))
                {
                    if($proveedor = Proveedor::find($id))
                    {
                        $proveedor->makeHidden([
                            'comprador_id',
                            'created_at', 
                            'updated_at'
                        ]);

                        $proveedor->comprador;
                        $proveedor->comprador->makeHidden([
                            'created_at', 
                            'updated_at'
                        ]);

                        $response = HelpController::buildResponse(
                            200,
                            null,
                            $proveedor
                        );
                    }   
                    else     
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'El proveedor no existe',
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
                    'No tienes acceso a visualizar proveedores',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener el proveedor [!]',
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
    public function update(Request $request, $comprador_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('proveedores update'))
            {
                $validatorInput = $request->only('rut', 'name', 'address', 'city', 'contact', 'phone');
            
                $validatorRules = [
                    'rut' => 'required|min:1',
                    'name' => 'required|min:4',
                    'address' => 'required|min:1',
                    'city' => 'required|min:1',
                    'contact' => 'required|min:1',
                    'phone' => 'required|min:1'
                ];

                $validatorMessages = [
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
                else if(($comprador = Comprador::find($comprador_id)) === null)
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El comprador no existe',
                        null
                    );
                }
                else if($comprador->proveedores->where('name', $request->name)->where('id', '<>', $id)->first())
                {
                    
                    $response = HelpController::buildResponse(
                        409,
                        [
                            'name' => [
                                'Ya existe un proveedor con el nombre ingresado para el comprador seleccionado'
                            ]
                        ],
                        null
                    );
                }  
                else if(Proveedor::where('rut', $request->rut)->where('id', '<>', $id)->first())
                {
                    
                    $response = HelpController::buildResponse(
                        409,
                        [
                            'rut' => [
                                'Ya existe otro proveedor con el con el RUT ingresado'
                            ]
                        ],
                        null
                    );
                }   
                else     
                {
                    if($proveedor = Proveedor::find($id))
                    {
                        $proveedor->fill($request->all());
                        $proveedor->comprador_id= $comprador_id;

                        if($proveedor->save())
                        {
                            $response = HelpController::buildResponse(
                                200,
                                'Proveedor actualizado',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al actualizar el proveedor',
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'El proveedor no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar proveedores',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al actualizar el proveedor[!]',
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
    public function destroy($comprador_id, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('proveedores destroy'))
            {
                if($proveedor = Proveedor::find($id))
                {
                    if(
                        Recepcion::select('recepciones.*')
                        ->join('proveedor_recepcion', 'proveedor_recepcion.recepcion_id', '=', 'recepciones.id')
                        ->where('recepcionable_type', '=', get_class(new Comprador()))
                        ->where('proveedor_recepcion.proveedor_id', '=', $id)
                        ->get()
                        ->count() > 0
                    )
                    {
                        $response = HelpController::buildResponse(
                            409,
                            'No puedes eliminar un proveedor con recepciones asociadas',
                            null
                        );
                    }
                    else if($proveedor->ocs->count() > 0)
                    {
                        $response = HelpController::buildResponse(
                            409,
                            'No puedes eliminar un proveedor con OCs asociadas',
                            null
                        );
                    }
                    else
                    {
                        if($proveedor->delete())
                        {
                            $response = HelpController::buildResponse(
                                200,
                                'Proveedor eliminado',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al eliminar el proveedor',
                                null
                            );
                        }
                    }
                    
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El proveedor no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a eliminar proveedores',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al eliminar el proveedor [!]',
                null
            );
        }

        return $response;
    }
}
