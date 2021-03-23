import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';
import { Parte } from '../interfaces/parte';

@Injectable({
  providedIn: 'root'
})
export class PartesService {

  constructor(private httpClient: HttpClient) { }

  public getParte(parte_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/partes/${parte_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public getPartes(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/partes`;
    
    return this.httpClient.get(endpoint);
  }

  public updateParte(parte_id: number, parte: Parte): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/partes/${parte_id}`;

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
    
    return this.httpClient.put(endpoint, parte, httpOptions);
  }

  public removeParte(id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/partes/${id}`;

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
