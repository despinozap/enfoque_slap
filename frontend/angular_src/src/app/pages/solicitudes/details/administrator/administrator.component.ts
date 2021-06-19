import { Component, OnInit, ViewChild } from '@angular/core';
import { Location } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { NotificationsService } from 'src/app/services/notifications.service';
import { SolicitudesService } from 'src/app/services/solicitudes.service';
import { UtilsService } from 'src/app/services/utils.service';

/* SweetAlert2 */
const Swal = require('../../../../../assets/vendors/sweetalert2/sweetalert2.all.min.js');

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
    },
    order: [[0, 'desc']]
  };

  
  dtTrigger: Subject<any> = new Subject<any>();
  
  solicitud: any = {
    id: -1,
    sucursal_name: null,
    faena_name: null,
    cliente_name: null,
    marca_name: null,
    comprador_name: null,
    estadosolicitud_id: -1,
    estadosolicitud_name: null,
    comentario: null
  };

  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  private sub: any;

  constructor(
    private location: Location,
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
      this.solicitud.sucursal_name = solicitudData.sucursal.name;
      this.solicitud.faena_name = solicitudData.faena.name;
      this.solicitud.cliente_name = solicitudData.faena.cliente.name;
      this.solicitud.marca_name = solicitudData.marca.name;
      this.solicitud.comprador_name = solicitudData.comprador.name;
      this.solicitud.estadosolicitud_id = solicitudData.estadosolicitud.id,
      this.solicitud.estadosolicitud_name = solicitudData.estadosolicitud.name;
      this.solicitud.comentario = solicitudData.comentario;

      this.partes = [];
      solicitudData.partes.forEach((p: any) => {
        this.partes.push(
          {
            'nparte': p.nparte,
            'descripcion': p.pivot.descripcion,
            'cantidad': p.pivot.cantidad,
            'costo': p.pivot.costo,
            'margen': p.pivot.margen,
            'tiempoentrega': p.pivot.tiempoentrega,
            'peso': p.pivot.peso,
            'flete': p.pivot.flete,
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
      this.goTo_back();
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
              'Error al cargar los datos de la solicitud',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
        this.goTo_back();
      }
    );
  }

  public closeSolicitud(): void{
    Swal.fire({
      title: 'Cerrar solicitud',
      text: `¿Realmente quieres cerrar la solicitud #${ this.solicitud.id }?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#555555',
      confirmButtonText: 'Sí, continuar',
      cancelButtonText: 'Cancelar',
      allowOutsideClick: false
    }).then((result: any) => {
      if(result.isConfirmed)
      {
        Swal.queue([{
          title: 'Cerrando..',
          icon: 'warning',
          showConfirmButton: false,
          showCancelButton: false,
          allowOutsideClick: false,
          showLoaderOnConfirm: true,
          preConfirm: () => {

          }    
        }]);

        this._solicitudesService.closeSolicitud(this.solicitud.id)
        .subscribe(
          //Success request
          (response: any) => {

            NotificationsService.showToast(
              response.message,
              NotificationsService.messageType.success
            );

            this.goTo_back();
          },
          //Error request
          (errorResponse: any) => {

            switch(errorResponse.status)
            {
              case 400: //Object not found
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.warning
                );

                break;
              }

              case 405: //Permission denied
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );

                break;
              }

              case 409: //Conflict
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );

                break;
              }

              case 500: //Internal server
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );

                break;
              }

              default: //Unhandled error
              {
                NotificationsService.showAlert(
                  'Error al intentar cerrar la solicitud',
                  NotificationsService.messageType.error
                );

                break;
              }
            }
          }
        );
      }
    });
  }

  public exportPartesToExcel(): void {

    let data: any[] = [];
    //Push header
    data.push(
      [
        'Cantidad',
        'N parte',
        'Descripcion',
        'Costo (USD)',
        'Margen (%)',
        'Tiempo entrega (dias)',
        'Peso (lb)',
        'Valor flete (USD)',
        'Backorder (SI = 1, NO = 0)'
      ]
    );

    //Add rows
    this.partes.forEach((p: any) => {
      data.push([
        p.cantidad,
        p.nparte,
        p.descripcion,
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

  public goTo_duplicateSolicitud(): void {
    this.router.navigate(['/panel/solicitudes/create', this.solicitud.id]);
  }

  public goTo_back(): void {
    this.location.back();
  }

}
