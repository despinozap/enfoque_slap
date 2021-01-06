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

  @Input() loggedUser: User;

  constructor(
    private router: Router,
    private _authService: AuthService
  ) {
    this.loggedUser = null as any;
  }

  ngOnInit(): void {
  }

  public goTo_login() {
    this.router.navigate(['login']);
  }

  public doLogOut(): void {
    this._authService.wipeAccessToken();
    this.goTo_login();
  }

}
