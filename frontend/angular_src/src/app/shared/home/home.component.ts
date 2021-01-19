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

  public loggedUser: User;

  constructor(
    private router: Router,
    private _authService: AuthService
  ) {
    this.loggedUser = null as any;
  }

  ngOnInit(): void {

    this.cleanScripts();
    this.loadScript('assets/js/app.js');

    if (this._authService.getAccessToken() === null) {
      this._authService.getAuthenticatedUser()
        .subscribe(
          //Success request
          (response: any) => {
            this.loggedUser = <User> response.data;
            this._authService.setLoggedUser(this.loggedUser);
          },
          //Error request
          (errorResponse: any) => {

            /*
            if(errorResponse.status === 401)
            {
              //Unauthorized
            }
  
            this.doLogOut();
            */
          }
        );
    }
  }

  private cleanScripts(): void {
    document.querySelectorAll('script')
      .forEach((script) => {
        script.remove();
      });
  }

  private loadScript(src: string) {
    var script = document.createElement("script");
    script.setAttribute("src", src);
    document.body.appendChild(script);
  }

  public goTo_login() {
    this.router.navigate(['login']);
  }

  public doLogOut(): void {
    this._authService.wipeAccessToken();
    this.goTo_login();
  }

}
