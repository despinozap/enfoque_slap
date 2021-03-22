import { Component, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { NotificationsService } from 'src/app/services/notifications.service';
import { SolicitudesService } from 'src/app/services/solicitudes.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-administrator',
  templateUrl: './administrator.component.html',
  styleUrls: ['./administrator.component.css']
})
export class SolicitudesDetailsAdministratorComponent implements OnInit {

  @ViewChild(DataTableDirective, {static: false})
  datatableElement_partes: DataTableDirective = null as any;
  dtOptions: any = {
    pagingType: 'full_numbers',
    pageLength: 10,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    }
  };

  
  dtTrigger: Subject<any> = new Subject<any>();
  
  solicitud: any = {
    id: -1,
    cliente_name: null,
    marca_name: null,
    estadosolicitud_id: -1,
    estadosolicitud_name: null,
    comentario: null
  };

  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  private sub: any;

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private _solicitudesService: SolicitudesService,
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      this.solicitud.id = params['id'];
    });
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadSolicitud();
    },
    100);
  }

  ngOnDestroy(): void {
    this.sub.unsubscribe();
    this.dtTrigger.unsubscribe();
  }
  
  private renderDataTable(dataTableElement: DataTableDirective): void {
    dataTableElement.dtInstance.then((dtInstance: DataTables.Api) => {
      // Destroy the table first
      dtInstance.destroy();
      // Call the dtTrigger to rerender again
      this.dtTrigger.next();
    });
  }
  
  private loadFormData(solicitudData: any)
  { 
    if(solicitudData['partes'].length > 0)
    {
      this.solicitud.id = solicitudData.id;
      this.solicitud.cliente_name = solicitudData.cliente.name;
      this.solicitud.marca_name = solicitudData.partes[0].marca.name;
      this.solicitud.estadosolicitud_id = solicitudData.estadosolicitud.id,
      this.solicitud.estadosolicitud_name = solicitudData.estadosolicitud.name;
      this.solicitud.comentario = solicitudData.comentario;

      this.partes = [];
      solicitudData.partes.forEach((p: any) => {
        this.partes.push(
          {
            'nparte': p.nparte,
            'cantidad': p.pivot.cantidad,
            'costo': p.pivot.costo,
            'margen': p.pivot.margen,
            'tiempoentrega': p.pivot.tiempoentrega,
            'peso': p.pivot.peso,
            'flete': p.pivot.flete,
            'monto': p.pivot.monto,
            'backorder': p.pivot.backorder === 1 ? true : false,
            'descripcion': p.pivot.descripcion
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
      this.goTo_solicitudesList();
    }
  }

  public loadSolicitud(): void {
    
    this.loading = true;

    this._solicitudesService.getSolicitud(this.solicitud.id)
    .subscribe(
      //Success request
      (response: any) => {

        this.loadFormData(response.data);
        this.renderDataTable(this.datatableElement_partes);

        this.loading = false;
      },
      //Error request
      (errorResponse: any) => {

        switch(errorResponse.status)
        {
        
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
        
          default: //Unhandled error
          {
            NotificationsService.showToast(
              'Error al cargar los datos de la solicitud',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
        this.goTo_solicitudesList();
      }
    );
  }

  public exportPartesToExcel(): void {

    let data: any[] = [];
    //Push header
    data.push(
      [
        'N parte',
        'Descripcion',
        'Cantidad',
        'Costo (USD)',
        'Margen (%)',
        'Tiempo entrega (dias)',
        'Peso (kg)',
        'Valor flete (USD)',
        'Backorder (SI = 1, NO = 0)'
      ]
    );

    //Add rows
    this.partes.forEach((p: any) => {
      data.push([
        p.nparte,
        p.descripcion,
        p.cantidad,
        p.costo,
        p.margen,
        p.tiempoentrega,
        p.peso,
        p.flete,
        (p.backorder === true) ? '1' : '0',
      ]);
    });

    this._utilsService.exportTableToExcel(data, `Solicitud_${ this.solicitud.id }-Partes`);
  }

  public moneyStringFormat(value: number): string {
    return this._utilsService.moneyStringFormat(value);
  }

  public goTo_solicitudesList(): void {
    this.router.navigate(['/panel/solicitudes']);
  }

}
