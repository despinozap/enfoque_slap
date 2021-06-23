import { Component, OnInit } from '@angular/core';
import { Location } from '@angular/common';
import { User } from 'src/app/interfaces/user';
import { AuthService } from 'src/app/services/auth.service';
import { NotificationsService } from 'src/app/services/notifications.service';


@Component({
  selector: 'app-details',
  templateUrl: './details.component.html',
  styleUrls: ['./details.component.css']
})
export class ProfileDetailsComponent implements OnInit {

  loggedUser: User = null as any;

  loading: boolean = false;

  constructor(
    private _authService: AuthService,
    private location: Location
  ) 
  {
  }

  ngOnInit(): void {

    // For loggedUser
    {
      this.loading = true;

      if(this._authService.getLoggedUser() !== null)
      {
        // If loggedUser is already stored in the service
        this.loggedUser = this._authService.getLoggedUser();

        this.loading = false;
      }
      else
      {
        // Subscribes for getting the loggedUser whenever is stored in the service
        this._authService.loggedUser$.subscribe(
          //Success request
          (response: any) => {
            this.loading = false;
            
            this.loggedUser = response.user as User;
          },
          //Error request
          (errorResponse: any) => {

            NotificationsService.showToast(
              'Error al cargar el perfil de usuario',
              NotificationsService.messageType.error
            );

            this.loading = false;
            this.goTo_back();
          }
        );
      }
    }
  }

  public goTo_back()
  {
    this.location.back();
  }

}
