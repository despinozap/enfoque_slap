import { Component, OnInit, ViewChild } from '@angular/core';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { AuthService } from 'src/app/services/auth.service';
import { CotizacionesService } from 'src/app/services/cotizaciones.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UtilsService } from 'src/app/services/utils.service';
import { PDFCotizacionComponent } from '../../pdfs/cotizacion/cotizacion.component';

/* SweetAlert2 */
const Swal = require('../../../../assets/vendors/sweetalert2/sweetalert2.all.min.js');

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class CotizacionesListComponent implements OnInit {

  @ViewChild('reportCotizacion') reportCotizacion: PDFCotizacionComponent = null as any;
  @ViewChild(DataTableDirective, {static: false})
  datatableElement_cotizaciones: DataTableDirective = null as any;
  dtOptions: any = {
    pagingType: 'full_numbers',
    pageLength: 10,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    },
    columnDefs: [
      { orderable: false, targets: 0 }
    ],
    order: [[1, 'desc']],
    /*
    // Declare the use of the extension in the dom parameter
    dom: 'Bfrtip',
    // Configure the buttons
    buttons: [
      'colvis',
      'excel',
      'pdf',
      'print'
    ]
    */
  };

  
  dtTrigger: Subject<any> = new Subject<any>();

  cotizaciones: any[] = [];
  loading: boolean = false;
  loggedUser: any = {
    role_id: -1,
  };

  reportsDataCotizacion: any[] = [];

  constructor(
    private _authService: AuthService,
    private _cotizacionesService: CotizacionesService,
    private _utilsService: UtilsService
  ) { 

    this.loggedUser = {
      role_id: -1,
    };
  }

  ngOnInit(): void {
    //For loggedUser
    {
      this._authService.loggedUser$.subscribe((data) => {
        this.loggedUser = data.user;
      });
      
      this._authService.notifyLoggedUser(this._authService.NOTIFICATION_RECEIVER_CONTENTPAGE);
    }
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadCotizacionesList();
    },
    100);
  }

  ngOnDestroy(): void {
    this.dtTrigger.unsubscribe();
  }

  private clearDataTable(dataTableElement: DataTableDirective): void {
    dataTableElement.dtInstance.then((dtInstance: DataTables.Api) => {
      // Clear the table first
      dtInstance.clear();
    });
  }

  private renderDataTable(dataTableElement: DataTableDirective): void {
    dataTableElement.dtInstance.then((dtInstance: DataTables.Api) => {
      // Destroy the table first
      dtInstance.destroy();
      // Call the dtTrigger to rerender again
      this.dtTrigger.next();
    });
  }

  public loadCotizacionesList()
  {
    this.loading = true;

    this.clearDataTable(this.datatableElement_cotizaciones);
    this._cotizacionesService.getCotizaciones()
    .subscribe(
      //Success request
      (response: any) => {
        this.cotizaciones = response.data;
        this.cotizaciones.forEach((cotizacion: any) => {
          cotizacion['checked'] = false;
        });

        this.renderDataTable(this.datatableElement_cotizaciones);

        this.loading = false;
      },
      //Error request
      (errorResponse: any) => {

        switch(errorResponse.status)
        {     
          case 405: //Permission denied
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
              'Error al intentar cargar la lista de cotizaciones',
              NotificationsService.messageType.error
            )
        
            break;
          }
        }

        this.cotizaciones = null as any;
        this.loading = false;
      }
    );
  }

  public loadReportsCotizacion(ids: any)
  {
    this.loading = true;

    let data = {
      cotizaciones: ids
    };

    this._cotizacionesService.getReportCotizacion(data)
    .subscribe(
      //Success request
      (response: any) => {
        // Load reports data
        this.loadReportsDataCotizacion(response.data);
        this.loading = false;
      },
      //Error request
      (errorResponse: any) => {
        switch(errorResponse.status)
        {     
          case 405: //Permission denied
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
              'Error al intentar cargar la lista de reportes',
              NotificationsService.messageType.error
            )
        
            break;
          }
        }

        this.reportsDataCotizacion = [];
        this.loading = false;
      }
    );
  }
  
  private loadReportsDataCotizacion(reportsData: any)
  { 
    this.reportsDataCotizacion = [];
    let cotizacion: any;

    reportsData.forEach((cotizacionData: any) => {

      cotizacion = {
        // Common fields
        id: -1,
        updated_at: null,
        faena_name: null,
        // Details
        dias: -1,
        cliente_name: null,
        marca_name: null,
        estadocotizacion_id: -1,
        estadocotizacion_name: null,
        motivorechazo_name: null,
        // Report
        solicitud_id: -1,
        faena_rut: null,
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
        user_phone: null,
        //Partes
        partes: []
      };

      if(cotizacionData['partes'].length > 0)
      {
        // Common fields
        cotizacion.id = cotizacionData.id;
        cotizacion.updated_at = cotizacionData.updated_at;
        cotizacion.faena_name = cotizacionData.solicitud.faena.name;

        // Details
        cotizacion.dias = cotizacionData.dias;
        cotizacion.cliente_name = cotizacionData.solicitud.faena.cliente.name;
        cotizacion.marca_name = cotizacionData.solicitud.marca.name;
        cotizacion.estadocotizacion_id = cotizacionData.estadocotizacion.id,
        cotizacion.estadocotizacion_name = cotizacionData.estadocotizacion.name;
        // Details - If Rechazada, then store Motivo rechazo name
        cotizacion.motivorechazo_name = ((cotizacion.estadocotizacion_id === 4) && (cotizacionData.motivorechazo !== null)) ? cotizacionData.motivorechazo.name : null

        // Report
        cotizacion.solicitud_id = cotizacionData.solicitud.id;
        cotizacion.faena_rut = cotizacionData.solicitud.faena.rut;
        cotizacion.faena_address = cotizacionData.solicitud.faena.address;
        cotizacion.faena_city = cotizacionData.solicitud.faena.city;
        cotizacion.faena_contact = cotizacionData.solicitud.faena.contact;
        cotizacion.faena_phone = cotizacionData.solicitud.faena.phone;
        cotizacion.sucursal_rut = cotizacionData.solicitud.faena.cliente.sucursal.rut;
        cotizacion.sucursal_name = cotizacionData.solicitud.faena.cliente.sucursal.name;
        cotizacion.sucursal_address = cotizacionData.solicitud.faena.cliente.sucursal.address;
        cotizacion.sucursal_city = cotizacionData.solicitud.faena.cliente.sucursal.city;
        cotizacion.user_name = cotizacionData.solicitud.user.name;
        cotizacion.user_email = cotizacionData.solicitud.user.email;
        cotizacion.user_phone = cotizacionData.solicitud.user.phone;

        cotizacion.checked = false;

        cotizacionData.partes.forEach((p: any) => {
          cotizacion.partes.push(
            {
              'id': p.id,
              'nparte': p.nparte,
              'descripcion': p.pivot.descripcion,
              'cantidad': p.pivot.cantidad,
              //'costo': p.pivot.costo,
              //'margen': p.pivot.margen,
              'tiempoentrega': p.pivot.tiempoentrega,
              //'peso': p.pivot.peso,
              //'flete': p.pivot.flete,
              'monto': p.pivot.monto,
              'backorder': p.pivot.backorder === 1 ? true : false,
            }
          )
        });

        this.reportsDataCotizacion.push(cotizacion);
      }
      else
      {
        NotificationsService.showToast(
          'Error al intentar cargar la lista de partes de la cotizacion',
          NotificationsService.messageType.error
        );

        this.reportsDataCotizacion = [];
        this.loading = false;
      }
    });

    // Generate reports delayed by 3 secs each one
    this.reportsDataCotizacion.forEach((cotizacion, index) => {
        setTimeout(() => {
          this.generateReportCotizacionPDF(cotizacion);
        },
        3000 * index
      );
    });
  }

  public generateReportCotizacionPDF(cotizacion: any): void {  

    // If report component was found
    if(this.reportCotizacion !== undefined)
    {
      let reportData = {
        cotizacion: cotizacion,
        partes: cotizacion.partes
      };

      // Set report data
      this.reportCotizacion.reportData = reportData;

      // Export report to PDF after 1 sec after data loaded in report
      setTimeout(() => {
          this.reportCotizacion.exportCotizacionToPdf();
        },
        1000
      );
      
    }
    else
    {
      NotificationsService.showToast(
        'Error al generar el reporte de cotizacion',
        NotificationsService.messageType.error
      );
    }
  }
  
  public removeCotizacion(cotizacion: any)
  {
    Swal.fire({
      title: 'Eliminar cotizacion',
      text: "¿Realmente quieres eliminar la cotizacion #" + cotizacion.id + "?",
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
          title: 'Eliminando..',
          icon: 'warning',
          showConfirmButton: false,
          showCancelButton: false,
          allowOutsideClick: false,
          showLoaderOnConfirm: true,
          preConfirm: () => {

          }    
        }]);

        this._cotizacionesService.removeCotizacion(cotizacion.id)
        .subscribe(
          //Success request
          (response: any) => {

            this.loadCotizacionesList();
            NotificationsService.showToast(
              response.message,
              NotificationsService.messageType.success
            );

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
                  'Error al intentar eliminar la cotizacion',
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

  public exportCotizacionesToPDF(): void {
    
    // Get selected elements id
    let ids = this.cotizaciones.reduce(
      (idsList, cotizacion) => {
        if(cotizacion.checked === true)
        {
          idsList.push(cotizacion.id);
        }

        return idsList;
      },
      [] // Initial ids list
    );

    // Request reports list
    this.loadReportsCotizacion(ids);
  }

  public dateStringFormat(value: string): string {
    return this._utilsService.dateStringFormat(value);
  }

  public moneyStringFormat(value: number): string {
    return this._utilsService.moneyStringFormat(value);
  }

  public checkCotizacionesList(evt: any): void {
    this.cotizaciones.forEach((cotizacion: any) => {
      cotizacion.checked = evt.target.checked;
    });
  }

  public checkCotizacionItem(cotizacion: any, evt: any): void {
    cotizacion.checked = evt.target.checked;
  }

  public isCheckedItem(dataSource: any[]): boolean
  {
    let index = dataSource.findIndex((e) => {
      if(e.checked === true)
      {
        return true;
      }
      else
      {
        return false;
      }
    });

    return index >= 0 ? true : false;
  }

  public isUncheckedItem(dataSource: any[]): boolean
  {
    let index = dataSource.findIndex((e) => {
      if(e.checked === false)
      {
        return true;
      }
      else
      {
        return false;
      }
    });

    return index >= 0 ? true : false;
  }

}
