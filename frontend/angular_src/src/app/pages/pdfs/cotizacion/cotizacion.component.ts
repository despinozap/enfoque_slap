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
    dias: -1,
    faena_name: null,
    cliente_name: null,
    marca_name: null,
    estadocotizacion_id: -1,
    estadocotizacion_name: null,
    motivorechazo_name: null
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
    this.loadCotizacion();
  }

  private loadFormData(cotizacionData: any)
  { 
    console.log(cotizacionData);
    if(cotizacionData['partes'].length > 0)
    {
      this.cotizacion.id = cotizacionData.id;
      this.cotizacion.updated_at = cotizacionData.updated_at;
      this.cotizacion.dias = cotizacionData.dias;
      this.cotizacion.faena_name = cotizacionData.solicitud.faena.name;
      this.cotizacion.cliente_name = cotizacionData.solicitud.faena.cliente.name;
      this.cotizacion.marca_name = cotizacionData.solicitud.marca.name;
      this.cotizacion.estadocotizacion_id = cotizacionData.estadocotizacion.id,
      this.cotizacion.estadocotizacion_name = cotizacionData.estadocotizacion.name;
      // If Rechazada, then store Motivo rechazo name
      this.cotizacion.motivorechazo_name = ((this.cotizacion.estadocotizacion_id === 4) && (cotizacionData.motivorechazo !== null)) ? cotizacionData.motivorechazo.name : null

      this.partes = [];
      cotizacionData.partes.forEach((p: any) => {
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

  public loadCotizacion(): void {
    
    this.loading = true;
    this._cotizacionesService.getCotizacion(this.cotizacion.id)
    .subscribe(
      //Success request
      (response: any) => {
        this.loadFormData(response.data);

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

}
