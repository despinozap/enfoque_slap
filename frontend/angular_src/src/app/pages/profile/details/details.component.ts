import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { User } from 'src/app/interfaces/user';
import { AuthService } from 'src/app/services/auth.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { threadId } from 'worker_threads';

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
    private router: Router
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
          (response: User) => {
            this.loading = false;
            
            this.loggedUser = response;
          },
          //Error request
          (errorResponse: any) => {

            NotificationsService.showToast(
              'Error al cargar el perfil de usuario',
              NotificationsService.messageType.error
            );

            this.loading = false;
            this.goTo_home();
          }
        );
      }
    }
  }

  public goTo_home()
  {
    this.router.navigate(['panel']);
  }

}
