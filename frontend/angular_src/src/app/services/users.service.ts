import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class UsersService {

  constructor(private httpClient: HttpClient) { }

  public getUsers(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/users`;
    
    return this.httpClient.get(endpoint);
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
