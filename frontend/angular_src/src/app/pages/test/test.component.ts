import { Component, OnInit } from '@angular/core';
import { AuthService } from 'src/app/services/auth.service';

@Component({
  selector: 'app-test',
  templateUrl: './test.component.html',
  styleUrls: ['./test.component.css']
})
export class TestComponent implements OnInit {

  constructor(private _authService: AuthService) { }

  ngOnInit(): void {
    //this.login();
    this.user();
  }

  public login() {
    this._authService.doLogin('admin@mail.com', 'admin')
    .subscribe(
      //Success request
      (response: any) => {

        console.log('RESPONSE', response);

      },
      //Error request
      (errorResponse: any) => {

        console.log('ERROR', errorResponse);
      }
    );
  }

  public user() {
    this._authService.getAuthenticatedUser()
    .subscribe(
      //Success request
      (response: any) => {

        console.log('RESPONSE', response);

      },
      //Error request
      (errorResponse: any) => {

        console.log('ERROR', errorResponse);
      }
    );
  }

}
