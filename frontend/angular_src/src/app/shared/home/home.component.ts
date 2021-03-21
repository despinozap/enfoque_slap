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

  public loggedUser: User = null as any;

  constructor(
    private router: Router,
    private _authService: AuthService
  ) {
  }

  ngOnInit(): void {

    if (this._authService.getLoggedUser() === null) {
      
      this._authService.getAuthenticatedUser()
        .subscribe(
          //Success request
          (response: any) => {

            this.loggedUser = {
              id: response.data.id,
              name: response.data.name,
              email: response.data.email,
              phone: response.data.phone,
              role_id: response.data.role.id,
              role_name: response.data.role.name
            } as User;
            
            this._authService.setLoggedUser(this.loggedUser);
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
