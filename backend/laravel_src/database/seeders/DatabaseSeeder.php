<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;

use Illuminate\Database\Seeder;
use App\Models\Sucursal;
use App\Models\Role;
use App\Models\Routepermission;
use App\Models\User;
use App\Models\Parameter;
use App\Models\Cliente;
use App\Models\Faena;
use App\Models\Marca;
use App\Models\Parte;
use App\Models\Comprador;
use App\Models\Estadosolicitud;
use App\Models\Solicitud;
use App\Models\Estadocotizacion;
use App\Models\Motivorechazo;
use App\Models\Cotizacion;
use App\Models\Proveedor;
use App\Models\Estadooc;
use App\Models\Estadoocparte;
use App\Models\OC;

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
            'cotizaciones report',
            'cotizaciones approve',
            'cotizaciones reject',
            'cotizaciones close',
            'cotizaciones destroy',
            //Ocs
            'ocs index',
            'ocs show',
            'ocs update',
            //Compradores
            'compradores index',
            'compradores show',
            //Proveedores
            'proveedores index',
            'proveedores store',
            'proveedores show',
            'proveedores update',
            'proveedores destroy',
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
                'cotizaciones report',
                'cotizaciones approve',
                'cotizaciones reject',
                'cotizaciones close',
                'cotizaciones destroy',
                //Ocs
                'ocs index',
                'ocs show',
                'ocs update',
                //Compradores
                'compradores index',
                'compradores show',
                //Proveedores
                'proveedores index',
                'proveedores store',
                'proveedores show',
                'proveedores update',
                'proveedores destroy',
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
                'cotizaciones report',
                'cotizaciones approve',
                'cotizaciones reject',
                'cotizaciones close',
                'cotizaciones destroy',
                //Ocs
                'ocs index',
                'ocs show',
                'ocs update',
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
        *   Sucursales
        */
        $sucursal = new Sucursal();
        $sucursal->rut = 'SucursalRUTTest01';
        $sucursal->name = 'SucursalNombreTest01';
        $sucursal->address = 'SucursalDireccionTest01';
        $sucursal->city = 'SucursalCiudadTest01';
        $sucursal->save();


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
        $cliente->sucursal_id = 1;
        $cliente->save();
        $cliente = new Cliente();
        $cliente->name = 'ClienteTest02';
        $cliente->sucursal_id = 1;
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
        *   Compradores
        */
        $comprador = new Comprador();
        $comprador->rut = 'CompradorRUTTest01';
        $comprador->name = 'CompradorNombreTest01';
        $comprador->address = 'CompradorDireccionTest01';
        $comprador->city = 'CompradorCiudadTest01';
        $comprador->contact = 'CompradorContactoTest01';
        $comprador->phone = 'CompradorTelefonoTest01';
        $comprador->save();


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
        {
            for($i = 0; $i <= 5; $i++)
            {
                $solicitud = new Solicitud();
                $solicitud->faena_id = 1;
                $solicitud->marca_id = 1;
                $solicitud->comprador_id = 1;
                $solicitud->user_id = 2;
                $solicitud->estadosolicitud_id = 1;
                $solicitud->comentario = 'Testing comment for SolicitudTest0' . ($i + 1);
                $solicitud->save();

                $solicitud->partes()->attach([ 
                    1 => [
                        'cantidad' => 10 * $i
                    ],
                    2 => [
                        'cantidad' => 85 * $i
                    ]
                ]);
            } 
        }
        

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
        *   Cotizaciones
        */
        {
            $success = true;

            DB::beginTransaction();
            foreach(Solicitud::whereIn('id', [2, 3, 4])->get() as $solicitud)
            {
                $solicitud->estadosolicitud_id = 3; // Cerrada
                if($solicitud->save())
                {
                    $cotizacion = new Cotizacion();
                    $cotizacion->solicitud_id = $solicitud->id;
                    $cotizacion->estadocotizacion_id = 1; //Initial Estadocotizacion
                    $cotizacion->usdvalue = 760;

                    if($cotizacion->save())
                    {
                        //Attaching each Parte to the Cotizacion
                        $syncData = [];
                        foreach($solicitud->partes as $parte)
                        {
                            $syncData[$parte->id] =  array(
                                'descripcion' => 'DescriptionParte-Rand' . rand(100, 999),
                                'cantidad' => $parte->pivot->cantidad,
                                'costo' => rand(1, 250),
                                'margen' => rand(15, 75),
                                'tiempoentrega' => rand(0, 40),
                                'peso' => rand(5, 700),
                                'flete' => rand(20, 100),
                                'monto' => rand(2, 900),
                                'backorder' => rand(0, 1),
                            );
                        }
        
                        // Fill randomly Partes for Cotizacion and update values on Solicitud
                        if((!$solicitud->partes()->sync($syncData)) || (!$cotizacion->partes()->sync($syncData)))
                        {
                            $success = false;

                            break;
                        }
                    }
                    else
                    {
                        $success = false;

                        break;
                    }
                }
                else
                {
                    $success = false;

                    break;
                }
            }

            if($success === true)
            {
                DB::commit();
            }
            else
            {
                DB::rollback();
            }
        }


        /*
        *   Proveedores
        */
        $proveedor = new Proveedor();
        $proveedor->comprador_id = $comprador->id;
        $proveedor->rut = 'ProveedorRUTTest01';
        $proveedor->name = 'ProveedorNombreTest01';
        $proveedor->address = 'ProveedorDireccionTest01';
        $proveedor->city = 'ProveedorCiudadTest01';
        $proveedor->contact = 'ProveedorContactoTest01';
        $proveedor->phone = 'ProveedorTelefonoTest01';
        $proveedor->save();
        $proveedor = new Proveedor();
        $proveedor->comprador_id = $comprador->id;
        $proveedor->rut = 'ProveedorRUTTest02';
        $proveedor->name = 'ProveedorNombreTest02';
        $proveedor->address = 'ProveedorDireccionTest02';
        $proveedor->city = 'ProveedorCiudadTest02';
        $proveedor->contact = 'ProveedorContactoTest02';
        $proveedor->phone = 'ProveedorTelefonoTest02';
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

        /*
        *   OCs
        */
        {
            foreach(Cotizacion::whereIn('id', [3, 4])->get() as $cotizacion)
            {
                DB::beginTransaction();

                $cotizacion->estadocotizacion_id = 3; // Aprobada
                $cotizacion->motivorechazo_id = null; // Removes Motivorechazo if it had

                if($cotizacion->save())
                {
                    $success = true;
                    $path = null;

                    $oc = new OC();
                    $oc->cotizacion_id = $cotizacion->id;
                    $oc->estadooc_id = 1; //Initial Estadooc
                    $oc->noccliente = '1234';
                    $oc->usdvalue = $cotizacion->usdvalue;

                    if($oc->save())
                    {
                        //Attaching each Parte to the Cotizacion
                        $syncData = [];

                        foreach($cotizacion->partes as $parte)
                        {
                            $syncData[$parte->id] =  array(
                                'estadoocparte_id' => 1, // Pendiente
                                'descripcion' => $parte->pivot->descripcion,
                                'cantidad' => $parte->pivot->cantidad,
                                'cantidadpendiente' => $parte->pivot->cantidad,
                            );
                        }


                        if($success === true)
                        {
                            if($oc->partes()->sync($syncData))
                            {
                                DB::commit();
                            }
                            else
                            {
                                DB::rollback();
                            }
                        }
                        else
                        {
                            //Error message already set
                        }
                        
                    }
                    else
                    {
                        DB::rollback();
                    }
                   
                }
                else
                {
                    DB::rollback();
                }
            }

        }
    }
}
