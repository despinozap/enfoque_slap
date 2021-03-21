import { Component, Input, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { User } from 'src/app/interfaces/user';
import { AuthService } from 'src/app/services/auth.service';

@Component({
  selector: 'app-topbar',
  templateUrl: './topbar.component.html',
  styleUrls: ['./topbar.component.css']
})
export class TopbarComponent implements OnInit {

  loggedUser: User = null as any;

  constructor(
    private router: Router,
    private _authService: AuthService
  ) {
  }

  ngOnInit(): void {

    //For loggedUser
    {
      this._authService.loggedUser$.subscribe((loggedUser) => {
        this.loggedUser = loggedUser;
      });
    }

    this._authService.notifyLoggedUser();
  }

  public goTo_login() {
    this.router.navigate(['login']);
  }

  public doLogOut(): void {
    this._authService.wipeAccessToken();
    this.goTo_login();
  }

}
