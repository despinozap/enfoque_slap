<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

use App\Models\Sucursal;

class SucursalesController extends Controller
{

    public function index_centrodistribucion($country_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('centrosdistribucion index'))
            {
                $centrosdistribucion = Sucursal::where('type', '=', 'centro')->where('country_id', '=', $country_id)->get();
                if($centrosdistribucion !== null)
                {
                    $centrosdistribucion = $centrosdistribucion->filter(function($centrodistribucion)
                    {
                        $centrodistribucion->makeHidden([
                            'type',
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);

                        $centrodistribucion->country;
                        $centrodistribucion->country->makeHidden(['created_at', 'updated_at']);

                        return $centrodistribucion;
                    });
                    
                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $centrosdistribucion
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de centros de distribucion',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar centros de distribucion',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de centros de distribucion [!]',
                null
            );
        }
        
        return $response;
    }
    
    public function index_sucursal($country_id)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('sucursales index'))
            {
                $sucursales = Sucursal::where('type', '=', 'sucursal')->where('country_id', '=', $country_id)->get();
                if($sucursales !== null)
                {
                    $sucursales = $sucursales->filter(function($sucursal)
                    {
                        $sucursal->makeHidden([
                            'type',
                            'country_id',
                            'created_at', 
                            'updated_at'
                        ]);

                        $sucursal->country;
                        $sucursal->country->makeHidden(['created_at', 'updated_at']);

                        return $sucursal;
                    });
                    
                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $sucursales
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de sucursales',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar sucursales',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de sucursales [!]',
                null
            );
        }
        
        return $response;
    }

}
