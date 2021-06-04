<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Sucursal;
use App\Models\Oc;
use App\Models\OcParte;
use App\Models\Faena;
use App\Models\Entrega;

class EntregasController extends Controller
{
    /*
     *  Sucursal (centro) CHECK
     */
    public function index_centrodistribucion($id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('centrosdistribucion entregas_index'))
            {
                if($centrodistribucion = Sucursal::where('id', $id)->where('type', 'centro')->first())
                {
                    $centrodistribucion->makeHidden([
                        'created_at', 
                        'updated_at'
                    ]);

                    $centrodistribucion->entregas;
                    $centrodistribucion->entregas = $centrodistribucion->entregas->filter(function($entrega)
                    {
                        $entrega->partes_total;
                        
                        $entrega->makeHidden([
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->ocpartes;
                        // $entrega->ocpartes = $entrega->partes->filter(function($parte)
                        // {
                        //     $parte->makeHidden([
                        //         'marca_id',
                        //         'created_at',
                        //         'updated_at',
                        //     ]);

                        //     $parte->pivot->makeHidden([
                        //         'parte_id',
                        //         'despacho_id',
                        //         'created_at',
                        //         'updated_at',
                        //     ]);

                        //     $parte->marca;
                        //     $parte->marca->makeHidden(['created_at', 'updated_at']);

                        //     return $parte;
                        // });

                        $entrega->faena;
                        $entrega->faena->makeHidden([
                            // 'type',
                            // 'rut',
                            // 'address',
                            // 'city',
                            // 'country_id',
                            // 'created_at', 
                            // 'updated_at'
                        ]);

                        return $entrega;
                    });

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $centrodistribucion->entregas
                    );
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El centro de distribucion no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar entregas de centro de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener las entregas del centro de distribucion [!]',
                null
            );
        }
            
        return $response;
    }


    public function store_prepare_centrodistribucion($centrodistribucion_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('centrosdistribucion entregas_store'))
            {
                if($centrodistribucion = Sucursal::where('id', $centrodistribucion_id)->where('type', 'centro')->first())
                {
                    // Ocs with Partes pending for deliver GET OCPARTE
                    {
                        // Get all the OcPartes in Recepciones for Sucursal (centro)
                        $ocParteList = OcParte::select('oc_parte.*')
                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                ->join('parte_recepcion', 'parte_recepcion.parte_id', '=', 'oc_parte.parte_id')
                                ->join('recepciones', 'recepciones.id', '=', 'parte_recepcion.recepcion_id')
                                ->join('sucursales', 'sucursales.id', '=', 'recepciones.recepcionable_id')
                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                ->where('recepciones.recepcionable_type', '=', get_class($centrodistribucion))
                                ->where('solicitudes.sucursal_id', '=', $centrodistribucion->id) // For this Sucursal (centro)
                                ->where('sucursales.type', '=', 'centro')
                                ->where('ocs.estadooc_id', 2) // Only Oc with estaodooc = 'En proceso'
                                ->get();
    
                        // Retrieves the partes list with cantidad_stock for dispatching
                        $ocList = $ocParteList->reduce(function($carry, $ocParte) use ($centrodistribucion)
                            {
                                // If the Oc (id) isn't in the list already, check it
                                if(in_array($ocParte->oc->id, array_keys($carry)) === false)
                                {
                                    // If has delivered less cantidad than total, add the Oc to the list
                                    if($ocParte->getCantidadEntregado() < $ocParte->cantidad)
                                    {
                                        $carry[$ocParte->oc->id] = $ocParte->oc;
                                    }
                                }
                                
                                return $carry;

                            },
                            array()
                        );
                    }

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $ocList
                    );
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        412,
                        'El centro de distribucion no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar partes disponibles para despachar',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener partes disponibles para despachar [!]',
                null
            );
        }
            
        return $response;
    }


    /*
     *  Sucursal
     */
    public function index_sucursal($id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales entregas_index'))
            {
                if($sucursal = Sucursal::where('id', $id)->where('type', 'sucursal')->first())
                {
                    $sucursal->makeHidden([
                        'created_at', 
                        'updated_at'
                    ]);

                    $sucursal->entregas;
                    $sucursal->entregas = $sucursal->entregas->filter(function($entrega)
                    {
                        $entrega->partes_total;
                        
                        $entrega->makeHidden([
                            'sucursal_id',
                            'faena_id',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $entrega->ocpartes;
                        $entrega->ocpartes = $entrega->ocpartes->filter(function($ocparte)
                        {
                            $ocparte->makeHidden([
                                'oc_id',
                                'parte_id',
                                'estadoocparte_id',
                                'tiempoentrega',
                                'created_at',
                                'updated_at'
                            ]);

                            $ocparte->pivot->makeHidden([
                                'entrega_id',
                                'ocparte_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $ocparte->parte;
                            $ocparte->parte->makeHidden([
                                'marca_id',
                                'created_at',
                                'updated_at',
                            ]);

                            $ocparte->parte->marca;
                            $ocparte->parte->marca->makeHidden(['created_at', 'updated_at']);

                            return $ocparte;
                        });

                        $entrega->faena;
                        $entrega->faena->makeHidden([
                            'rut',
                            'address',
                            'city',
                            'contact',
                            'phone',
                            'cliente_id',
                            'created_at', 
                            'updated_at'
                        ]);

                        return $entrega;
                    });

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $sucursal->entregas
                    );
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        412,
                        'La sucursal no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar entregas de sucursal',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener las entregas de la sucursal [!]',
                null
            );
        }
            
        return $response;
    }


    public function store_prepare_sucursal($sucursal_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales entregas_store'))
            {
                if($sucursal = Sucursal::where('id', $sucursal_id)->where('type', 'sucursal')->first())
                {
                    // Ocs with Partes pending for deliver GET OCPARTE
                    {
                        // Get all the OcPartes in Recepciones for Sucursal
                        $ocParteList = OcParte::select('oc_parte.*')
                                ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                ->join('parte_recepcion', 'parte_recepcion.parte_id', '=', 'oc_parte.parte_id')
                                ->join('recepciones', 'recepciones.id', '=', 'parte_recepcion.recepcion_id')
                                ->join('sucursales', 'sucursales.id', '=', 'recepciones.recepcionable_id')
                                ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                ->where('recepciones.recepcionable_type', '=', get_class($sucursal))
                                ->where('solicitudes.sucursal_id', '=', $sucursal->id) // For this Sucursal
                                ->where('sucursales.type', '=', 'centro')
                                ->where('ocs.estadooc_id', 2) // Only Oc with estaodooc = 'En proceso'
                                ->get();
    
                        // Retrieves the Oc with pending OcParte for Entrega
                        $ocList = $ocParteList->reduce(function($carry, $ocParte) use ($sucursal)
                            {
                                // If the Oc (id) isn't in the list already, check it
                                if(in_array($ocParte->oc->id, array_keys($carry)) === false)
                                {
                                    // If has delivered less cantidad than total, add the Oc to the list
                                    if($ocParte->getCantidadEntregado() < $ocParte->cantidad)
                                    {
                                        $ocParte->oc->makeHidden([
                                            'cotizacion_id',
                                            'proveedor_id',
                                            'filedata_id',
                                            'motivobaja_id',
                                            'partes',
                                            'estadooc_id', 
                                            'created_at', 
                                            //'updated_at'
                                        ]);

                                        $ocParte->oc->cotizacion;
                                        $ocParte->oc->cotizacion->makeHidden([
                                            'solicitud_id',
                                            'motivorechazo_id',
                                            'estadocotizacion_id',
                                            'usdvalue',
                                            'partes_total',
                                            'dias',
                                            'created_at', 
                                            'updated_at',
                                            'partes',
                                        ]);
                                        
                                        $ocParte->oc->cotizacion->solicitud;
                                        $ocParte->oc->cotizacion->solicitud->makeHidden([
                                            'partes_total',
                                            'comentario',
                                            'sucursal_id',
                                            'comprador_id',
                                            'user_id',
                                            'faena_id',
                                            'marca_id',
                                            'estadosolicitud_id',
                                            'created_at', 
                                            'updated_at'
                                        ]);
                                                    
                                        $ocParte->oc->cotizacion->solicitud->sucursal;
                                        $ocParte->oc->cotizacion->solicitud->sucursal->makeHidden([
                                            'type',
                                            'rut',
                                            'address',
                                            'city',
                                            'country_id',
                                            'created_at', 
                                            'updated_at'
                                        ]);
                                        
                                        $ocParte->oc->cotizacion->solicitud->faena;
                                        $ocParte->oc->cotizacion->solicitud->faena->makeHidden([
                                            'rut',
                                            'address',
                                            'city',
                                            'contact',
                                            'phone',
                                            'cliente_id', 
                                            'created_at', 
                                            'updated_at'
                                        ]);

                                        $ocParte->oc->cotizacion->solicitud->faena->cliente;
                                        $ocParte->oc->cotizacion->solicitud->faena->cliente->makeHidden([
                                            'country_id',
                                            'created_at', 
                                            'updated_at'
                                        ]);
                                        
                                        $ocParte->oc->cotizacion->solicitud->marca;
                                        $ocParte->oc->cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);
                                        
                                        $ocParte->oc->cotizacion->solicitud->user;
                                        $ocParte->oc->cotizacion->solicitud->user->makeHidden(['email', 'phone', 'country_id', 'role_id', 'email_verified_at', 'created_at', 'updated_at']);
                                        
                                        $ocParte->oc->estadooc;
                                        $ocParte->oc->estadooc->makeHidden(['created_at', 'updated_at']);

                                        $carry[$ocParte->oc->id] = $ocParte->oc;
                                    }
                                }
                                
                                return $carry;
                            },
                            array()
                        );
                    }

                    $ocs = array();
                    foreach(array_keys($ocList) as $ocId)
                    {
                        array_push($ocs, $ocList[$ocId]);
                    }

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $ocs
                    );
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        412,
                        'La sucursal no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar partes disponibles para entregar',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener partes disponibles para entregar [!]',
                null
            );
        }
            
        return $response;
    }


    public function store_prepare_oc_sucursal($sucursal_id, $oc_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales entregas_store'))
            {
                if($sucursal = Sucursal::where('id', $sucursal_id)->where('type', 'sucursal')->first())
                {
                    if($oc = Oc::select('ocs.*')
                            ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                            ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                            ->where('solicitudes.sucursal_id', '=', $sucursal->id) // Oc belongs to the Sucursal
                            ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                            ->where('ocs.id', '=', $oc_id)->first() // Filter the requested Oc
                    )
                    {
                        $oc->makeHidden([
                            'cotizacion_id',
                            'proveedor_id',
                            'filedata_id',
                            'motivobaja_id',
                            'partes',
                            'estadooc_id', 
                            'created_at', 
                            //'updated_at'
                        ]);

                        $oc->cotizacion;
                        $oc->cotizacion->makeHidden([
                            'solicitud_id',
                            'motivorechazo_id',
                            'estadocotizacion_id',
                            'usdvalue',
                            'partes_total',
                            'dias',
                            'created_at', 
                            'updated_at',
                            'partes',
                        ]);
                        
                        $oc->cotizacion->solicitud;
                        $oc->cotizacion->solicitud->makeHidden([
                            'partes_total',
                            'comentario',
                            'sucursal_id',
                            'comprador_id',
                            'user_id',
                            'faena_id',
                            'marca_id',
                            'estadosolicitud_id',
                            'created_at', 
                            'updated_at'
                        ]);
                                    
                        $oc->cotizacion->solicitud->sucursal;
                        $oc->cotizacion->solicitud->sucursal->makeHidden([
                            'type',
                            'rut',
                            'address',
                            'city',
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $oc->cotizacion->solicitud->faena;
                        $oc->cotizacion->solicitud->faena->makeHidden([
                            'rut',
                            'address',
                            'city',
                            'contact',
                            'phone',
                            'cliente_id', 
                            'created_at', 
                            'updated_at'
                        ]);

                        $oc->cotizacion->solicitud->faena->cliente;
                        $oc->cotizacion->solicitud->faena->cliente->makeHidden([
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);
                        
                        $oc->cotizacion->solicitud->marca;
                        $oc->cotizacion->solicitud->marca->makeHidden(['created_at', 'updated_at']);
                        
                        $oc->cotizacion->solicitud->user;
                        $oc->cotizacion->solicitud->user->makeHidden(['email', 'phone', 'country_id', 'role_id', 'email_verified_at', 'created_at', 'updated_at']);
                        
                        $oc->estadooc;
                        $oc->estadooc->makeHidden(['created_at', 'updated_at']);

                        $queuePartes = $oc->partes->reduce(function($carry, $parte) use($sucursal)
                            {
                                // Get the stock cantidad in Sucursal
                                $cantidadStock = $parte->getCantidadRecepcionado($sucursal) - $parte->getCantidadDespachado($sucursal);

                                $parteData = [
                                    "id" => $parte->id,
                                    "nparte" => $parte->nparte,
                                    "marca" => $parte->marca->makeHidden(['created_at', 'updated_at']),
                                    "cantidad_total" => $parte->pivot->cantidad,
                                    "cantidad_entregado" => $parte->pivot->getCantidadEntregado(),
                                    "cantidad_stock" => $cantidadStock
                                ];
                                
                                array_push($carry, $parteData);

                                return $carry;      
                            },
                            array()
                        );

                        $data = [
                            "oc" => $oc,
                            "queue_partes" => $queuePartes 
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
                            412,
                            'La OC no existe en la sucursal',
                            null
                        );
                    }                    
                }   
                else     
                {
                    $response = HelpController::buildResponse(
                        412,
                        'La sucursal no existe',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a visualizar partes disponibles para entregar',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener partes disponibles para entregar [!]' . $e,
                null
            );
        }
            
        return $response;
    }


    public function store_sucursal(Request $request, $sucursal_id, $oc_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales entregas_store'))
            {
                $validatorInput = $request->only('faena_id', 'fecha', 'ndocumento', 'responsable', 'comentario', 'partes');
            
                $validatorRules = [
                    'faena_id' => 'required|exists:faenas,id',
                    'fecha' => 'required|date_format:Y-m-d|before:tomorrow', // it includes today
                    'ndocumento' => 'nullable|min:1',
                    'responsable' => 'required|min:1',
                    'comentario' => 'sometimes|nullable',
                    'partes' => 'required|array|min:1',
                    'partes.*.id'  => 'required|exists:partes,id',
                    'partes.*.cantidad'  => 'required|numeric|min:1',
                ];
        
                $validatorMessages = [
                    'faena_id.required' => 'Debes ingresar la faena',
                    'faena_id.exists' => 'La faena ingresada no existe',
                    'fecha.required' => 'Debes ingresar la fecha de despacho',
                    'fecha.date_format' => 'El formato de fecha de despacho es invalido',
                    'fecha.before' => 'La fecha debe ser igual o anterior a hoy',
                    'ndocumento.min' => 'El numero de documento debe tener al menos un digito',
                    'responsable.required' => 'Debes ingresar el nombre de la persona que despacha',
                    'responsable.min' => 'El nombre de la persona que despacha debe tener al menos un digito',
                    'partes.required' => 'Debes seleccionar las partes despachadas',
                    'partes.array' => 'Lista de partes despachadas invalida',
                    'partes.min' => 'El despacho debe contener al menos 1 parte despachada',
                    'partes.*.id.required' => 'La lista de partes despachadas es invalida',
                    'partes.*.id.exists' => 'La parte despachada ingresada no existe',
                    'partes.*.cantidad.required' => 'Debes ingresar la cantidad para la parte despachada',
                    'partes.*.cantidad.numeric' => 'La cantidad para la parte despachada debe ser numerica',
                    'partes.*.cantidad.min' => 'La cantidad para la parte despachada debe ser mayor a 0',
                    
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
                    if($sucursal = Sucursal::where('id', $sucursal_id)->where('type', 'sucursal')->first())
                    {
                        if($faena = Faena::select('faenas.*')
                                    ->join('clientes', 'clientes.id', '=', 'faenas.cliente_id')
                                    ->where('clientes.country_id', '=', $sucursal->country_id)
                                    ->where('faenas.id', '=', $request->faena_id)
                                    ->first()
                        )
                        {
                            if($oc = Oc::select('ocs.*')
                                    ->join('cotizaciones', 'cotizaciones.id', '=', 'ocs.cotizacion_id')
                                    ->join('solicitudes', 'solicitudes.id', '=', 'cotizaciones.solicitud_id')
                                    ->where('solicitudes.sucursal_id', '=', $sucursal->id) // Oc belongs to the Sucursal
                                    ->where('ocs.estadooc_id', '=', 2) // Oc with estadooc = 'En proceso'
                                    ->where('ocs.id', '=', $oc_id)->first() // Filter the requested Oc
                            )
                            {
                                DB::beginTransaction();
    
                                $entrega = new Entrega();
                                // Fill the data
                                $entrega->sucursal_id = $sucursal->id;
                                $entrega->faena_id = $faena->id;
                                $entrega->fecha = $request->fecha;
                                $entrega->ndocumento = $request->ndocumento;
                                $entrega->responsable = $request->responsable;
                                $entrega->comentario = $request->comentario;
    
                                if($entrega->save())
                                {
                                    $success = true;
    
                                    foreach($request->partes as $p)
                                    {
                                        if($parte = $oc->partes->find($p['id']))
                                        {
                                            // Calc cantidad pendiente with cantidad in Oc - cantidad in Entregas
                                            $cantidadPendiente = $parte->pivot->cantidad - $parte->pivot->getCantidadEntregado();
                                            if($cantidadPendiente > 0)
                                            {
                                                if($p['cantidad'] <= $cantidadPendiente)
                                                {
                                                    $cantidadStock = $parte->getCantidadRecepcionado($sucursal) - $parte->getCantidadEntregado($sucursal);
                                                    if($cantidadStock > 0)
                                                    {
                                                        if($p['cantidad'] <= $cantidadStock)
                                                        {
                                                            // Add the OcParte to the Entrega
                                                            $entrega->ocpartes()->attach(
                                                                array(
                                                                    $parte->pivot->id => array(
                                                                        "cantidad" => $p['cantidad']
                                                                    )
                                                                )
                                                            );
                                                        }
                                                        else
                                                        {
                                                            // If the entered cantidad for parte is more than in stock
                                                            $response = HelpController::buildResponse(
                                                                409,
                                                                'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor al stock disponible en la sucursal',
                                                                null
                                                            );

                                                            $success = false;
                    
                                                            break;
                                                        }
                                                    }
                                                    else
                                                    {
                                                        // If the entered parte has no stock in Sucursal
                                                        $response = HelpController::buildResponse(
                                                            409,
                                                            'La parte "' . $parte->nparte . '" no tiene stock disponible en la sucursal',
                                                            null
                                                        );

                                                        $success = false;
                
                                                        break;
                                                    }
                                                }
                                                else
                                                {
                                                    // If the received parts are more than waiting in queue
                                                    $response = HelpController::buildResponse(
                                                        409,
                                                        'La cantidad ingresada para la parte "' . $parte->nparte . '" es mayor a la cantidad de pendiente de entrega',
                                                        null
                                                    );
                                                }
                                            }
                                            else
                                            {
                                                // If the entered parte isn't in queue
                                                $response = HelpController::buildResponse(
                                                    409,
                                                    'La parte "' . $parte->nparte . '" no tiene partes pendiente de entrega en la OC',
                                                    null
                                                );

                                                $success = false;
        
                                                break;
                                            }
                                        }
                                        else
                                        {
                                            // If the entered parte isn't in the Oc
                                            $response = HelpController::buildResponse(
                                                409,
                                                'Una de las partes ingresadas no existe en la OC',
                                                null
                                            );
        
                                            $success = false;
        
                                            break;
                                        }
                                    }
    
                                    if($success === true)
                                    {
    
                                        DB::commit();
                                            
                                        $response = HelpController::buildResponse(
                                            201,
                                            'Entrega creada',
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
                                        'Error al crear la entrega',
                                        null
                                    );
                                }
                            }
                            else
                            {
                                $response = HelpController::buildResponse(
                                    412,
                                    'La OC no existe en la sucursal',
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
                        $response = HelpController::buildResponse(
                            412,
                            'La sucursal no existe',
                            null
                        );
                    }
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a registrar entregas para sucursal',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al crear la entrega [!]',
                null
            );
        }
        
        return $response;
    }
}
