import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class CotizacionesService {

  constructor(private httpClient: HttpClient) { }

  public rejectCotizacion(cotizacion_id: number, data: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/cotizaciones/reject/${cotizacion_id}`;

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
    
    return this.httpClient.post(endpoint, data, httpOptions);
  }

  public approveCotizacion(solicitud_id: number, data: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/cotizaciones/approve/${solicitud_id}`;

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
    
    return this.httpClient.post(endpoint, data, httpOptions);
  }

  public getMotivosRechazoFull(): Observable<any> {
    let endpoint: string = `${environment.ENDPOINT_BASE}/motivosrechazo/all`;
    
    return this.httpClient.get(endpoint);
  }

  public getCotizacion(cotizacion_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/cotizaciones/${cotizacion_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public getCotizaciones(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/cotizaciones`;
    
    return this.httpClient.get(endpoint);
  }

  public removeCotizacion(id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/cotizaciones/${id}`;

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
