<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use Auth;

class RolesController extends Controller
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
        if($user->role->hasRoutepermission('roles indexfull'))
        {
            if($roles = Role::all())
            {
                $roles = $roles->filter(function($role)
                {
                    $role->makeHidden([
                        'created_at',
                        'updated_at'
                    ]);

                    return $role;
                });

                $response = HelpController::buildResponse(
                    200,
                    null,
                    $roles
                );
            }
            else
            {
                $response = HelpController::buildResponse(
                    500,
                    'Error al obtener la lista de roles',
                    null
                );
            }
        }
        else
        {
            $response = HelpController::buildResponse(
                405,
                'No tienes acceso a listar roles',
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
