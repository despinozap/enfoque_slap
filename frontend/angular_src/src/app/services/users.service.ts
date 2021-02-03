import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';
import { User } from '../interfaces/user';

@Injectable({
  providedIn: 'root'
})
export class UsersService {

  constructor(private httpClient: HttpClient) { }

  public getUser(user_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/users/${user_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public getUsers(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/users`;
    
    return this.httpClient.get(endpoint);
  }

  public storeUser(user: User): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/users`;

    let httpOptions = { 
      method: 'POST',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };

    return this.httpClient.post(endpoint, user, httpOptions);
  }

  public removeUser(id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/users/${id}`;

    let httpOptions = { 
      method: 'DELETE',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };
    
    return this.httpClient.delete(endpoint, httpOptions);
  }

}
