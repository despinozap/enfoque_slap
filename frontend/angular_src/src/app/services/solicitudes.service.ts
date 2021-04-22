import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';
import { Solicitud } from '../interfaces/solicitud';

@Injectable({
  providedIn: 'root'
})
export class SolicitudesService {

  constructor(private httpClient: HttpClient) { }

  public getSolicitud(solicitud_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/solicitudes/${solicitud_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public getSolicitudes(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/solicitudes`;
    
    return this.httpClient.get(endpoint);
  }

  public closeSolicitud(solicitud_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/solicitudes/close/${solicitud_id}`;

    let httpOptions = { 
      method: 'POST',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET,POST,OPTIONS,DELETE,PUT'
      }
    };
    
    return this.httpClient.post(endpoint, httpOptions);
  }

  public completeSolicitud(solicitud_id: number, solicitud: Solicitud): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/solicitudes/complete/${solicitud_id}`;

    let httpOptions = { 
      method: 'POST',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET,POST,OPTIONS,DELETE,PUT'
      }
    };
    
    return this.httpClient.post(endpoint, solicitud, httpOptions);
  }

  public updateSolicitud(solicitud_id: number, solicitud: Solicitud): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/solicitudes/${solicitud_id}`;

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
    
    return this.httpClient.put(endpoint, solicitud, httpOptions);
  }

  public storeSolicitud(solicitud: Solicitud): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/solicitudes`;

    let httpOptions = { 
      method: 'POST',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };

    return this.httpClient.post(endpoint, solicitud, httpOptions);
  }

  public prepareSolicitud(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/solicitudes/prepare`;
    
    return this.httpClient.get(endpoint);
  }

  public removeSolicitud(id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/solicitudes/${id}`;

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
