import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable, Subject } from 'rxjs';
import { environment } from 'src/environments/environment';
import { User } from '../interfaces/user';

@Injectable({
	providedIn: 'root'
})
export class AuthService {

	private ACCESS_TOKEN_ITEM_NAME: string = 'access_token';

	//Notification receivers
	NOTIFICATION_RECEIVER_HOME = 0;
	NOTIFICATION_RECEIVER_CONTENTPAGE = 1;

	private loggedUser: User = null as any;

	private loggedUserSource = new Subject<any>();
	public loggedUser$ = this.loggedUserSource.asObservable();

	constructor(private httpClient: HttpClient) {
	}

	public resetPassword(email: string, token: string, password: string, confirm: string): Observable<any> {
		let endpoint: string = `${environment.ENDPOINT_BASE}/auth/reset`;
		let requestData: any = {
			'email': email,
			'token': token,
			'password': password,
			'password_confirmation': confirm
		};

		let httpOptions = {
			method: 'POST',
			headers:
			{
				'Accept': 'application/json',
				'Content-Type': 'application/json',
			}
		};

		return this.httpClient.post(endpoint, requestData, httpOptions);
	}

	public forgotPassword(email: string): Observable<any> {
		let endpoint: string = `${environment.ENDPOINT_BASE}/auth/forgot`;
		let requestData: any = {
			'email': email
		};

		let httpOptions = {
			method: 'POST',
			headers:
			{
				'Accept': 'application/json',
				'Content-Type': 'application/json',
			}
		};

		return this.httpClient.post(endpoint, requestData, httpOptions);
	}

	public doLogin(email: string, password: string): Observable<any> {
		let endpoint: string = `${environment.ENDPOINT_BASE}/auth/login`;
		let requestData: any = {
			'email': email,
			'password': password
		};

		let httpOptions = {
			method: 'POST',
			headers:
			{
				'Accept': 'application/json',
				'Content-Type': 'application/json',
			}
		};

		return this.httpClient.post(endpoint, requestData, httpOptions);
	}

	public getAuthenticatedUser(): Observable<any> {
		let endpoint: string = `${environment.ENDPOINT_BASE}/auth/user`;

		return this.httpClient.get(endpoint);
	}

	public setLoggedUser(user: User): void
	{
		this.loggedUser = user;

		this.notifyLoggedUser(this.NOTIFICATION_RECEIVER_HOME);
	}

	public notifyLoggedUser(notificationReceiver: number): boolean {
		if(this.loggedUser !== null)
		{
			let data: any = {
				'user' : this.loggedUser,
				'receiver' : notificationReceiver
			};

			//The observable notifies all the subscribers about the new value for loggedUser
			this.loggedUserSource.next(data);

			return true;
		}
		else
		{
			return false;
		}
	
	}

	public getLoggedUser(): User
	{
		return this.loggedUser;
	}

	public setAccessToken(accessToken: string): void {
		localStorage.setItem(this.ACCESS_TOKEN_ITEM_NAME, accessToken);
	}

	public wipeAccessToken() {
		localStorage.removeItem(this.ACCESS_TOKEN_ITEM_NAME);
	}

	public getAccessToken(): string {
		return localStorage.getItem(this.ACCESS_TOKEN_ITEM_NAME) as any;
	}
}
