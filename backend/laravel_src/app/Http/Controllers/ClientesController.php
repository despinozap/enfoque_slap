<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use Auth;

class ClientesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function indexFull()
    {
        $response = null;

        $user = Auth::user();
        if($user->role->hasRoutepermission('clientes index_full'))
        {
            if($clientes = Cliente::all())
            {
                $clientes = $clientes->filter(function($cliente)
                {
                    $cliente->makeHidden([
                        'created_at',
                        'updated_at'
                    ]);

                    return $cliente;
                });

                $response = HelpController::buildResponse(
                    200,
                    null,
                    $clientes
                );
            }
            else
            {
                $response = HelpController::buildResponse(
                    500,
                    'Error al obtener la lista de clientes',
                    null
                );
            }
        }
        else
        {
            $response = HelpController::buildResponse(
                405,
                'No tienes acceso a listar clientes',
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
