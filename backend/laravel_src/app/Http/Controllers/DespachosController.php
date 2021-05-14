<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Comprador;
use App\Models\OcParte;

class DespachosController extends Controller
{

    public function queuePartes_comprador($id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('compradores despachos_store'))
            {
                if($comprador = Comprador::find($id))
                {
                        
                    $ocParteList = OcParte::select('oc_parte.*')
                                    ->join('ocs', 'ocs.id', '=', 'oc_parte.oc_id')
                                    ->where('ocs.estadooc_id', '=', 2) // Estadooc = 'En proceso'
                                    ->get();


                    // Retrieves the partes list with cantidad_stock for dispatching
                    $queuePartesData = $ocParteList->reduce(function($carry, $ocParte)
                        {
                            // Get how many partes have been received but not dispatched yet in Comprador
                            $cantidad_stock = $ocParte->cantidad_compradorrecepcionado - $ocParte->cantidad_compradordespachado;
                            if($cantidad_stock > 0)
                            {
                                if(isset($carry[$ocParte->parte->id]))
                                {
                                    // If parte is already in the list, adds the cantidad_pendiente to the total
                                    $carry[$ocParte->parte->id]['cantidad_stock'] += $cantidad_stock;
                                }
                                else
                                {
                                    // If parte is not in the list, inserts the parte to the list
                                    $parte = [
                                        "id" => $ocParte->parte->id,
                                        "nparte" => $ocParte->parte->nparte,
                                        "marca" => $ocParte->parte->marca->makeHidden(['created_at', 'updated_at']),
                                        "cantidad_stock" => $cantidad_stock,
                                    ];

                                    $carry[$parte['id']] = $parte;
                                }
                                
                            }

                            return $carry;
                        },
                        array()
                    );

                    // Transform the queuePartesData key-value array into a list
                    $queuePartes = array();
                    foreach(array_keys($queuePartesData) as $key)
                    {
                        array_push($queuePartes, $queuePartesData[$key]);
                    }

                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $queuePartes
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
                    'No tienes acceso a visualizar partes disponibles para despachar',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener partes disponibles para despachar [!]' . $e,
                null
            );
        }
            
        return $response;
    }

}
