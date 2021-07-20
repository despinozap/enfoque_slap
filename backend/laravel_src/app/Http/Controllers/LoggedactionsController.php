<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;

use App\Models\Loggedaction;


class LoggedactionsController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public static function log($loggeable, $action, $arrData)
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
                $loggedaction->action = $action;
                $loggedaction->data = ($arrData !== null) ? json_encode($arrData) : null;

                $response = ($loggedaction->save()) ? true : false;
            }
            else
            {
                $response = false;
            }
        }
        catch(\Exception $e)
        {
            $response = false;
        }

        return $response;
    }
}
