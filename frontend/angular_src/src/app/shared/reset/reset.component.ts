import { Component, OnInit } from '@angular/core';
import { FormGroup, FormControl, Validators, ValidationErrors, AbstractControl } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from 'src/app/services/auth.service';
import { NotificationsService } from 'src/app/services/notifications.service';

@Component({
  selector: 'app-reset',
  templateUrl: './reset.component.html',
  styleUrls: ['./reset.component.css']
})
export class ResetComponent implements OnInit {

  email: string;
  token: string;
  loading: boolean;
  responseErrors: any;

  /*
  *		0: Reset password form
  *		1: Password reseted
  */
  PAGE_STATUS: number;

  resetForm: FormGroup = new FormGroup({
    password: new FormControl('', [Validators.required, Validators.minLength(8)]),
    confirm: new FormControl('', [Validators.required, Validators.minLength(8)])
  });

  constructor(
    private router: Router,
    private activatedRoute: ActivatedRoute,
    private _authService: AuthService
  ) {
    this.loading = false;
    this.responseErrors = [];
    this.PAGE_STATUS = 0; //Reset password form

    this.email = '';
    this.token = '';
  }

  ngOnInit(): void {
    this.cleanScripts();
    this.loadScript('assets/js/app.js');

    this.activatedRoute.queryParams.subscribe(params => {
      this.email = params['email'];
      this.token = params['token'];
    });
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

  public doResetPassword(): void {
    this.resetForm.disable();

    this.responseErrors = [];
    this.loading = true;
    this._authService.resetPassword(this.email, this.token, this.resetForm.value.password, this.resetForm.value.confirm)
      .subscribe(
        //Success request
        (response: any) => {

          this.goTo_PasswordReseted();

        },
        //Error request
        (errorResponse: any) => {

          console.log(errorResponse);

          switch (errorResponse.status) {

            case 400: //Invalid request parameters (format)
              {
                this.responseErrors = errorResponse.error.message;

                break;
              }

            case 403: //Invalid request parameters (forbidden)
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );

                break;
              }

            case 500: //Server error on resetting the password
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
                  'Fail on resetting the password',
                  NotificationsService.messageType.error
                );

                break;
              }
          }

          this.resetForm.enable();
          this.loading = false;
        }
      );
  }

  public goTo_PasswordReseted(): void {
    this.PAGE_STATUS = 1; // Password reseted
  }

  public goTo_Login(): void {
    this.router.navigate(['login']);
  }

}
