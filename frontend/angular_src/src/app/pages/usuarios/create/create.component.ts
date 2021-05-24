import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { Role } from 'src/app/interfaces/role';
import { NotificationsService } from 'src/app/services/notifications.service';
import { RolesService } from 'src/app/services/roles.service';
import { User } from 'src/app/interfaces/user';
import { UsersService } from 'src/app/services/users.service';

@Component({
  selector: 'app-create',
  templateUrl: './create.component.html',
  styleUrls: ['./create.component.css']
})
export class UsuariosCreateComponent implements OnInit {

  roles: Array<Role> = null as any;
  loading: boolean = false;
  responseErrors: any = [];

  userForm: FormGroup = new FormGroup({
    name: new FormControl('', [Validators.required, Validators.minLength(4)]),
    email: new FormControl('', [Validators.required, Validators.email]),
    phone: new FormControl('', [Validators.required, Validators.min(0)]),
    role: new FormControl('', [Validators.required])
  });

  constructor(
    private _rolesService: RolesService,
    private _usersService: UsersService,
    private router: Router
  ) {
  }

  ngOnInit(): void {
    this.userForm.disable();
    this.loadRoles();
  }

  public storeUser():void {
    this.userForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let user: User = {
      name: this.userForm.value.name,
      email: this.userForm.value.email,
      phone: this.userForm.value.phone,
      role_id: this.userForm.value.role,
      country_id: 1 // Chile
    } as User;

    this._usersService.storeUser(user)
    .subscribe(
      //Success request
      (response: any) => {

        NotificationsService.showToast(
          response.message,
          NotificationsService.messageType.success
        );

        this.goTo_usersList();
      },
      //Error request
      (errorResponse: any) => {
        switch(errorResponse.status)
        {
          case 400: //Invalid request parameters
          {
            this.responseErrors = errorResponse.error.message;

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

          case 422: //Invalid request parameters
          {
            this.responseErrors = errorResponse.error.message;

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
              'Error al intentar guardar el usuario',
              NotificationsService.messageType.error
            );

            break;
          }
        }

        this.userForm.enable();
        this.loading = false;
      }
    );
  }

  private loadRoles()
  {
    this.loading = true;
    this._rolesService.getRoles()
    .subscribe(
      //Success request
      (response: any) => {
        this.loading = false;

        this.roles = <Array<Role>>(response.data);
        
        this.userForm.enable();
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
              'Error al cargar la lista de roles',
              NotificationsService.messageType.error
            )
        
            break;
          }
        }
        
        this.roles = null as any;
        this.loading = false;

        this.goTo_usersList();
      }
    );  
  }

  public goTo_usersList()
  {
    this.router.navigate(['/panel/usuarios']);
  }
}