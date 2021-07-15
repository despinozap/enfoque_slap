<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;

use App\Models\Comprador;

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
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);

                        $comprador->country;
                        $comprador->country->makeHidden(['created_at', 'updated_at']);

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
                        'country_id',
                        'created_at', 
                        'updated_at'
                    ]);

                    $comprador->country;
                    $comprador->country->makeHidden(['created_at', 'updated_at']);

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
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores update'))
            {
                $validatorInput = $request->only('rut', 'name', 'address', 'city', 'email', 'phone');
            
                $validatorRules = [
                    'rut' => 'required|min:1',
                    'name' => 'required|min:4',
                    'address' => 'required|min:1',
                    'city' => 'required|min:1',
                    'email' => 'required|email',
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
                    'email.required' => 'Debes ingresar el email',
                    'email.email' => 'El email debe ser valido',
                    'phone.required' => 'Debes ingresar el telefono',
                    'phone.min' => 'El telefono debe tener al menos 1 caracter'
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
                else if(Comprador::where('name', $request->name)->where('id', '<>', $id)->first())
                {
                    $response = HelpController::buildResponse(
                        400,
                        [
                            'name' => [
                                'Ya existe un comprador con el nombre ingresado'
                            ]
                        ],
                        null
                    );
                }  
                else if(Comprador::where('rut', $request->name)->where('id', '<>', $id)->first())
                {
                    
                    $response = HelpController::buildResponse(
                        400,
                        [
                            'rut' => [
                                'Ya existe comprador con el RUT ingresado'
                            ]
                        ],
                        null
                    );
                }   
                else     
                {
                    if($comprador = Comprador::find($id))
                    {
                        // Fill every single field for preventing overwriting country from request
                        $comprador->rut = $request->rut;
                        $comprador->name = $request->name;
                        $comprador->address = $request->address;
                        $comprador->city = $request->city;
                        $comprador->email = $request->email;
                        $comprador->phone = $request->phone;

                        if($comprador->save())
                        {
                            $response = HelpController::buildResponse(
                                200,
                                'Comprador actualizado',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al actualizar el comprador',
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
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar compradores',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al actualizar el comprador [!]',
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
