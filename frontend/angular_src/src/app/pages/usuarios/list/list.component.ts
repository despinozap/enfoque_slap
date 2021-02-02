import { Component, Input, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { last } from 'rxjs/operators';
import { User } from 'src/app/interfaces/user';
import { DatatablesService } from 'src/app/services/datatables.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UsersService } from 'src/app/services/users.service';

/* SweetAlert2 */
const Swal = require('../../../../assets/vendors/sweetalert2/sweetalert2.all.min.js');

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class UsuariosListComponent implements OnInit {

  public users: Array<User>;
  loading: boolean;

  constructor(private router: Router, private _usersService: UsersService) {
    this.loading = false;
    this.users = null as any;
  }

  ngOnInit(): void {
    this.loadUsersList();
  }

  public loadUsersList()
  {
    this.loading = true;
    this._usersService.getUsers()
    .subscribe(
      //Success request
      (response: any) => {
        this.updateContent(response.data);
        
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
      confirmButtonText: 'Si, eliminar!',
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
      }
    });

  }

  private updateContent(content: any)
  {
    this.users = <Array<User>>(content);
    // this.users = <Array<User>>(content.data);
    // this.pagination = {
    //   current_page: content.current_page,
    //   from: content.from,
    //   last_page: content.last_page,
    //   per_page: content.per_page,
    //   to: content.to,
    //   total: content.total
    // } as Pagination;
  }
  
}
