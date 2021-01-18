import { Injectable } from '@angular/core';
import {
	HttpRequest,
	HttpHandler,
	HttpEvent,
	HttpInterceptor,
	HttpErrorResponse
} from '@angular/common/http';
import { Observable } from 'rxjs';
import { map, tap } from 'rxjs/operators';
import { Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

@Injectable()
export class AuthHttpInterceptor implements HttpInterceptor {

	constructor(
		private router: Router,
		private _authService: AuthService
	) { }

	intercept(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {

		//Intercepts for adding the auth token

		const accessToken = 'MyAccessToken';

		if (request.url.toUpperCase().endsWith('AUTH/LOGIN')) {
			return next.handle(request);
		}
		else {
			const authRequest = request.clone({
				setHeaders: {
					'Authorization': `Bearer ${this._authService.getAccessToken()}`
				}
			});

			return next.handle(authRequest).pipe(
				tap(() => { }, //On success
					(err: any) => { //On error
						if (err instanceof HttpErrorResponse) {
							if (err.status === 401) //Unauthorized
							{
								this._authService.wipeAccessToken();
								this.router.navigate(['login']);
							}
							else {
								return err;
							}

						}

						return true;
					}
				)
			);
		}

	}

}
