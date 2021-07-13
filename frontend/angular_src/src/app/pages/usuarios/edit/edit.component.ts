import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { Role } from 'src/app/interfaces/role';
import { User } from 'src/app/interfaces/user';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UsersService } from 'src/app/services/users.service';

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class UsuariosEditComponent implements OnInit {

  roles: any[] = null as any;
  stations: any[] = null as any;
  loading: boolean = false;
  responseErrors: any = [];
  
  private sub: any;
  private id: number = -1;

  userForm: FormGroup = new FormGroup({
    station: new FormControl('', [Validators.required]),
    name: new FormControl('', [Validators.required, Validators.minLength(4)]),
    email: new FormControl('', [Validators.required, Validators.email]),
    phone: new FormControl('', [Validators.required, Validators.min(0)])
  });
  

  constructor(
    private route: ActivatedRoute, 
    private _usersService: UsersService,
    private router: Router
  ) 
  {
  }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      
      this.id = params['id'];
      this.userForm.disable();
      this.loading = true;

      this.roles = [
        // Vendedor solicitante (Vendedor en Sucursal Santiago)
        {
          name: "seller",
          label: "Vendedor en Santiago",
          stationable_id: 1
        },
        // Vendedor solicitante (Vendedor en Sucursal Antofagasta)
        {
          name: "seller",
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

      this._usersService.getUser(this.id)
      .subscribe(
        //Success request
        (response: any) => {
          this.userForm.enable();
          this.loading = false;
          
          this.loadFormData(response.data);
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
          
            default: //Unhandled error
            {
              NotificationsService.showToast(
                'Error al cargar los datos del usuario',
                NotificationsService.messageType.error
              );
    
              break;

            }
          }

          this.loading = false;
          this.goTo_usersList();
        }
      );
    });
  }

  ngOnDestroy() {
    this.sub.unsubscribe();
  }

  private loadFormData(userData: any)
  {
    this.stations = this.roles.filter((role) => {
      return (role.name === userData.role.name);
    });

    this.userForm.controls.name.setValue(userData.name);
    this.userForm.controls.email.setValue(userData.email);
    this.userForm.controls.station.setValue(userData.stationable.id);
    this.userForm.controls.phone.setValue(userData.phone);
  }

  public updateUser()
  {
    this.userForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let user: any = {
      stationable_id: this.userForm.value.station,
      name: this.userForm.value.name,
      email: this.userForm.value.email,
      phone: this.userForm.value.phone
    };
    
    this._usersService.updateUser(this.id, user)
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
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.warning
            );

            this.goTo_usersList();

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
              'Error al actualizar el usuario',
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
