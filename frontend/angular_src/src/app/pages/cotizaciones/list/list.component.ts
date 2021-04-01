import { Component, OnInit, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { AuthService } from 'src/app/services/auth.service';
import { CotizacionesService } from 'src/app/services/cotizaciones.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UtilsService } from 'src/app/services/utils.service';

/* SweetAlert2 */
const Swal = require('../../../../assets/vendors/sweetalert2/sweetalert2.all.min.js');

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class CotizacionesListComponent implements OnInit {

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

        this.loggedUser = this._authService.getLoggedUser();
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

  public exportCotizacionesToExcel(): void {

    let data: any[] = [];
    //Push header
    data.push(['Cotizacion', 'Fecha', 'Cliente', 'Marca', 'Ejecutivo', 'Partes', 'Dias', 'Monto (USD)', 'Estado']);
    //Add checked rows
    this.cotizaciones.forEach((c: any) => {
      if(c.checked === true)
      {
        data.push([
          c.id,
          this._utilsService.dateStringFormat(c.updated_at),
          c.solicitud.faena.cliente.name,
          c.solicitud.marca.name,
          c.solicitud.user.name,
          c.partes_total,
          c.dias,
          c.monto,
          c.estadocotizacion.name
        ]);
      }
    });

    this._utilsService.exportTableToExcel(data, 'Cotizaciones');
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

}
