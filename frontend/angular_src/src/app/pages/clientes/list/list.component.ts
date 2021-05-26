import { Component, OnInit, ViewChild } from '@angular/core';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { Cliente } from 'src/app/interfaces/cliente';
import { NotificationsService } from 'src/app/services/notifications.service';
import { ClientesService } from 'src/app/services/clientes.service';

/* SweetAlert2 */
const Swal = require('../../../../assets/vendors/sweetalert2/sweetalert2.all.min.js');

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class ClientesListComponent implements OnInit {

  @ViewChild(DataTableDirective, {static: false})
  datatableElement_clientes: DataTableDirective = null as any;
  dtOptions: any = {
    pagingType: 'full_numbers',
    pageLength: 10,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    },
    // Declare the use of the extension in the dom parameter
    dom: 'Bfrtip',
    // Configure the buttons
    buttons: [
      'colvis',
      'excel',
      'pdf',
      'print'
    ]
  };

  dtTrigger: Subject<any> = new Subject<any>();

  clientes: any[] = [];
  loading: boolean = false;

  constructor(
    private _clientesService: ClientesService
  ) 
  {
  }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadClientesList();
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

  public loadClientesList()
  {
    this.loading = true;

    this.clearDataTable(this.datatableElement_clientes);
    this._clientesService.getClientes()
    .subscribe(
      //Success request
      (response: any) => {
        this.clientes = response.data;
        this.renderDataTable(this.datatableElement_clientes);

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
              'Error al intentar cargar la lista de clientes',
              NotificationsService.messageType.error
            )
        
            break;
          }
        }

        this.clientes = null as any;
        this.loading = false;
      }
    );
  }

  public removeCliente(cliente: Cliente)
  {
    Swal.fire({
      title: 'Eliminar cliente',
      text: `¿Realmente quieres eliminar el cliente "${ cliente.name }"?`,
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

        this._clientesService.removeCliente(cliente.id)
        .subscribe(
          //Success request
          (response: any) => {

            this.loadClientesList();
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
                  'Error al intentar eliminar el cliente',
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

}
