<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;

use App\Models\Loggedaction;


class LoggedactionsController extends Controller
{
    public function index()
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('loggedactions index'))
            {
                if($loggedactions = Loggedaction::all())
                {                    
                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $loggedactions
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de acciones',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar acciones',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de acciones [!]',
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
    public static function log($loggeable, $description)
    {
        try
        {
            $user = Auth::user();
            if($user->role->hasRoutepermission('loggedactions store'))
            {
                $loggedaction = new Loggedaction();
                $loggedaction->user_id = $user->id;
                $loggedaction->loggeable_type = get_class($loggeable);
                $loggedaction->loggeable_id = $loggeable->id;
                $loggedaction->description = $description;
                
                if($loggedaction->save())
                {
                    $response = HelpController::buildResponse(
                        201,
                        'Accion registrada',
                        null
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al crear el cliente',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a registrar acciones',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al registrar la accion [!]',
                null
            );
        }

        return $response;
    }
}
