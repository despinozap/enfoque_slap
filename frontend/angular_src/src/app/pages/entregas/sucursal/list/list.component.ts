import { Component, OnInit, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { User } from 'src/app/interfaces/user';
import { AuthService } from 'src/app/services/auth.service';
import { EntregasService } from 'src/app/services/entregas.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UtilsService } from 'src/app/services/utils.service';

/* SweetAlert2 */
const Swal = require('../../../../../assets/vendors/sweetalert2/sweetalert2.all.min.js');

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class EntregasSucursalListComponent implements OnInit {

  @ViewChild(DataTableDirective, {static: false})
  datatableElement_entregas: DataTableDirective = null as any;
  dtOptions: any = {
    pagingType: 'full_numbers',
    pageLength: 25,
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

  loggedUser: User = null as any;
  private subLoggedUser: any;
  
  entregas: any[] = [];
  sucursal_id : number = 2;
  loading: boolean = false;
  
  constructor(
    private router: Router,
    private _authService: AuthService,
    private _entregasService: EntregasService,
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
    //For loggedUser
    {
      this.subLoggedUser = this._authService.loggedUser$.subscribe((data) => {
        this.loggedUser = data.user as User;
      });
      
      this._authService.notifyLoggedUser(this._authService.NOTIFICATION_RECEIVER_CONTENTPAGE);
    }
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadEntregasList();
    },
    100);
  }

  ngOnDestroy(): void {
    this.subLoggedUser.unsubscribe();
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

  public loadEntregasList()
  {
    this.loading = true;

    this.clearDataTable(this.datatableElement_entregas);
    this._entregasService.getEntregas_sucursal(this.sucursal_id)
    .subscribe(
      //Success request
      (response: any) => {
        
        this.entregas = response.data;
        this.renderDataTable(this.datatableElement_entregas);

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

          case 412: //Object not found
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
              'Error al intentar cargar la lista de entregas',
              NotificationsService.messageType.error
            )
        
            break;
          }
        }

        this.entregas = null as any;
        this.loading = false;
      }
    );
  }

  public removeEntrega(entrega: any)
  {
    Swal.fire({
      title: 'Eliminar entrega',
      text: `¿Realmente quieres eliminar la entrega a "${ entrega.oc.cotizacion.solicitud.faena.name }"?`,
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

        this._entregasService.removeEntrega_sucursal(this.sucursal_id, entrega.id)
        .subscribe(
          //Success request
          (response: any) => {

            this.loadEntregasList();
            NotificationsService.showToast(
              response.message,
              NotificationsService.messageType.success
            );

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

              case 409: //Conflict
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );

                break;
              }

              case 412: //Object not found
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.warning
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
                  'Error al intentar eliminar la entrega',
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
  
  public dateStringFormat(value: string): string {
    return this._utilsService.dateStringFormat(value);
  }
  
  public goTo_newEntrega(): void {
    this.router.navigate(['/panel/entregas/sucursal/create']);
  }
  
}
