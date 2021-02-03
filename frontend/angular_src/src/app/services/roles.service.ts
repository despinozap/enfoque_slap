import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class RolesService {

  constructor(private httpClient: HttpClient) { }

  public getRoles(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/roles/all`;
    
    return this.httpClient.get(endpoint);
  }
}
