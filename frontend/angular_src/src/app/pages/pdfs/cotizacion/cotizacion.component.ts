import { Component, OnInit } from '@angular/core';

import { NotificationsService } from 'src/app/services/notifications.service';
import { CotizacionesService } from 'src/app/services/cotizaciones.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-cotizacion',
  templateUrl: './cotizacion.component.html',
  styleUrls: ['./cotizacion.component.css']
})
export class PDFCotizacionComponent implements OnInit {


  cotizacion: any = {
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
  };

  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  constructor(
    private _cotizacionesService: CotizacionesService,
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
    this.cotizacion.id = 1;
    this.loadReportCotizacion();
  }

  private loadReportData(cotizacionData: any)
  { 
    if(cotizacionData.cotizacion.partes.length > 0)
    {
      this.cotizacion.id = cotizacionData.cotizacion.id;
      this.cotizacion.updated_at = cotizacionData.cotizacion.updated_at;
      this.cotizacion.solicitud_id = cotizacionData.cotizacion.solicitud.id;
      this.cotizacion.faena_rut = cotizacionData.cotizacion.solicitud.faena.rut;
      this.cotizacion.faena_name = cotizacionData.cotizacion.solicitud.faena.name;
      this.cotizacion.faena_address = cotizacionData.cotizacion.solicitud.faena.address;
      this.cotizacion.faena_city = cotizacionData.cotizacion.solicitud.faena.city;
      this.cotizacion.faena_contact = cotizacionData.cotizacion.solicitud.faena.contact;
      this.cotizacion.faena_phone = cotizacionData.cotizacion.solicitud.faena.phone;
      this.cotizacion.sucursal_rut = cotizacionData.sucursal.rut;
      this.cotizacion.sucursal_name = cotizacionData.sucursal.name;
      this.cotizacion.sucursal_address = cotizacionData.sucursal.address;
      this.cotizacion.sucursal_city = cotizacionData.sucursal.city;
      this.cotizacion.user_name = cotizacionData.cotizacion.solicitud.user.name;
      this.cotizacion.user_email = cotizacionData.cotizacion.solicitud.user.email;
      this.cotizacion.user_phone = cotizacionData.cotizacion.solicitud.user.phone;
      
      this.partes = [];
      cotizacionData.cotizacion.partes.forEach((p: any) => {
        this.partes.push(
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

      this.loading = false;
    }
  }

  public loadReportCotizacion(): void {
    
    this.loading = true;
    this._cotizacionesService.getReportCotizacion(this.cotizacion.id)
    .subscribe(
      //Success request
      (response: any) => {
        this.loadReportData(response.data);

        this.loading = false;
      },
      //Error request
      (errorResponse: any) => {

        switch(errorResponse.status)
        {
        
          case 400: //Bad request
          {
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.error
            );

            break;
          }

          case 405: //Permission denied
          {
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.error
            );

            break;
          }

          case 412: //Object not found
          {
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.warning
            );

            break;
          }

          case 500: //Internal server
          {
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.error
            );

            break;
          }
        
          default: //Unhandled error
          {
            NotificationsService.showToast(
              'Error al cargar los datos de la cotizacion',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
      }
    );
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
    return this.partes.reduce(
      (carry, parte) => {
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
