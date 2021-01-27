<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Rol;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $response = null;

        //Quantity definition for pagination (if in request)
        $quantity = (($request->quantity) && ($request->quantity > 0)) ? $request->quantity : 10;

        if($users = User::paginate($quantity))
        {
            $users = $users->filter(function($user)
            {
                $user->rol;

                $user->makeHidden([
                    'email_verified_at',
                    'rol_id',
                    'created_at', 
                    'updated_at'
                ]);

                $user->rol->makeHidden(['created_at', 'updated_at']);

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
        $validatorInput = $request->only('nombre', 'email', 'telefono', 'rol_id');
		
		$validatorRules = [
			'nombre' => 'required|min:4',
            'email' => 'required|email|unique:users',
            'telefono' => 'digits:10',
			'rol_id' => 'required|exists:rols,id'
		];

		$validatorMessages = [
			'nombre.required' => 'Debes ingresar el nombre',
			'nombre.min' => 'El nombre debe tener al menos 4 caracteres',
            'email.required' => 'Debes ingresar el email',
            'email.email' => 'El email debe ser valido',
            'email.unique' => 'El email ya esta asociado a otro usuario',
            'telefono.digits' => 'El telefono debe tener 10 digitos',
            'rol_id.required' => 'Debes seleccionar el rol',
            'rol_id.exists' => 'El rol ingresado no existe'
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
            $user = new User();
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
        if($user = User::find($id))
        {
            $user->rol;

            $user->makeHidden([
                'email_verified_at',
                'rol_id',
                'created_at', 
                'updated_at'
            ]);

            $user->rol->makeHidden(['created_at', 'updated_at']);

			
            $response = HelpController::buildResponse(
                200,
                null,
                $user
            );
        }   
        else     
        {
            $response = HelpController::buildResponse(
                400,
                'El usuario no existe',
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
        $validatorInput = $request->only('nombre', 'email', 'telefono', 'rol_id');
		
		$validatorRules = [
			'nombre' => 'required|min:4',
            'email' => 'required|email',
            'telefono' => 'digits:10',
			'rol_id' => 'required|exists:rols,id'
		];

		$validatorMessages = [
			'nombre.required' => 'Debes ingresar el nombre',
			'nombre.min' => 'El nombre debe tener al menos 4 caracteres',
            'email.required' => 'Debes ingresar el email',
            'email.email' => 'El email debe ser valido',
            'telefono.digits' => 'El telefono debe tener 10 digitos',
            'rol_id.required' => 'Debes seleccionar el rol',
            'rol_id.exists' => 'El rol ingresado no existe'
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
                    400,
                    'El usuario no existe',
                    null
                );
            }
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
		if($user = User::find($id))
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
                400,
                'El usuario no existe',
                null
            );
        }

        return $response;
    }
}
