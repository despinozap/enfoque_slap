import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { User } from 'src/app/interfaces/user';
import { AuthService } from 'src/app/services/auth.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UsersService } from 'src/app/services/users.service';

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class ProfileEditComponent implements OnInit {

  loggedUser: User = null as any;

  loading: boolean = false;
  responseErrors: any = [];
  
  profileForm: FormGroup = new FormGroup({
    email: new FormControl('', [Validators.required, Validators.email]),
    phone: new FormControl('', [Validators.required, Validators.min(0)]),
  });

  constructor(
    private _authService: AuthService,
    private _usersService: UsersService,
    private router: Router
  )
  { 
  }

  ngOnInit(): void {

    // For loggedUser
    {

      this.profileForm.disable();
      this.loading = true;

      if(this._authService.getLoggedUser() !== null)
      {
        // If loggedUser is already stored in the service
        this.loggedUser = this._authService.getLoggedUser();

        this.profileForm.enable();
        this.loading = false;
        
        this.loadFormData(this.loggedUser);
      }
      else
      {
        // Subscribes for getting the loggedUser whenever is stored in the service
        this._authService.loggedUser$.subscribe(
          //Success request
          (response: User) => {
            this.profileForm.enable();
            this.loading = false;
            
            this.loggedUser = response;
            this.loadFormData(this.loggedUser);
          },
          //Error request
          (errorResponse: any) => {

            NotificationsService.showToast(
              'Error al cargar el perfil de usuario',
              NotificationsService.messageType.error
            );

            this.loading = false;
            this.goTo_profile();
          }
        );
      }
    }
  }

  private loadFormData(user: User)
  {
    this.profileForm.controls.email.setValue(user.email);
    this.profileForm.controls.phone.setValue(user.phone);
  }

  public updateProfile()
  {
    this.profileForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let profileData: any = {
      email: this.profileForm.value.email,
      phone: this.profileForm.value.phone,
    };
    

    this._usersService.updateProfile(profileData)
    .subscribe(
      //Success request
      (response: any) => {
        NotificationsService.showToast(
          response.message,
          NotificationsService.messageType.success
        );

        this.goTo_profile();
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

          case 412: //Object not found
          {
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.warning
            );

            this.goTo_profile();

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
              'Error al actualizar el perfil de usuario',
              NotificationsService.messageType.error
            );

            break;
          }

        }

        this.profileForm.enable();
        this.loading = false;
      }
    );
  }

  public goTo_profile()
  {
    this.router.navigate(['profile/details']);
  }

}
