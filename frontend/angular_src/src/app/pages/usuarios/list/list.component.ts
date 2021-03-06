import { AfterViewInit, Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { User } from 'src/app/interfaces/user';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UsersService } from 'src/app/services/users.service';
import { Subject } from 'rxjs';
import { DataTableDirective, DataTablesModule } from 'angular-datatables';

/* SweetAlert2 */
const Swal = require('../../../../assets/vendors/sweetalert2/sweetalert2.all.min.js');

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class UsuariosListComponent implements OnInit, AfterViewInit, OnDestroy {

  @ViewChild(DataTableDirective, {static: false})
  datatableElement_users: DataTableDirective = null as any;
  dtOptions: any = {
    pagingType: 'full_numbers',
    pageLength: 25,
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

  users: any[] = [];
  loading: boolean = false;

  constructor(
    private _usersService: UsersService
  ) 
  {
  }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadUsersList();
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

  public loadUsersList()
  {
    this.loading = true;

    this.clearDataTable(this.datatableElement_users);
    this._usersService.getUsers()
    .subscribe(
      //Success request
      (response: any) => {
        this.users = response.data;
        this.renderDataTable(this.datatableElement_users);

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
              'Error al intentar cargar la lista de usuarios',
              NotificationsService.messageType.error
            )
        
            break;
          }
        }

        this.users = null as any;
        this.loading = false;
      }
    );
  }

  public removeUser(user: User)
  {
    Swal.fire({
      title: 'Eliminar usuario',
      text: "¿Realmente quieres eliminar el usuario \"" + user.name + "\" (" + user.email + ")?",
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
                  'Error al intentar eliminar el usuario',
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