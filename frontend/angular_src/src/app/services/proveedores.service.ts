import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';
import { Proveedor } from '../interfaces/proveedor';

@Injectable({
  providedIn: 'root'
})
export class ProveedoresService {

  constructor(private httpClient: HttpClient) { }

  public getProveedor(comprador_id: number, id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/proveedores/${id}`;
    
    return this.httpClient.get(endpoint);
  }

  public getProveedores(comprador_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/proveedores`;

    return this.httpClient.get(endpoint);
  }

  public storeProveedor(comprador_id:number, proveedor: Proveedor): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/proveedores`;

    let httpOptions = { 
      method: 'POST',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };

    return this.httpClient.post(endpoint, proveedor, httpOptions);
  }

  public updateProveedor(comprador_id: number, id: number, proveedor: Proveedor): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/proveedores/${id}`;

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
    
    return this.httpClient.put(endpoint, proveedor, httpOptions);
  }

  public removeProveedor(comprador_id: number, id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/proveedores/${id}`;

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
