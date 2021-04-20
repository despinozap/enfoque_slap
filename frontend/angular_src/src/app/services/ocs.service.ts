import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class OcsService {

  constructor(
    private httpClient: HttpClient
  ) { }

  public getOC(oc_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/ocs/${oc_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public getOCs(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/ocs`;
    
    return this.httpClient.get(endpoint);
  }

  // public removeOc(id: number): Observable<any>
  // {
  //   let endpoint: string = `${environment.ENDPOINT_BASE}/ocs/${id}`;

  //   let httpOptions = { 
  //     method: 'DELETE',
  //     headers:
  //     {
  //       'Accept': 'application/json',
  //       'Content-Type': 'application/json'
  //     }
  //   };
    
  //   return this.httpClient.delete(endpoint, httpOptions);
  // }
}
