import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { User } from 'src/app/interfaces/user';
import { AuthService } from 'src/app/services/auth.service';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css']
})
export class HomeComponent implements OnInit {

  constructor(
    private router: Router,
    private _authService: AuthService
  ) {
  }

  ngOnInit(): void {

    if (this._authService.getLoggedUser() !== null) 
    {
      this._authService.notifyLoggedUser(this._authService.NOTIFICATION_RECEIVER_HOME);
    }
    else
    { 
      this._authService.getAuthenticatedUser()
        .subscribe(
          //Success request
          (response: any) => {

            let loggedUser = response.data as User;
            
            this._authService.setLoggedUser(loggedUser);
          },
          //Error request
          (errorResponse: any) => {

            if(errorResponse.status === 401)
            {
              //Unauthorized
            }
  
            this.doLogOut();
            
          }
        );
    }
  }

  public goTo_login() {
    this.router.navigate(['login']);
  }

  public doLogOut(): void {
    this._authService.wipeAccessToken();
    this.goTo_login();
  }

}
