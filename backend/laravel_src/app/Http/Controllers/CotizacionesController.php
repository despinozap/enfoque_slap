<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Parameter;
use App\Models\Solicitud;
use App\Models\Parte;
use App\Models\Cotizacion;
use App\Models\Motivorechazo;

class CotizacionesController extends Controller
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
            if($user->role->hasRoutepermission('cotizaciones index'))
            {
                
                if($cotizaciones = ($user->role->id === 2) ? // By role
                    // If Vendedor filters only the belonging data
                    Cotizacion::select('cotizaciones.solicitud_id')->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')->where('solicitudes.user_id', '=', $user->id)->get() :
                    // For any other role
                    Cotizacion::all()
                )
                {
                    foreach($cotizaciones as $cotizacion)
                    {
                        $cotizacion->partes_total;
                        $cotizacion->dias;
                        $cotizacion->monto;

                        $cotizacion->makeHidden([
                            'solicitud_id', 
                            'estadocotizacion_id', 
                            'created_at', 
                            //'updated_at'
                        ]);

                        foreach($cotizacion->partes as $parte)
                        {   
                            $parte->makeHidden(['marca_id', 'created_at', 'updated_at']);
                            
                            $parte->pivot;
                            $parte->pivot->makeHidden(['cotizacion_id', 'parte_id']);

                            $parte->marca;
                            $parte->marca->makeHidden(['created_at', 'updated_at']);
                        }

                        $cotizacion->solicitud;
                        $cotizacion->solicitud->makeHidden(['partes', 'faena_id', 'marca_id', 'user_id', 'estadosolicitud_id', 'marca_id', 'created_at', 'updated_at']);
                        $cotizacion->solicitud->faena;
                        $cotizacion->solicitud->faena->makeHidden(['cliente_id', 'created_at', 'updated_at']);
                        $cotizacion->solicitud->faena->cliente;
                        $cotizacion->solicitud->faena->cliente->makeHidden(['created_at', 'updated_at']);
                        $cotizacion->solicitud->marca;
                        $cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);
                        $cotizacion->solicitud->user;
                        $cotizacion->solicitud->user->makeHidden(['email', 'phone', 'role_id', 'email_verified_at', 'created_at', 'updated_at']);

                        $cotizacion->estadocotizacion;
                        $cotizacion->estadocotizacion->makeHidden(['created_at', 'updated_at']);
                    }

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $cotizaciones
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de cotizaciones',
                        null
                    );
                }

            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar cotizaciones',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de cotizaciones [!]',
                null
            );
        }

        return $response;
    }

    public function indexMotivosRechazoFull()
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('cotizaciones show'))
            {
                if($motivosRechazo = Motivorechazo::all())
                {
                    foreach($motivosRechazo as $motivoRechazo)
                    {
                        $motivoRechazo->makeHidden([ 
                            'created_at', 
                            'updated_at'
                        ]);
                    }

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $motivosRechazo
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de motivos de rechazo',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar motivos de rechazo',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de motivos de rechazo [!]',
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
            if($user->role->hasRoutepermission('cotizaciones show'))
            {
                if($cotizacion = Cotizacion::find($id))
                {
                    if(($user->role_id === 2) && ($cotizacion->solicitud->user_id !== $user->id))
                    {
                        //If Vendedor and cotizacion doesn't belong
                        $response = HelpController::buildResponse(
                            405,
                            'No tienes acceso a visualizar esta cotizacion',
                            null
                        );
                    }
                    else
                    {
                        $cotizacion->dias;
                        $cotizacion->makeHidden([
                            'solicitud_id',
                            'estadocotizacion_id',
                            'created_at',
                        ]);

                        $cotizacion->solicitud->makeHidden([
                            'partes',
                            'faena_id',
                            'marca_id',
                            'user_id',
                            'estadosolicitud_id',
                            'created_at', 
                            'updated_at'
                        ]);
    
                        $cotizacion->solicitud->faena;
                        $cotizacion->solicitud->faena->makeHidden(['cliente_id', 'created_at', 'updated_at']);
    
                        $cotizacion->solicitud->faena->cliente;
                        $cotizacion->solicitud->faena->cliente->makeHidden(['created_at', 'updated_at']);
                        
                        $cotizacion->solicitud->marca;
                        $cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);
    
                        $cotizacion->estadocotizacion;
                        $cotizacion->estadocotizacion->makeHidden(['created_at', 'updated_at']);

                        $cotizacion->motivorechazo;
                        if($cotizacion->motivorechazo !== null)
                        {
                            $cotizacion->motivorechazo->makeHidden(['created_at', 'updated_at']);
                        }
    
                        $cotizacion->partes;
                        foreach($cotizacion->partes as $parte)
                        {
                            $parte->makeHidden([
                                'marca_id', 
                                'created_at', 
                                'updated_at'
                            ]);
    
                            $parte->marca;
                            $parte->marca->makeHidden(['created_at', 'updated_at']);
    
                            switch($user->role_id)
                            {
                                
                                case 1: { // Administrador
    
                                    $parte->pivot->makeHidden([
                                        'cotizacion_id',
                                        'parte_id',
                                        'marca_id', 
                                        'created_at', 
                                        'updated_at'
                                    ]);
    
                                    break;
                                }
                                
                                case 2: { // Vendedor
    
                                    if($parte->pivot->monto !== null)
                                    {
                                        $parte->pivot->monto = $parte->pivot->monto * $cotizacion->usdvalue;
                                    }
                                    
                                    $parte->pivot->makeHidden([
                                        //'costo',
                                        //'margen',
                                        //'peso',
                                        //'flete',
                                        'cotizacion_id',
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
                            $cotizacion
                        );
                    }
                    
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        400,
                        'La cotizacion no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar cotizaciones',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la cotizacion [!]',
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
        //
    }

    public function approve(Request $request, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('cotizaciones approve'))
            {
                $validatorInput = $request->only(
                    'occliente'
                );
                
                $validatorRules = [
                    'occliente' => 'required|min:1',
                ];
        
                $validatorMessages = [
                    'occliente.required' => 'Debes ingresar el numero de OC cliente',
                    'occliente.min' => 'El numero de OC cliente debe tener al menos un digito',
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
                    if($cotizacion = Cotizacion::find($id))
                    {
                        if(($user->role_id === 2) && ($cotizacion->solicitud->user_id !== $user->id))
                        {
                            //If Vendedor and solicitud doesn't belong
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a aprobar esta cotizacion',
                                null
                            );
                        }
                        else
                        {
                            $cotizacion->estadocotizacion_id = 3; // Aprobada
                            //SAVE FILE HERE
                            
                            if($cotizacion->save())
                            {
                                $response = HelpController::buildResponse(
                                    200,
                                    'Cotizacion aprobada',
                                    null
                                );
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al aprobar la cotizacion',
                                    null
                                );   
                            }
                        }
                        
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            400,
                            'La cotizacion no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a aprobar cotizaciones',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al aprobar la cotizacion [!]',
                null
            );
        }
        
        return $response;
    }

    public function reject(Request $request, $id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('cotizaciones reject'))
            {
                $validatorInput = $request->only(
                    'motivorechazo_id'
                );
                
                $validatorRules = [
                    'motivorechazo_id' => 'required|exists:motivosrechazo,id',
                ];
        
                $validatorMessages = [
                    'motivorechazo_id.required' => 'Debes seleccionar el motivo de rechazo',
                    'motivorechazo_id.exists' => 'El motivo de rechazo no existe',
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
                    if($cotizacion = Cotizacion::find($id))
                    {
                        if(($user->role_id === 2) && ($cotizacion->solicitud->user_id !== $user->id))
                        {
                            //If Vendedor and solicitud doesn't belong
                            $response = HelpController::buildResponse(
                                405,
                                'No tienes acceso a rechazar esta cotizacion',
                                null
                            );
                        }
                        else
                        {
                            $cotizacion->estadocotizacion_id = 4; // Rechazada
                            $cotizacion->motivorechazo_id = $request->motivorechazo_id;
                            
                            if($cotizacion->save())
                            {
                                $response = HelpController::buildResponse(
                                    200,
                                    'Cotizacion rechazada',
                                    null
                                );
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    500,
                                    'Error al rechazar la cotizacion',
                                    null
                                );   
                            }
                        }
                        
                    }
                    else
                    {
                        $response = HelpController::buildResponse(
                            400,
                            'La cotizacion no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a rechazar cotizaciones',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al rechazar la cotizacion [!]',
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
