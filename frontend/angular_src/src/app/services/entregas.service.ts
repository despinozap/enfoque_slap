import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class EntregasService {

  constructor(private httpClient: HttpClient) { }

  /*
   *  Sucursal
   */
  public removeEntrega_sucursal(sucursal_id: number, entrega_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/sucursales/${sucursal_id}/entregas/${entrega_id}`;

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

  public updateEntrega_sucursal(sucursal_id: number, entrega_id: number, entrega: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/sucursales/${sucursal_id}/entregas/${entrega_id}`;

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
    
    return this.httpClient.put(endpoint, entrega, httpOptions);
  }

  public prepareUpdateEntrega_sucursal(sucursal_id: number, entrega_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/sucursales/${sucursal_id}/entregas/${entrega_id}/prepare`;
    
    return this.httpClient.get(endpoint);
  }

  public getEntrega_sucursal(sucursal_id: number, entrega_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/sucursales/${sucursal_id}/entregas/${entrega_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public storeEntrega_sucursal(sucursal_id: number, oc_id: number, entrega: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/sucursales/${sucursal_id}/entregas/ocs/${oc_id}`;

    let httpOptions = { 
      method: 'POST',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };

    return this.httpClient.post(endpoint, entrega, httpOptions);
  }

  public prepareStoreEntrega_sucursal(sucursal_id: number, oc_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/sucursales/${sucursal_id}/entregas/prepare/ocs/${oc_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public getQueueOcs_sucursal(sucursal_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/sucursales/${sucursal_id}/entregas/queueocs`;
    
    return this.httpClient.get(endpoint);
  }

  public getEntregas_sucursal(sucursal_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/sucursales/${sucursal_id}/entregas`;
    
    return this.httpClient.get(endpoint);
  }
  
}
