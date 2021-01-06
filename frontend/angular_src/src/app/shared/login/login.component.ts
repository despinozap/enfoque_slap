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

	loginForm: FormGroup = new FormGroup({
		email: new FormControl('', [Validators.required, Validators.email]),
		password: new FormControl('', [Validators.required])
	});

	constructor(
		private router: Router,
		private _authService: AuthService
	) {
		this.loading = false;
		this.responseErrors = [];
	}

	ngOnInit(): void {
		if (this._authService.getAccessToken() !== null) {
			this.router.navigate(['home']);
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

					let loggedUser = <User>response.data.user;
					this._authService.setLoggedUser(loggedUser);
					this._authService.setAccessToken(response.data.access_token);

					this.router.navigate(['home']);
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

}
