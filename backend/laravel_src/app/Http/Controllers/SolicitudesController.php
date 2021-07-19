<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;
use Illuminate\Support\Facades\DB;
use DateTime;

use App\Models\Parameter;
use App\Models\Sucursal;
use App\Models\Faena;
use App\Models\Marca;
use App\Models\Comprador;
use App\Models\Solicitud;
use App\Models\Parte;
use App\Models\Cotizacion;

class SolicitudesController extends Controller
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
            if($user->role->hasRoutepermission('solicitudes index'))
            {
                switch($user->role->name)
                {
                    case 'admin': // Administrador
                        {
                            if(
                                $solicitudes = Solicitud::select('solicitudes.*')
                                            ->join('sucursales', 'sucursales.id', '=', 'solicitudes.sucursal_id')
                                            ->where('sucursales.country_id', '=', $user->stationable->country->id) // All the data in its own country
                                            ->get()
                            )
                            {
                                foreach($solicitudes as $solicitud)
                                {
                                    $solicitud->makeHidden([
                                        'partes',
                                        'sucursal_id',
                                        'faena_id',
                                        'marca_id',
                                        'comprador_id',
                                        'user_id', 
                                        'estadosolicitud_id'
                                    ]);

                                    foreach($solicitud->partes as $parte)
                                    {   
                                        $parte->makeHidden(['marca_id', 'created_at', 'updated_at']);
                                        
                                        $parte->pivot;
                                        $parte->pivot->makeHidden(['solicitud_id', 'parte_id']);

                                        $parte->marca;
                                        $parte->marca->makeHidden(['created_at', 'updated_at']);
                                    }

                                    $solicitud->partes_total;
                                    $solicitud->sucursal;
                                    $solicitud->sucursal->makeHidden([
                                        'type',
                                        'rut',
                                        'address',
                                        'city',
                                        'country_id',
                                        'created_at', 
                                        'updated_at'
                                    ]);
                                    $solicitud->faena;
                                    $solicitud->faena->makeHidden([
                                        'sucursal_id',
                                        'rut',
                                        'address',
                                        'city',
                                        'contact',
                                        'phone',
                                        'cliente_id', 
                                        'created_at', 
                                        'updated_at'
                                    ]);
                                    $solicitud->faena->cliente;
                                    $solicitud->faena->cliente->makeHidden(['country_id', 'created_at', 'updated_at']);
                                    $solicitud->marca;
                                    $solicitud->marca->makeHidden(['created_at', 'updated_at']);
                                    $solicitud->comprador;
                                    $solicitud->comprador->makeHidden([
                                        'rut',
                                        'address',
                                        'city',
                                        'contact',
                                        'phone',
                                        'country_id', 
                                        'created_at', 
                                        'updated_at'
                                    ]);
                                    $solicitud->user;
                                    $solicitud->user->makeHidden(['email', 'phone', 'country_id', 'role_id', 'email_verified_at', 'created_at', 'updated_at']);
                                    $solicitud->estadosolicitud;
                                    $solicitud->estadosolicitud->makeHidden(['created_at', 'updated_at']);
                                }

                                $response = HelpController::buildResponse(
                                    200,
                                    null,
                                    $solicitudes
                                );
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al obtener la lista de solicitudes',
                                    null
                                );
                            }

                            break;
                        }

                    case 'seller': // Vendedor
                        {
                            if(
                                $solicitudes = Solicitud::where('sucursal_id', '=', $user->stationable->id)  //Only belonging data in its Sucursal
                                            ->where('user_id', '=', $user->id)
                                            ->get()
                            )
                            {
                                foreach($solicitudes as $solicitud)
                                {
                                    $solicitud->makeHidden([
                                        'partes',
                                        'sucursal_id',
                                        'faena_id',
                                        'marca_id',
                                        'comprador_id',
                                        'user_id', 
                                        'estadosolicitud_id'
                                    ]);

                                    foreach($solicitud->partes as $parte)
                                    {   
                                        $parte->makeHidden(['marca_id', 'created_at', 'updated_at']);
                                        
                                        $parte->pivot;
                                        $parte->pivot->makeHidden(['solicitud_id', 'parte_id']);

                                        $parte->marca;
                                        $parte->marca->makeHidden(['created_at', 'updated_at']);
                                    }

                                    $solicitud->partes_total;
                                    $solicitud->sucursal;
                                    $solicitud->sucursal->makeHidden([
                                        'type',
                                        'rut',
                                        'address',
                                        'city',
                                        'country_id',
                                        'created_at', 
                                        'updated_at'
                                    ]);
                                    $solicitud->faena;
                                    $solicitud->faena->makeHidden([
                                        'sucursal_id',
                                        'rut',
                                        'address',
                                        'city',
                                        'contact',
                                        'phone',
                                        'cliente_id', 
                                        'created_at', 
                                        'updated_at'
                                    ]);
                                    $solicitud->faena->cliente;
                                    $solicitud->faena->cliente->makeHidden(['country_id', 'created_at', 'updated_at']);
                                    $solicitud->marca;
                                    $solicitud->marca->makeHidden(['created_at', 'updated_at']);
                                    $solicitud->comprador;
                                    $solicitud->comprador->makeHidden([
                                        'rut',
                                        'address',
                                        'city',
                                        'contact',
                                        'phone',
                                        'country_id', 
                                        'created_at', 
                                        'updated_at'
                                    ]);
                                    $solicitud->user;
                                    $solicitud->user->makeHidden(['email', 'phone', 'country_id', 'role_id', 'email_verified_at', 'created_at', 'updated_at']);
                                    $solicitud->estadosolicitud;
                                    $solicitud->estadosolicitud->makeHidden(['created_at', 'updated_at']);
                                }

                                $response = HelpController::buildResponse(
                                    200,
                                    null,
                                    $solicitudes
                                );
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al obtener la lista de solicitudes',
                                    null
                                );
                            }

                            break;
                        }

                    case 'agtcom': // Agente de compra
                        {
                            if($solicitudes = Solicitud::where('comprador_id', '=', $user->stationable->id)->get()) //Only Solicitudes for its Comprador
                            {
                                foreach($solicitudes as $solicitud)
                                {
                                    $solicitud->makeHidden([
                                        'partes',
                                        'sucursal_id',
                                        'faena_id',
                                        'marca_id',
                                        'comprador_id',
                                        'user_id', 
                                        'estadosolicitud_id'
                                    ]);

                                    foreach($solicitud->partes as $parte)
                                    {   
                                        $parte->makeHidden(['marca_id', 'created_at', 'updated_at']);
                                        
                                        $parte->pivot;
                                        $parte->pivot->makeHidden(['solicitud_id', 'parte_id']);

                                        $parte->marca;
                                        $parte->marca->makeHidden(['created_at', 'updated_at']);
                                    }

                                    $solicitud->partes_total;
                                    $solicitud->sucursal;
                                    $solicitud->sucursal->makeHidden([
                                        'type',
                                        'rut',
                                        'address',
                                        'city',
                                        'country_id',
                                        'created_at', 
                                        'updated_at'
                                    ]);
                                    $solicitud->faena;
                                    $solicitud->faena->makeHidden([
                                        'sucursal_id',
                                        'rut',
                                        'address',
                                        'city',
                                        'contact',
                                        'phone',
                                        'cliente_id', 
                                        'created_at', 
                                        'updated_at'
                                    ]);
                                    $solicitud->faena->cliente;
                                    $solicitud->faena->cliente->makeHidden(['country_id', 'created_at', 'updated_at']);
                                    $solicitud->marca;
                                    $solicitud->marca->makeHidden(['created_at', 'updated_at']);
                                    $solicitud->comprador;
                                    $solicitud->comprador->makeHidden([
                                        'rut',
                                        'address',
                                        'city',
                                        'contact',
                                        'phone',
                                        'country_id', 
                                        'created_at', 
                                        'updated_at'
                                    ]);
                                    $solicitud->user;
                                    $solicitud->user->makeHidden(['email', 'phone', 'country_id', 'role_id', 'email_verified_at', 'created_at', 'updated_at']);
                                    $solicitud->estadosolicitud;
                                    $solicitud->estadosolicitud->makeHidden(['created_at', 'updated_at']);
                                }

                                $response = HelpController::buildResponse(
                                    200,
                                    null,
                                    $solicitudes
                                );
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al obtener la lista de solicitudes',
                                    null
                                );
                            }

                            break;
                        }

                    default: //All others
                        {
                            $solicitudes = [];

                            break;
                        }
                }
                
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar solicitudes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de solicitudes [!]',
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
     * It retrieves all the required info for
     * selecting data and storing a new Solicitud
     * 
     */
    public function store_prepare()
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('solicitudes store'))
            {
                $faenas = null;

                switch($user->role->name)
                {
                    case 'admin':
                        {
                            // Gets only the Faenas in the same country than user station (Sucursal)
                            $faenas = Faena::select('faenas.*')
                                    ->join('clientes', 'clientes.id', '=', 'faenas.cliente_id')
                                    ->where('clientes.country_id', '=', $user->stationable->country->id)
                                    ->get();

                            break;
                        }

                    case 'seller':
                        {
                            // Gets only the Faenas with the same delivery Sucursal than user station (Sucursal)
                            $faenas = Faena::select('faenas.*')
                                    ->where('faenas.sucursal_id', '=', $user->stationable->id)
                                    ->get();

                            break;
                        }

                    default:
                    {
                        break;
                    }
                }

                if($faenas === null)
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de faenas',
                        null
                    );
                }
                else if( ! ($marcas = Marca::all()) )
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de marcas',
                        null
                    );
                }
                else if( ! ($compradores = Comprador::all()) )
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de compradores',
                        null
                    );
                }
                else
                {
                    $faenas = $faenas->filter(function($faena)
                    {
                        $faena->makeHidden([
                            'cliente_id',
                            'sucursal_id',
                            'rut',
                            'address',
                            'city',
                            'contact',
                            'phone',
                            'created_at',
                            'updated_at'
                        ]);

                        $faena->cliente;
                        $faena->cliente->makeHidden([
                            'country_id',
                            'created_at',
                            'updated_at'
                        ]);

                        return $faena;
                    });

                    $marcas = $marcas->filter(function($marca)
                    {
                        $marca->makeHidden([
                            'created_at',
                            'updated_at'
                        ]);

                        return $marca;
                    });

                    $compradores = $compradores->filter(function($comprador)
                    {
                        $comprador->makeHidden([
                            'rut',
                            'address',
                            'city',
                            'contact',
                            'phone',
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);

                        return $comprador;
                    });
                    
                    $data = [
                        'faenas' => $faenas,
                        'marcas' => $marcas,
                        'compradores' => $compradores
                    ];

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $data
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a registrar solicitudes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            DB::rollback();

            $response = HelpController::buildResponse(
                500,
                'Error al preparar la solicitud [!]',
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
            if($user->role->hasRoutepermission('solicitudes store'))
            {
                $validatorInput = $request->only(
                    'sucursal_id',
                    'faena_id',
                    'marca_id',
                    'comprador_id',
                    'comentario',
                    'partes'
                );
                
                $validatorRules = [
                    'sucursal_id' => 'required|exists:sucursales,id',
                    'faena_id' => 'required|exists:faenas,id',
                    'marca_id' => 'required|exists:marcas,id',
                    'comprador_id' => 'required|exists:compradores,id',
                    'partes' => 'required|array|min:1',
                    'partes.*.nparte'  => 'required',
                    'partes.*.cantidad'  => 'required|numeric|min:1',
                ];
        
                $validatorMessages = [
                    'sucursal_id.required' => 'Debes seleccionar una sucursal',
                    'sucursal_id.exists' => 'La sucursal no existe',
                    'faena_id.required' => 'Debes seleccionar una faena',
                    'faena_id.exists' => 'La faena no existe',
                    'marca_id.required' => 'Debes seleccionar una marca',
                    'marca_id.exists' => 'La marca no existe',
                    'comprador_id.required' => 'Debes seleccionar un comprador',
                    'comprador_id.exists' => 'El comprador no existe',
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
                    $forbidden = true;

                    switch($user->role->name)
                    {
                        case 'admin':
                            {
                                // Get Sucursal in request
                                if($sucursal = Sucursal::find($request->sucursal_id))
                                {
                                    // If user belongs to the same country than Sucursal in request
                                    if($user->stationable->country->id === $sucursal->country->id)
                                    {
                                        // Allow user for registering the Solicitud
                                        $forbidden = false;
                                    }
                                }

                                break;
                            }

                        case 'seller':
                            {
                                // If user belongs to the same Sucursal than Sucursal in request
                                if($user->stationable->id === $request->sucursal_id)
                                {
                                    // Allow user for registering the Solicitud
                                    $forbidden = false;
                                }

                                break;
                            }

                        default:
                        {
                            break;
                        }
                    }

                    if($forbidden === false)
                    {
                        // Check if faena is in the same country than Sucursal
                        if($faena = Faena::select('faenas.*')
                            ->join('clientes', 'clientes.id', '=', 'faenas.cliente_id')
                            ->join('sucursales', 'sucursales.country_id', '=', 'clientes.country_id')
                            ->where('sucursales.id', '=', $request->sucursal_id)
                            ->where('faenas.id', '=', $request->faena_id)
                            ->first()
                        )
                        {
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
                                        $p = new Parte();
                                        $p->nparte = $parte['nparte'];
                                        $p->marca_id = $request->marca_id;
                                        if($p->save())
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
                                                500,
                                                'Error al crear la parte N:' . $parte['nparte'],
                                                null
                                            );
                    
                                            break;
                                        }
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
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La faena no existe para la sucursal',
                                null
                            );
                        }
                    }
                    else
                    {
                        //If it's forbidden for registering the Solicitud
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a registrar solicitudes para la sucursal ingresada',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a registrar solicitudes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            DB::rollback();

            $response = HelpController::buildResponse(
                500,
                'Error al crear la solicitud [!]',
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
            if($user->role->hasRoutepermission('solicitudes show'))
            {
                if($solicitud = Solicitud::find($id))
                {
                    // Administrador
                    if(
                        ($user->role->name === 'admin') && 
                        ($solicitud->sucursal->country->id !== $user->stationable->country->id)
                    )
                    {
                        //If Administrator and solicitud doesn't belong to its country
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a visualizar esta solicitud',
                            null
                        );
                    }
                    // Vendedor
                    else if(
                        ($user->role->name === 'seller') &&
                        (
                            ($solicitud->sucursal->id !== $user->stationable->id) ||
                            ($solicitud->user->id !== $user->id)
                        ) 
                    )
                    {
                        //If Vendedor and solicitud doesn't belong or not in its Sucursal
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a visualizar esta solicitud',
                            null
                        );
                    }
                    // Agente de compra
                    else if(
                        ($user->role->name === 'agtcom') &&
                        ($solicitud->comprador->id !== $user->stationable->id)
                    )
                    {
                        //If Agente de compra and solicitud isn't to its Comprador
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a visualizar esta solicitud',
                            null
                        );
                    }
                    else
                    {
                        if($usdToClp = Parameter::where('name', 'usd_to_clp')->first())
                        {
                            $solicitud->makeHidden([
                                'sucursal_id',
                                'comprador_id',
                                'user_id',
                                'faena_id',
                                'marca_id',
                                'estadosolicitud_id',
                                'created_at', 
                                'updated_at'
                            ]);
        
                            $solicitud->partes_total;
                                    
                            $solicitud->sucursal;
                            $solicitud->sucursal->makeHidden([
                                'type',
                                'rut',
                                'address',
                                'city',
                                'country_id',
                                'created_at', 
                                'updated_at'
                            ]);
                            
                            $solicitud->faena;
                            $solicitud->faena->makeHidden([
                                'sucursal_id',
                                'rut',
                                'address',
                                'city',
                                'contact',
                                'phone',
                                'cliente_id', 
                                'created_at', 
                                'updated_at'
                            ]);
                            
                            $solicitud->faena->cliente;
                            $solicitud->faena->cliente->makeHidden(['country_id', 'created_at', 'updated_at']);
                            
                            $solicitud->marca;
                            $solicitud->marca->makeHidden(['created_at', 'updated_at']);
                            
                            $solicitud->comprador;
                            $solicitud->comprador->makeHidden([
                                'rut',
                                'address',
                                'city',
                                'contact',
                                'phone',
                                'country_id', 
                                'created_at', 
                                'updated_at'
                            ]);
                            
                            $solicitud->user;
                            $solicitud->user->makeHidden(['email', 'phone', 'country_id', 'role_id', 'email_verified_at', 'created_at', 'updated_at']);
                            
                            $solicitud->estadosolicitud;
                            $solicitud->estadosolicitud->makeHidden(['created_at', 'updated_at']);
        
                            $solicitud->partes;
                            foreach($solicitud->partes as $parte)
                            {
                                $parte->makeHidden([
                                    'marca_id', 
                                    'created_at', 
                                    'updated_at'
                                ]);
        
                                $parte->marca;
                                $parte->marca->makeHidden(['created_at', 'updated_at']);
        
                                switch($user->role->name)
                                {
                                    case 'admin': { // Administrador
        
                                        $parte->pivot->makeHidden([
                                            'solicitud_id',
                                            'parte_id',
                                            'marca_id', 
                                            'created_at', 
                                            'updated_at'
                                        ]);
        
                                        break;
                                    }
        
                                    case 'seller': { // Vendedor
        
                                        if($parte->pivot->monto !== null)
                                        {
                                            $parte->pivot->monto = $parte->pivot->monto * $usdToClp->value;
                                        }
                                        
                                        $parte->pivot->makeHidden([
                                            'costo',
                                            'margen',
                                            'peso',
                                            'flete',
                                            'solicitud_id',
                                            'parte_id',
                                            'marca_id',
                                            'created_at', 
                                            'updated_at'
                                        ]);
        
                                        break;
                                    }

                                    case 'agtcom': { // Agente de compra
        
                                        $parte->pivot->makeHidden([
                                            'solicitud_id',
                                            'parte_id',
                                            'marca_id', 
                                            'created_at', 
                                            'updated_at'
                                        ]);
        
                                        break;
                                    }
        
                                    default: {
        
                                        break;
                                    }
                                }
                            }
                            
                            $response = HelpController::buildResponse(
                                200,
                                null,
                                $solicitud
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al obtener el valor USD para conversion',
                                null
                            );
                        }
                    }
                    
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        412,
                        'La solicitud no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar solicitudes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la solicitud [!]',
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
            if($user->role->hasRoutepermission('solicitudes update'))
            {
                $validatorInput = $request->only(
                    'faena_id',
                    'marca_id',
                    'comprador_id',
                    'comentario',
                    'partes'
                );
                
                $validatorRules = [
                    'faena_id' => 'required|exists:faenas,id',
                    'marca_id' => 'required|exists:marcas,id',
                    'comprador_id' => 'required|exists:compradores,id',
                    'partes' => 'required|array|min:1',
                    'partes.*.nparte'  => 'required',
                    'partes.*.cantidad'  => 'required|numeric|min:1',
                ];
        
                $validatorMessages = [
                    'faena_id.required' => 'Debes seleccionar una faena',
                    'faena_id.exists' => 'La faena no existe',
                    'marca_id.required' => 'Debes seleccionar una marca',
                    'marca_id.exists' => 'La marca no existe',
                    'comprador_id.required' => 'Debes seleccionar un comprador',
                    'comprador_id.exists' => 'El comprador no existe',
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
                        // Check if faena is in the same country than Surursal
                        if($faena = Faena::select('faenas.*')
                            ->join('clientes', 'clientes.id', '=', 'faenas.cliente_id')
                            ->join('sucursales', 'sucursales.country_id', '=', 'clientes.country_id')
                            ->where('sucursales.id', '=', $solicitud->sucursal_id)
                            ->where('faenas.id', '=', $request->faena_id)
                            ->first()
                        )
                        {
                            // Administrador
                            if(
                                ($user->role->name === 'admin') && 
                                ($solicitud->sucursal->country->id !== $user->stationable->country->id)
                            )
                            {
                                //If Administrator and solicitud doesn't belong to its country
                                $response = HelpController::buildResponse(
                                    405,
                                    'No tienes acceso a actualizar esta solicitud',
                                    null
                                );
                            }
                            // Vendedor
                            else if(
                                ($user->role->name === 'seller') &&
                                (
                                    ($solicitud->sucursal->id !== $user->stationable->id) ||
                                    ($solicitud->user->id !== $user->id)
                                ) 
                            )
                            {
                                //If Vendedor and solicitud doesn't belong or not in its Sucursal
                                $response = HelpController::buildResponse(
                                    405,
                                    'No tienes acceso a actualizar esta solicitud',
                                    null
                                );
                            }
                            else if($solicitud->estadosolicitud_id === 3)
                            {
                                // If solicitud is already 'Cerrada'
                                $response = HelpController::buildResponse(
                                    409,
                                    'No puedes editar una solicitud cerrada',
                                    null
                                );
                            }
                            else
                            {
                                $solicitud->fill($request->all());
            
                                DB::beginTransaction();
                
                                if($solicitud->save())
                                {
                                    $success = true;
                
                                    $syncData = [];
                                    foreach($request->partes as $parte)
                                    {
                                        if($p = Parte::where('nparte', $parte['nparte'])->where('marca_id', $request->marca_id)->first())
                                        {
                                            $syncData[$p->id] =  array('cantidad' => $parte['cantidad']);
                                        }
                                        else
                                        {
                                            $p = new Parte();
                                            $p->nparte = $parte['nparte'];
                                            $p->marca_id = $request->marca_id;
                                            if($p->save())
                                            {
                                                $syncData[$p->id] =  array('cantidad' => $parte['cantidad']);
                                            }
                                            else
                                            {
                                                $success = false;
                    
                                                $response = HelpController::buildResponse(
                                                    500,
                                                    'Error al crear la parte N:' . $parte['nparte'],
                                                    null
                                                );
                        
                                                break;
                                            }
                                        }
                                    }
                
                                    if($success === true)
                                    {
                                        $solicitud->partes()->sync($syncData);

                                        DB::commit();
                
                                        $response = HelpController::buildResponse(
                                            200,
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
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                412,
                                'La faena no existe para la sucursal',
                                null
                            );
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'La solicitud no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar solicitudes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {        
            $response = HelpController::buildResponse(
                500,
                'Error al editar la solicitud [!]',
                null
            );
        }
        
        return $response;
    }

    /**
     * It retrieves all the required info for
     * selecting data and updating a Recepcion for Comprador
     * 
     */
    public function complete_prepare(Request $request, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('solicitudes complete'))
            {
                if($solicitud = Solicitud::find($id))
                {
                    // Administrador
                    if(
                        ($user->role->name === 'admin') && 
                        ($solicitud->sucursal->country->id !== $user->stationable->country->id)
                    )
                    {
                        //If Administrator and solicitud doesn't belong to its country
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a actualizar esta solicitud',
                            null
                        );
                    }
                    // Agente de compra
                    else if(
                        ($user->role->name === 'agtcom') &&
                        ($solicitud->comprador->id !== $user->stationable->id)
                    )
                    {
                        //If Agente de compra and solicitud isn't to its Comprador
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a actualizar esta solicitud',
                            null
                        );
                    }
                    else
                    {
                        if($lbInUsd = Parameter::where('name', 'lb_in_usd')->first())
                        {
                            $solicitud->makeHidden([
                                'sucursal_id',
                                'comprador_id',
                                'user_id',
                                'faena_id',
                                'marca_id',
                                'estadosolicitud_id',
                                'created_at', 
                                'updated_at'
                            ]);
        
                            $solicitud->partes_total;
                                    
                            $solicitud->sucursal;
                            $solicitud->sucursal->makeHidden([
                                'type',
                                'rut',
                                'address',
                                'city',
                                'country',
                                'country_id',
                                'created_at', 
                                'updated_at'
                            ]);
                            
                            $solicitud->faena;
                            $solicitud->faena->makeHidden([
                                'sucursal_id',
                                'rut',
                                'address',
                                'city',
                                'contact',
                                'phone',
                                'cliente_id', 
                                'created_at', 
                                'updated_at'
                            ]);
                            
                            $solicitud->faena->cliente;
                            $solicitud->faena->cliente->makeHidden(['country_id', 'created_at', 'updated_at']);
                            
                            $solicitud->marca;
                            $solicitud->marca->makeHidden(['created_at', 'updated_at']);
                            
                            $solicitud->comprador;
                            $solicitud->comprador->makeHidden([
                                'rut',
                                'address',
                                'city',
                                'contact',
                                'phone',
                                'country_id', 
                                'created_at', 
                                'updated_at'
                            ]);
                            
                            $solicitud->user;
                            $solicitud->user->makeHidden([
                                'stationable_type',
                                'stationable_id',
                                'email', 
                                'phone', 
                                'country_id', 
                                'role_id', 
                                'email_verified_at', 
                                'created_at', 
                                'updated_at'
                            ]);
                            
                            $solicitud->estadosolicitud;
                            $solicitud->estadosolicitud->makeHidden(['created_at', 'updated_at']);
        
                            $solicitud->partes;
                            foreach($solicitud->partes as $parte)
                            {
                                $parte->makeHidden([
                                    'marca_id', 
                                    'created_at', 
                                    'updated_at'
                                ]);
        
                                $parte->marca;
                                $parte->marca->makeHidden(['created_at', 'updated_at']);
        
                                $parte->pivot->makeHidden([
                                    'solicitud_id',
                                    'parte_id',
                                    'marca_id', 
                                    'created_at', 
                                    'updated_at'
                                ]);
                            }

                            $data = [
                                'solicitud' => $solicitud,
                                'lb_in_usd' => $lbInUsd->value
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
                                'Error al obtener el valor LB en USD',
                                null
                            );
                        }
                    }
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        412,
                        'La solicitud no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar solicitudes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al completar la solicitud [!]',
                null
            );
        }
        
        return $response;
    }

    public function complete(Request $request, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('solicitudes complete'))
            {
                $validatorInput = $request->only(
                    'partes'
                );
                
                $validatorRules = [
                    'partes' => 'required|array|min:1',
                    'partes.*.nparte'  => 'required',
                    'partes.*.descripcion'  => 'nullable',
                    'partes.*.cantidad'  => 'required|numeric|min:1',
                    'partes.*.costo'  => 'nullable|numeric|min:0',
                    'partes.*.margen'  => 'nullable|numeric|min:0',
                    'partes.*.tiempoentrega'  => 'nullable|numeric|min:0',
                    'partes.*.peso'  => 'nullable|numeric|min:0',
                    'partes.*.flete'  => 'nullable|numeric|min:0',
                    'partes.*.monto'  => 'nullable|numeric|min:0',
                    'partes.*.backorder'  => 'required|boolean',
                ];
        
                $validatorMessages = [
                    'partes.required' => 'Debes seleccionar las partes',
                    'partes.array' => 'Lista de partes invalida',
                    'partes.min' => 'La solicitud debe contener al menos 1 parte',
                    'partes.*.nparte.required' => 'La lista de partes es invalida',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte debe ser mayor a 0',
                    'partes.*.costo.numeric' => 'El costo para la parte debe ser numerico',
                    'partes.*.costo.min' => 'El costo para la parte debe ser mayor o igual a 0',
                    'partes.*.margen.numeric' => 'El margen para la parte debe ser numerico',
                    'partes.*.margen.min' => 'El margen para la parte debe ser mayor o igual a 0',
                    'partes.*.tiempoentrega.numeric' => 'El tiempo de entrega para la parte debe ser numerico',
                    'partes.*.tiempoentrega.min' => 'El tiempo de entrega para la parte debe ser mayor o igual a 0',
                    'partes.*.peso.numeric' => 'El peso para la parte debe ser numerico',
                    'partes.*.peso.min' => 'El peso para la parte debe ser mayor a 0',
                    'partes.*.flete.numeric' => 'El flete para la parte debe ser numerico',
                    'partes.*.flete.min' => 'El flete para la parte debe ser mayor o igual a 0',
                    'partes.*.monto.numeric' => 'El monto para la parte debe ser numerico',
                    'partes.*.monto.min' => 'El monto para la parte debe ser mayor o igual a 0',
                    'partes.*.backorder.required' => 'Debes seleccionar si la parte es backorder',
                    'partes.*.backorder.boolean' => 'La seleccion de backorder para la parte es invalida',
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
                        // Administrador
                        if(
                            ($user->role->name === 'admin') && 
                            ($solicitud->sucursal->country->id !== $user->stationable->country->id)
                        )
                        {
                            //If Administrator and solicitud doesn't belong to its country
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a completar esta solicitud',
                                null
                            );
                        }
                        // Agente de compra
                        else if(
                            ($user->role->name === 'agtcom') &&
                            ($solicitud->comprador->id !== $user->stationable->id)
                        )
                        {
                            //If Agente de compra and solicitud isn't to its Comprador
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a completar esta solicitud',
                                null
                            );
                        }
                        else if($solicitud->estadosolicitud_id === 3)
                        {
                            // If solicitud is already 'Cerrada'
                            $response = HelpController::buildResponse(
                                409,
                                'No puedes completar una solicitud cerrada',
                                null
                            );
                        }
                        else if(!($lbInUsd = Parameter::where('name', 'lb_in_usd')->first()))
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al obtener el valor LB en USD',
                                null
                            );
                        }
                        else
                        {
                            $success = true;
            
                            DB::beginTransaction();
    
                            $syncData = [];
                            foreach($request->partes as $parte)
                            {
                                if($p = $solicitud->partes->where('nparte', $parte['nparte'])->first())
                                {
                                    $syncData[$p->id] =  array(
                                        'descripcion' => isset($parte['descripcion']) ? $parte['descripcion'] : null,
                                        'cantidad' => $parte['cantidad'],
                                        'costo' => isset($parte['costo']) ? $parte['costo'] : null,
                                        'margen' => isset($parte['margen']) ? $parte['margen'] : null,
                                        'tiempoentrega' => isset($parte['tiempoentrega']) ? $parte['tiempoentrega'] : null,
                                        'peso' => isset($parte['peso']) ? $parte['peso'] : null,
                                        'flete' => isset($parte['flete']) ? $parte['flete'] : null,
                                        'monto' => isset($parte['monto']) ? $parte['monto'] : null,
                                        'backorder' => $parte['backorder'],
                                    );
                                }
                                else
                                {
                                    $success = false;
    
                                    $response = HelpController::buildResponse(
                                        409,
                                        'La parte N:' . $parte['nparte'] . ' no existe en la solicitud seleccionada',
                                        null
                                    );
    
                                    break;
                                }
                            }
                            
                            $solicitud->partes()->sync($syncData);
    
                            $completed = true;
                            foreach($syncData as $parte)
                            {
                                if(
                                    ($parte['costo'] === null) || 
                                    ($parte['margen'] === null) || 
                                    ($parte['tiempoentrega'] === null) || 
                                    ($parte['peso'] === null) || 
                                    ($parte['flete'] === null) || 
                                    ($parte['monto'] === null)
                                )
                                {
                                    $completed = false;
                                    
                                    break;
                                }
                            }
    
                            if($completed === true)
                            {
                                $solicitud->estadosolicitud_id = 2; // Completa
                            }
                            else
                            {
                                $solicitud->estadosolicitud_id = 1; // Pendiente
                            }
    
                            $solicitud->save();
    
                            if($success === true)
                            {
                                DB::commit();
                                
                                // Reload Solicitud
                                $solicitud = Solicitud::find($solicitud->id);
                                
                                $solicitud->makeHidden([
                                    'sucursal_id',
                                    'comprador_id',
                                    'user_id',
                                    'faena_id',
                                    'marca_id',
                                    'estadosolicitud_id',
                                    'created_at', 
                                    'updated_at'
                                ]);
            
                                $solicitud->partes_total;
                                        
                                $solicitud->sucursal;
                                $solicitud->sucursal->makeHidden([
                                    'type',
                                    'rut',
                                    'address',
                                    'city',
                                    'country',
                                    'country_id',
                                    'created_at', 
                                    'updated_at'
                                ]);
                                
                                $solicitud->faena;
                                $solicitud->faena->makeHidden([
                                    'sucursal_id',
                                    'rut',
                                    'address',
                                    'city',
                                    'contact',
                                    'phone',
                                    'cliente_id', 
                                    'created_at', 
                                    'updated_at'
                                ]);
                                
                                $solicitud->faena->cliente;
                                $solicitud->faena->cliente->makeHidden(['country_id', 'created_at', 'updated_at']);
                                
                                $solicitud->marca;
                                $solicitud->marca->makeHidden(['created_at', 'updated_at']);
                                
                                $solicitud->comprador;
                                $solicitud->comprador->makeHidden([
                                    'rut',
                                    'address',
                                    'city',
                                    'contact',
                                    'phone',
                                    'country_id', 
                                    'created_at', 
                                    'updated_at'
                                ]);
                                
                                $solicitud->user;
                                $solicitud->user->makeHidden([
                                    'stationable_type',
                                    'stationable_id',
                                    'email', 
                                    'phone', 
                                    'country_id', 
                                    'role_id', 
                                    'email_verified_at', 
                                    'created_at', 
                                    'updated_at'
                                ]);
                                
                                $solicitud->estadosolicitud;
                                $solicitud->estadosolicitud->makeHidden(['created_at', 'updated_at']);
            
                                $solicitud->partes;
                                foreach($solicitud->partes as $parte)
                                {
                                    $parte->makeHidden([
                                        'marca_id', 
                                        'created_at', 
                                        'updated_at'
                                    ]);
            
                                    $parte->marca;
                                    $parte->marca->makeHidden(['created_at', 'updated_at']);
            
                                    $parte->pivot->makeHidden([
                                        'solicitud_id',
                                        'parte_id',
                                        'marca_id', 
                                        'created_at', 
                                        'updated_at'
                                    ]);
                                }
    
                                $data = [
                                    'solicitud' => $solicitud,
                                    'lb_in_usd' => $lbInUsd->value
                                ];
    
                                $response = HelpController::buildResponse(
                                    200,
                                    'Solicitud actualizada',
                                    $data
                                );
                            }
                            else
                            {
                                DB::rollback();
                            }
                        }
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            412,
                            'La solicitud no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a actualizar solicitudes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al completar la solicitud [!]',
                null
            );
        }

        return $response;
    }

    public function close($id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('solicitudes close'))
            {
                if($solicitud = Solicitud::find($id))
                {    
                    // Administrador
                    if(
                        ($user->role->name === 'admin') && 
                        ($solicitud->sucursal->country->id !== $user->stationable->country->id)
                    )
                    {
                        //If Administrator and solicitud doesn't belong to its country
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a cerrar esta solicitud',
                            null
                        );
                    }
                    // Vendedor
                    else if(
                        ($user->role->name === 'seller') &&
                        (
                            ($solicitud->sucursal->id !== $user->stationable->id) || 
                            ($solicitud->user->id !== $user->id)
                        )
                    )
                    {
                        //If Vendedor and solicitud doesn't belong or isn't to its Sucursal
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a cerrar esta solicitud',
                            null
                        );
                    }
                    else if($solicitud->estadosolicitud_id === 3) // Cerrada
                    {
                        // If solicitud is already 'Cerrada'
                        $response = HelpController::buildResponse(
                            409,
                            'La solicitud ya esta cerrada',
                            null
                        );
                    }
                    else
                    {
                        if($solicitud->estadosolicitud_id === 2) // If Estadosolicitud = 'Completa'
                        {
                            if($usdToClpParam = Parameter::where('name', 'usd_to_clp')->first())
                            {
                                DB::beginTransaction();

                                $solicitud->estadosolicitud_id = 3; // Cerrada
                                if($solicitud->save())
                                {
                                    $cotizacion = new Cotizacion();
                                    $cotizacion->solicitud_id = $solicitud->id;
                                    $cotizacion->estadocotizacion_id = 1; //Initial Estadocotizacion
                                    $cotizacion->usdvalue = $usdToClpParam->value;
                                    $cotizacion->lastupdate = new DateTime();

                                    if($cotizacion->save())
                                    {
                                        //Attaching each Parte to the Cotizacion
                                        $syncData = [];
                                        foreach($solicitud->partes as $parte)
                                        {
                                            $syncData[$parte->id] =  array(
                                                'descripcion' => $parte->pivot->descripcion,
                                                'cantidad' => $parte->pivot->cantidad,
                                                'costo' => $parte->pivot->costo,
                                                'margen' => $parte->pivot->margen,
                                                'tiempoentrega' => $parte->pivot->tiempoentrega,
                                                'peso' => $parte->pivot->peso,
                                                'flete' => $parte->pivot->flete,
                                                'monto' => $parte->pivot->monto,
                                                'backorder' => $parte->pivot->backorder,
                                            );
                                        }
                        
                                        if($cotizacion->partes()->sync($syncData))
                                        {
                                            DB::commit();

                                            $response = HelpController::buildResponse(
                                                201,
                                                'Solicitud cerrada',
                                                null
                                            );
                                        }
                                        else
                                        {
                                            DB::rollback();

                                            $response = HelpController::buildResponse(
                                                500,
                                                'Error al crear la cotizacion',
                                                null
                                            );
                                        }
                                        
                                    }
                                    else
                                    {
                                        DB::rollback();

                                        $response = HelpController::buildResponse(
                                            500,
                                            'Error al crear la cotizacion',
                                            null
                                        );
                                    }
                                    
                                }
                                else
                                {
                                    DB::rollback();

                                    $response = HelpController::buildResponse(
                                        500,
                                        'Error al cerrar la solicitud',
                                        null
                                    );
                                }
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al obtener el valor USD para conversion',
                                    null
                                );
                            }
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                409,
                                'La solicitud no esta completa',
                                null
                            );
                        }
                    }
                }
                else
                {
                    $response = HelpController::buildResponse(
                        412,
                        'La solicitud no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a cerrar solicitudes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al cerrar la solicitud [!]',
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
            if($user->role->hasRoutepermission('solicitudes destroy'))
            {
                if($solicitud = Solicitud::find($id))
                {    
                    // Administrador
                    if(
                        ($user->role->name === 'admin') && 
                        ($solicitud->sucursal->country->id !== $user->stationable->country->id)
                    )
                    {
                        //If Administrator and solicitud doesn't belong to its country
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a eliminar esta solicitud',
                            null
                        );
                    }
                    // Vendedor
                    else if(
                        ($user->role->name === 'seller') &&
                        (
                            ($solicitud->sucursal->id !== $user->stationable->id) || 
                            ($solicitud->user->id !== $user->id)
                        )
                    )
                    {
                        //If Vendedor and solicitud doesn't belong or isn't to its Sucursal
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a eliminar esta solicitud',
                            null
                        );
                    }
                    else if($solicitud->estadosolicitud_id === 3) // Cerrada
                    {
                        // If solicitud is already 'Cerrada'
                        $response = HelpController::buildResponse(
                            409,
                            'No puedes eliminar una solicitud cerrada',
                            null
                        );
                    }
                    else
                    {
                        if($solicitud->delete())
                        {
                            $response = HelpController::buildResponse(
                                200,
                                'Solicitud eliminada',
                                null
                            );
                        }
                        else
                        {
                            $response = HelpController::buildResponse(
                                500,
                                'Error al eliminar la solicitud',
                                null
                            );
                        }
                    }
                }
                else
                {
                    $response = HelpController::buildResponse(
                        412,
                        'La solicitud no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a eliminar solicitudes',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al eliminar la solicitud [!]',
                null
            );
        }

        return $response;
    }

}
