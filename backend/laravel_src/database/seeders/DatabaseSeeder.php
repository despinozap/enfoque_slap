<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;

use Illuminate\Database\Seeder;
use App\Models\Country;
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
use App\Models\Motivobaja;
use App\Models\Estadoocparte;
use App\Models\Oc;

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
            //Loggedactions
            'loggedactions index',
            'loggedactions store',
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
            'solicitudes complete_prepare',
            'solicitudes complete',
            'solicitudes close',
            'solicitudes destroy',
            //Cotizaciones
            'cotizaciones index',
            'cotizaciones report',
            'cotizaciones approve',
            'cotizaciones reject',
            'cotizaciones destroy',
            //Ocs
            'ocs index',
            'ocs report',
            'ocs start',
            'ocs update',
            'ocs reject',
            'ocs close',
            //Compradores
            'compradores index',
            'compradores show',
            'compradores update',
            'compradores recepciones_index',
            'compradores recepciones_store',
            'compradores recepciones_show',
            'compradores recepciones_update',
            'compradores recepciones_destroy',
            'compradores despachos_index',
            'compradores despachos_store',
            'compradores despachos_show',
            'compradores despachos_update',
            'compradores despachos_destroy',
            //Sucursales
            'sucursales index',
            'sucursales show',
            'sucursales recepciones_index',
            'sucursales recepciones_store',
            'sucursales recepciones_show',
            'sucursales recepciones_update',
            'sucursales recepciones_destroy',
            'sucursales despachos_index',
            'sucursales despachos_store',
            'sucursales despachos_show',
            'sucursales despachos_update',
            'sucursales despachos_destroy',
            'sucursales entregas_index',
            'sucursales entregas_store',
            'sucursales entregas_show',
            'sucursales entregas_update',
            'sucursales entregas_destroy',
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
        //Super Administrator
        {
            $role = new Role();
            $role->name = 'suadm';
            $role->label = 'Super Admin';
            $role->save();

            $routePermissionNames = [
                //Loggedactions
                'loggedactions index',
                'loggedactions store'
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

        //Administrator
        {
            $role = new Role();
            $role->name = 'admin';
            $role->label = 'Administrador';
            $role->save();

            $routePermissionNames = [
                //Roles
                'roles index_full',
                //Users
                'users index',
                'users store',
                'users show',
                'users update',
                'users destroy',
                //Loggedactions
                'loggedactions index',
                'loggedactions store',
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
                'solicitudes complete_prepare',
                'solicitudes complete',
                'solicitudes close',
                'solicitudes destroy',
                //Cotizaciones
                'cotizaciones index',
                'cotizaciones report',
                'cotizaciones approve',
                'cotizaciones reject',
                'cotizaciones destroy',
                //Ocs
                'ocs index',
                'ocs report',
                'ocs start',
                'ocs update',
                'ocs reject',
                'ocs close',
                //Compradores
                'compradores index',
                'compradores show',
                'compradores update',
                'compradores recepciones_index',
                'compradores recepciones_store',
                'compradores recepciones_show',
                'compradores recepciones_update',
                'compradores recepciones_destroy',
                'compradores despachos_index',
                'compradores despachos_store',
                'compradores despachos_show',
                'compradores despachos_update',
                'compradores despachos_destroy',
                //Sucursales
                'sucursales index',
                'sucursales show',
                'sucursales recepciones_index',
                'sucursales recepciones_store',
                'sucursales recepciones_show',
                'sucursales recepciones_update',
                'sucursales recepciones_destroy',
                'sucursales despachos_index',
                'sucursales despachos_store',
                'sucursales despachos_show',
                'sucursales despachos_update',
                'sucursales despachos_destroy',
                'sucursales entregas_index',
                'sucursales entregas_store',
                'sucursales entregas_show',
                'sucursales entregas_update',
                'sucursales entregas_destroy',
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
            $role->name = 'seller';
            $role->label = 'Vendedor';
            $role->save();

            $routePermissionNames = [
                //Loggedactions
                'loggedactions index',
                'loggedactions store',
                //Solicitudes
                'solicitudes index',
                'solicitudes store',
                'solicitudes show',
                'solicitudes update',
                'solicitudes destroy',
                //Cotizaciones
                'cotizaciones index',
                'cotizaciones report',
                'cotizaciones approve',
                'cotizaciones reject',
                //Ocs
                'ocs index',
                'ocs report',
                'ocs update',
                'ocs close',
                //Compradores
                'compradores recepciones_index',
                'compradores recepciones_show',
                'compradores despachos_index',
                'compradores despachos_show',
                //Sucursales
                'sucursales recepciones_index',
                'sucursales recepciones_show',
                'sucursales despachos_index',
                'sucursales despachos_show',
                'sucursales entregas_index',
                'sucursales entregas_store',
                'sucursales entregas_show',
                'sucursales entregas_update',
                'sucursales entregas_destroy',
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
        
        //Agente de compra en Comprador
        {
            $role = new Role();
            $role->name = 'agtcom';
            $role->label = 'Agente Compras';
            $role->save();

            $routePermissionNames = [
                //Loggedactions
                'loggedactions index',
                'loggedactions store',
                //Solicitudes
                'solicitudes index',
                'solicitudes show',
                'solicitudes complete_prepare',
                'solicitudes complete',
                'solicitudes close',
                //Ocs
                'ocs index',
                'ocs report',
                'ocs start',
                'ocs update',
                'ocs reject',
                //Proveedores
                'proveedores index',
                //Compradores
                'compradores recepciones_index',
                'compradores recepciones_store',
                'compradores recepciones_show',
                'compradores recepciones_update',
                'compradores recepciones_destroy',
                'compradores despachos_index',
                'compradores despachos_store',
                'compradores despachos_show',
                'compradores despachos_update',
                'compradores despachos_destroy',
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
        
        //Coordinador Logistico en Comprador
        {
            $role = new Role();
            $role->name = 'colcom';
            $role->label = 'Coordinador COM';
            $role->save();

            $routePermissionNames = [
                //Loggedactions
                'loggedactions index',
                'loggedactions store',
                //Ocs
                'ocs index',
                'ocs report',
                //Compradores
                'compradores recepciones_index',
                'compradores recepciones_store',
                'compradores recepciones_show',
                'compradores recepciones_update',
                'compradores recepciones_destroy',
                'compradores despachos_index',
                'compradores despachos_store',
                'compradores despachos_show',
                'compradores despachos_update',
                'compradores despachos_destroy',
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

        //Coordinador Logistico solicitante en Sucursal
        {
            $role = new Role();
            $role->name = 'colsol';
            $role->label = 'Coordinador SOL';
            $role->save();

            $routePermissionNames = [
                //Loggedactions
                'loggedactions index',
                'loggedactions store',
                //Ocs
                'ocs index',
                'ocs report',
                //Compradores
                'compradores recepciones_index',
                'compradores recepciones_show',
                'compradores despachos_index',
                'compradores despachos_show',
                //Sucursales
                'sucursales recepciones_index',
                'sucursales recepciones_store',
                'sucursales recepciones_show',
                'sucursales recepciones_update',
                'sucursales recepciones_destroy',
                'sucursales despachos_index',
                'sucursales despachos_store',
                'sucursales despachos_show',
                'sucursales despachos_update',
                'sucursales despachos_destroy',
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
        *   Countries
        */
        $country = new Country();
        $country->name = 'Chile';
        $country->save();
        $country = new Country();
        $country->name = 'USA';
        $country->save();
        

        /*
        *   Sucursales
        */
        $sucursal = new Sucursal();
        $sucursal->type = 'centro';
        $sucursal->rut = '76.790.684-6';
        $sucursal->name = 'American Parts SPA (Central)';
        $sucursal->address = 'Av. La Dehesa 181 Of. 1313';
        $sucursal->city = 'Santiago';
        $sucursal->country_id = 1; // Chile
        $sucursal->save();

        $sucursal = new Sucursal();
        $sucursal->type = 'sucursal';
        $sucursal->rut = '76.790.684-6';
        $sucursal->name = 'American Parts SPA (Antofagasta)';
        $sucursal->address = 'Coquimbo 712 Of. 401';
        $sucursal->city = 'Antofagasta';
        $sucursal->country_id = 1; // Chile
        $sucursal->save();


        /*
        *   Compradores
        */
        $comprador = new Comprador();
        $comprador->rut = '5.917.158-5';
        $comprador->name = 'American Parts Miami';
        $comprador->address = 'CompradorDireccionTest01';
        $comprador->city = 'Miami';
        $comprador->country_id = 2; // USA
        $comprador->email = 'americanpartsmiami@mail.com';
        $comprador->phone = '+12345678901';
        $comprador->save();

    
        /*
        *   Users
        */
        // Administrador
        $station = Sucursal::find(2); // Sucursal Antofagasta
        $user = new User();
        $user->stationable_type = get_class($station);
        $user->stationable_id = $station->id;
        $user->name = 'Administrador AP';
        $user->email = 'admin@mail.com';
        $user->phone = '9012345678';
        $user->password = bcrypt('admin');
        $user->role_id = 2; // Administrador
        $user->save();

        // Vendedor Chile
        $station = Sucursal::find(2); // Sucursal Antofagasta
        $user = new User();
        $user->stationable_type = get_class($station);
        $user->stationable_id = $station->id;
        $user->name = 'VendedorTest ANF';
        $user->email = 'seller@mail.com';
        $user->phone = '9012345678';
        $user->password = bcrypt('seller');
        $user->role_id = 3; // Vendedor
        $user->save();

        // Agente de compra
        $station = Comprador::find(1); // Comprador USA
        $user = new User();
        $user->stationable_type = get_class($station);
        $user->stationable_id = $station->id;
        $user->name = 'AgenteTest MIA';
        $user->email = 'agent@mail.com';
        $user->phone = '9012345678';
        $user->password = bcrypt('agent');
        $user->role_id = 4; // Agente de compra
        $user->save();


        /*
        *   Parameters
        */
        // Value USD to CLP
        $parameter = new Parameter();
        $parameter->name = 'usd_to_clp';
        $parameter->description = 'Valor del Dolar (USD) para transformar a peso chileno (CLP)';
        $parameter->value = 740;
        $parameter->save();
        // Value LB in USD
        $parameter = new Parameter();
        $parameter->name = 'lb_in_usd';
        $parameter->description = 'Valor de flete en Dolar (USD) para cada libra';
        $parameter->value = 2.5;
        $parameter->save();


        /*
        *   Clientes
        */
        $cliente = new Cliente();
        $cliente->name = 'ClienteTest01';
        $cliente->country_id = 1; // Chile
        $cliente->save();
        $cliente = new Cliente();
        $cliente->name = 'Codelco';
        $cliente->country_id = 1; // Chile
        $cliente->save();


        /*
        *   Faenas
        */
        $faena = new Faena();
        $faena->cliente_id = 1;
        $faena->sucursal_id = 1; // Delivered at
        $faena->rut = 'RutTest01';
        $faena->name = 'FaenaTest01';
        $faena->address = 'FaenaAddressTest01';
        $faena->city = 'FaenaCityTest01';
        $faena->contact = 'FaenaContactTest01';
        $faena->phone = 'FaenaPhoneTest01';
        $faena->save();
        $faena = new Faena();
        $faena->cliente_id = 2;
        $faena->sucursal_id = 2; // Delivered at
        $faena->rut = '12.345.678-9';
        $faena->name = 'Chuquicamata';
        $faena->address = 'Chuquicamata';
        $faena->city = 'Calama';
        $faena->contact = 'Juan Gonzales';
        $faena->phone = '+56998765432';
        $faena->save();


        /*
        *   Marcas
        */
        $marca = new Marca();
        $marca->name = 'MarcaTest01';
        $marca->save();
        $marca = new Marca();
        $marca->name = 'Komatsu';
        $marca->save();
        $marca = new Marca();
        $marca->name = 'CAT';
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
        $estadosolicitud->name = 'Completa';
        $estadosolicitud->save();
        $estadosolicitud = new Estadosolicitud();
        $estadosolicitud->name = 'Cerrada';
        $estadosolicitud->save();


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
        $proveedor->comprador_id = $comprador->id;
        $proveedor->rut = 'ProveedorRUTTest01';
        $proveedor->name = 'ProveedorNombreTest01';
        $proveedor->address = 'ProveedorDireccionTest01';
        $proveedor->city = 'ProveedorCiudadTest01';
        $proveedor->email = 'proveedor1@mail.com';
        $proveedor->phone = 'ProveedorTelefonoTest01';
        $proveedor->delivered = false;
        $proveedor->save();
        $proveedor = new Proveedor();
        $proveedor->comprador_id = $comprador->id;
        $proveedor->rut = '39.230.797-4';
        $proveedor->name = 'KTractor Parts, Inc.';
        $proveedor->address = '8147 NW 67th St';
        $proveedor->city = 'Miami';
        $proveedor->email = 'ktractorparts@mail.com';
        $proveedor->phone = '+1 305-392-7452';
        $proveedor->delivered = false;
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
        $estadooc = new Estadooc();
        $estadooc->name = 'Baja';
        $estadooc->save();

        
        /*
        *   Motivos de baja (OCs)
        */
        $motivobaja = new Motivobaja();
        $motivobaja->name = 'Disponibilidad';
        $motivobaja->save();
        $motivobaja = new Motivobaja();
        $motivobaja->name = 'Gestion';
        $motivobaja->save();
        $motivobaja = new Motivobaja();
        $motivobaja->name = 'Tiempo';
        $motivobaja->save();


        /*
        *   Estado oc partes
        */
        $estadoocparte = new Estadoocparte();
        $estadoocparte->name = 'Pendiente';
        $estadoocparte->save();
        $estadoocparte = new Estadoocparte();
        $estadoocparte->name = 'En transito';
        $estadoocparte->save();
        $estadoocparte = new Estadoocparte();
        $estadoocparte->name = 'Entregado';
        $estadoocparte->save();

        /*
        *   Testing data
        */
        /*
        // Solicitudes
        {
            for($i = 0; $i <= 20; $i++)
            {
                $solicitud = new Solicitud();
                $solicitud->sucursal_id = rand(1, 2);
                $solicitud->faena_id = 1;
                $solicitud->marca_id = 1;
                $solicitud->comprador_id = 1;
                $solicitud->user_id = 2;
                $solicitud->estadosolicitud_id = 1;
                $solicitud->comentario = 'Testing comment for SolicitudTest0' . ($i + 1);
                $solicitud->save();

                $solicitud->partes()->attach([ 
                    1 => [
                        'cantidad' => rand(1, 300),
                        'descripcion' => 'DescriptionParte-Rand' . rand(100, 999)
                    ],
                    2 => [
                        'cantidad' => rand(1, 300),
                        'descripcion' => 'DescriptionParte-Rand' . rand(100, 999)
                    ]
                ]);
            } 
        }

        // Cotizaciones
        {
            $success = true;

            DB::beginTransaction();
            foreach(Solicitud::where('solicitudes.id', '>=', 4)->get() as $solicitud)
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
                                'descripcion' => $parte->pivot->descripcion,
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
        
        // OCs
        {
            // Create
            foreach(Cotizacion::where('cotizaciones.id', '>=', 8)->get() as $cotizacion)
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
                    $oc->noccliente = rand(1000, 9999);
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
                                'tiempoentrega' => rand(1, 30),
                                'backorder' => rand(0, 1)
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

            // Set as "En proceso"
            foreach(Oc::where('ocs.id', '>=', 10)->get() as $oc)
            {
                $oc->estadooc_id = 2; // En proceso
                $oc->proveedor_id = 1; // Set proveedor
                $oc->motivobaja_id = null; // Removes Motivorechazo if it had
                
                $oc->save();
            }
        }

        // Solicitud American Parts, OC Interna: 228.SG
        {
            // Solicitud
            {
                // Partes
                $partes = array();
                $parte = new Parte();
                $parte->marca_id = 2;
                $parte->nparte = '1987251121';
                $item = [
                    'parte' => $parte,
                    'cantidad' => 2,
                    'descripcion' => 'BRACKET'
                ];
                array_push($partes, $item);
                $parte = new Parte();
                $parte->marca_id = 2;
                $parte->nparte = '1986111250';
                $item = [
                    'parte' => $parte,
                    'cantidad' => 2,
                    'descripcion' => 'BOLT'
                ];
                array_push($partes, $item);
                $parte = new Parte();
                $parte->marca_id = 2;
                $parte->nparte = '5610782712';
                $item = [
                    'parte' => $parte,
                    'cantidad' => 2,
                    'descripcion' => 'COVER'
                ];
                array_push($partes, $item);
                $parte = new Parte();
                $parte->marca_id = 2;
                $parte->nparte = '5610782790';
                $item = [
                    'parte' => $parte,
                    'cantidad' => 2,
                    'descripcion' => 'SCREW'
                ];
                array_push($partes, $item);
                $parte = new Parte();
                $parte->marca_id = 2;
                $parte->nparte = '5615485771';
                $item = [
                    'parte' => $parte,
                    'cantidad' => 1,
                    'descripcion' => 'MAT'
                ];
                array_push($partes, $item);
                $parte = new Parte();
                $parte->marca_id = 2;
                $parte->nparte = '5615485780';
                $item = [
                    'parte' => $parte,
                    'cantidad' => 1,
                    'descripcion' => 'MAT'
                ];
                array_push($partes, $item);
    
                $solicitud = new Solicitud();
                $solicitud->sucursal_id = 2; // American Parts (Antofagasta)
                $solicitud->faena_id = 2;
                $solicitud->marca_id = 2;
                $solicitud->comprador_id = 1;
                $solicitud->user_id = 2;
                $solicitud->estadosolicitud_id = 1;
                $solicitud->comentario = 'Esta es una solicitud que se ha creado para pruebas, basado en el caso real OC Interna: 228.SG';
                $solicitud->save();
    
                foreach($partes as $item)
                {
                    $item['parte']->save();
    
                    $solicitud->partes()->attach([
                        $item['parte']->id => [
                            'cantidad' => $item['cantidad'],
                            'descripcion' => $item['descripcion']
                        ]
                    ]);
                }
            }

            // Cotizacion
            {
                $success = true;
    
                DB::beginTransaction();

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
                                'descripcion' => $parte->pivot->descripcion,
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
                        }
                    }
                    else
                    {
                        $success = false;
                    }
                }
                else
                {
                    $success = false;
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

            // OC
            {
                DB::beginTransaction();

                $cotizacion->estadocotizacion_id = 3; // Aprobada
                $cotizacion->motivorechazo_id = null; // Removes Motivorechazo if it had

                if($cotizacion->save())
                {
                    $oc = new OC();
                    $oc->cotizacion_id = $cotizacion->id;
                    $oc->noccliente = rand(1000, 9999);
                    $oc->usdvalue = $cotizacion->usdvalue;
                    $oc->estadooc_id = 2; // En proceso
                    $oc->proveedor_id = 2; // Set proveedor
                    $oc->motivobaja_id = null; // Removes Motivorechazo if it had

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
                                'tiempoentrega' => rand(1, 30),
                                'backorder' => rand(0, 1)
                            );
                        }


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
                        DB::rollback();
                    }
                    
                }
                else
                {
                    DB::rollback();
                }

            }
        }

        */


        /*
         *  Demo data
         */
        {
            /*
            *   Cliente: Collahuasi
            */
            $cliente = new Cliente();
            $cliente->name = 'Collahuasi';
            $cliente->country_id = 1; // Chile
            $cliente->save();

            $faena = new Faena();
            $faena->cliente_id = $cliente->id;
            $faena->sucursal_id = 2; // Delivered at Antofagasta
            $faena->rut = '89.468.900- 5';
            $faena->name = 'Cia Minera Dona Inés de Collahuasi SCM';
            $faena->address = 'Av Andres Bello 2457';
            $faena->city = 'Santiago';
            $faena->contact = 'Veronica Gonzalez';
            $faena->phone = '934359026';
            $faena->save();
            
            /*
            *   Cliente: Pucobre
            */
            $cliente = new Cliente();
            $cliente->name = 'Pucobre';
            $cliente->country_id = 1; // Chile
            $cliente->save();

            $faena = new Faena();
            $faena->cliente_id = $cliente->id;
            $faena->sucursal_id = 1; // Delivered at Santiago
            $faena->rut = '96.561.560-1';
            $faena->name = 'Soc Punta del Cobre S.A';
            $faena->address = 'Rancagua 200';
            $faena->city = 'Copiapo';
            $faena->contact = 'Cristian Haro';
            $faena->phone = '52229354';
            $faena->save();

            // Proveedor
            $proveedor = new Proveedor();
            $proveedor->comprador_id = $comprador->id;
            $proveedor->rut = '11.222.333-4';
            $proveedor->name = 'Power Depot Inc';
            $proveedor->address = '3553 NW 78th Ave';
            $proveedor->city = 'Medley, Florida 33122';
            $proveedor->email = 'jgarzon@powerdepot.com';
            $proveedor->phone = '305 592 7100';
            $proveedor->delivered = true;
            $proveedor->delivery_name = 'Capital';
            $proveedor->delivery_address = '7315 NW 31 Street';
            $proveedor->delivery_city = 'Miami, Florida 33122';
            $proveedor->delivery_email = 'mlorenzo@capitaltrans.com';
            $proveedor->delivery_phone = '305 597 2300';
            $proveedor->save();

            /*
             *  Vendedores
             */
            // Vendedor: Abel Gonzalez Ortiz
            $station = Sucursal::find(2); // Sucursal Antofagasta
            $user = new User();
            $user->stationable_type = get_class($station);
            $user->stationable_id = $station->id;
            $user->role_id = 3; // Vendedor
            $user->email = 'agonzalez@americanparts.cl';
            $user->name = 'Abel Gonzalez Ortiz';
            $user->phone = '940427346';
            $user->password = bcrypt('ventas');
            $user->save();

            // Vendedor: Boris Rozas Poblete
            $station = Sucursal::find(2); // Sucursal Antofagasta
            $user = new User();
            $user->stationable_type = get_class($station);
            $user->stationable_id = $station->id;
            $user->role_id = 3; // Vendedor
            $user->email = 'brozas@americanparts.cl';
            $user->name = 'Boris Rozas Poblete';
            $user->phone = '965099724';
            $user->password = bcrypt('ventas');
            $user->save();

            // Vendedor: Ignacio Paredes Cortés
            $station = Sucursal::find(2); // Sucursal Antofagasta
            $user = new User();
            $user->stationable_type = get_class($station);
            $user->stationable_id = $station->id;
            $user->role_id = 3; // Vendedor
            $user->email = 'iparedes@americanparts.cl';
            $user->name = 'Ignacio Paredes Cortés';
            $user->phone = '991374253';
            $user->password = bcrypt('ventas');
            $user->save();

            // Vendedor: Javier Lagos Rochefort
            $station = Sucursal::find(2); // Sucursal Antofagasta
            $user = new User();
            $user->stationable_type = get_class($station);
            $user->stationable_id = $station->id;
            $user->role_id = 3; // Vendedor
            $user->email = 'jlagos@americanparts.cl';
            $user->name = 'Javier Lagos Rochefort';
            $user->phone = '940958190';
            $user->password = bcrypt('ventas');
            $user->save();

            // Vendedor: Ricardo Valdivieso Morales
            $station = Sucursal::find(2); // Sucursal Antofagasta
            $user = new User();
            $user->stationable_type = get_class($station);
            $user->stationable_id = $station->id;
            $user->role_id = 3; // Vendedor
            $user->email = 'rvaldivieso@americanparts.cl';
            $user->name = 'Ricardo Valdivieso Morales';
            $user->phone = '998823972';
            $user->password = bcrypt('ventas');
            $user->save();
        }
    }
}
