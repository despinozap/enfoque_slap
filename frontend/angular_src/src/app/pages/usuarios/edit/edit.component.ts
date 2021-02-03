import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { Role } from 'src/app/interfaces/role';
import { User } from 'src/app/interfaces/user';
import { NotificationsService } from 'src/app/services/notifications.service';
import { RolesService } from 'src/app/services/roles.service';
import { UsersService } from 'src/app/services/users.service';

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class UsuariosEditComponent implements OnInit {

  public roles: Array<Role>;
  loading: boolean;
  responseErrors: any;
  
  private sub: any;
  private id: number;

  userForm: FormGroup = new FormGroup({
    name: new FormControl('', [Validators.required, Validators.minLength(4)]),
    email: new FormControl('', [Validators.required, Validators.email]),
    phone: new FormControl('', [Validators.required, Validators.min(0)]),
    role: new FormControl('', [Validators.required])
  });
  

  constructor(
    private route: ActivatedRoute, 
    private _rolesService: RolesService,
    private _usersService: UsersService,
    private router: Router
  ) 
  {
    this.roles = null as any;
    this.loading = false;
    this.responseErrors = [];
    this.id = -1;
  }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      
      this.id = params['id'];
      this.userForm.disable();
      this.loading = true;

      this.loadRoles();

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

  private loadFormData(userData: any)
  {
    this.userForm.controls.name.setValue(userData.name);
    this.userForm.controls.email.setValue(userData.email);
    this.userForm.controls.role.setValue(userData.role.id);
    this.userForm.controls.phone.setValue(userData.phone);
  }

  public updateUser()
  {
    this.userForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let user: User = {
      name: this.userForm.value.name,
      email: this.userForm.value.email,
      phone: this.userForm.value.phone,
      role_id: this.userForm.value.role
    } as User;
    
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
       
          case 412: //Object not found
          {
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.warning
            );

            this.goTo_usersList();

            break;
          }
        
          case 422: //Invalid request parameters
          {
            this.responseErrors = errorResponse.error;

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
