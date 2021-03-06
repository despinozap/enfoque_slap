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
                $validatorInput = $request->only(
                    'rut', 
                    'name', 
                    'address', 
                    'city', 
                    'email', 
                    'phone', 
                    'delivered', 
                    'delivery_name',
                    'delivery_address',
                    'delivery_city',
                    'delivery_email',
                    'delivery_phone'
                );
            
                $validatorRules = [
                    'rut' => 'required|min:4',
                    'name' => 'required|min:4',
                    'address' => 'required|min:4',
                    'city' => 'required|min:4',
                    'email' => 'required|email',
                    'phone' => 'required|min:4',
                    'delivered' => 'required|boolean',
                    'delivery_name' => 'sometimes',
                    'delivery_address' => 'sometimes',
                    'delivery_city' => 'sometimes',
                    'delivery_email' => 'sometimes',
                    'delivery_phone' => 'sometimes'
                ];

                $validatorMessages = [
                    'rut.required' => 'Debes ingresar el RUT',
                    'rut.min' => 'El RUT debe tener al menos 4 caracteres',
                    'name.required' => 'Debes ingresar el nombre',
                    'name.min' => 'El nombre debe tener al menos 4 caracteres',
                    'address.required' => 'Debes ingresar la direccion',
                    'address.min' => 'La direccion debe tener al menos 4 caracteres',
                    'city.required' => 'Debes ingresar la ciudad',
                    'city.min' => 'La ciudad debe tener al menos 4 caracteres',
                    'email.required' => 'Debes ingresar el email',
                    'email.email' => 'El email debe ser valido',
                    'phone.required' => 'Debes ingresar el telefono',
                    'phone.min' => 'El telefono debe tener al menos 4 caracteres',
                    'delivered.required' => 'Debes seleccionar si el proveedor requiere entrega',
                    'delivered.boolean' => 'La seleccion de requerir entrega para el proveedor es invalido',
                    'delivery_email.email' => 'El email del punto de entrega debe ser valido',
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
                        400,
                        [
                            'name' => [
                                'Ya existe un proveedor con el nombre ingresado para el comprador seleccionado'
                            ]
                        ],
                        null
                    );
                }
                else if(Comprador::find($comprador_id)->proveedores->where('rut', $request->rut)->first())
                {
                    $response = HelpController::buildResponse(
                        400,
                        [
                            'rut' => [
                                'Ya existe un proveedor con el RUT ingresado para el comprador seleccionado'
                            ]
                        ],
                        null
                    );
                }
                else       
                {
                    $success = true;

                    // If requieres delivery
                    if($request->delivered === true)
                    {
                        $errorMessages = [];
                        if(!isset($request->delivery_name))
                        {
                            $success = false;
                        
                            $errorMessages['delivery_name'] = array(
                                'Debes ingresar el nombre del punto de entrega'
                            );
                        }
                        
                        if(!isset($request->delivery_address))
                        {
                            $success = false;
                        
                            $errorMessages['delivery_address'] = array(
                                'Debes ingresar la direccion del punto de entrega'
                            );
                        }
                        
                        if(!isset($request->delivery_city))
                        {
                            $success = false;
                        
                            $errorMessages['delivery_city'] = array(
                                'Debes ingresar la ciudad del punto de entrega'
                            );
                        }
                        
                        if(!isset($request->delivery_email))
                        {
                            $success = false;
                        
                            $errorMessages['delivery_email'] = array(
                                'Debes ingresar el email del punto de entrega'
                            );
                        }
                        
                        if(!isset($request->delivery_phone))
                        {
                            $success = false;
                        
                            $errorMessages['delivery_phone'] = array(
                                'Debes ingresar el telefono del punto de entrega'
                            );
                        }
                    }

                    if($success === true)
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
                    else
                    {
                        $response = HelpController::buildResponse(
                            400,
                            $errorMessages,
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
                            'country_id',
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
                $validatorInput = $request->only(
                    'rut', 
                    'name', 
                    'address', 
                    'city', 
                    'email', 
                    'phone', 
                    'delivered', 
                    'delivery_name',
                    'delivery_address',
                    'delivery_city',
                    'delivery_email',
                    'delivery_phone'
                );
            
                $validatorRules = [
                    'rut' => 'required|min:4',
                    'name' => 'required|min:4',
                    'address' => 'required|min:4',
                    'city' => 'required|min:4',
                    'email' => 'required|email',
                    'phone' => 'required|min:4',
                    'delivered' => 'required|boolean',
                    'delivery_name' => 'sometimes',
                    'delivery_address' => 'sometimes',
                    'delivery_city' => 'sometimes',
                    'delivery_email' => 'sometimes',
                    'delivery_phone' => 'sometimes'
                ];

                $validatorMessages = [
                    'rut.required' => 'Debes ingresar el RUT',
                    'rut.min' => 'El RUT debe tener al menos 4 caracteres',
                    'name.required' => 'Debes ingresar el nombre',
                    'name.min' => 'El nombre debe tener al menos 4 caracteres',
                    'address.required' => 'Debes ingresar la direccion',
                    'address.min' => 'La direccion debe tener al menos 4 caracteres',
                    'city.required' => 'Debes ingresar la ciudad',
                    'city.min' => 'La ciudad debe tener al menos 4 caracteres',
                    'email.required' => 'Debes ingresar el email',
                    'email.email' => 'El email debe ser valido',
                    'phone.required' => 'Debes ingresar el telefono',
                    'phone.min' => 'El telefono debe tener al menos 4 caracteres',
                    'delivered.required' => 'Debes seleccionar si el proveedor requiere entrega',
                    'delivered.boolean' => 'La seleccion de requerir entrega para el proveedor es invalido',
                    'delivery_email.email' => 'El email del punto de entrega debe ser valido',
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
                        400,
                        [
                            'name' => [
                                'Ya existe un proveedor con el nombre ingresado para el comprador seleccionado'
                            ]
                        ],
                        null
                    );
                }  
                else if($comprador->proveedores->where('rut', $request->rut)->where('id', '<>', $id)->first())
                {
                    
                    $response = HelpController::buildResponse(
                        400,
                        [
                            'rut' => [
                                'Ya existe un proveedor con el RUT ingresado para el comprador seleccionado'
                            ]
                        ],
                        null
                    );
                }    
                else     
                {
                    if($proveedor = Proveedor::find($id))
                    {
                        $success = true;

                        // If requieres delivery
                        if($request->delivered === true)
                        {
                            $errorMessages = [];
                            if(!isset($request->delivery_name))
                            {
                                $success = false;
                            
                                $errorMessages['delivery_name'] = array(
                                    'Debes ingresar el nombre del punto de entrega'
                                );
                            }
                            
                            if(!isset($request->delivery_address))
                            {
                                $success = false;
                            
                                $errorMessages['delivery_address'] = array(
                                    'Debes ingresar la direccion del punto de entrega'
                                );
                            }
                            
                            if(!isset($request->delivery_city))
                            {
                                $success = false;
                            
                                $errorMessages['delivery_city'] = array(
                                    'Debes ingresar la ciudad del punto de entrega'
                                );
                            }
                            
                            if(!isset($request->delivery_email))
                            {
                                $success = false;
                            
                                $errorMessages['delivery_email'] = array(
                                    'Debes ingresar el email del punto de entrega'
                                );
                            }
                            
                            if(!isset($request->delivery_phone))
                            {
                                $success = false;
                            
                                $errorMessages['delivery_phone'] = array(
                                    'Debes ingresar el telefono del punto de entrega'
                                );
                            }
                        }

                        if($success === true)
                        {
                            $proveedor->fill($request->all());

                            if($request->delivered === false)
                            {
                                $proveedor->delivery_name = null;
                                $proveedor->delivery_address = null;
                                $proveedor->delivery_city = null;
                                $proveedor->delivery_email = null;
                                $proveedor->delivery_phone = null;
                            }

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
                                400,
                                $errorMessages,
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
                    if($proveedor->recepciones->count() > 0)
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
