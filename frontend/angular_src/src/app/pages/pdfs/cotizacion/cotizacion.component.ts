import { Component, Input, OnInit } from '@angular/core';

import { NotificationsService } from 'src/app/services/notifications.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-pdf-cotizacion',
  templateUrl: './cotizacion.component.html',
  styleUrls: ['./cotizacion.component.css']
})
export class PDFCotizacionComponent implements OnInit {

  reportData: any = {
    cotizacion: {
      id: -1,
      updated_at: null,
      solicitud_id: -1,
      faena_rut: null,
      faena_name: null,
      faena_address: null,
      faena_city: null,
      faena_contact: null,
      faena_phone: null,
      sucursal_rut: null,
      sucursal_name: null,
      sucursal_address: null,
      sucursal_city: null,
      user_name: null,
      user_email: null,
      user_phone: null
    },
    partes: []
  };

  constructor(
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
  }

  public loadReportData(cotizacionData: any)
  { 
    if(cotizacionData.cotizacion.partes.length > 0)
    {
      this.reportData.cotizacion.id = cotizacionData.cotizacion.id;
      this.reportData.cotizacion.updated_at = cotizacionData.cotizacion.updated_at;
      this.reportData.cotizacion.solicitud_id = cotizacionData.cotizacion.solicitud.id;
      this.reportData.cotizacion.faena_rut = cotizacionData.cotizacion.solicitud.faena.rut;
      this.reportData.cotizacion.faena_name = cotizacionData.cotizacion.solicitud.faena.name;
      this.reportData.cotizacion.faena_address = cotizacionData.cotizacion.solicitud.faena.address;
      this.reportData.cotizacion.faena_city = cotizacionData.cotizacion.solicitud.faena.city;
      this.reportData.cotizacion.faena_contact = cotizacionData.cotizacion.solicitud.faena.contact;
      this.reportData.cotizacion.faena_phone = cotizacionData.cotizacion.solicitud.faena.phone;
      this.reportData.cotizacion.sucursal_rut = cotizacionData.sucursal.rut;
      this.reportData.cotizacion.sucursal_name = cotizacionData.sucursal.name;
      this.reportData.cotizacion.sucursal_address = cotizacionData.sucursal.address;
      this.reportData.cotizacion.sucursal_city = cotizacionData.sucursal.city;
      this.reportData.cotizacion.user_name = cotizacionData.cotizacion.solicitud.user.name;
      this.reportData.cotizacion.user_email = cotizacionData.cotizacion.solicitud.user.email;
      this.reportData.cotizacion.user_phone = cotizacionData.cotizacion.solicitud.user.phone;
      
      this.reportData.partes = [];
      cotizacionData.cotizacion.partes.forEach((p: any) => {
        this.reportData.partes.push(
          {
            'id': p.id,
            'nparte': p.nparte,
            'descripcion': p.pivot.descripcion,
            'cantidad': p.pivot.cantidad,
            //'costo': p.pivot.costo,
            //'margen': p.pivot.margen,
            'tiempoentrega': 2,//p.pivot.tiempoentrega,
            //'peso': p.pivot.peso,
            //'flete': p.pivot.flete,
            'monto': p.pivot.monto,
            'backorder': p.pivot.backorder === 1 ? true : false,
          }
        )
      });
    }
    else
    {
      NotificationsService.showToast(
        'Error al intentar cargar la lista de partes',
        NotificationsService.messageType.error
      );
    }
  }

  public exportCotizacionToPdf(): void {
    const data = document.getElementById('divReport');
    if(data !== null)
    {
      this._utilsService.exportHtmlToPdf(data, 'Cotizacion.pdf');
    }    
  }

  public getTotalNetoPartes(): number
  {
    return this.reportData.partes.reduce(
      (carry: any, parte: any) => {
        return carry + (parte.monto * parte.cantidad);
      },
      0
    );
  }

  public moneyStringFormat(value: number): string {
    return this._utilsService.moneyStringFormat(value);
  }

  public dateStringFormat(value: string): string {
    return this._utilsService.dateStringFormat(value);
  }
}
