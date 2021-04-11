<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Routepermission;
use App\Models\User;
use App\Models\Parameter;
use App\Models\Cliente;
use App\Models\Faena;
use App\Models\Marca;
use App\Models\Parte;
use App\Models\Estadosolicitud;
use App\Models\Solicitud;
use App\Models\Estadocotizacion;
use App\Models\Motivorechazo;
use App\Models\Proveedor;
use App\Models\Estadooc;
use App\Models\Estadoocparte;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        /*
        *   Route permissions
        */
        $routePermissionNames = [
            //Roles
            'roles index_full',
            //Users
            'users index',
            'users store',
            'users show',
            'users update',
            'users destroy',
            //Parameters
            'parameters index',
            'parameters show',
            'parameters update',
            //Clientes
            'clientes index',
            'clientes store',
            'clientes show',
            'clientes update',
            'clientes destroy',
            //Faenas
            'faenas index_full',
            'faenas index',
            'faenas store',
            'faenas show',
            'faenas update',
            'faenas destroy',
            //Marcas
            'marcas index_full',
            //Partes
            'partes index',
            'partes show',
            'partes update',
            'partes destroy',
            //Solicitudes
            'solicitudes index',
            'solicitudes store',
            'solicitudes show',
            'solicitudes update',
            'solicitudes complete',
            'solicitudes close',
            'solicitudes destroy',
            //Cotizaciones
            'cotizaciones index',
            'cotizaciones show',
            'cotizaciones approve',
            'cotizaciones reject',
            'cotizaciones close',
            'cotizaciones destroy',
        ];

        //Add route permissions
        foreach($routePermissionNames as $routePermissionName)
        {
            $routepermission = new Routepermission();
            $routepermission->name = $routePermissionName;
            $routepermission->save();
        }

        /*
        *   Roles
        */

        //Administrador
        {
            $role = new Role();
            $role->name = 'Administrador';
            $role->save();

            //Route permissions to for Role: Administrador
            $routePermissionNames = [
                //Roles
                'roles index_full',
                //Users
                'users index',
                'users store',
                'users show',
                'users update',
                'users destroy',
                //Parameters
                'parameters index',
                'parameters show',
                'parameters update',
                //Partes
                'partes index',
                'partes show',
                'partes update',
                'partes destroy',
                //Clientes
                'clientes index',
                'clientes store',
                'clientes show',
                'clientes update',
                'clientes destroy',
                //Faenas
                'faenas index_full',
                'faenas index',
                'faenas store',
                'faenas show',
                'faenas update',
                'faenas destroy',
                //Marcas
                'marcas index_full',
                //Solicitudes
                'solicitudes index',
                'solicitudes store',
                'solicitudes show',
                'solicitudes update',
                'solicitudes complete',
                'solicitudes destroy',
                //Cotizaciones
                'cotizaciones index',
                'cotizaciones show',
                'cotizaciones approve',
                'cotizaciones reject',
                'cotizaciones close',
                'cotizaciones destroy',
            ];

            $routePermissionIds = [];

            //Get all the Routepermissions IDs
            foreach($routePermissionNames as $routePermissionName)
            {
                $routePermission = Routepermission::where('name', $routePermissionName)->first();
                $routePermissionIds[] = $routePermission->id;
            }

            //Sync permissions to the role
            $role->routepermissions()->sync($routePermissionIds);
        }
        
        //Vendedor
        {
            $role = new Role();
            $role->name = 'Vendedor';
            $role->save();

            //Route permissions to for Role: Vendedor
            $routePermissionNames = [
                //Faenas
                'faenas index_full',
                //Marcas
                'marcas index_full',
                //Solicitudes
                'solicitudes index',
                'solicitudes store',
                'solicitudes show',
                'solicitudes update',
                'solicitudes close',
                'solicitudes destroy',
                //Cotizaciones
                'cotizaciones index',
                'cotizaciones show',
                'cotizaciones approve',
                'cotizaciones reject',
                'cotizaciones close',
                'cotizaciones destroy',
            ];

            $routePermissionIds = [];

            //Get all the Routepermissions IDs
            foreach($routePermissionNames as $routePermissionName)
            {
                $routePermission = Routepermission::where('name', $routePermissionName)->first();
                $routePermissionIds[] = $routePermission->id;
            }

            //Sync permissions to the role
            $role->routepermissions()->sync($routePermissionIds);
        }

        /*
        *   Users
        */
        $user = new User();
        $user->name = 'Administrador AP';
        $user->email = 'admin@mail.com';
        $user->phone = '9012345678';
        $user->password = bcrypt('admin');
        $user->role_id = 1; // Administrador
        $user->save();

        $user = new User();
        $user->name = 'Vendedor AP';
        $user->email = 'seller@mail.com';
        $user->phone = '9012345678';
        $user->password = bcrypt('seller');
        $user->role_id = 2; // Vendedor
        $user->save();

        /*
        *   Parameters
        */
        $parameter = new Parameter();
        $parameter->name = 'usd_to_clp';
        $parameter->description = 'Valor del Dolar (USD) para transformar a peso chileno (CLP)';
        $parameter->value = 740;
        $parameter->save();

        /*
        *   Clientes
        */
        $cliente = new Cliente();
        $cliente->name = 'ClienteTest01';
        $cliente->save();
        $cliente = new Cliente();
        $cliente->name = 'ClienteTest02';
        $cliente->save();

        /*
        *   Faenas
        */
        $faena = new Faena();
        $faena->cliente_id = 1;
        $faena->rut = 'RutTest01';
        $faena->name = 'FaenaTest01';
        $faena->address = 'FaenaAddressTest01';
        $faena->city = 'FaenaCityTest01';
        $faena->contact = 'FaenaContactTest01';
        $faena->phone = 'FaenaPhoneTest01';
        $faena->save();
        $faena = new Faena();
        $faena->cliente_id = 1;
        $faena->rut = 'RutTest02';
        $faena->name = 'FaenaTest02';
        $faena->address = 'FaenaAddressTest02';
        $faena->city = 'FaenaCityTest02';
        $faena->contact = 'FaenaContactTest02';
        $faena->phone = 'FaenaPhoneTest02';
        $faena->save();

        /*
        *   Marcas
        */
        $marca = new Marca();
        $marca->name = 'MarcaTest01';
        $marca->save();
        $marca = new Marca();
        $marca->name = 'MarcaTest02';
        $marca->save();

        /*
        *   Partes
        */
        $parte = new Parte();
        $parte->marca_id = 1;
        $parte->nparte = 'NParteTest01';
        $parte->save();
        $parte = new Parte();
        $parte->marca_id = 1;
        $parte->nparte = 'NParteTest02';
        $parte->save();

        /*
        *   Estado solicitudes
        */
        $estadosolicitud = new Estadosolicitud();
        $estadosolicitud->name = 'Pendiente';
        $estadosolicitud->save();
        $estadosolicitud = new Estadosolicitud();
        $estadosolicitud->name = 'Completada';
        $estadosolicitud->save();
        $estadosolicitud = new Estadosolicitud();
        $estadosolicitud->name = 'Cerrada';
        $estadosolicitud->save();

        /*
        *   Solicitudes
        */
        $solicitud = new Solicitud();
        $solicitud->faena_id = 1;
        $solicitud->marca_id = 1;
        $solicitud->user_id = 2;
        $solicitud->estadosolicitud_id = 1;
        $solicitud->comentario = 'Testing comment for SolicitudTest01';
        $solicitud->save();

        $solicitud->partes()->attach([ 
            1 => [
                'cantidad' => 100
            ],
            2 => [
                'cantidad' => 850
            ]
        ]);

        /*
        *   Estado cotizaciones
        */
        $estadocotizacion = new Estadocotizacion();
        $estadocotizacion->name = 'Pendiente';
        $estadocotizacion->save();
        $estadocotizacion = new Estadocotizacion();
        $estadocotizacion->name = 'Vencida';
        $estadocotizacion->save();
        $estadocotizacion = new Estadocotizacion();
        $estadocotizacion->name = 'Aprobada';
        $estadocotizacion->save();
        $estadocotizacion = new Estadocotizacion();
        $estadocotizacion->name = 'Rechazada';
        $estadocotizacion->save();

        /*
        *   Motivos de rechazo (cotizaciones)
        */
        $motivorechazo = new Motivorechazo();
        $motivorechazo->name = 'Precio';
        $motivorechazo->save();
        $motivorechazo = new Motivorechazo();
        $motivorechazo->name = 'Gestion';
        $motivorechazo->save();
        $motivorechazo = new Motivorechazo();
        $motivorechazo->name = 'Tiempo';
        $motivorechazo->save();

        /*
        *   Proveedores
        */
        $proveedor = new Proveedor();
        $proveedor->rut = '12.345.678-9';
        $proveedor->name = 'ProveedorTest01';
        $proveedor->save();
        $proveedor = new Proveedor();
        $proveedor->rut = '98.765.432-1';
        $proveedor->name = 'ProveedorTest02';
        $proveedor->save();

        /*
        *   Estado ocs
        */
        $estadooc = new Estadooc();
        $estadooc->name = 'Pendiente';
        $estadooc->save();
        $estadooc = new Estadooc();
        $estadooc->name = 'En proceso';
        $estadooc->save();
        $estadooc = new Estadooc();
        $estadooc->name = 'Cerrada';
        $estadooc->save();

        /*
        *   Estado oc partes
        */
        $estadoocparte = new Estadoocparte();
        $estadoocparte->name = 'Pendiente';
        $estadoocparte->save();
        $estadoocparte = new Estadoocparte();
        $estadoocparte->name = 'En proceso';
        $estadoocparte->save();
        $estadoocparte = new Estadoocparte();
        $estadoocparte->name = 'Entregado';
        $estadoocparte->save();
    }
}
