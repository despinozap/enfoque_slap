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

  roles: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  userForm: FormGroup = new FormGroup({
    name: new FormControl('', [Validators.required, Validators.minLength(4)]),
    email: new FormControl('', [Validators.required, Validators.email]),
    phone: new FormControl('', [Validators.required, Validators.min(0)]),
    role: new FormControl('', [Validators.required])
  });

  constructor(
    private _usersService: UsersService,
    private router: Router
  ) {
  }

  ngOnInit(): void {

    this.roles = [
      // Vendedor solicitante (Vendedor en Sucursal Santiago)
      {
        name: "vensol",
        label: "Vendedor en Santiago",
        stationable_id: 1
      },
      // Vendedor solicitante (Vendedor en Sucursal Antofagasta)
      {
        name: "vensol",
        label: "Vendedor en Antofagasta",
        stationable_id: 2
      },
      // Coordinador Logistico comprador (bodega en Comprador)
      {
        name: "colcom",
        label: "Coordinador logistico en USA",
        stationable_id: 1
      },
      // Coordinador Logistico solicitante (Bodega en Sucursal Santiago)
      {
        name: "colsol",
        label: "Coordinador logistico en Santiago",
        stationable_id: 1
      },
      // Coordinador Logistico solicitante (Bodega en Sucursal Antofagasta)
      {
        name: "colsol",
        label: "Coordinador logistico en Antofagasta",
        stationable_id: 2
      }
    ];
  }

  public storeUser():void {
    this.userForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let user: any = {
      stationable_id: this.roles[this.userForm.value.role].stationable_id,
      name: this.userForm.value.name,
      email: this.userForm.value.email,
      phone: this.userForm.value.phone,
      role_name: this.roles[this.userForm.value.role].name
    };

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

  public goTo_usersList()
  {
    this.router.navigate(['/panel/usuarios']);
  }
}