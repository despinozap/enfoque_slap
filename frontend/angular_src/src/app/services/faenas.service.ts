import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';
import { Faena } from '../interfaces/faena';

@Injectable({
  providedIn: 'root'
})
export class FaenasService {

  constructor(private httpClient: HttpClient) { }

  public getFaena(cliente_id: number, id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/clientes/${cliente_id}/faenas/${id}`;
    
    return this.httpClient.get(endpoint);
  }

  public getFaenasFull(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/faenas/all`;

    return this.httpClient.get(endpoint);
  }

  public getFaenas(cliente_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/clientes/${ cliente_id }/faenas`;

    return this.httpClient.get(endpoint);
  }

  public storeFaena(cliente_id:number, faena: Faena): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/clientes/${cliente_id}/faenas`;

    let httpOptions = { 
      method: 'POST',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };

    return this.httpClient.post(endpoint, faena, httpOptions);
  }

  public updateFaena(cliente_id: number, id: number, faena: Faena): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/clientes/${cliente_id}/faenas/${id}`;

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
    
    return this.httpClient.put(endpoint, faena, httpOptions);
  }

  public removeFaena(cliente_id: number, id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/clientes/${cliente_id}/faenas/${id}`;

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
