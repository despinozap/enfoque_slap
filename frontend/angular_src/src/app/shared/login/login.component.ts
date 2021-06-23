import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { User } from 'src/app/interfaces/user';
import { AuthService } from 'src/app/services/auth.service';
import { NotificationsService } from 'src/app/services/notifications.service';

@Component({
	selector: 'app-login',
	templateUrl: './login.component.html',
	styleUrls: ['./login.component.css']
})
export class LoginComponent implements OnInit {

	loading: boolean;
	responseErrors: any;

	/*
	*		0: Login
	*		1: Forgot password
	*		2: Reset password email sent
	*/
	PAGE_STATUS: number;

	loginForm: FormGroup = new FormGroup({
		email: new FormControl('', [Validators.required, Validators.email]),
		password: new FormControl('', [Validators.required])
	});

	forgotPasswordForm: FormGroup = new FormGroup({
		email: new FormControl('', [Validators.required, Validators.email])
	});

	constructor(
		private router: Router,
		private _authService: AuthService
	) {
		this.loading = false;
		this.responseErrors = [];
		this.PAGE_STATUS = 0; //Login form
	}

	ngOnInit(): void {
		if (this._authService.getAccessToken() !== null) {
			this.router.navigate(['panel']);
		}
		else {
			this.cleanScripts();
			this.loadScript('assets/js/app.js');
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

	public doLogIn() {

		this.loginForm.disable();

		this.responseErrors = [];
		this.loading = true;
		this._authService.doLogin(this.loginForm.value.email, this.loginForm.value.password)
			.subscribe(
				//Success request
				(response: any) => {
					
					let loggedUser = response.data.user as User;

					this._authService.setLoggedUser(loggedUser);
					this._authService.setAccessToken(response.data.access_token);

					this.router.navigate(['panel']);
				},
				//Error request
				(errorResponse: any) => {
					switch (errorResponse.status) {

						case 403: //Invalid request parameters (forbidden)
							{
								this.responseErrors = errorResponse.error.message;

								break;
							}

						default: //Unhandled error
							{
								NotificationsService.showAlert(
									'Fail on logging in',
									NotificationsService.messageType.error
								);

								break;
							}
					}

					this.loginForm.enable();
					this.loading = false;
				}
			);
	}

	public forgotPassword() {

		this.forgotPasswordForm.disable();

		this.responseErrors = [];
		this.loading = true;
		this._authService.forgotPassword(this.forgotPasswordForm.value.email)
			.subscribe(
				//Success request
				(response: any) => {

					this.forgotPasswordForm.enable();
					this.forgotPasswordForm.reset();
					this.loading = false;

					this.goTo_ResetPasswordEmailSent();
				},
				//Error request
				(errorResponse: any) => {

					switch (errorResponse.status) {
						case 400: //Invalid request parameters (forbidden)
							{
								this.responseErrors = errorResponse.error.message;

								break;
							}

						case 500: //Invalid request parameters (forbidden)
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
									'Error al solicitar el link de recuperacion',
									NotificationsService.messageType.error
								);

								break;
							}
					}

					this.forgotPasswordForm.enable();
					this.loading = false;
				}

			);
	}

	public goTo_ResetPasswordEmailSent(): void
	{
		this.PAGE_STATUS = 2; // Reset password email sent
	}

	public goTo_ForgotPassword(): void
	{
		this.PAGE_STATUS = 1; // Recover password form
	}

	public goTo_Login(): void
	{
		this.PAGE_STATUS = 0; // Login form
	}

}
