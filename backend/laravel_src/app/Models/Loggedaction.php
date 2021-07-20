<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loggedaction extends Model
{
    use HasFactory;

    protected $table = 'loggedactions';
    protected $fillable = ['user_id', 'loggeable_id', 'loggeable_type', 'action', 'data'];

    /*
     *  LoggedActions action for different models:
     * 
     *      User:
     *          login: User has logged in -> ip_address
     * 
     *      Oc:
     *          updated: OC status updated -> status_name
     *          parte_removed: Parte removed from OC -> nparte, cantidad
     * 
     *      OcParte:
     *          created: OcParte was added to Oc -> cantidad
     *          updated: OcParte status updated -> status_name
     *          values_updated: OcParte valued updated -> previous_cantidad, cantidad, previous_tiempoentrega, tiempoentrega, previous_backorder and backorder)
     *          received: OcParte added to Recepcion -> cantidad, recepcion_id
     *          recepcion_updated: OcParte's cantidad had changed in Recepcion -> previous_cantidad, cantidad, recepcion_id
     *          removed_from_recepcion: OcParte was removed from Recepcion -> cantidad, recepcion_id
     *          added_to_recepcion: OcParte was added to an existing Recepcion -> cantidad, recepcion_id
     *          recepcion_removed: Recepcion what OcParte was belonged was removed -> cantidad, recepcion_id, sourceable_type, sourceable_id, recepcionable_type, recepcionable_id
     *          dispatched: OcParted added to Despacho -> cantidad, despacho_id
     *          despacho_updated: OcParte's cantidad had changed in Despacho -> previous_cantidad, cantidad, despacho_id
     *          removed_from_despacho: OcParte was removed from Despacho -> cantidad, despacho_id
     *          added_to_despacho: OcParte was added to an existing Despacho -> cantidad, despacho_id
     *          delivered: OcParted added to Entrega -> cantidad, entrega_id
     *          entrega_updated: OcParte's cantidad had changed in Entrega -> previous_cantidad, cantidad, entrega_id
     *          removed_from_entrega: OcParte was removed from Entrega -> cantidad, entrega_id
     *          added_to_entrega: OcParte was added to an existing Entrega -> cantidad, entrega_id
     */
}
