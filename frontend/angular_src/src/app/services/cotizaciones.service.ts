import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class CotizacionesService {

  constructor(private httpClient: HttpClient) { }

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
