<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Role;
use App\Models\Sucursal;
use App\Models\Comprador;
use Auth;

class UsersController extends Controller
{
    public function updateProfile(Request $request)
    {
        try
        {
            $validatorInput = $request->only('email', 'phone');
		
            $validatorRules = [
                'email' => 'required|email',
                'phone' => 'digits:10'
            ];

            $validatorMessages = [
                'email.required' => 'Debes ingresar el email',
                'email.email' => 'El email debe ser valido',
                'phone.digits' => 'El telefono debe tener 10 digitos',
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
                if($user = Auth::user())
                {
                    if(User::where('email', $request->email)->where('id', '<>', $user->id)->first())
                    {
                        $response = HelpController::buildResponse(
                            400,
                            [
                                'email' => [
                                    'El email ya esta asociado a otro usuario'
                                ]
                            ],
                            null
                        );
                    }
                    else
                    {
                        $user->fill($request->all());

                        if($user->save())
                        {
                            $response = HelpController::buildResponse(
                                200,
                                'Perfil de usuario actualizado',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al actualizar el perfil de usuario',
                                null
                            );
                        }
                    }
                }
                else
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El usuario no existe',
                        null
                    );
                }
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al actualizar el perfil de usuario [!]',
                null
            );
        }

        return $response;
    }

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
            if($user->role->hasRoutepermission('users index'))
            {
                if($users = User::where('id', '<>', $user->id)->get())
                {
                    $users = $users->filter(function($user)
                    {
                        $user->makeHidden([
                            'stationable_type',
                            'stationable_id',
                            'role_id',
                            'country_id',
                            'email_verified_at',
                            'created_at', 
                            'updated_at'
                        ]);
    
                        $user->stationable->makeHidden([
                            'type',
                            'rut',
                            'address',
                            'city',
                            'contact',
                            'phone',
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);
    
                        $user->stationable->country->makeHidden([
                            'created_at', 
                            'updated_at'
                        ]);
    
                        $user->role;
                        $user->role->makeHidden(['created_at', 'updated_at']);
                        
                        return $user;
                    });
                    
                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $users
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de usuarios',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar usuarios',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de usuarios [!]',
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
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('users store'))
            {
                $validatorInput = $request->only('stationable_id', 'name', 'email', 'phone', 'role_name');
            
                $validatorRules = [
                    'stationable_id' => 'required|numeric',
                    'name' => 'required|min:4',
                    'email' => 'required|email|unique:users',
                    'phone' => 'digits:10',
                    'role_name' => 'required|exists:roles,name'
                ];

                $validatorMessages = [
                    'stationable_id.required' => 'Debes ingresar el la estacion de trabajo',
                    'stationable_id.numeric' => 'La estacion de trabajo debe ser numerica',
                    'name.required' => 'Debes ingresar el nombre',
                    'name.min' => 'El nombre debe tener al menos 4 caracteres',
                    'email.required' => 'Debes ingresar el email',
                    'email.email' => 'El email debe ser valido',
                    'email.unique' => 'El email ya esta asociado a otro usuario',
                    'phone.digits' => 'El telefono debe tener 10 digitos',
                    'role_name.required' => 'Debes seleccionar el rol',
                    'role_name.exists' => 'El rol ingresado no existe'
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
                    if($role = Role::where('name', '=', $request->role_name)->first())
                    {
                        $station = null;
                        switch($role->name)
                        {
                            // Vendedor solicitante (Vendedor en Sucursal)
                            case 'seller': {
                                
                                $station = Sucursal::find($request->stationable_id);

                                break;
                            }

                            // Agente de compra en Comprador
                            case 'agtcom': {
                                
                                $station = Comprador::find($request->stationable_id);

                                break;
                            }

                            // Coordinador Logistico comprador (bodega en Comprador)
                            case 'colcom': {
                                
                                $station = Comprador::find($request->stationable_id);

                                break;
                            }

                            // Coordinador Logistico solicitante (Bodega en Sucursal)
                            case 'colsol': {
                                
                                $station = Sucursal::find($request->stationable_id);

                                break;
                            }

                            default: {
                                break;
                            }
                        }

                        if($station !== null)
                        {
                            $user = new User();
                            $user->stationable_type = get_class($station);
                            $user->stationable_id = $station->id;
                            $user->role_id = $role->id;
                            $user->fill($request->all());
                            $user->password = bcrypt($request->email);
                            
                            if($user->save())
                            {
                                $response = HelpController::buildResponse(
                                    201,
                                    'Usuario creado',
                                    null
                                );
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al crear el usuario',
                                    null
                                );
                            }
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La estacion de trabajo no existe',
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'El rol no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a agregar usuarios',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al crear el usuario [!]',
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
            if($user->role->hasRoutepermission('users show'))
            {
                if($user = User::find($id))
                {
                    $user->makeHidden([
                        'stationable_type',
                        'stationable_id',
                        'role_id',
                        'country_id',
                        'email_verified_at',
                        'created_at', 
                        'updated_at'
                    ]);

                    $user->stationable->makeHidden([
                        'type',
                        'rut',
                        'address',
                        'city',
                        'contact',
                        'phone',
                        'country_id',
                        'created_at', 
                        'updated_at'
                    ]);

                    $user->stationable->country->makeHidden([
                        'created_at', 
                        'updated_at'
                    ]);

                    $user->role;
                    $user->role->makeHidden(['created_at', 'updated_at']);
                    
                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $user
                    );
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                    412,
                        'El usuario no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar usuarios',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener el usuario [!]',
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
            if($user->role->hasRoutepermission('users update'))
            {
                $validatorInput = $request->only('stationable_id', 'name', 'email', 'phone');
            
                $validatorRules = [
                    'stationable_id' => 'required|numeric',
                    'name' => 'required|min:4',
                    'email' => 'required|email',
                    'phone' => 'digits:10',
                ];

                $validatorMessages = [
                    'stationable_id.required' => 'Debes ingresar el la estacion de trabajo',
                    'stationable_id.numeric' => 'La estacion de trabajo debe ser numerica',
                    'name.required' => 'Debes ingresar el nombre',
                    'name.min' => 'El nombre debe tener al menos 4 caracteres',
                    'email.required' => 'Debes ingresar el email',
                    'email.email' => 'El email debe ser valido',
                    'phone.digits' => 'El telefono debe tener 10 digitos'
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
                else if(User::where('email', $request->email)->where('id', '<>', $id)->first())
                {
                    
                    $response = HelpController::buildResponse(
                        400,
                        [
                            'email' => [
                                'El email ya esta asociado a otro usuario'
                            ]
                        ],
                        null
                    );
                }   
                else     
                {
                    if($user = User::find($id))
                    {
                        $station = null;
                        switch($user->role->name)
                        {
                            // Vendedor
                            case 'seller': {
                                
                                $station = Sucursal::find($request->stationable_id);

                                break;
                            }

                            // Agente de compra en Comprador
                            case 'agtcom': {
                                
                                $station = Comprador::find($request->stationable_id);

                                break;
                            }

                            // Coordinador Logistico comprador (bodega en Comprador)
                            case 'colcom': {
                                
                                $station = Comprador::find($request->stationable_id);

                                break;
                            }

                            // Coordinador Logistico solicitante (Bodega en Sucursal)
                            case 'colsol': {
                                
                                $station = Sucursal::find($request->stationable_id);

                                break;
                            }

                            default: {
                                break;
                            }
                        }

                        if($station !== null)
                        {
                            $user->stationable_type = get_class($station);
                            $user->stationable_id = $station->id;
                            $user->fill($request->all());

                            if($user->save())
                            {
                                $response = HelpController::buildResponse(
                                    200,
                                    'Usuario actualizado',
                                    null
                                );
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al actualizar el usuario',
                                    null
                                );
                            }
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La estacion de trabajo no existe',
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'El usuario no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar usuarios',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al actualizar el usuario [!]',
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
            if($user->role->hasRoutepermission('users destroy'))
            {
                if($user->id !== $id)
                {
                    if($user = User::find($id))
                    {
                        if($user->solicitudes->count() === 0)
                        {
                            if($user->delete())
                            {
                                $response = HelpController::buildResponse(
                                    200,
                                    'Usuario eliminado',
                                    null
                                );
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al eliminar el usuario',
                                    null
                                );
                            }
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'No se puede eliminar un usuario con solicitudes asociadas',
                                null
                            );
                        }
                    }   
                    else     
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'El usuario no existe',
                            null
                        );
                    }
                }
                else
                {
                    $response = HelpController::buildResponse(
                        409,
                        'No puedes eliminar tu propio usuario',
                        null
                    );
                }
                
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a eliminar usuarios',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al eliminar el usuario [!]',
                null
            );
        }

        return $response;
    }
}
