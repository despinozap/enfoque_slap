import { Component, OnInit } from '@angular/core';

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
      currentdate: null,
      created_at: null,
      solicitud_id: -1,
      sucursal_rut: null,
      sucursal_name: null,
      sucursal_address: null,
      sucursal_city: null,
      faena_rut: null,
      faena_name: null,
      faena_address: null,
      faena_city: null,
      faena_contact: null,
      faena_phone: null,
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
    if(cotizacionData.partes.length > 0)
    {
      this.reportData.cotizacion.id = cotizacionData.cotizacion.id;
      this.reportData.cotizacion.currentdate = cotizacionData.cotizacion.currentdate;
      this.reportData.cotizacion.created_at = cotizacionData.cotizacion.created_at;
      this.reportData.cotizacion.solicitud_id = cotizacionData.cotizacion.solicitud.id;
      this.reportData.cotizacion.sucursal_rut = cotizacionData.sucursal.rut;
      this.reportData.cotizacion.sucursal_name = cotizacionData.sucursal.name;
      this.reportData.cotizacion.sucursal_address = cotizacionData.sucursal.address;
      this.reportData.cotizacion.sucursal_city = cotizacionData.sucursal.city;
      this.reportData.cotizacion.faena_rut = cotizacionData.cotizacion.solicitud.faena.rut;
      this.reportData.cotizacion.faena_name = cotizacionData.cotizacion.solicitud.faena.name;
      this.reportData.cotizacion.faena_address = cotizacionData.cotizacion.solicitud.faena.address;
      this.reportData.cotizacion.faena_city = cotizacionData.cotizacion.solicitud.faena.city;
      this.reportData.cotizacion.faena_contact = cotizacionData.cotizacion.solicitud.faena.contact;
      this.reportData.cotizacion.faena_phone = cotizacionData.cotizacion.solicitud.faena.phone;
      this.reportData.cotizacion.user_name = cotizacionData.cotizacion.solicitud.user.name;
      this.reportData.cotizacion.user_email = cotizacionData.cotizacion.solicitud.user.email;
      this.reportData.cotizacion.user_phone = cotizacionData.cotizacion.solicitud.user.phone;
      
      this.reportData.partes = cotizacionData.partes;
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
      this._utilsService.exportHtmlToPdf(data, `Cotizacion-${ this.reportData.cotizacion.id }.pdf`);
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

  // Modified for removing decimals when in CLP value
  public moneyStringFormat(value: number): string {
    let moneyStr = this._utilsService.moneyStringFormat(value);
    
    // Modify value removing decimals
    let index = moneyStr.indexOf('.');
    moneyStr = moneyStr.substring(0, index);
    
    return moneyStr;
  }

  public dateStringFormat(value: string): string {
    return this._utilsService.dateStringFormat(value);
  }
}
