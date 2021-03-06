import { Component, OnInit, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import { NotificationsService } from 'src/app/services/notifications.service';
import { SolicitudesService } from 'src/app/services/solicitudes.service';
import { Subject } from 'rxjs';
import { DataTableDirective, DataTablesModule } from 'angular-datatables';

/* SweetAlert2 */
const Swal = require('../../../../assets/vendors/sweetalert2/sweetalert2.all.min.js');

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class SolicitudesListComponent implements OnInit {

  @ViewChild(DataTableDirective, {static: false})
  datatableElement_solicitudes: DataTableDirective = null as any;
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

  solicitudes: any[] = [];
  loading: boolean = false;

  constructor(
    private router: Router,
    private _solicitudesService: SolicitudesService
  ) { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadSolicitudesList();
    },
    100);
  }

  ngOnDestroy(): void {
    this.dtTrigger.unsubscribe();
  }

  clearDataTable(dataTableElement: DataTableDirective): void {
    dataTableElement.dtInstance.then((dtInstance: DataTables.Api) => {
      // Clear the table first
      dtInstance.clear();
    });
  }

  renderDataTable(dataTableElement: DataTableDirective): void {
    dataTableElement.dtInstance.then((dtInstance: DataTables.Api) => {
      // Destroy the table first
      dtInstance.destroy();
      // Call the dtTrigger to rerender again
      this.dtTrigger.next();
    });
  }

  public loadSolicitudesList()
  {
    this.loading = true;

    this.clearDataTable(this.datatableElement_solicitudes);
    this._solicitudesService.getSolicitudes()
    .subscribe(
      //Success request
      (response: any) => {
        this.solicitudes = response.data;
        this.renderDataTable(this.datatableElement_solicitudes);

        this.loading = false;
      },
      //Error request
      (errorResponse: any) => {

        switch(errorResponse.status)
        {     
          case 500: //Internal server
          {
            NotificationsService.showAlert(
              errorResponse.message,
              NotificationsService.messageType.error
            );

            break;
          }
        
          default: //Unhandled error
          {
            NotificationsService.showAlert(
              'Error al intentar cargar la lista de solicitudes',
              NotificationsService.messageType.error
            )
        
            break;
          }
        }

        this.solicitudes= null as any;
        this.loading = false;
      }
    );
  }

  public removeSolicitud(solicitud: any)
  {
    Swal.fire({
      title: 'Eliminar solicitud',
      text: "¿Realmente quieres eliminar la solicitud #" + solicitud.id + "?",
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
        /*
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

        this._usersService.removeUser(user.id)
        .subscribe(
          //Success request
          (response: any) => {

            this.loadUsersList();
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
                  errorResponse.message,
                  NotificationsService.messageType.warning
                );

                break;
              }

              case 500: //Internal server
              {
                NotificationsService.showAlert(
                  errorResponse.message,
                  NotificationsService.messageType.error
                );

                break;
              }

              default: //Unhandled error
              {
                NotificationsService.showAlert(
                  'Error al intentar eliminar el usuario',
                  NotificationsService.messageType.error
                );

                break;
              }
            }
          }
        );
        */
      }
    });

  }

  public exportToExcelSolicitudes(): void {
    NotificationsService.showAlert(
      'Esta funcionalidad aun no se implementa',
      NotificationsService.messageType.info
    );
  }

  public goTo_newSolicitud(): void {
    this.router.navigate(['/panel/solicitudes/create']);
  }

}
