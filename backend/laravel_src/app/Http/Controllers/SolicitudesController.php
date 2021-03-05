<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Solicitud;
use App\Models\Parte;

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
            'marca_id',
            'comentario',
            'partes'
        );
		
		$validatorRules = [
			'cliente_id' => 'required|exists:clientes,id',
            'marca_id' => 'required|exists:marcas,id',
			'partes' => 'required|array|min:1',
            'partes.*.nparte'  => 'required',
            'partes.*.cantidad'  => 'required|numeric|min:1',
        ];

		$validatorMessages = [
			'cliente_id.required' => 'Debes seleccionar un cliente',
            'cliente_id.exists' => 'El cliente no existe',
            'marca_id.required' => 'Debes seleccionar una marca',
            'marca_id.exists' => 'La marca no existe',
			'partes.required' => 'Debes seleccionar las partes',
            'partes.array' => 'Lista de partes invalida',
            'partes.min' => 'La solicitud debe contener al menos 1 parte',
            'partes.*.nparte.required' => 'La lista de partes es invalida',
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
            

            $user = Auth::user();

            $solicitud = new Solicitud();
            $solicitud->fill($request->all());
            $solicitud->user_id = $user->id;
            $solicitud->estadosolicitud_id = 1; //Initial Estadosolicitud

            DB::beginTransaction();

            if($solicitud->save())
            {
                $success = true;

                //Attaching each Parte to the Solicitud
                foreach($request->partes as $parte)
                {
                    if($p = Parte::where('nparte', $parte['nparte'])->where('marca_id', $request->marca_id)->first())
                    {
                        $solicitud->partes()->attach([ 
                            $p->id => [
                                'cantidad' => $parte['cantidad']
                            ]
                        ]);
                    }
                    else
                    {
                        $success = false;

                        $response = HelpController::buildResponse(
                            422,
                            'La parte N:' . $parte['nparte'] . ' no existe en la marca seleccionada',
                            null
                        );

                        break;
                    }
                }

                if($success === true)
                {
                    DB::commit();

                    $response = HelpController::buildResponse(
                        201,
                        'Solicitud creada',
                        null
                    );
                }
                else
                {
                    DB::rollback();
                }
            }
            else
            {
                DB::rollback();

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
        $validatorInput = $request->only(
            'cliente_id',
            'marca_id',
            'comentario',
            'partes'
        );
		
		$validatorRules = [
			'cliente_id' => 'required|exists:clientes,id',
            'marca_id' => 'required|exists:marcas,id',
			'partes' => 'required|array|min:1',
            'partes.*.nparte'  => 'required',
            'partes.*.cantidad'  => 'required|numeric|min:1',
        ];

		$validatorMessages = [
			'cliente_id.required' => 'Debes seleccionar un cliente',
            'cliente_id.exists' => 'El cliente no existe',
            'marca_id.required' => 'Debes seleccionar una marca',
            'marca_id.exists' => 'La marca no existe',
			'partes.required' => 'Debes seleccionar las partes',
            'partes.array' => 'Lista de partes invalida',
            'partes.min' => 'La solicitud debe contener al menos 1 parte',
            'partes.*.nparte.required' => 'La lista de partes es invalida',
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
            if($solicitud = Solicitud::find($id))
            {
                $solicitud->fill($request->all());

                DB::beginTransaction();

                if($solicitud->save())
                {
                    $success = true;

                    //Detaching all the Partes from the solicitud
                    $solicitud->partes()->detach();

                    //Attaching each Parte to the Solicitud
                    foreach($request->partes as $parte)
                    {
                        if($p = Parte::where('nparte', $parte['nparte'])->where('marca_id', $request->marca_id)->first())
                        {
                            $solicitud->partes()->attach([ 
                                $p->id => [
                                    'cantidad' => $parte['cantidad']
                                ]
                            ]);
                        }
                        else
                        {
                            $success = false;

                            $response = HelpController::buildResponse(
                                422,
                                'La parte N:' . $parte['nparte'] . ' no existe en la marca seleccionada',
                                null
                            );

                            break;
                        }
                    }

                    if($success === true)
                    {
                        DB::commit();

                        $response = HelpController::buildResponse(
                            201,
                            'Solicitud editada',
                            null
                        );
                    }
                    else
                    {
                        DB::rollback();
                    }
                }
                else
                {
                    DB::rollback();

                    $response = HelpController::buildResponse(
                        500,
                        'Error al editar la solicitud',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    400,
                    'La solicitud no existe',
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
        //
    }
}
