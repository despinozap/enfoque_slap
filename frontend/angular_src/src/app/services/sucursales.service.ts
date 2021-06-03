import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class SucursalesService {

  constructor(
    private httpClient: HttpClient
  ) { }

  public getSucursales(country_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/sucursales/countries/${country_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public getCentrosdistribucion(country_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/centrosdistribucion/countries/${country_id}`;
    
    return this.httpClient.get(endpoint);
  }
}
