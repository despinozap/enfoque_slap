<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Solicitud;

class SolicitudesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if($solicitudes = Solicitud::all())
        {
            foreach($solicitudes as $solicitud)
            {
                $solicitud->makeHidden(['cliente_id', 'user_id', 'estadosolicitud_id']);

                foreach($solicitud->partes as $parte)
                {   
                    $parte->makeHidden(['marca_id', 'created_at', 'updated_at']);
                    
                    $parte->pivot;
                    $parte->pivot->makeHidden(['solicitud_id', 'parte_id']);

                    $parte->marca;
                    $parte->marca->makeHidden(['created_at', 'updated_at']);
                }

                $solicitud->cliente;
                $solicitud->cliente->makeHidden(['created_at', 'updated_at']);
                $solicitud->user;
                $solicitud->user->makeHidden(['role_id', 'email_verified_at', 'created_at', 'updated_at']);
                $solicitud->user->role;
                $solicitud->user->role->makeHidden(['created_at', 'updated_at']);
                $solicitud->estadosolicitud;
                $solicitud->estadosolicitud->makeHidden(['created_at', 'updated_at']);
            }

            return $solicitudes;
        }
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
        $validatorInput = $request->only(
            'cliente_id', 
            'user_id', 
            'partes'
        );
		
		$validatorRules = [
			'cliente_id' => 'required|exists:clientes,id',
            'user_id' => 'required|exists:users,id',
			'partes' => 'required|array|min:1',
            'partes.*.id'  => 'required|exists:partes,id',
            'partes.*.cantidad'  => 'required|numeric|min:1',
        ];

		$validatorMessages = [
			'cliente_id.required' => 'Debes seleccionar un cliente',
            'cliente_id.exists' => 'El cliente no existe',
            'user_id.required' => 'Debes ingresar el usuario',
            'user_id.exists' => 'El usuario no existe',
			'partes.required' => 'Debes seleccionar las partes',
            'partes.array' => 'Lista de partes invalida',
            'partes.min' => 'La solicitud debe contener al menos 1 parte',
            'partes.*.id.required' => 'La lista de partes es invalida',
            'partes.*.id.exists' => 'La parte seleccionada no existe',
            'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte',
            'partes.*.cantidad.numeric' => 'La cantidad para la parte debe ser numerica',
            'partes.*.cantidad.min' => 'La cantidad para la parte debe ser mayor a 0',
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
            $solicitud = new Solicitud();
            $solicitud->fill($request->all());
            $solicitud->estadosolicitud_id = 1; //Initial Estadosolicitud

            if($solicitud->save())
            {
                //Attaching each Part to the Solicitud
                foreach($request->partes as $parte)
                {
                    $solicitud->partes()->attach([ 
                        $parte['id'] => [
                                            'cantidad' => $parte['cantidad']
                                        ]
                    ]);
                }

                $response = HelpController::buildResponse(
                    201,
                    'Solicitud creada',
                    null
                );
            }
            else
            {
                $response = HelpController::buildResponse(
                    500,
                    'Error al crear la solicitud',
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
        //
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
        //
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
