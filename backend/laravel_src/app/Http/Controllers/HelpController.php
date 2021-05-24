<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelpController extends Controller
{

    /*
        RESPONSE API RESTful HTTP status codes:
            
            200 OK
            201 Created
            400 Bad Request
            401 Unauthorized
            403 Forbidden
            404 Not Found
            405 Method Not Allowed
            409 Conflict
            411 Length Required
            412 Precondition Failed (Object not found)
            422 Invalid parameters
            429 Too Many Requests
            500 Internal Server Error
            503 Service Unavailable

    */
    public static function buildResponse($code, $message, $data)
    {
        $contents = [
            'message' => $message,
            'data' => $data
        ];

        $response = response()->json($contents, $code);

        return $response;
    }

    public function getUsers()
    {
        return App\User::all();
    }
}