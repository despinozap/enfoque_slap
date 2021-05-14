<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

use App\Models\Comprador;

class CompradoresController extends Controller
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
            if($user->role->hasRoutepermission('compradores index'))
            {
                if($compradores = Comprador::all())
                {
                    $compradores = $compradores->filter(function($comprador)
                    {
                        $comprador->makeHidden([
                            'created_at', 
                            'updated_at'
                        ]);

                        $comprador->proveedores;
                        $comprador->proveedores->makeHidden([
                            'comprador_id',
                            'created_at', 
                            'updated_at'
                        ]);

                        return $comprador;
                    });
                    
                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $compradores
                    );
                }
                else
                {
                    $response = HelpController::buildResponse(
                        500,
                        'Error al obtener la lista de compradores',
                        null
                    );
                }
            }
            else
            {
                $response = HelpController::buildResponse(
                    405,
                    'No tienes acceso a listar compradores',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener la lista de compradores [!]',
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
            if($user->role->hasRoutepermission('compradores show'))
            {
                if($comprador = Comprador::find($id))
                {
                    $comprador->makeHidden([
                        'created_at', 
                        'updated_at'
                    ]);

                    $comprador->proveedores;
                    $comprador->proveedores = $comprador->proveedores->filter(function($proveedor)
                    {
                        return $proveedor->makeHidden(['comprador_id', 'created_at', 'updated_at']);
                    });

                    
                    $response = HelpController::buildResponse(
                        200,
                        null,
                        $comprador
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
                    'No tienes acceso a visualizar compradores',
                    null
                );
            }
        }
        catch(\Exception $e)
        {
            $response = HelpController::buildResponse(
                500,
                'Error al obtener el comprador [!]',
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
