import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';
import { Comprador } from '../interfaces/comprador';

@Injectable({
  providedIn: 'root'
})
export class CompradoresService {

  constructor(
    private httpClient: HttpClient
  ) { }

  public updateComprador(id: number, comprador: Comprador): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${id}`;

    let httpOptions = { 
      method: 'PUT',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET,POST,OPTIONS,DELETE,PUT'
      }
    };
    
    return this.httpClient.put(endpoint, comprador, httpOptions);
  }

  public getComprador(comprador_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public getCompradores(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores`;
    
    return this.httpClient.get(endpoint);
  }
}
